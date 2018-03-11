<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\OneStepCheckout\Model\Total\Order\Creditmemo;

/**
 * Class GiftWrap
 *
 * @category Magestore
 * @package  Magestore_OneStepCheckout
 * @module   OneStepCheckout
 * @author   Magestore Developer
 */
class GiftWrap extends \Magento\Sales\Model\Order\Total\AbstractTotal
{
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setOnestepcheckoutGiftwrapAmount(0);
        $giftWrapAmount = $creditmemo->getOrder()->getOnestepcheckoutGiftwrapAmount();
        $baseGiftWrapAmount = $creditmemo->getOrder()->getOnestepcheckoutBaseGiftwrapAmount();
        if ($giftWrapAmount) {
            $creditmemo->setOnestepcheckoutGiftwrapAmount($giftWrapAmount);
            $creditmemo->setOnestepcheckoutBaseGiftwrapAmount($baseGiftWrapAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $giftWrapAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseGiftWrapAmount);
        }

        return $this;
    }
}
