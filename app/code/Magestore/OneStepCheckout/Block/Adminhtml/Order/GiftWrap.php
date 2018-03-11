<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Block\Adminhtml\Order;

/**
 * Class GiftWrap
 * @package Magestore\OneStepCheckout\Block\Adminhtml\Order
 */
class GiftWrap extends \Magento\Sales\Block\Adminhtml\Totals
{
    /**
     * Init totals
     */
    public function initTotals()
    {
        parent::_initTotals();
        $orderTotalsBlock = $this->getParentBlock();
        $order = $orderTotalsBlock->getOrder();
        $giftWrapAmount = $order->getOnestepcheckoutGiftwrapAmount();
        $baseGiftWrapAmount = $order->getOnestepcheckoutBaseGiftwrapAmount();
        if ($giftWrapAmount > 0) {
            $orderTotalsBlock->addTotal(new \Magento\Framework\DataObject([
                'code'       => 'gift_wrap',
                'label'      => __('Gift Wrap'),
                'value'      => $giftWrapAmount,
                'base_value' => $baseGiftWrapAmount,
            ]), 'subtotal');
        }
    }
}