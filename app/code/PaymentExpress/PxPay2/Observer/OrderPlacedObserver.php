<?php

namespace PaymentExpress\PxPay2\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \PaymentExpress;

class OrderPlacedObserver implements ObserverInterface
{
    /**
     *
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $_orderSender;
    
    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;
    
    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_orderSender = $objectManager->get("\Magento\Sales\Model\Order\Email\Sender\OrderSender");
    
        $this->_logger->info(__METHOD__);
    }
    
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        
        $payment = $order->getPayment();
        $method = $payment->getMethod();

        $this->_logger->info(__METHOD__ . " orderId:".$order->getId() . " paymentMethod:{$method}");
        
        if ($method != PaymentExpress\PxPay2\Model\Payment::PXPAY_CODE &&
            $method !=  PaymentExpress\PxPay2\Model\PxFusion\Payment::CODE){
            return; // only send mail for payment methods in dps
        }
        
        if ($order->getCanSendNewEmailFlag()) {
            try {
                $this->_orderSender->send($order);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
    }
}