<?php
namespace MR\PartPay\Helper;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Module\ModuleListInterface;

class Configuration extends AbstractHelper
{
    const PARTPAY_PATH = "payment/mr_partpay/";
    const MODULE_NAME = "MR_PartPay";

    /**
     *
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $_moduleList;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_moduleList = $objectManager->get("Magento\Framework\Module\ModuleListInterface");
        $this->_logger = $objectManager->get("MR\PartPay\Logger\PartPayLogger");
    }

    public function getModuleVersion()
    {
        if ($this->_moduleList == null)
            return "M2-unknown";
        return "M2-" . $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getPartPayUserId($storeId = null)
    {
        return $this->_getPartPayStoreConfig("pxPayUserId", $storeId);
    }

    public function getPartPayKey($storeId = null)
    {
        return $this->_getPartPayStoreConfig("pxPayKey", $storeId, true);
    }

    public function getPartPayUrl($storeId = null)
    {
        return $this->_getPartPayStoreConfig("pxPayUrl", $storeId);
    }

    public function getEnabled($storeId = null)
    {
        return filter_var($this->_getPartPayStoreConfig("active", $storeId), FILTER_VALIDATE_BOOLEAN);
    }

    public function getAllowRebill($storeId = null)
    {
        return filter_var($this->_getPartPayStoreConfig("allowRebill", $storeId), FILTER_VALIDATE_BOOLEAN);
    }

    public function getPaymentType($storeId = null)
    {
        return (string)$this->_getPartPayStoreConfig("paymenttype", $storeId);
    }

    public function getForceA2A($storeId = null)
    {
        return filter_var($this->_getPartPayStoreConfig("forcea2a", $storeId), FILTER_VALIDATE_BOOLEAN);
    }

    public function getPxPostUsername($storeId = null)
    {
        return $this->_getPartPayStoreConfig("pxpostusername", $storeId);
    }

    public function getPxPassword($storeId = null)
    {
        return $this->_getPartPayStoreConfig("pxpostpassword", $storeId, true);
    }

    public function getPxPostUrl($storeId = null)
    {
        return $this->_getPartPayStoreConfig("pxposturl", $storeId);
    }

    public function getMerchantLinkData($storeId = null)
    {
        return [
            "Url" => $this->_getPartPayStoreConfig("merchantLinkUrl", $storeId),
            "Text" => $this->_getPartPayStoreConfig("merchantLinkText", $storeId)
        ];
    }

    public function getMerchantText($storeId = null)
    {
        return $this->_getPartPayStoreConfig("merchantText", $storeId);
    }

    public function getLogoSource($logoPrefix, $storeId = null)
    {
        return $this->_getPartPayStoreConfig($logoPrefix . "Source", $storeId);
    }

    public function getLogoAlt($logoPrefix, $storeId = null)
    {
        return $this->_getPartPayStoreConfig($logoPrefix . "Alt", $storeId);
    }

    public function getLogoHeight($logoPrefix, $storeId = null)
    {
        return (int)$this->_getPartPayStoreConfig($logoPrefix . "Height", $storeId);
    }

    public function getLogoWidth($logoPrefix, $storeId = null)
    {
        return (int)$this->_getPartPayStoreConfig($logoPrefix . "Width", $storeId);
    }

    private function _getPartPayStoreConfig($configName, $storeId = null, $isSensitiveData = false)
    {
        $this->_logger->info("Configuration::_getPartPayStoreConfig storeId argument:" . $storeId);
        
        $value = $this->scopeConfig->getValue(self::PARTPAY_PATH . $configName, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        
        if (!$isSensitiveData) {
            $this->_logger->info(__METHOD__ . " configName:{$configName} storeId:{$storeId} value:{$value}");
        } else {
            $this->_logger->info(__METHOD__ . " configName:{$configName} storeId:{$storeId} value:*****");
        }
        return $value;
    }
}
