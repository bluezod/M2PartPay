<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Model\System\Config\Source;
/**
 * Class GiftWrap
 * @package Magestore\OneStepCheckout\Model\System\Config\Source
 */
class GiftWrap implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            0 => __('Per Order'),
            1 => __('Per Item'),
        ];
    }
}