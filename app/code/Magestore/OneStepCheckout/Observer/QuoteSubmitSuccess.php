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
 * Class QuoteSubmitSuccess
 * @package Magestore\OneStepCheckout\Observer
 */
class QuoteSubmitSuccess implements ObserverInterface
{
    /**
     * @var \Magestore\OneStepCheckout\Helper\Data
     */
    protected $_helper;


    /**
     * QuoteSubmitSuccess constructor.
     *
     * @param \Magestore\OneStepCheckout\Helper\Data $helper
     */
    public function __construct(\Magestore\OneStepCheckout\Helper\Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $this->_helper->sendNewOrderEmail($order);
    }
}
