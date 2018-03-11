<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Block\Order\Invoice;

/**
 * Class Totals
 * @package Magestore\OneStepCheckout\Block\Order\Invoice
 */
class Totals extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * Init totals
     *
     */
    public function initTotals()
    {
        $orderTotalsBlock = $this->getParentBlock();
        $order = $orderTotalsBlock->getInvoice();
        if ($order->getOnestepcheckoutGiftwrapAmount() > 0) {
            $orderTotalsBlock->addTotal(new \Magento\Framework\DataObject([
                'code'       => 'gift_wrap',
                'label'      => __('Gift Wrap'),
                'value'      => $order->getOnestepcheckoutGiftwrapAmount(),
                'base_value' => $order->getOnestepcheckoutBaseGiftwrapAmount(),
            ]), 'subtotal');
        }
    }

}