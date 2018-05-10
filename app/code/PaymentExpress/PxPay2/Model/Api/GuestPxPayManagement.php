<?php
namespace PaymentExpress\PxPay2\Model\Api;

class GuestPxPayManagement implements \PaymentExpress\PxPay2\Api\GuestPxPayManagementInterface
{
    // http://devdocs.magento.com/guides/v2.0/extension-dev-guide/service-contracts/service-to-web-service.html
    
        /**
     * 
     * @var \PaymentExpress\PxPay2\Model\Api\ApiPxPayHelper
     */
    private $_apiHelper;

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;


    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxPay\UrlCreator
     */
    private $_pxpayUrlCreator;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $_cartRepository;
    
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $_quoteIdMaskFactory;
    
    /**
     * @var \Magento\Quote\Api\GuestBillingAddressManagementInterface
     */
    private $billingAddressManagement;
    
    
    public function __construct(
    		\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
    		\Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
    		\Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_apiHelper = $objectManager->get("\PaymentExpress\PxPay2\Model\Api\ApiPxPayHelper");
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
        
        $this->_cartRepository = $quoteRepository;
        $this->_quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->billingAddressManagement = $billingAddressManagement;
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
        // Create pxpay redirect url.
        
        if ($billingAddress) {
        	$this->_logger->info(__METHOD__. " assigning billing address.");
        	$billingAddress->setEmail($email);
        	$this->billingAddressManagement->assign($cartId, $billingAddress);
        } else {
        	$quoteIdMask = $this->_quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        	$quoteId = $quoteIdMask->getQuoteId();
        	$this->_cartRepository->getActive($quoteId)->getBillingAddress()->setEmail($email);
        }
        
        $url = $this->_apiHelper->createUrlForGuest($cartId, $email, $method);
        
        $this->_logger->info(__METHOD__. " redirectUrl:{$url}");
        return $url;
    }

}