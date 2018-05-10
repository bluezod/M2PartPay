<?php
namespace PaymentExpress\PxPay2\Model\Api;

use \Magento\Framework\Exception\State\InvalidTransitionException;

class PxFusionManagement implements \PaymentExpress\PxPay2\Api\PxFusionManagementInterface
{
    // http://devdocs.magento.com/guides/v2.0/extension-dev-guide/service-contracts/service-to-web-service.html
    /**
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $_quoteRepository;
    
    /**
     * @var \Magento\Quote\Model\QuoteValidator
     */
    private $_quoteValidator;
    
    /**
     *
     * @var \Magento\Framework\Url
     */
    private $_url;
    
    /**
     *
     * @var \Magento\Quote\Model\PaymentMethodManagement
     */
    private $_paymentMethodManagement;

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Communication
     */
    private $_communication;

    /**
     * 
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Configuration
     */
    private $_configuration;
    
    /**
     *
     * @var \Magento\Quote\Api\BillingAddressManagementInterface
     */
    private $_billingAddressManagement;
    
    
    public function __construct(
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \PaymentExpress\PxPay2\Helper\PxFusion\Configuration $configuration
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_paymentMethodManagement = $objectManager->get("\Magento\Quote\Model\PaymentMethodManagement");
        $this->_quoteRepository = $objectManager->get("\Magento\Quote\Model\QuoteRepository");
        $this->_quoteValidator = $objectManager->get("\Magento\Quote\Model\QuoteValidator");
        $this->_url = $objectManager->get("\Magento\Framework\Url");
        
        $this->_configuration = $configuration;
        $this->_communication = $objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Communication");
        $this->_logger = $objectManager->get("PaymentExpress\PxPay2\Logger\DpsLogger");
     
        $this->_billingAddressManagement = $billingAddressManagement;
        
        $this->_logger->info(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function set($cartId, \Magento\Quote\Api\Data\PaymentInterface $method, \Magento\Quote\Api\Data\AddressInterface $billingAddress = null)
    {
        $this->_logger->info(__METHOD__. " cartId:{$cartId}");
        
        if ($billingAddress) {
        	$this->_logger->info(__METHOD__. " assigning billing address");
        	$this->_billingAddressManagement->assign($cartId, $billingAddress);
        }
        
        $this->_paymentMethodManagement->set($cartId, $method);

        $quote = $this->_quoteRepository->get($cartId);
        $quote->setCustomerIsGuest(false);
        $quote->reserveOrderId();
        $this->_quoteRepository->save($quote);
        
        $this->_quoteValidator->validateBeforeSubmit($quote); // ensure all the data is correct

        $addData = $method->getAdditionalData();
        $dpsBillingId = "";
        $storeId = $quote->getStoreId();
        $useSavedCard = filter_var($addData["useSavedCard"], FILTER_VALIDATE_BOOLEAN);
        $enableAddBillCard = false;
        if (array_key_exists("enableAddBillCard", $addData))
        	$enableAddBillCard = $this->_configuration->getAllowRebill($storeId) && filter_var($addData["enableAddBillCard"], FILTER_VALIDATE_BOOLEAN);
        
        if ($useSavedCard && array_key_exists("billingId", $addData)) {
        	$dpsBillingId = $addData["billingId"];
        }
        
        if (!$useSavedCard || empty($dpsBillingId)) {
	        $result = $this->_communication->createTransaction($quote, $this->_buildReturnUrl(), $enableAddBillCard);
	        if (!$result->success){
	            $quoteId = $quote->getId();
	            $this->_logger->critical(__METHOD__ . " Failed to create transaction quoteId:{$quoteId}");
	            throw new InvalidTransitionException(__('Failed to create transaction.'));
	        }
	
	        $transactionId = $result->transactionId;
	        return $transactionId;
        }
        else {
        	$result = $this->_communication->rebill($quote, $dpsBillingId, $quote->getStoreId());
        	$url = $this->_url->getUrl($result, ['_secure' => true]);
        	return $url;
        }
    }
    
    private function _buildReturnUrl()
    {
        $this->_logger->info(__METHOD__);
        $url = $this->_url->getUrl('pxpay2/pxfusion/result', ['_secure' => true]);
        $this->_logger->info(__METHOD__ . " url: {$url} ");
        return $url;
    }
}