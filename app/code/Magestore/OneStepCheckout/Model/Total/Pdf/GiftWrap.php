<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

namespace Magestore\OneStepCheckout\Model\Total\Pdf;

/**
 * Class GiftWrap
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class GiftWrap extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{

    /**
     * @return array
     */
    public function getTotalsForDisplay()
    {

        $amount = $this->getOrder()->formatPriceTxt($this->getOrder()->getOnestepcheckoutGiftwrapAmount());
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $totals = [[
            'label'     => __('Gift Wrap:'),
            'amount'    => $amount,
            'font_size' => $fontSize,]];

        return $totals;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->getOrder()->getOnestepcheckoutGiftwrapAmount();
    }

}
