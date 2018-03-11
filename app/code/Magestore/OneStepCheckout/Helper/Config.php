<?php
/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
namespace Magestore\OneStepCheckout\Helper;
use Magento\Customer\Model\AccountManagement;

/**
 * Class Config
 * @package Magestore\OneStepCheckout\Helper
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper {


    /**
     * @var DetectCountry
     */
    protected $_detectCountry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected $_regionCollection;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magento\Config\Model\Config\Source\Locale\Country
     */
    protected $_localCountry;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param DetectCountry $detectCountry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Config\Model\Config\Source\Locale\Country $localCountry
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magestore\OneStepCheckout\Helper\DetectCountry $detectCountry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Config\Model\Config\Source\Locale\Country $localCountry,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_detectCountry = $detectCountry;
        $this->_customerSession = $customerSession;
        $this->_regionCollection = $regionCollection;
        $this->_directoryHelper = $directoryHelper;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_moduleManager = $context->getModuleManager();
        $this->_localCountry = $localCountry;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * Section config onestep checkout
     */
    const SECTION_CONFIG_ONESTEPCHECKOUT = 'onestepcheckout';
    
    /**
     * 
     * @param string $relativePath
     * @return string
     */
    public function getOneStepConfig($relativePath) {
        //return $this->scopeConfig->getValue(self::SECTION_CONFIG_ONESTEPCHECKOUT . '/' . $relativePath);
        return $this->scopeConfig->getValue(self::SECTION_CONFIG_ONESTEPCHECKOUT . '/' . $relativePath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function isEnabledOneStep() {
        return $this->getOneStepConfig('general/active');
    }

    /**
     * @return bool
     */
    public function allowRedirectCheckoutAfterAddProduct()
    {
        return (boolean)$this->getOneStepConfig('general/redirect_to_checkout');
    }

    /**
     * 
     * @return string
     */
    public function getGeoIpInformation() {
        return $this->_detectCountry->detect();
    }

    /**
     *
     * @return mixed
     */
    public function getDefaultShippingMethod()
    {
        return $this->getOneStepConfig('general/default_shipping');
    }

    /**
     * @return mixed
     */
    public function getDefaultPaymentMethod()
    {
        return $this->getOneStepConfig('general/default_payment');
    }

    /**
     * Hide section Shipping Method if only one method is applicable
     *
     * @return bool
     */
    public function hideOneShippingMethod()
    {
        return (boolean)$this->getOneStepConfig('general/hide_shipping_method');
    }

    /**
     * @return mixed
     */
    public function isEnableGeoIp()
    {
        return $this->getOneStepConfig('geoip_management/geo_ip');
    }


    /**
     * 
     * @return string
     */
    public function getFullRequest()
    {
        $routeName = $this->_getRequest()->getRouteName();
        $controllerName = $this->_getRequest()->getControllerName();
        $actionName = $this->_getRequest()->getActionName();
        return $routeName.'_'.$controllerName.'_'.$actionName;
    }
    
    /**
     * @return mixed
     */
    public function getAddressFieldsConfig()
    {
        $configs = array();
        $configs['twoFields'] = array();
        $configs['oneFields'] = array('street.0','street.1','street.2','street.3');
        $configs['lastFields'] = array();
        $configs['position'] = array();
        for($position = 0; $position < 20; $position++){
            $prePos = $position - 1;
            $currentPos = $position;
            $nextPos = $position + 1;
            $prepath = 'field_position_management/row_'.$prePos;
            $path = 'field_position_management/row_'.$currentPos;
            $nextpath = 'field_position_management/row_'.$nextPos;
            $preField = $this->getOneStepConfig($prepath);
            $currentField = $this->getOneStepConfig($path);
            $nextField = $this->getOneStepConfig($nextpath);
            if($currentField != '0'){
                if($currentField == 'street'){
                    $configs['position']['street'] = $currentPos;
                    $configs['position']['street.0'] = $currentPos;
                    $configs['position']['street.1'] = $currentPos;
                    $configs['position']['street.2'] = $currentPos;
                    $configs['position']['street.3'] = $currentPos;
                }elseif($currentField == 'region_id'){
                    $configs['position']['region_id'] = $currentPos;
                    $configs['position']['region'] = $currentPos;
                }else{
                    $configs['position'][$currentField] = $currentPos;
                }
            }
            if($currentField != 'street' && $currentField != '0'){
                if( $currentPos%2 == 0){
                    if($currentField != '0' && $nextField == '0'){
                        $configs['oneFields'][] = $currentField;
                        if($currentField == 'region_id'){
                            $configs['oneFields'][] = 'region';
                        }
                    }else{
                        $configs['twoFields'][] = $currentField;
                        if($currentField == 'region_id'){
                            $configs['twoFields'][] = 'region';
                        }
                    }
                }else{
                    if($currentField != '0' && $preField == '0'){
                        $configs['oneFields'][] = $currentField;
                        if($currentField == 'region_id'){
                            $configs['oneFields'][] = 'region';
                        }
                    }else{
                        $configs['twoFields'][] = $currentField;
                        $configs['lastFields'][] = $currentField;
                        if($currentField == 'region_id'){
                            $configs['twoFields'][] = 'region';
                            $configs['lastFields'][] = 'region';
                        }
                    }
                }
            }
        }
        /* fix by ronald - fix show field by default config */
        $configs['twoFields'] = array_filter($configs['twoFields']);
        $configs['oneFields'] = array_filter($configs['oneFields']);
        $configs['lastFields'] = array_filter($configs['lastFields']);
        /* end fix */
        return $configs;
    }
    
    /**
     * @return mixed
     */
    public function getAddressFieldsJsonConfig()
    {
        return \Zend_Json::encode($this->getAddressFieldsConfig());
    }
    
    /**
     * 
     * @param string $fieldKey
     * @return boolean|string
     */
    public function getFieldSortOrder($fieldKey){
        $config = $this->getAddressFieldsConfig();
        if(isset($config['position']) && isset($config['position'][$fieldKey])){
            return $config['position'][$fieldKey];
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isLogin() {
        return $this->_customerSession->isLoggedIn();
    }


    /**
     * @return array
     */
    public function getDefaultAddressInformation() {
        $defaultInformation = array();
        $isAllowGeoIp = $this->getOneStepConfig('geoip_management/geo_ip');

        $geoInformation = $this->getGeoIpInformation();
        $regionId = 0;
        if (isset($geoInformation['region']) && $geoInformation['region']) {
            $regionModel = $this->_regionCollection->addRegionNameFilter($geoInformation['region'])
                ->getFirstItem();
            $regionId = $regionModel->getRegionId();
        }


        if (isset($geoInformation['country_id']) && $geoInformation['country_id']
            && $this->isAllowCountries($geoInformation['country_id']) && $isAllowGeoIp) {
            $defaultInformation['country_id'] = $geoInformation['country_id'];
        } elseif (isset($geoInformation['country_id'])) {
            $defaultInformation['country_id'] = $this->getOneStepConfig('general/country_id');
        } else {
            $defaultInformation['country_id'] = $this->_directoryHelper->getDefaultCountry();
        }


        if (isset($geoInformation['region']) && $regionId && $this->isAllowCountries($geoInformation['country_id'] && $isAllowGeoIp)) {
            $defaultInformation['region_id'] = $regionId;
        } elseif ($this->getOneStepConfig('general/region_id') && $this->getOneStepConfig('general/region_id')!='null') {
            $defaultInformation['region_id'] = $this->getOneStepConfig('general/region_id');
        } else {
            $defaultInformation['region_id'] = 0;
        }
        

        if (isset($geoInformation['postcode']) && $geoInformation['postcode']
            && $this->isAllowCountries($geoInformation['country_id']) && $isAllowGeoIp) {
            $defaultInformation['postcode'] = $geoInformation['postcode'];
        } else {
            $defaultInformation['postcode'] = $this->getOneStepConfig('general/postcode');
        }

        if (isset($geoInformation['city']) && $geoInformation['city']
            && $this->isAllowCountries($geoInformation['country_id']) && $isAllowGeoIp) {
            $defaultInformation['city'] = $geoInformation['city'];
        } else {
            $defaultInformation['city'] = $this->getOneStepConfig('general/city');
        }
        return $defaultInformation;
    }

    /**
     * @param $countryCode
     * @return bool
     */
    public function isAllowCountries($countryCode) {
        $allowCountries = explode(',', (string)$this->scopeConfig->getValue('general/country/allow',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        if (!empty($allowCountries)) {
            if (!in_array($countryCode, $allowCountries)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Get default country id
     *
     * @return mixed
     */
    public function getDefaultCountryId()
    {
        return $this->getOneStepConfig('general/country_id');
    }

    /**
     * Get default Postcode
     *
     * @return mixed
     */
    public function getDefaultPostcode()
    {
        return $this->getOneStepConfig('general/postcode');
    }

    /**
     * Get default Region Id
     *
     * @return mixed
     */
    public function getDefaultRegionId()
    {
        return $this->getOneStepConfig('general/region_id');
    }

    /**
     * Get default Region Id
     *
     * @return mixed
     */
    public function getDefaultCity()
    {
        return $this->getOneStepConfig('general/city');
    }

    /**
     * @return string
     */
    public function isEnableGiftWrap()
    {
        return $this->getOneStepConfig('giftwrap/enable_giftwrap');
    }

    /**
     * @return string
     */
    public function getGiftWrapType()
    {
        return $this->getOneStepConfig('giftwrap/giftwrap_type');
    }

    /**
     * @return string
     */
    public function getGiftWrapAmount()
    {
        return $this->getOneStepConfig('giftwrap/giftwrap_amount');
    }


    /**
     * @return mixed|string
     */
    public function getStyleColor()
    {
        $style = $this->getOneStepConfig('style_management/style');
        $colorStyle = $this->getOneStepConfig('style_management/custom_style');
        if ($style == 'custom') {
            return '#' . $colorStyle;
        } else {
            return $style;
        }
    }

    /**
     * @return mixed|string
     */
    public function getButtonColor()
    {
        $button = $this->getOneStepConfig('style_management/button');
        $buttonStyle = $this->getOneStepConfig('style_management/custom_button');
        if ($button == 'custom') {
            return '#' . $buttonStyle;
        } else {
            return $button;
        }
    }

    /**
     * @return bool
     */
    public function enableGiftMessage()
    {
        return $this->getOneStepConfig('giftmessage/enable_giftmessage');
    }

    /**
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return $this->getOneStepConfig('order_notification/template');
    }

    /**
     * @return mixed
     */
    public function isEnableSendEmailAdmin()
    {
        return $this->getOneStepConfig('order_notification/enable_notification');
    }

    /**
     * @return mixed
     */
    public function notifyToEmail()
    {
        return $this->getOneStepConfig('order_notification/notification_email');
    }

    /**
     * @return mixed
     */
    public function getHourConfig()
    {
        $hourList = array();
        for ($i=0; $i<=23; $i++) {
            $hourList[] = $i;
        }
        $disableHourList = $this->getOneStepConfig('general/disable_hour');
        $disableListArray = explode(',', $disableHourList);
        foreach ($disableListArray as $disableRange) {
            $splitRange = explode('-', $disableRange);
            $from = '';
            $to = '';
            if (isset($splitRange[0])) {
                $from = $splitRange[0];
            }

            if (isset($splitRange[1])) {
                $to = $splitRange[1];
            }

            if ($from!='' && $to!='') {
                for ($i=0; $i<=23; $i++) {
                    if ($i>= (int) $from && $i<=(int) $to) {
                        if(($key = array_search($i, $hourList)) !== false) {
                            unset($hourList[$key]);
                        }
                    }
                }
            }
        }
        
        return implode(',',$hourList);
    }

    /**
     * Get minimum password length
     *
     * @return string
     */
    public function getMinimumPasswordLength()
    {
        return $this->scopeConfig->getValue('customer/password/minimum_password_length');
    }

    /**
     * Get number of password required character classes
     *
     * @return string
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->scopeConfig->getValue('customer/password/required_character_classes_number');
    }

    /**
     * @return bool
     */
    public function canShowNewsletter()
    {
        $isShowNewsletter = $this->getOneStepConfig('general/show_newsletter');

        if ($isShowNewsletter && !$this->isSignUpNewsletter()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @return bool
     */
    public function isSignUpNewsletter()
    {
        $isLogin = $this->_customerSession->isLoggedIn();
        if ($isLogin) {
            $customer = $this->_customerSession->getCustomer();
            if (isset($customer))
                $customerNewsletter = $this->_subscriberFactory->create()->loadByEmail($customer->getEmail());
            if (isset($customerNewsletter) && $customerNewsletter->getId() != null &&
                $customerNewsletter->getData('subscriber_status') == 1
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasStorePickup()
    {
        return $this->_moduleManager->isEnabled('Magestore_Storepickup');
    }

    /**
     * @return bool
     */
    public function hasRewardPoints()
    {
        return $this->_moduleManager->isEnabled('Magestore_Rewardpoints');
    }

    /**
     * @return bool
     */
    public function hasAffiliate()
    {
        return $this->_moduleManager->isEnabled('Magestore_Affiliateplus');
    }

    /**
     * @return mixed
     */
    public function getMagentoVersion() {
        $productMetadata = $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }

    /**
     * @return bool
     */
    public function canShowPasswordMeterValidate() {
        if(version_compare($this->getMagentoVersion(), '2.1.0') >= 0) {
            return true;
        } else {
            return false;
        }
    }

    public function isEnableLoginWithAmazon() {
        return (boolean) ($this->_moduleManager->isEnabled('Amazon_Login')
            && $this->_moduleManager->isEnabled('Amazon_Core')
            && $this->_moduleManager->isEnabled('Amazon_Payment'));
    }
}