<?php
namespace PaymentExpress\PxPay2\Model\PxFusion;

use \Magento\Checkout\Model\ConfigProviderInterface;

// Invoked by Magento\Checkout\Block\Onepage::getCheckoutConfig
class ConfigProvider implements ConfigProviderInterface
{

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Configuration
     */
    private $_configuration;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PaymentUtil
     */
    private $_paymentUtil;
    
    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Communication
     */
    private $_communication;

    /**
     *
     * @var \Magento\Framework\Url
     */
    private $_url;

    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;

    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Url $url
    ) {
        $this->_objectManager = $objectManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_url = $url;
        $this->_configuration = $objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Configuration");
        $this->_communication = $objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Communication");
        $this->_paymentUtil = $objectManager->get("\PaymentExpress\PxPay2\Helper\PaymentUtil");
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }
    
    // QuoteData Magento\Checkout\Helper\Data\DefaultConfigProvider
    public function getConfig()
    {
        $this->_logger->info(__METHOD__. " quoteId: ". $this->_checkoutSession->getQuoteId());
        $quote = $this->_checkoutSession->getQuote();

        $customerSession = $this->_objectManager->get("\Magento\Customer\Model\Session");
        $isRebillEnabled = ($customerSession->isLoggedIn() && $this->_configuration->getAllowRebill());
        $showCardOptions = $isRebillEnabled; // no other conditions for rebilling on PxFusion?
        $customerId = $quote->getCustomerId();

        return [
            'payment' => [
                'paymentexpress' => [
                    'pxfusion' => [
                        'postUrl' => $this->_configuration->getPostUrl($quote->getStoreId()),
                        'payemntExpressLogo' => $this->_paymentUtil->getPaymentExpressLogoSrc(),
                        'savedCards' => $this->_loadSavedCards($customerId),
                        'isRebillEnabled' => $isRebillEnabled,
                        'showCardOptions' => $showCardOptions,
                        'method' => \PaymentExpress\PxPay2\Model\PxFusion\Payment::CODE,
                    ]
                ]
            ]
        ];
    }

    private function _loadSavedCards($customerId)
    {
        $this->_logger->info(__METHOD__ . " customerId:{$customerId}");
        $savedCards = [];
        
        if (!empty($customerId)) { // do not access database if the order is processed by guest, to improve performance.
            $billingModel = $this->_objectManager->create("\PaymentExpress\PxPay2\Model\BillingToken");
            $billingModelCollection = $billingModel->getCollection()->addFieldToFilter('customer_id', $customerId);
            $billingModelCollection->getSelect()->group(
                [
                'masked_card_number',
                'cc_expiry_date'
                ]
            );
            
            foreach ($billingModelCollection as $item) {
                $maskedCardNumber = trim($item->getMaskedCardNumber());
                $ccExpiryDate = trim($item->getCcExpiryDate());
                if (!empty($maskedCardNumber)) {
                    $savedCards[] = [
                        "billing_token" => $item->getDpsBillingId(),
                        "card_number" => $maskedCardNumber,
                        "expiry_date" => $ccExpiryDate,
                        "card_info" => $maskedCardNumber . " Expiry Date:" . $ccExpiryDate
                    ];
                }
            }
        }
        
        return $savedCards;
    }

}
