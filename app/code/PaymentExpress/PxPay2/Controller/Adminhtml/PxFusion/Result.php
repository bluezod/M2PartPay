<?php
namespace PaymentExpress\PxPay2\Controller\Adminhtml\PxFusion;

// TODO: move the common code out.
class Result extends \PaymentExpress\PxPay2\Controller\PxFusion\CommonAction
{
    /**
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    public function __construct(\Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $this->_objectManager->get("\Magento\Framework\Controller\Result\JsonFactory");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        $transactionId = $this->getRequest()->getParam('sessionid');
        $this->_logger->info(__METHOD__ . " transactionId:{$transactionId}");
        return $this->_processPaymentResult($transactionId);
    }

    private function _processPaymentResult($transactionId)
    {
        $userName = $this->_configuration->getUserName();
        $this->_logger->info(__METHOD__ . " userName:{$userName} transactionId:{$transactionId}");
        $dataBag = $this->_loadTransactionResultFromCache($userName, $transactionId);

        $errorText = "Payment failed. Error: ";
        if (empty($dataBag)) {
            $transactionResult = $this->_getPaymentResult($transactionId, 0);
            $status = $transactionResult["status"];
            if ($status === self::APPROVED) {
                $quoteId = $transactionResult["txnRef"];
                $quote = $this->_quoteRepository->get($quoteId);
                $payment = $quote->getPayment();
                
                $this->_updatePaymentData($payment, $transactionResult);
                $this->_savePaymentResult($userName, $transactionId, $quote, $transactionResult);
                
                $errorText = $errorText. " ReCo:" . $transactionResult["responseCode"] . " ResponeText:" . $transactionResult["responseText"];
            }
            
        } else {
            $transactionResult = $dataBag->getTransactionResult();
            $status = $transactionResult["status"];
        }
        
        $success = false;
        
        if ($status != self::NO_TRANSACTION && $status != self::RESULT_UNKOWN) {
            $errorText = $errorText . " ReCo:" . $transactionResult["responseCode"] . " ResponeText:" . $transactionResult["responseText"];
        } else {
            $errorText = $errorText . " transaction not found";
        }
        
        if ($status == self::APPROVED) {
            $success = true;
            $errorText = "";
        }
        else {
            $this->_logger->critical(__METHOD__." status:{$status} ". $errorText);
        }
        
        $response = [
            "Success" => $success,
            "Error" => $errorText
        ];
        
		// set http://stackoverflow.com/questions/2483771/how-can-i-convince-ie-to-simply-display-application-json-rather-than-offer-to-dow
		// Use 'text/plain' to avoid IE display download
		$this->getResponse()->setHeader('Content-type', 'text/plain'); 
		$jsonContent = \Zend_Json::encode($response, false, []);
		$this->getResponse()->setContent($jsonContent);
        
        $this->_logger->info(__METHOD__ . " jsonContent:{$jsonContent} response:" . var_export($response, true));
    }
}