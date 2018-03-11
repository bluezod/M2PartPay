<?php
/**
 * Created by PhpStorm.
 * User: Eden Duong
 * Date: 16/12/2016
 * Time: 8:07 SA
 */

namespace Magestore\OneStepCheckout\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ){
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
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $comment = $this->_checkoutSession->getData('osc_comment');
        $order->setData('onestepcheckout_order_comment', $comment);
    }
}
