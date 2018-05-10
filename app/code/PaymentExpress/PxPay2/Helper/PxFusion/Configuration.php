<?php
namespace PaymentExpress\PxPay2\Helper\PxFusion;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Module\ModuleListInterface;

class Configuration extends AbstractHelper
{
    const PXFUSION_PATH = "payment/paymentexpress_pxfusion/";
    const MODULE_NAME = "PaymentExpress_PxPay2";

    /**
     *
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $_moduleList;
    
    public function __construct(Context $context)
    {
        parent::__construct($context);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_moduleList = $objectManager->get("\Magento\Framework\Module\ModuleListInterface");
        $this->_logger = $objectManager->get("\PaymentExpress\PxPay2\Logger\DpsLogger");
    }

    public function getModuleVersion()
    {
        if ($this->_moduleList == null)
            return "M2-unknown";
        return "M2-" . $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }
    
    public function getEnabled($storeId = null)
    {
        return filter_var($this->_getStoreConfig("active", $storeId), FILTER_VALIDATE_BOOLEAN);
    }
    
    public function getUserName($storeId = null)
    {
        return $this->_getStoreConfig("username", $storeId);
    }
    
    public function getPassword($storeId = null)
    {
        return $this->_getStoreConfig("password", $storeId, true);
    }
    
    public function getPostUrl($storeId = null)
    {
        return $this->_getStoreConfig("postUrl", $storeId, false);
    }
    
    public function getWsdl($storeId = null)
    {
        return $this->_getStoreConfig("wsdl", $storeId);
    }
    
    public function getPaymentType($storeId = null)
    {
        return (string)$this->_getStoreConfig("paymenttype", $storeId);
    }
    
    public function getPxPostUsername($storeId = null)
    {
        return $this->_getStoreConfig("pxpostusername", $storeId);
    }
    
    public function getPxPostPassword($storeId = null)
    {
        return $this->_getStoreConfig("pxpostpassword", $storeId, true);
    }
    
    public function getPxPostUrl($storeId = null)
    {
        return $this->_getStoreConfig("pxposturl", $storeId);
    }

    public function getAllowRebill($storeId = null)
    {
        return filter_var($this->_getStoreConfig("allowRebill", $storeId), FILTER_VALIDATE_BOOLEAN);
    }
    
    private function _getStoreConfig($configName, $storeId = null, $isSensitiveData = false)
    {
        $this->_logger->info(__METHOD__. " storeId:{$storeId}");
    
        $value = $this->scopeConfig->getValue(self::PXFUSION_PATH . $configName, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    
        if (!$isSensitiveData) {
            $this->_logger->info(__METHOD__ . " storeId:{$storeId} configName:{$configName} value:{$value}");
        } else {
            $this->_logger->info(__METHOD__ . " storeId:{$storeId} configName:{$configName} value:*****");
        }
        return $value;
    }
}