<?php
namespace MR\PartPay\Helper\PartPay;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Payment\Gateway\Http\Client\Soap;

class UrlCreator
{

    /**
     *
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;
    
    /**
     *
     * @var \MR\PartPay\Logger\PartPayLogger
     */
    private $_logger;

    /**
     *
     * @var \MR\PartPay\Helper\Configuration
     */
    protected $_configuration;

    /**
     *
     * @var \MR\PartPay\Helper\Communication
     */
    protected $_communication;

    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_objectManager = $objectManager;
        $this->_communication = $objectManager->get("\MR\PartPay\Helper\Communication");
        $this->_logger = $objectManager->get("\MR\PartPay\Logger\PartPayLogger");
        $this->_configuration = $objectManager->get("\MR\PartPay\Helper\Configuration");
        
        $this->_logger->info(__METHOD__);
    }

    public function CreateUrl(\Magento\Quote\Model\Quote $quote)
    {
        $this->_logger->info(__METHOD__);
        
        $transactionType = $this->_configuration->getPaymentType($quote->getStoreId());
        $forceA2A = $this->_configuration->getForceA2A($quote->getStoreId());
        $requestData = $this->_buildPxPayRequestData($quote, $transactionType, $forceA2A);
        
        $responseXml = $this->_communication->getPxPay2Page($requestData);
        
        $responseXmlElement = simplexml_load_string($responseXml);
        if (!$responseXmlElement) {
            $error = "Invalid response from PaymentExpress: " . $responseXml;
            $this->_logger->critical(__METHOD__ . " " . $error);
            return "";
        }
        
        if ($responseXmlElement['valid'] != "1" || !$responseXmlElement->URI) {
            $error = "Failed to get the Payment Url";
            // <Request valid="1"><Reco>W2</Reco><ResponseText>No Account2Account Account Setup For Payment Currency</ResponseText></Request>
            if (isset($responseXmlElement->Reco) || isset($responseXmlElement->ResponseText)) {
                $error = "Error from PaymentExpress: ReCo: " . $responseXmlElement->Reco . " ResponseText:" .
                     $responseXmlElement->ResponseText;
            } elseif (isset($responseXmlElement->URI)) {
                $error = "Error from PaymentExpress: " . $responseXmlElement->URI;
            }

            $this->_logger->critical(__METHOD__ . " " . $error);
            
            return "";
        }

        return (string)$responseXmlElement->URI;
    }

    private function _buildPxPayRequestData(\Magento\Quote\Model\Quote $quote, $transactionType, $forceA2A)
    {
        $orderIncrementId = $quote->getReservedOrderId();
        $this->_logger->info(
            __METHOD__ . " orderIncrementId:{$orderIncrementId} transactionType:{$transactionType} forceA2A:{$forceA2A}");
        
        $currency = $quote->getBaseCurrencyCode();
        $amount = $quote->getBaseGrandTotal();
        
        $additionalInfo = [];
        
        $payment = $quote->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $dataBag = $objectManager->create("\Magento\Framework\DataObject");
        $dataBag->setForceA2A(false);
        if ($transactionType == "Purchase" && $forceA2A) {
            $dataBag->setForceA2A(true);
        }
        
        $txnId = substr(uniqid(rand()), 0, 16);
        $dataBag->setTxnId($txnId); // quote cannot be used as txnId. As quote may pay failed.
        
        // <TxnId>ABC123</TxnId>
        // <TxnData1>John Doe</TxnData1>
        // <TxnData2>0211111111</TxnData2>
        // <TxnData3>98 Anzac Ave, Auckland 1010</TxnData3>
        
        $dataBag->setAmount($amount);
        $dataBag->setCurrency($currency);
        $dataBag->setTransactionType($transactionType);
        $dataBag->setOrderIncrementId($orderIncrementId);
        $dataBag->setQuoteId($quote->getId());
        
        $customerInfo = $this->_loadCustomerInfo($quote);
        $dataBag->setCustomerInfo($customerInfo);
        $this->_logger->info(__METHOD__ . " dataBag:" . var_export($dataBag, true));
        return $dataBag;
    }

    private function _loadCustomerInfo(\Magento\Quote\Model\Quote $quote)
    {
        $customerId = $quote->getCustomerId();
        $this->_logger->info(__METHOD__ . " customerId:{$customerId}");
        $customerInfo = $this->_objectManager->create("\Magento\Framework\DataObject");
        
        $customerInfo->setId($customerId);
        
        $customerInfo->setName($this->_getCustomerName($quote));
        $customerInfo->setEmail($quote->getCustomerEmail());
        
        try {
            $address = $quote->getBillingAddress();
            if ($address) {
                $customerInfo->setPhoneNumber($address->getTelephone());
                
                $streetFull = implode(" ", $address->getStreet()) . " " . $address->getCity() . ", " .
                     $address->getRegion() . " " . $address->getPostcode() . " " . $address->getCountryId();
                
                $customerInfo->setAddress($streetFull);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->critical($e->_toString());
        }
        
        return $customerInfo;
    }

    /**
     * Retrieve customer name
     *
     * @return string
     */
    private function _getCustomerName(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getCustomerFirstname()) {
            $customerName = $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname();
        } else {
            $customerName = (string)__('Guest');
        }
        
        $this->_logger->info(__METHOD__ . " customerName:{$customerName}");
        return $customerName;
    }
}
