<?php

// Magento\Framework\DataObject implements the magic call function
namespace PaymentExpress\PxPay2\Model\PxFusion;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{

    const CODE = "paymentexpress_pxfusion";

    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;

    protected $_code = "paymentexpress_pxfusion";
    
    // used to render the panel when creating order in admin page.
    protected $_formBlockType = 'PaymentExpress\PxPay2\Block\PxFusion\Adminhtml\Form';

    protected $_infoBlockType = 'PaymentExpress\PxPay2\Block\Info';

    protected $_isGateway = true;

    protected $_canAuthorize = true;

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canUseInternal = true;

    protected $_canUseCheckout = true;

    protected $_canUseForMultishipping = false;

    protected $_canRefund = false;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PaymentUtil
     */
    protected $_paymentUtil;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Configuration
     */
    protected $_configuration;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PxFusion\Communication
     */
    protected $_communication;

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
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, 
            $logger, $resource, $resourceCollection, $data);
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $this->_objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_paymentUtil = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\PaymentUtil");
        $this->_configuration = $this->_objectManager->get(\PaymentExpress\PxPay2\Helper\PxFusion\Configuration::class);
        $this->_communication = $this->_objectManager->get("\PaymentExpress\PxPay2\Helper\PxFusion\Communication");
        $this->_logger->info(__METHOD__);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $this->_logger->info(__METHOD__);
        $enabled = $this->_configuration->getEnabled();
        $this->_logger->info(__METHOD__ . " enabled:" . $enabled);
        return $enabled;
    }

    // invoked by Magento\Quote\Model\PaymentMethodManagement::set
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->_logger->info(__METHOD__ . " data:" . var_export($data, true));
        
        $infoInstance = $this->getInfoInstance();
        
        $info = $infoInstance->getAdditionalInformation();
        if (isset($info) && !empty($info['DpsTxnRef'])){
            return $this; //The transaction is processed.
        }
        
        // sessionId and transactionId is always sent by JS (dps-pxfusion.js/getPaymentData)
        $source = $data;
        if (isset($data['additional_data'])){
            $source = $this->_objectManager->create("Magento\Framework\DataObject");
            $source->setData($data['additional_data']);
        }
        
        $info = [
            "sessionId" => $source->getData('sessionId'),
            "transactionId" => $source->getData('transactionId'),
            "cartId" => $source->getData('cartId'),
            "guestEmail" => $source->getData('guestEmail')
        ];

        $dpsBillingId = $source->getData("billingId");
        $info["UseSavedCard"] = filter_var($source->getData("useSavedCard"), FILTER_VALIDATE_BOOLEAN);
        $info["EnableAddBillCard"] = filter_var($source->getData("enableAddBillCard"), FILTER_VALIDATE_BOOLEAN);
        if (isset($dpsBillingId) && !empty($dpsBillingId)) {
            $info["DpsBillingId"] = $dpsBillingId;
            $info["UseSavedCard"] = true;
            
            $info["EnableAddBillCard"] = false; // Do not add billing token when rebill.
        } else {
            $info["UseSavedCard"] = false;
        }


        $infoInstance->setAdditionalInformation($info);
        $infoInstance->save();
        $this->_logger->info(__METHOD__ . " data:" . var_export($info, true));
        
        return $this;
    }

    public function getConfigPaymentAction()
    {
        // invoked by Magento\Sales\Model\Order\Payment::place
        $this->_logger->info(__METHOD__);
        $paymentType = $this->_configuration->getPaymentType($this->getStore());
        $paymentAction = "";
        
        if ($paymentType == \PaymentExpress\PxPay2\Model\Config\Source\PaymentOptions::PURCHASE) {
            $paymentAction = \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
        }
        if ($paymentType == \PaymentExpress\PxPay2\Model\Config\Source\PaymentOptions::AUTH) {
            $paymentAction = \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
        }
        $this->_logger->info(__METHOD__ . " paymentAction: {$paymentAction}");
        return $paymentAction;
    }

    public function canCapture()
    {
        $this->_logger->info(__METHOD__);
        
        $paymentType = $this->_configuration->getPaymentType($this->getStore());
        if ($paymentType == \PaymentExpress\PxPay2\Model\Config\Source\PaymentOptions::PURCHASE) {
            $this->_canCapture = true;
        } else {
            $this->_canCapture = !($this->canRefund()); // Complete transaction is processed.
        }
        $this->_logger->info(__METHOD__ . " canCapture:{$this->_canCapture}");
        return $this->_canCapture;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // refer to Magento\Sales\Model\Order\Payment\Transaction\Builder::build for which fields should be set.
        $this->_logger->info(__METHOD__);
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }
        
        $orderId = "unknown";
        $order = $payment->getOrder();
        if ($order) {
            $orderId = $order->getIncrementId();
        }
        
        $isPurchase = ($payment->getAdditionalInformation("DpsTransactionType") == "Purchase");
        $info = $payment->getAdditionalInformation();
        
        $transactionId = $info["DpsTxnRef"]; // ensure it is unique
        
        if (!$isPurchase) {
            $currency = $info["Currency"];
            $dpsTxnRef = $info["DpsTxnRef"];
            $responseXml = $this->_communication->complete($amount, $currency, $dpsTxnRef, $this->getStore());
            $responseXmlElement = simplexml_load_string($responseXml);
            $this->_logger->info(__METHOD__ . "  responseXml:" . $responseXml);
            if (!$responseXmlElement) {
                $this->_paymentUtil->saveInvalidResponse($payment, $responseXml);
                $errorMessage = "Failed to capture order:{$orderId}, response from paymentexpress: {$responseXml}";
                $this->_logger->critical(__METHOD__ . $errorMessage);
                throw new \Magento\Framework\Exception\PaymentException($errorMessage);
            }
            if (!$responseXmlElement->Transaction || $responseXmlElement->Transaction["success"] != "1") {
                $this->_paymentUtil->savePxPostResponse($payment, $responseXmlElement);
                $errorMessage = "Failed to capture order:{$orderId}, response from paymentexpress: {$responseXml}";
                $this->_logger->critical(__METHOD__ . $errorMessage);
                throw new \Magento\Framework\Exception\PaymentException($errorMessage);
            }
            
            $pxpostInfo = $this->_paymentUtil->savePxPostResponse($payment, $responseXmlElement);
            
            $transactionId = (string)$responseXmlElement->DpsTxnRef; // use the DpsTxnRef of Complete
        }

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(1);
        $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $info);
        
        return $this;
    }

    public function canRefund()
    {
        $this->_logger->info(__METHOD__);
        $payment = $this->getInfoInstance();
        $info = $payment->getAdditionalInformation();
        $dpsTxnRefForRefund = $this->_paymentUtil->findDpsTxnRefForRefund($info);
        
        $this->_canRefund = !empty($dpsTxnRefForRefund);
        
        $this->_logger->info(__METHOD__ . " canRefund:{$this->_canRefund} DpsTxnRefForRefund:{$dpsTxnRefForRefund}");
        return $this->_canRefund;
    }
    
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //TODO: same code with PxPay2. move the common code to separate class to reuse code.
        $this->_logger->info(__METHOD__);
        $info = $payment->getAdditionalInformation();
        $dpsTxnRef = $this->_paymentUtil->findDpsTxnRefForRefund($info);
        $currency = $info["Currency"];
        $responseXml = $this->_communication->refund($amount, $currency, $dpsTxnRef, $this->getStore());
        $responseXmlElement = simplexml_load_string($responseXml);
        
        $orderId = "unknown";
        $order = $payment->getOrder();
        if ($order) {
            $orderId = $order->getIncrementId();
        }
        $this->_logger->info(__METHOD__ . " orderId:{$orderId}");
        
        if (!$responseXmlElement) {
            $this->_paymentUtil->saveInvalidResponse($payment, $responseXml);
            $errorMessage = " Failed to refund order:{$orderId}, response from paymentexpress: {$responseXml}";
            $this->_logger->critical(__METHOD__ . $errorMessage);
            throw new \Magento\Framework\Exception\PaymentException($errorMessage);
        }
        
        $this->_paymentUtil->savePxPostResponse($payment, $responseXmlElement);
        
        if (!$responseXmlElement->Transaction || $responseXmlElement->Transaction["success"] != "1") {
            $errorMessage = " Failed to refund order:{$orderId}. response from paymentexpress: {$responseXml}";
            $this->_logger->critical(__METHOD__ . $errorMessage);
            throw new \Magento\Framework\Exception\PaymentException($errorMessage);
        }
        
        return $this;
    }
}