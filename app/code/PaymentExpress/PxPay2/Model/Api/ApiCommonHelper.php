<?php
namespace PaymentExpress\PxPay2\Model\Api;

class ApiCommonHelper
{
    // http://devdocs.magento.com/guides/v2.0/extension-dev-guide/service-contracts/service-to-web-service.html
    /**
     *
     * @var /Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $_quoteIdMaskFactory;
    
    /**
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $_quoteRepository;
    
    /**
     *
     * @var \Magento\Quote\Model\PaymentMethodManagement
     */
    private $_paymentMethodManagement;
    
    /**
     * @var \Magento\Quote\Model\QuoteValidator
     */
    private $_quoteValidator;

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_quoteIdMaskFactory = $objectManager->get("\Magento\Quote\Model\QuoteIdMaskFactory");
        $this->_paymentMethodManagement = $objectManager->get("\Magento\Quote\Model\PaymentMethodManagement");
        $this->_quoteRepository = $objectManager->get("\Magento\Quote\Model\QuoteRepository");
        $this->_quoteValidator = $objectManager->get("\Magento\Quote\Model\QuoteValidator");
        
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        
        $this->_logger->info(__METHOD__);
    }

    public function setPaymentForLoggedinCustomer($quoteId, \Magento\Quote\Api\Data\PaymentInterface $method)
    {
        $this->_logger->info(__METHOD__. " quoteId:{$quoteId}");
        $this->_paymentMethodManagement->set($quoteId, $method);

        $quote = $this->_quoteRepository->get($quoteId);
        
        $this->_quoteValidator->validateBeforeSubmit($quote); // ensure all the data is correct
        
        $quote->reserveOrderId();
        $this->_quoteRepository->save($quote);
        
        return $quote;
    }
    
    public function setPaymentForGuest($cartId, $email, \Magento\Quote\Api\Data\PaymentInterface $method)
    {
        $this->_logger->info(__METHOD__. " cartId:{$cartId}");
        $quoteIdMask = $this->_quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = $quoteIdMask->getQuoteId();

        $this->_logger->info(__METHOD__. " cartId:{$cartId}  quoteId:{$quoteId}");

        $this->_paymentMethodManagement->set($quoteId, $method);
        $quote = $this->_quoteRepository->get($quoteId);
        $quote->getBillingAddress()->setEmail($email);
        
        $this->_quoteValidator->validateBeforeSubmit($quote); // ensure all the data is correct
        
        $quote->setCustomerIsGuest(true);
        $quote->reserveOrderId();
        $this->_quoteRepository->save($quote);
        $payment = $quote->getPayment();
        $info = $payment->getAdditionalInformation();
        if ($info["cartId"] != $cartId) {
            // Maybe merchant do not use the default implementation of PaymentInterface, leads the $method->getData() not return the data from js?
            $this->_logger->info(__METHOD__ . " Unexpect behavior! cartId set incorrectly. PaymentInterface.class:" . get_class($method) ." payment.info:" . var_export($info, true));
            $info["cartId"] = $cartId;
            $info["guestEmail"] = $email;
            
            $payment->setAdditionalInformation($info);
            $payment->save();
        }
        $info = $payment->getAdditionalInformation();
        $this->_logger->info(__METHOD__ . " PaymentInterface.class:" . get_class($method) . " info: " . var_export($info, true));
        
        return $quote;
    }
}