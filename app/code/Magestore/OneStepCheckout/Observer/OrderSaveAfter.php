<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class OrderSaveAfter
 * @package Magestore\OneStepCheckout\Observer
 */
class OrderSaveAfter implements ObserverInterface
{
    /**
     * @var \Magestore\OneStepCheckout\Model\DeliveryFactory
     */
    protected $_deliveryFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * OrderSaveAfter constructor.
     * @param \Magestore\OneStepCheckout\Model\DeliveryFactory $deliveryFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magestore\OneStepCheckout\Model\DeliveryFactory $deliveryFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    )
    {
        $this->_logger = $logger;
        $this->_deliveryFactory = $deliveryFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getId();
        $deliveryDate = $this->_checkoutSession->getData('osc_delivery_date', true);
        $houseSecurityCode =  $this->_checkoutSession->getData('osc_security_code', true);
        $deliveryTime = $this->_checkoutSession->getData('osc_delivery_time', true);
        if ($deliveryDate) {
            if ($orderId && $deliveryDate) {
                /** @var \Magestore\OneStepCheckout\Model\Delivery $delivery */
                $delivery = $this->_deliveryFactory->create()->setData([
                    'order_id'           => $orderId,
                    'delivery_time_date' => $deliveryDate. ' '.$deliveryTime,
                    'osc_security_code' => $houseSecurityCode
                ]);
                try {
                    $delivery->save();
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }
    }
}
