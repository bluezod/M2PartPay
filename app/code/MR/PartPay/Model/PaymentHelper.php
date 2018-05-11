<?php

// Magento\Framework\DataObject implements the magic call function

// Create payment module http://www.josephmcdermott.co.uk/basics-creating-magento2-payment-method
// https://github.com/magento/magento2-samples/tree/master/sample-module-payment-provider
namespace MR\PartPay\Model;

class PaymentHelper
{
    /**
     *
     * @var \MR\PartPay\Helper\PaymentUtil
     */
    protected $_paymentUtil;
    
    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    protected $_configuration;
    
    /**
     *
     * @var \MR\PartPay\Helper\Communication
     */
    protected $_communication;
	
	public function __construct()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_paymentUtil = $objectManager->get("\MR\PartPay\Helper\PaymentUtil");
        $this->_communication = $objectManager->get("\MR\PartPay\Helper\Communication");
		$this->_logger->info(__METHOD__);
	}
	

	public function init($configuration, $communication)
	{
		$this->_logger->info(__METHOD__);
		
	    $this->_configuration = $configuration;
	    $this->_communication = $communication;
	}
	
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
	{
	    $this->_logger->info(__METHOD__);
	    if ($quote != null){
	        $enabled = $this->_configuration->getEnabled($quote->getStoreId());
	    }
	    else {
	        $enabled = $this->_configuration->getEnabled();
	    }
	    $this->_logger->info(__METHOD__ . " enabled:" . $enabled);
	    return $enabled;
	}
	
	public function getConfigPaymentAction($storeId)
    {
        // invoked by Magento\Sales\Model\Order\Payment::place
        $this->_logger->info(__METHOD__);
        $paymentType = $this->_configuration->getPaymentType($storeId);
        $paymentAction = "";
    
        if ($paymentType == \PaymentExpress\PxPay2\Model\Config\Source\PaymentOptions::PURCHASE) {
            $paymentAction = \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
        }
        if ($paymentType == \PaymentExpress\PxPay2\Model\Config\Source\PaymentOptions::AUTH) {
            $paymentAction = \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
        }
        $this->_logger->info(__METHOD__ . " paymentAction: {$paymentAction}");
        return $paymentAction;
    }
	
	public function canCapture($storeId, $info)
	{
		$this->_logger->info(__METHOD__);

		$paymentType = $this->_configuration->getPaymentType($storeId);
		$canCapture = true;
		if ($paymentType == \PaymentExpress\PxPay2\Model\Config\Source\PaymentOptions::PURCHASE) {
			$canCapture = true;
		} else {
			$canCapture = !($this->canRefund($info)); // Complete transaction is processed.
		}
		$this->_logger->info(__METHOD__ . " canCapture:{$canCapture}");
		return $canCapture;
	}
	
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount, $storeId)
    {
        // refer to Magento\Sales\Model\Order\Payment\Transaction\Builder::build for which fields should be set.
        $this->_logger->info(__METHOD__);
        
        $orderId = "unknown";
        $order = $payment->getOrder();
        if ($order) {
            $orderId = $order->getIncrementId();
        }
        
        if (!$payment->hasAdditionalInformation())
        	$this->_logger->info(__METHOD__ . " orderId:{$orderId} additional_information is empty");
        
        $isPurchase = ($payment->getAdditionalInformation("DpsTransactionType") == "Purchase");
        $info = $payment->getAdditionalInformation();
        
        $transactionId = $info["DpsTxnRef"]; // ensure it is unique
        
        if (!$isPurchase) {
            $currency = $info["Currency"];
            $dpsTxnRef = $info["DpsTxnRef"];
            $responseXml = $this->_communication->complete($amount, $currency, $dpsTxnRef, $storeId);
            $responseXmlElement = simplexml_load_string($responseXml);
            $this->_logger->info(__METHOD__ . "  responseXml:" . $responseXml);
            if (!$responseXmlElement) {
                $this->_paymentUtil->saveInvalidResponse($payment, $responseXml);
                $errorMessage = "Failed to capture order:{$orderId}, response from paymentexpress: {$responseXml}";
                $this->_logger->critical(__METHOD__ . $errorMessage);
                throw new \Magento\Framework\Exception\PaymentException(__($errorMessage));
            }
            if (!$responseXmlElement->Transaction || $responseXmlElement->Transaction["success"] != "1") {
                $this->_paymentUtil->savePxPostResponse($payment, $responseXmlElement);
                $errorMessage = "Failed to capture order:{$orderId}, response from paymentexpress: {$responseXml}";
                $this->_logger->critical(__METHOD__ . $errorMessage);
                throw new \Magento\Framework\Exception\PaymentException(__($errorMessage));
            }
            
            $this->_paymentUtil->savePxPostResponse($payment, $responseXmlElement);
            
            $transactionId = (string)$responseXmlElement->DpsTxnRef; // use the DpsTxnRef of Complete
        }

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(1);
        $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $info);
    }
    

    public function canRefund($info)
    {
        $this->_logger->info(__METHOD__);
        $dpsTxnRefForRefund = $this->_paymentUtil->findDpsTxnRefForRefund($info);
    
        $canRefund = false;
        if (isset($info["CardName"]) && $info["CardName"] != "Account2Account") {
            $canRefund = !empty($dpsTxnRefForRefund);
        }
    
        $this->_logger->info(__METHOD__ . " canRefund:{$canRefund} DpsTxnRefForRefund:{$dpsTxnRefForRefund}");
        return $canRefund;
    }
    
    // Mage_Sales_Model_Order_Payment::refund
    // use getInfoInstance to get object of Mage_Payment_Model_Info (Mage_Payment_Model_Info::getMethodInstance Mage_Sales_Model_Order_Payment is sub class of Mage_Payment_Model_Info)
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount, $storeId)
    {
        $this->_logger->info(__METHOD__);
        $info = $payment->getAdditionalInformation();
        $dpsTxnRef = $this->_paymentUtil->findDpsTxnRefForRefund($info);
        $currency = $info["Currency"];
        $responseXml = $this->_communication->refund($amount, $currency, $dpsTxnRef, $storeId);
        $responseXmlElement = simplexml_load_string($responseXml);
    
        $orderId = "unknown";
        $order = $payment->getOrder();
        if ($order) {
            $orderId = $order->getIncrementId();
        }
        $this->_logger->info(__METHOD__ . " orderId:{$orderId}");
    
        if (!$responseXmlElement) {
            $this->_paymentUtil->saveInvalidResponse($payment, $responseXml);
            $errorMessage = " Failed to refund order:{$orderId}, response from paymentexpress: {$responseXml}";
            $this->_logger->critical(__METHOD__ . $errorMessage);
            throw new \Magento\Framework\Exception\PaymentException(__($errorMessage));
        }
    
        $this->_paymentUtil->savePxPostResponse($payment, $responseXmlElement);
    
        if (!$responseXmlElement->Transaction || $responseXmlElement->Transaction["success"] != "1") {
            $errorMessage = " Failed to refund order:{$orderId}. response from paymentexpress: {$responseXml}";
            $this->_logger->critical(__METHOD__ . $errorMessage);
            throw new \Magento\Framework\Exception\PaymentException(__($errorMessage));
        }
    
    }
}
