<?php

namespace PaymentExpress\PxPay2\Api;


interface PxFusionManagementInterface
{
    /**
     * Add a specified payment method to a specified shopping cart.
     *
     * @param string $cartId The cart ID.
     * @param \Magento\Quote\Api\Data\PaymentInterface $method The payment method.
     * @return string PxFusion transaction ID
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException The billing or shipping address
     * is not set, or the specified payment method is not available.
     */
    public function set($cartId, \Magento\Quote\Api\Data\PaymentInterface $method, \Magento\Quote\Api\Data\AddressInterface $billingAddress = null);

}
