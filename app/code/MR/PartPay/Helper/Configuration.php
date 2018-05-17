<?php

namespace MR\PartPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

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

    public function getPaymentType($storeId = null)
    {
        return 'Purchase';
    }

    public function getModuleVersion()
    {
        if ($this->_moduleList == null)
            return "M2-unknown";
        return "M2-" . $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getPartPayClientId($storeId = null)
    {
        return $this->_getPartPayStoreConfig("client_id", $storeId);
    }

    public function getPartPayClientSecret($storeId = null)
    {
        return $this->_getPartPayStoreConfig("client_secret", $storeId, true);
    }

    public function getPartPayApiEndpoint($storeId = null)
    {
        return $this->_getPartPayStoreConfig("api_endpoint", $storeId);
    }

    public function getPartPayAuthTokenEndpoint($storeId = null)
    {
        return $this->_getPartPayStoreConfig("auth_token_endpoint", $storeId);
    }

    public function getPartPayApiAudience($storeId = null)
    {
        return $this->_getPartPayStoreConfig("api_audience", $storeId);
    }

    public function getEnabled($storeId = null)
    {
        return filter_var($this->_getPartPayStoreConfig("active", $storeId), FILTER_VALIDATE_BOOLEAN);
    }

    public function getDebugFlag($storeId = null)
    {
        return filter_var($this->_getPartPayStoreConfig("debug_flag", $storeId), FILTER_VALIDATE_BOOLEAN);
    }

    public function getMerchantName($storeId = null)
    {
        return $this->_getPartPayStoreConfig("merchant_name", $storeId);
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
