<?php
/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
namespace Magestore\OneStepCheckout\Model;
/**
 * Class OneStepConfigProvider
 * @package Magestore\OneStepCheckout\Model
 */
class OneStepConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magestore\OneStepCheckout\Helper\Config
     */
    protected $_configHelper;
    /**
     * @var \Magestore\OneStepCheckout\Helper\Data
     */
    protected $_oscHelper;

    /**
     * OneStepConfigProvider constructor.
     * @param \Magestore\OneStepCheckout\Helper\Config $configHelper
     * @param \Magestore\OneStepCheckout\Helper\Data $oscHelper
     */
    public function __construct(
        \Magestore\OneStepCheckout\Helper\Config $configHelper,
        \Magestore\OneStepCheckout\Helper\Data $oscHelper
    ) {
        $this->_configHelper = $configHelper;
        $this->_oscHelper = $oscHelper;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        $output['suggest_address'] = (boolean) $this->_configHelper->getOneStepConfig('general/suggest_address');
        $output['google_api_key'] = $this->_configHelper->getOneStepConfig('general/google_api_key');
        $output['has_store_pickup'] = (boolean) $this->_configHelper->hasStorePickup();
        $output['geoip'] = $this->_configHelper->getGeoIpInformation();
        $output['checkout_description'] = $this->_configHelper->getOneStepConfig('general/checkout_description');
        $output['checkout_title'] = $this->_configHelper->getOneStepConfig('general/checkout_title');
        $output['show_login_link'] = (boolean) $this->_configHelper->getOneStepConfig('login_link/show_login_link');
        $output['is_login'] = (boolean) $this->_configHelper->isLogin();
        $output['login_link_title'] = $this->_configHelper->getOneStepConfig('login_link/login_link_title');
        $output['enable_giftwrap'] = (boolean) $this->_configHelper->getOneStepConfig('giftwrap/enable_giftwrap');
        $output['giftwrap_amount'] = $this->_oscHelper->getOrderGiftWrapAmount();
        $output['has_giftwrap'] = (boolean) $this->_oscHelper->hasGiftwrap();
        $output['giftwrap_type'] = $this->_configHelper->getOneStepConfig('giftwrap/giftwrap_type');
        $output['enable_items_image'] =(boolean) $this->_configHelper->getOneStepConfig('general/enable_items_image');
        $output['show_discount'] = (boolean) $this->_configHelper->getOneStepConfig('general/show_discount');
        $output['show_comment'] = (boolean) $this->_configHelper->getOneStepConfig('general/show_comment');
        $output['show_newsletter'] = (boolean) $this->_configHelper->canShowNewsletter();
        $output['newsletter_default_checked'] = (boolean) $this->_configHelper->getOneStepConfig('general/newsletter_default_checked');
        $output['delivery_time_date'] = (boolean) $this->_configHelper->getOneStepConfig('general/delivery_time_date');
        $output['delivery_hour'] = $this->_configHelper->getHourConfig();
        $output['is_enable_security_code'] = (boolean) $this->_configHelper->getOneStepConfig('general/is_enable_security_code');
        $output['show_shipping_address'] = (boolean) $this->_configHelper->getOneStepConfig('general/show_shipping_address');
        $output['default_shipping'] = $this->_configHelper->getDefaultShippingMethod();
        $output['default_payment'] = $this->_configHelper->getDefaultPaymentMethod();
        $output['hide_shipping_method'] = (boolean) $this->_configHelper->hideOneShippingMethod();
        $output['disable_day'] = $this->_configHelper->getOneStepConfig('general/disable_day');
        $output['has_rewardpoints'] = (boolean) $this->_configHelper->hasRewardPoints();
        $output['has_affiliateplus'] = (boolean) $this->_configHelper->hasAffiliate();
        $output['one_step_checkout_is_actived'] = (boolean) $this->_configHelper->getOneStepConfig('general/active');
        return $output;
    }
    
    
}
