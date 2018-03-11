<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Model\System\Config\Source;

/**
 * Class Shipping
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class Shipping implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Shipping\Model\CarrierFactory             $carrierFactory
     * @param \Magento\Framework\ObjectManagerInterface          $objectManager
     * @param array                                              $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        array $data = []
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_carrierFactory = $carrierFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => __('-- Please select --'),
                'value' => '',
            ],
        ];

        foreach ($this->_scopeConfig->getValue('carriers') as $code => $carrier) {
            if(isset($carrier['active'])) {
                $active = $carrier['active'];
                if ($active == 1 || $active == true) {
                    if (isset($carrier['title'])) {
                        $options[] = [
                            'label' => $carrier['title'],
                            'value' => $code,
                        ];
                    }
                }
            }
        }

        return $options;
    }
}
