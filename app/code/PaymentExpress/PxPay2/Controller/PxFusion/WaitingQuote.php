<?php
namespace PaymentExpress\PxPay2\Controller\PxFusion;

class WaitingQuote extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    /**
     *
     * @var \Magento\Sales\Model\Order
     */
    private $_orderManager;

    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    public function __construct(\Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);
        $this->_checkoutSession = $this->_objectManager->get("\Magento\Checkout\Model\Session");
        $this->_orderManager = $this->_objectManager->get("\Magento\Sales\Model\Order");
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $reservedOrderId = $this->getRequest()->getParam('reservedOrderId');
        $triedTimes = $this->getRequest()->getParam("triedTimes");

        $lastRealOrderId = $this->_checkoutSession->getLastRealOrderId();
        $this->_logger->info(__METHOD__ . " reservedOrderId:{$reservedOrderId} triedTimes:{$triedTimes} lastRealOrderId:{$lastRealOrderId}");

        $order = $this->_orderManager->loadByAttribute("increment_id", $reservedOrderId);
        if ($order->getId()){
            $quoteId = $order->getQuoteId();
            $this->_checkoutSession->setLastQuoteId($quoteId);
            $this->_checkoutSession->setLastSuccessQuoteId($quoteId);
            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());
            

            $this->_logger->info(__METHOD__ . " load order:{$reservedOrderId} from db and redirect to the success page.");
            $this->_redirect("checkout/onepage/success", [
                "_secure" => true
                ]);
            return;
        }
        
        if ($triedTimes > 10){
            // defensive code. should never happens.
            $this->_logger->info(__METHOD__ . " order:{$reservedOrderId} is not created yet, redirecting to the cart page, please check if there is any excption happened.");
            $this->_redirect("checkout/cart");
            return;
        }
        
        sleep(1); // wait for order ready.
        
        $this->_redirect("pxpay2/pxfusion/waitingQuote", [
            "_secure" => true,
            "triedTimes" => $triedTimes + 1,
            "reservedOrderId" => $reservedOrderId
            ]);
        return;
    }
}