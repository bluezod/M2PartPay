<?php
namespace PaymentExpress\PxPay2\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;

class Communication extends AbstractHelper
{

    private $_sensitiveFields = [
        "PxPayKey",
        "PostPassword"
    ];

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\PaymentUtil
     */
    private $_paymentUtil;

    /**
     *
     * @var \PaymentExpress\PxPay2\Helper\Configuration
     */
    private $_configuration;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_configuration = $objectManager->get("\PaymentExpress\PxPay2\Helper\Configuration");
        $this->_paymentUtil = $objectManager->get("\PaymentExpress\PxPay2\Helper\PaymentUtil");
        $this->_logger->info(__METHOD__);
    }

    public function getPxPay2Page($requestData, $storeId = null)
    {
        $this->_logger->info(__METHOD__);
        $requestXml = $this->_buildPxPay2Request($requestData);
        $url = $this->_configuration->getPxPayUrl($storeId);
        return $this->_sendRequest($requestXml, $url);
    }

    public function getTransactionStatus($userId, $token, $storeId = null)
    {
        $this->_logger->info(__METHOD__ . " pxPayUserId:{$userId} storeId:{$storeId}");
        $requestXml = $this->_buildProcessResponseRequest($userId, $token);
        
        $pxPayUrl = $this->_configuration->getPxPayUrl($storeId);
        $responseXml = $this->_sendRequest($requestXml, $pxPayUrl);
        
        $this->_logger->info(__METHOD__ . " responseXml:" . $responseXml);
        return $responseXml;
    }

    public function refund($amount, $currency, $dpsTxnRef, $storeId)
    {
        $this->_logger->info(__METHOD__ . " amount:{$amount} currency:{$currency} dpsTxnRef:{$dpsTxnRef} storeId:{$storeId}");
        $requestXml = $this->_buildRefundRequestXml($amount, $currency, $dpsTxnRef, $storeId);
        $url = $this->_configuration->getPxPostUrl($storeId);
        
        return $this->_sendRequest($requestXml, $url);
    }

    public function complete($amount, $currency, $dpsTxnRef, $storeId)
    {
        $this->_logger->info(__METHOD__ . " amount:{$amount} currency:{$currency} dpsTxnRef:{$dpsTxnRef} storeId:{$storeId}");
        $requestXml = $this->_buildCompleteRequestXml($amount, $currency, $dpsTxnRef, $storeId);
        $url = $this->_configuration->getPxPostUrl($storeId);
        
        return $this->_sendRequest($requestXml, $url);
    }
    
    // Private function below
    private function _buildPxPay2Request($requestData, $storeId = null)
    {
        $this->_logger->info(__METHOD__);
        $userId = $this->_configuration->getPxPayUserId($storeId);
        $pxPayKey = $this->_configuration->getPxPayKey($storeId);
        
        $urlFail = $this->_getUrl('pxpay2/pxpay2/fail', ['_secure' => true]);
        $urlSuccess = $this->_getUrl('pxpay2/pxpay2/success', ['_secure' => true]);
        
        $amount = $requestData->getAmount();
        $currency = $requestData->getCurrency();
        $formattedAmount = $this->_paymentUtil->formatCurrency($amount, $currency);
        
        $requestObject = new \SimpleXMLElement("<GenerateRequest></GenerateRequest>");
        $requestObject->addChild("PxPayUserId", $userId);
        $requestObject->addChild("PxPayKey", $pxPayKey);
        $requestObject->addChild("TxnType", $requestData->getTransactionType());
        $requestObject->addChild("MerchantReference", $requestData->getOrderIncrementId()); // order incrementId
        $requestObject->addChild("TxnId", $requestData->getTxnId());
        $requestObject->addChild("AmountInput", $formattedAmount);
        $requestObject->addChild("CurrencyInput", $currency);
        $requestObject->addChild("UrlFail", $urlFail);
        $requestObject->addChild("UrlSuccess", $urlSuccess);
        $requestObject->addChild("ClientVersion", $this->_configuration->getModuleVersion());
        
        if ($requestData->getForceA2A()) {
            $requestObject->addChild("ForcePaymentMethod", "Account2Account");
        }

        // field max length: https://www.paymentexpress.com/Technical_Resources/Ecommerce_Hosted/PxPay_2_0
        $addNonEmptyValue = function ($name, $value, $maxLength) use (&$requestObject) {
            if (isset($value) && $value) {
                $requestObject->addChild($name, substr($value, 0, $maxLength));
            }
        };
        
        $dpsBillingId = $requestData->getDpsBillingId();
        if (!empty($dpsBillingId)) {
            $requestObject->addChild("DpsBillingId", $dpsBillingId);
        } else {
            $addNonEmptyValue("EnableAddBillCard", $requestData->getEnableAddBillCard(), 1);
        }
        
        // customer information:
        
        // <TxnData1>John Doe</TxnData1>
        // <TxnData2>0211111111</TxnData2>
        // <TxnData3>98 Anzac Ave, Auckland 1010</TxnData3>
        
        // This always if possible (consumer email)
        // <EmailAddress>samplepxpayuser@paymentexpress.com</EmailAddress>
        
        // This is for look up (should be order id )
        // <TxnId>ABC123</TxnId>
        
        // For risk management (consumer information):
        // PhoneNumber -- if possible.
        // AccountInfo -- should be like the specific user.
        
        $customerInfo = $requestData->getCustomerInfo();
        $addNonEmptyValue("TxnData1", $customerInfo->getName(), 255);
        $addNonEmptyValue("TxnData2", $customerInfo->getPhoneNumber(), 255);
        $addNonEmptyValue("TxnData3", $customerInfo->getAddress(), 255);
        
        $addNonEmptyValue("EmailAddress", $customerInfo->getEmail(), 255);
        $addNonEmptyValue("PhoneNumber", $customerInfo->getPhoneNumber(), 10);
        $addNonEmptyValue("AccountInfo", $customerInfo->getId(), 128);
        
        $requestXml = $requestObject->asXML();
        
        $this->_logger->info(__METHOD__ . " request: {$this->_obscureSensitiveFields($requestObject)}");
        return $requestXml;
    }

    private function _buildProcessResponseRequest($userId, $token)
    {
        $this->_logger->info(__METHOD__ . " pxPayUserId:{$userId} token:{$token}");
        $pxPayKey = "";
        if ($userId == $this->_configuration->getPxPayUserId()) {
            $pxPayKey = $this->_configuration->getPxPayKey();
        }
        
        $requestObject = new \SimpleXMLElement("<ProcessResponse></ProcessResponse>");
        $requestObject->addChild("PxPayUserId", $userId);
        $requestObject->addChild("PxPayKey", $pxPayKey);
        $requestObject->addChild("Response", $token);
        $requestObject->addChild("ClientVersion", $this->_configuration->getModuleVersion());
        
        $requestXml = $requestObject->asXML();
        
        $this->_logger->info(__METHOD__ . " request: {$this->_obscureSensitiveFields($requestObject)}");
        
        return $requestXml;
    }

    private function _sendRequest($requestXml, $postUrl)
    {
        $this->_logger->info(__METHOD__ . " postUrl: {$postUrl}");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        if (!$response) {
            $errorMessage = " Error:" . curl_error($ch) . " Error Code:" . curl_errno($ch);
            $this->_logger->critical(__METHOD__ . $errorMessage);
        } else {
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpcode && substr($httpcode, 0, 2) != "20") {
                $errorMessage = " HTTP CODE: {$httpcode} for URL: {$postUrl}";
                $this->_logger->critical(__METHOD__ . $errorMessage);
            }
        }
        curl_close($ch);
        
        $this->_logger->info(__METHOD__ . " response from PxPay2:" . $response);
        
        return $response;
    }

    private function _buildRefundRequestXml($amount, $currency, $dpsTxnRef, $storeId = null)
    {
        $this->_logger->info(__METHOD__);
        return $requestObject = $this->_buildPxPostRequestXml($amount, $currency, $dpsTxnRef, "Refund", $storeId);
    }

    private function _buildCompleteRequestXml($amount, $currency, $dpsTxnRef, $storeId = null)
    {
        $this->_logger->info(__METHOD__);
        return $this->_buildPxPostRequestXml($amount, $currency, $dpsTxnRef, "Complete", $storeId);
    }

    private function _buildPxPostRequestXml($amount, $currency, $dpsTxnRef, $dpsTxnType, $storeId)
    {
        // <Txn>
        // <PostUsername>Pxpay_HubertFu</PostUsername>
        // <PostPassword>TestPassword</PostPassword>
        // <Amount>1.23</Amount>
        // <InputCurrency>NZD</InputCurrency>
        // <TxnType>Complete</TxnType>
        // <DpsTxnRef>000000600000005b</DpsTxnRef>
        // </Txn>
        $this->_logger->info(__METHOD__);
        $username = $this->_configuration->getPxPostUsername($storeId);
        $password = $this->_configuration->getPxPassword($storeId);
        
        $formattedAmount = $this->_paymentUtil->formatCurrency($amount, $currency);
        $requestObject = new \SimpleXMLElement("<Txn></Txn>");
        $requestObject->addChild("PostUsername", $username);
        $requestObject->addChild("PostPassword", $password);
        $requestObject->addChild("InputCurrency", $currency);
        $requestObject->addChild("Amount", $formattedAmount);
        $requestObject->addChild("DpsTxnRef", $dpsTxnRef);
        $requestObject->addChild("TxnType", $dpsTxnType);
        $requestObject->addChild("ClientVersion", $this->_configuration->getModuleVersion());

        $requestXml = $requestObject->asXML();
        
        $this->_logger->info(__METHOD__ . " request: {$this->_obscureSensitiveFields($requestObject)}");
        
        return $requestXml;
    }

    private function _obscureSensitiveFields($requestObject)
    {
        foreach ($requestObject->children() as $child) {
            $name = $child->getName();
            if (in_array($name, $this->_sensitiveFields)) {
                $child[0] = "****";
            }
        }
        return $requestObject->asXML();
    }
}
