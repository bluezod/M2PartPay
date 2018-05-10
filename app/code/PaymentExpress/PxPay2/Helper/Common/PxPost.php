<?php
namespace PaymentExpress\PxPay2\Helper\Common;

use \Magento\Framework\DataObject;

class PxPost
{
    private $_sensitiveFields = [
        "PxPayKey",
        "PostPassword"
    ];

    const MODULE_NAME = "PaymentExpress_PxPay2";

    /**
     *
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $_moduleList;
	
    public function __construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_moduleList = $objectManager->get("\Magento\Framework\Module\ModuleListInterface");
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
        $this->_logger->info(__METHOD__);
    }

    public function getModuleVersion()
    {
        if ($this->_moduleList == null)
            return "M2-unknown";
        return "M2-" . $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * @param \Magento\Framework\DataObject $requestObject
     */
    public function send($requestObject)
    {
        $this->_logger->info(__METHOD__);
        $requestXml = $this->_buildXml($requestObject);
        $url = $requestObject->getPostUrl();
        return $this->_sendRequest($requestXml, $url);
    }
    
    /**
     * @param \Magento\Framework\DataObject $requestObject
     */
    public function sendStatusRequest($requestObject)
    {
    	$this->_logger->info(__METHOD__);
    	$requestXml = $this->_buildStatusRequestXml($requestObject);
    	$url = $requestObject->getPostUrl();
    	return $this->_sendRequest($requestXml, $url);
	}
    
    private function _sendRequest($requestData, $postUrl, $timeout = 180)
    {
        $this->_logger->info(__METHOD__ . " postUrl: {$postUrl}");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
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
        
        $this->_logger->info(__METHOD__ . " response:" . $response);
        
        return $response;
    }

    /**
     * @param \Magento\Framework\DataObject $requestObject
     * @param string $fieldName
     * @param string $value
     */
    private function _addFieldIfSet($requestObject, $fieldName, $value)
    {
    	if (isset($value))
    		$requestObject->addChild($fieldName, $value);
    }
    
    private function _buildXml($requestData)
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
        
        $requestObject = new \SimpleXMLElement("<Txn></Txn>");
        $requestObject->addChild("PostUsername", $requestData->getUsername());
        $requestObject->addChild("PostPassword", $requestData->getPassword());
        $requestObject->addChild("InputCurrency", $requestData->getCurrency());
        $requestObject->addChild("Amount", $requestData->getAmount());
        $requestObject->addChild("TxnType", $requestData->getTxnType());
        
        $addNonEmptyValue = function ($name, $value, $maxLength) use (&$requestObject) {
        	if (isset($value) && $value) {
        		$requestObject->addChild($name, substr($value, 0, $maxLength));
        	}
        };
        
        $addNonEmptyValue("DpsTxnRef", $requestData->getDpsTxnRef(), 16);
        $addNonEmptyValue("DpsBillingId", $requestData->getDpsBillingId(), 16);
        $addNonEmptyValue("TxnId", $requestData->getTxnId(), 16);
        $addNonEmptyValue("MerchantReference", $requestData->getMerchantReference(), 64);
        $addNonEmptyValue("TxnData1", $requestData->getTxnData1(), 255);
        $addNonEmptyValue("TxnData2", $requestData->getTxnData2(), 255);
        $addNonEmptyValue("TxnData3", $requestData->getTxnData3(), 255);
        $addNonEmptyValue("AccountInfo", $requestData->getAccountInfo(), 128);
        $addNonEmptyValue("ClientVersion", $this->getModuleVersion(), 64);

        $requestXml = $requestObject->asXML();
        
        $this->_logger->info(__METHOD__ . " request: {$this->_obscureSensitiveFields($requestObject)}");
        
        return $requestXml;
    }
    
    private function _buildStatusRequestXml($requestData)
    {
    	// <Txn>
    	// <PostUsername>Pxpay_HubertFu</PostUsername>
    	// <PostPassword>TestPassword</PostPassword>
    	// <TxnType>Status</TxnType>
    	// <TxnId>000000600000005b</TxnId>
    	// </Txn>
    	$this->_logger->info(__METHOD__);
    
    	$requestObject = new \SimpleXMLElement("<Txn></Txn>");
    	$requestObject->addChild("PostUsername", $requestData->getUsername());
    	$requestObject->addChild("PostPassword", $requestData->getPassword());
    	$requestObject->addChild("TxnType", "Status");
    	$requestObject->addChild("TxnId", $requestData->getTxnId());
        $requestObject->addChild("ClientVersion", $this->getModuleVersion());
    
    	$requestXml = $requestObject->asXML();
    
    	$this->_logger->info(__METHOD__ . " request: {$requestXml}");
    
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