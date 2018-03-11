<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Block\Layout;

/**
 * Class Style
 * @package Magestore\OneStepCheckout\Block\Layout
 */
class Style extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magestore\OneStepCheckout\Helper\Config
     */
    protected $_configHelper;

    /**
     * Style constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magestore\OneStepCheckout\Helper\Config $configHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magestore\OneStepCheckout\Helper\Config $configHelper,
        array $data
    ){
        $this->_configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed|string
     */
    public function getStyleColor()
    {
        return $this->_configHelper->getStyleColor();
    }

    /**
     * @return mixed|string
     */
    public function getButtonColor()
    {
        return $this->_configHelper->getButtonColor();
    }

}