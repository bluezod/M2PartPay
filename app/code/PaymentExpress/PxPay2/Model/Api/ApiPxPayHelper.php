<?php
namespace PaymentExpress\PxPay2\Model\Api;

use \Magento\Framework\Exception\State\InvalidTransitionException;

class ApiPxPayHelper
{
    // http://devdocs.magento.com/guides/v2.0/extension-dev-guide/service-contracts/service-to-web-service.html

    /**
     *
     * @var \PaymentExpress\PxPay2\Model\Api\ApiCommonHelper
     */
    private $_apiCommonHelper;
    
    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxPay\UrlCreator
     */
    private $_pxpayUrlCreator;
    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_pxpayUrlCreator = $objectManager->get("\PaymentExpress\PxPay2\Helper\PxPay\UrlCreator");
        $this->_apiCommonHelper = $objectManager->get("\PaymentExpress\PxPay2\Model\Api\ApiCommonHelper");
        
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        
        $this->_logger->info(__METHOD__);
    }

    public function createUrlForCustomer($quoteId, \Magento\Quote\Api\Data\PaymentInterface $method)
    {
        $this->_logger->info(__METHOD__. " quoteId:{$quoteId}");

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->_apiCommonHelper->setPaymentForLoggedinCustomer($quoteId, $method);
        
        return $this->_createUrl($quote);
    }
    
    public function createUrlForGuest($cartId, $email, \Magento\Quote\Api\Data\PaymentInterface $method)
    {
        $this->_logger->info(__METHOD__. " cartId:{$cartId}");

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->_apiCommonHelper->setPaymentForGuest($cartId, $email, $method);
        
        return $this->_createUrl($quote);
    }
    
    private function _createUrl(\Magento\Quote\Model\Quote $quote)
    {
        // Create pxpay redirect url.
        $url = $this->_pxpayUrlCreator->CreateUrl($quote);
        if (!isset($url) || empty($url)){
            $quoteId = $quote->getId();
            $this->_logger->critical(__METHOD__ . " Failed to create transaction quoteId:{$quoteId}");
            throw new InvalidTransitionException(__('Failed to create transaction.'));
        }
        
        $this->_logger->info(__METHOD__. " redirectUrl:{$url}");
        return $url;
    }
}