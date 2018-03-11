<?php

/**
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace Magestore\OneStepCheckout\Model\Total\Quote;

    /**
     * Class GiftWrap
     *
     * @category Magestore
     * @package  Magestore_OneStepCheckout
     * @module   OneStepCheckout
     * @author   Magestore Developer
     */
    /**
     * Class GiftWrap
     * @package Magestore\OneStepCheckout\Model\Total\Quote
     */
/**
 * Class GiftWrap
 * @package Magestore\OneStepCheckout\Model\Total\Quote
 */
class GiftWrap extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
     * @var \Magestore\OneStepCheckout\Helper\Config
     */
    protected $_configHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;


    /**
     * GiftWrap constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magestore\OneStepCheckout\Helper\Config $configHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magestore\OneStepCheckout\Helper\Config $configHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    )
    {
        $this->setCode('gift_wrap');
        $this->priceCurrency = $priceCurrency;
        $this->_checkoutSession = $checkoutSession;
        $this->_configHelper = $configHelper;
    }


    /**
     * @param \Magento\Quote\Model\Quote                          $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total            $total
     *
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        parent::collect($quote, $shippingAssignment, $total);
        $active = $this->_configHelper->isEnableGiftWrap();
        if (!$active) {
            return $this;
        }

        $giftWrap = $this->_checkoutSession->getData('onestepcheckout_giftwrap');
        if (!$giftWrap) {
            return $this;
        }

        $address = $shippingAssignment->getShipping()->getAddress();
        if ($quote->isVirtual() && $address->getAddressType() == 'shipping') {
            return true;
        }
        if (!$quote->isVirtual() && $address->getAddressType() == 'billing') {
            return true;
        }

        $items = $quote->getAllVisibleItems();
        if (!count($items)) {
            return $this;
        }

        $giftWrapType = $this->_configHelper->getGiftWrapType();
        $giftWrapAmount = $this->_configHelper->getGiftWrapAmount();
        $baseWrapTotal = 0;
        if ($giftWrapType == 1) {
            foreach ($items as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
                $baseWrapTotal += $giftWrapAmount * ($item->getQty());
            }
        } else {
            $baseWrapTotal = $giftWrapAmount;
        }
        $wrapTotal = $this->priceCurrency->convert($baseWrapTotal);
        $this->_checkoutSession->setData('onestepcheckout_giftwrap_amount', $wrapTotal);
        $this->_checkoutSession->setData('onestepcheckout_base_giftwrap_amount', $baseWrapTotal);
        $quote->setOnestepcheckoutGiftwrapAmount($wrapTotal);
        $quote->setOnestepcheckoutBaseGiftwrapAmount($baseWrapTotal);
        $total->setOnestepcheckoutGiftwrapAmount($wrapTotal);
        $total->setOnestepcheckoutBaseGiftwrapAmount($baseWrapTotal);
        $total->setGrandTotal($total->getGrandTotal() + $total->getOnestepcheckoutGiftwrapAmount());
        $total->setBaseGrandTotal($total->getBaseGrandTotal() + $total->getOnestepcheckoutBaseGiftwrapAmount());

        return $this;
    }


    /**
     * @param \Magento\Quote\Model\Quote               $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array|null
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $total->getOnestepcheckoutGiftwrapAmount();
        if ($amount != 0) {
            $result = [
                'code'  => $this->getCode(),
                'title' => __('Gift Wrap'),
                'value' => $amount,
            ];
        }

        return $result;
    }
}
