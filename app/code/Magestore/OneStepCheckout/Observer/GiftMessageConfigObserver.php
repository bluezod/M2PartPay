<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Observer;

use Magento\GiftMessage\Helper\Message;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class GiftMessageConfigObserver
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class GiftMessageConfigObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;

    /**
     * @var \Magestore\OneStepCheckout\Helper\Config
     */
    protected $_configHelper;

    /**
     * GiftMessageConfigObserver constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magestore\OneStepCheckout\Helper\Config $configHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magestore\OneStepCheckout\Helper\Config $configHelper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_resourceConfig = $resourceConfig;
        $this->_configHelper = $configHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $scopeId = 1;
        $isGiftMessage = $this->_configHelper->enableGiftMessage();

        $this->_resourceConfig->saveConfig(
            Message::XPATH_CONFIG_GIFT_MESSAGE_ALLOW_ORDER,
            $isGiftMessage,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );
        $this->_resourceConfig->saveConfig(
            Message::XPATH_CONFIG_GIFT_MESSAGE_ALLOW_ITEMS,
            $isGiftMessage,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );
    }
}
