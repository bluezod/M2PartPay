<?php
namespace PaymentExpress\PxPay2\Controller\Adminhtml\PxFusion;

class CreateTransaction extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    /**
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $_quoteSession;
    
    /**
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $_quoteRepository;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Configuration
     */
    private $_configuration;
    

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Communication
     */
    private $_communication;

    public function __construct(\Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);

        $this->_resultJsonFactory = $this->_objectManager->get("\Magento\Framework\Controller\Result\JsonFactory");
        $this->_quoteSession = $this->_objectManager->get("\Magento\Backend\Model\Session\Quote");
        $this->_quoteRepository = $this->_objectManager->get("\Magento\Quote\Model\QuoteRepository");
        $this->_configuration = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Configuration");
        $this->_communication = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Communication");
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    public function execute()
    {
        // return json: http://magento.stackexchange.com/questions/99358/magento2-how-to-get-json-response-from-controller
        $this->_logger->info(__METHOD__);
        
        $quote = $this->_quoteSession->getQuote();
        $quote->reserveOrderId();
        $this->_quoteRepository->save($quote);
        $transaction = $this->_communication->createTransaction($quote, $this->_buildReturnUrl());
        $postUrl = $this->_configuration->getPostUrl($quote->getStoreId());
        
        $response = [
            "Success" => $transaction->success,
            "TransactionId" => $transaction->transactionId,
            "PostUrl" => $postUrl
        ];
        
        $result = $this->_resultJsonFactory->create();
        $result = $result->setData($response);
        return $result;
    }
    
    private function _buildReturnUrl()
    {
       $url = $this->_url->getUrl("pxpay2/pxfusion/result", ['_secure' => true]);
       $this->_logger->info(__METHOD__." url:{$url}");
       return $url;
    }
}