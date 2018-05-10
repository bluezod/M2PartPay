<?php
namespace PaymentExpress\PxPay2\Model\Api;

use \Magento\Framework\Exception\State\InvalidTransitionException;

class GuestPxFusionManagement implements \PaymentExpress\PxPay2\Api\GuestPxFusionManagementInterface
{
    // http://devdocs.magento.com/guides/v2.0/extension-dev-guide/service-contracts/service-to-web-service.html
    
    protected $_quoteIdMaskFactory;
    
    /**
     * @var \Magento\Quote\Model\QuoteValidator
     */
    private $_quoteValidator;
    
    /**
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $_quoteRepository;
    
    /**
     *
     * @var \Magento\Framework\Url
     */
    private $_url;
    
    /**
     *
     * @var \Magento\Quote\Model\GuestCart\GuestPaymentMethodManagement
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
     * @var \Magento\Quote\Api\GuestBillingAddressManagementInterface
     */
    private $billingAddressManagement;
    
    public function __construct(
        \Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_paymentMethodManagement = $objectManager->get("\Magento\Quote\Model\GuestCart\GuestPaymentMethodManagement");
        $this->_quoteIdMaskFactory = $objectManager->get("Magento\Quote\Model\QuoteIdMaskFactory");
        $this->_quoteValidator = $objectManager->get("\Magento\Quote\Model\QuoteValidator");
        $this->_quoteRepository = $objectManager->get("\Magento\Quote\Model\QuoteRepository");
        $this->_url = $objectManager->get("\Magento\Framework\Url");
        
        $this->_communication = $objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Communication");
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        
        $this->billingAddressManagement = $billingAddressManagement;
        
        $this->_logger->info(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function set(
    		$cartId, 
    		$email, 
    		\Magento\Quote\Api\Data\PaymentInterface $method, 
    		\Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->_logger->info(__METHOD__. " cartId:{$cartId} guestEmail:{$email}");
        $this->_paymentMethodManagement->set($cartId, $method);
        
        $quoteIdMask = $this->_quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = $quoteIdMask->getQuoteId();
        
        if ($billingAddress) {
        	$this->_logger->info(__METHOD__. " assigning billing address");
        	
        	$billingAddress->setEmail($email);
        	$this->billingAddressManagement->assign($cartId, $billingAddress);
        } else {
        	$this->_quoteRepository->getActive($quoteId)->getBillingAddress()->setEmail($email);
        }
        
        $quote = $this->_quoteRepository->get($quoteId);
        
        $quote->setCustomerIsGuest(true);
        $quote->reserveOrderId();
        $this->_quoteRepository->save($quote);
        
        $this->_quoteValidator->validateBeforeSubmit($quote); // ensure all the data is correct

        $result = $this->_communication->createTransaction($quote, $this->_buildReturnUrl(), false);
        if (!$result->success){
            $quoteId = $quote->getId();
            $this->_logger->critical(__METHOD__ . " Failed to create transaction quoteId:{$quoteId}");
            throw new InvalidTransitionException(__('Failed to create transaction.'));
        }

        $sessionId = $result->sessionId;
        $transactionId = $result->transactionId;
        
        return $transactionId;
    }

    private function _buildReturnUrl()
    {
        $this->_logger->info(__METHOD__);
        $url = $this->_url->getUrl('pxpay2/pxfusion/result', ['_secure' => true]);
        $this->_logger->info(__METHOD__ . " url: {$url} ");
        return $url;
    }
}