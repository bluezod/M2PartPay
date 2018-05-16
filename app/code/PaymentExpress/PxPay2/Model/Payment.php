<?php

// Magento\Framework\DataObject implements the magic call function

// Create payment module http://www.josephmcdermott.co.uk/basics-creating-magento2-payment-method
// https://github.com/magento/magento2-samples/tree/master/sample-module-payment-provider
namespace PaymentExpress\PxPay2\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;

    protected $_code;
    
    protected $_infoBlockType = 'PaymentExpress\PxPay2\Block\Info';

    protected $_isGateway = true;

    protected $_canAuthorize = true;

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canUseInternal = false;

    protected $_canUseCheckout = true;

    protected $_canUseForMultishipping = false;

    protected $_canRefund = false;

    /**
     * 
     * @var \PaymentExpress\PxPay2\Model\PaymentHelper
     */
    private $_paymentHelper;

    const PXPAY_CODE = "paymentexpress_pxpay2";

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_code = self::PXPAY_CODE;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
		
		/** @var \PaymentExpress\PxPay2\Helper\Configuration $configuration*/
        $configuration = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\Configuration");
		/** @var \PaymentExpress\PxPay2\Helper\Communication $communication*/
        $communication = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\Communication");
        $this->_paymentHelper = $this->_objectManager->create("\PaymentExpress\PxPay2\Model\PaymentHelper");
        $this->_paymentHelper->init($configuration, $communication);
        
        $this->_logger->info(__METHOD__);
    }

    // invoked by Magento\Quote\Model\PaymentMethodManagement::set
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->_logger->info(__METHOD__ . " data:" . var_export($data, true));
        $infoInstance = $this->getInfoInstance();

        $info = $infoInstance->getAdditionalInformation();
        if (isset($info) && !empty($info['DpsTxnRef'])){
            $this->_logger->info(__METHOD__ . " payment finished. DpsTxnRef:" . $info['DpsTxnRef']);
            return $this; //The transaction is processed.
        }
        
        $source = $data;
        if (isset($data['additional_data'])){
            $source = $this->_objectManager->create("\Magento\Framework\DataObject");
            $source->setData($data['additional_data']);
        }
        
        $info = [];
        $dpsBillingId = $source->getData("paymentexpress_billingId");
        $info["UseSavedCard"] = filter_var($source->getData("paymentexpress_useSavedCard"), FILTER_VALIDATE_BOOLEAN);
        $info["EnableAddBillCard"] = filter_var($source->getData("paymentexpress_enableAddBillCard"), FILTER_VALIDATE_BOOLEAN);
        if (isset($dpsBillingId) && !empty($dpsBillingId)) {
            $info["DpsBillingId"] = $dpsBillingId;
            $info["UseSavedCard"] = true;
            
            $info["EnableAddBillCard"] = false; // Do not add billing token when rebill.
        } else {
            $info["UseSavedCard"] = false;
        }
        $info["cartId"] = $source->getData("cartId");
        $info["guestEmail"] = $source->getData("guestEmail");
        
        $infoInstance->setAdditionalInformation($info);
        $infoInstance->save();

        $this->_logger->info(__METHOD__ . " info:" . var_export($info, true));
        return $this;
    }
    
    public function getConfigPaymentAction()
    {
        return $this->_paymentHelper->getConfigPaymentAction($this->getStore());
    }
    
    public function canCapture()
    {
        $payment = $this->getInfoInstance();
        $info = $payment->getAdditionalInformation();
        $this->_canCapture =  $this->_paymentHelper->canCapture($this->getStore(), $info);
        return $this->_canCapture;
    }
    
    // Invoked by Mage_Sales_Model_Order_Payment::capture
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_logger->info(__METHOD__ . " payment amount:" . $amount);
        $this->_paymentHelper->capture($payment, $amount, $this->getStore());
        return $this;
    }

    public function canRefund()
    {
        $this->_logger->info(__METHOD__);
        $payment = $this->getInfoInstance();
        $info = $payment->getAdditionalInformation();
        $this->_canRefund = $this->_paymentHelper->canRefund($info);
        return $this->_canRefund;
    }
    
    // Mage_Sales_Model_Order_Payment::refund
    // use getInfoInstance to get object of Mage_Payment_Model_Info (Mage_Payment_Model_Info::getMethodInstance Mage_Sales_Model_Order_Payment is sub class of Mage_Payment_Model_Info)
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_logger->info(__METHOD__);
        $this->_paymentHelper->refund($payment, $amount, $this->getStore());
        return $this;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $this->_logger->info(__METHOD__);
        return $this->_paymentHelper->isAvailable($quote);
    }
}
