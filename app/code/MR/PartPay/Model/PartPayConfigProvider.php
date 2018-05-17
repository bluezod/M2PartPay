<?php
namespace MR\PartPay\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;

// Invoked by Magento\Checkout\Block\Onepage::getCheckoutConfig
class PartPayConfigProvider implements ConfigProviderInterface
{

    /**
     *
     * @var \MR\PartPay\Logger\PartPayLogger
     */
    private $_logger;

    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;

    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    private $_configuration;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
        $this->_configuration = $this->_objectManager->get("\MR\PartPay\Helper\Configuration");
        $this->_logger = $this->_objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_logger->info(__METHOD__);
    }

    public function getConfig()
    {
        $this->_logger->info(__METHOD__);
        $session = $this->_objectManager->get('\Magento\Checkout\Model\Session');
        $quote = $session->getQuote();
        $quoteId = $quote->getId();
        
//        $customerSession = $this->_objectManager->get("\Magento\Customer\Model\Session");
        $paymentUtil = $this->_objectManager->get("\MR\PartPay\Helper\PaymentUtil");

        
        return [
            'payment' => [
                'partpay' => [
                    'redirectUrl' => $paymentUtil->buildRedirectUrl($quoteId),
                    'method' => \MR\PartPay\Model\Payment::MR_PARTPAY_CODE
                ]
            ]
        ];
    }
}
