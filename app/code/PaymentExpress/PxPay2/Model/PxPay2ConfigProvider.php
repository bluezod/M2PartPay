<?php
namespace PaymentExpress\PxPay2\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;

// Invoked by Magento\Checkout\Block\Onepage::getCheckoutConfig
class PxPay2ConfigProvider implements ConfigProviderInterface
{

    /**
     *
     * @var \PaymentExpress\PxPay2\Logger\DpsLogger
     */
    private $_logger;

    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\Configuration
     */
    private $_configuration;

    /**
     *
     * @var \PaymentExpress\PxPay2\Block\MerchantLogo
     */
    private $_merchantLogo;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
        $this->_configuration = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\Configuration");
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_merchantLogo = $this->_objectManager->get("\PaymentExpress\PxPay2\Block\MerchantLogo");
        $this->_logger->info(__METHOD__);
    }

    public function getConfig()
    {
        $this->_logger->info(__METHOD__);
        $session = $this->_objectManager->get('\Magento\Checkout\Model\Session');
        $quote = $session->getQuote();
        $quoteId = $quote->getId();
        $customerId = $quote->getCustomerId();
        
        $customerSession = $this->_objectManager->get("\Magento\Customer\Model\Session");
        $isRebillEnabled = ($customerSession->isLoggedIn() && $this->_configuration->getAllowRebill());
        $showCardOptions = $isRebillEnabled && !$this->_configuration->getForceA2A(); // not show card configuration when rebill is false or A2A is disabled.
        
        $paymentUtil = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\PaymentUtil");
        
        $logos = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $this->_merchantLogo->setLogoPathPrefix("merchantLogo{$i}");
            $url = $this->_merchantLogo->getLogoUrl();
            if (empty($url)) {
                continue;
            }
            $logos[] = [
                "Url" => $url,
                "Alt" => $this->_merchantLogo->getLogoAlt(),
                "Width" => $this->_merchantLogo->getLogoWidth(),
                "Height" => $this->_merchantLogo->getLogoHeight()
            ];
        }
        
        $merchantUICustomOptions = [
            'linkData' => $this->_configuration->getMerchantLinkData(),
            'logos' => $logos,
            'text' => $this->_configuration->getMerchantText()
        ];
        
        return [
            'payment' => [
                'paymentexpress' => [
                    'redirectUrl' => $paymentUtil->buildRedirectUrl($quoteId),
                    'savedCards' => $this->_loadSavedCards($customerId),
                    'isRebillEnabled' => $isRebillEnabled,
                    'showCardOptions' => $showCardOptions,
                    'payemntExpressLogo' => $paymentUtil->getPaymentExpressLogoSrc(),
                    'merchantUICustomOptions' => $merchantUICustomOptions,
                    'method' => \PaymentExpress\PxPay2\Model\Payment::PXPAY_CODE
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
