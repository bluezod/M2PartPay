/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

define(
    [
        'underscore',
        'jquery',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/select-shipping-address',
        'uiRegistry',
        'Magestore_OneStepCheckout/js/model/shipping-rate-service',
        'Magestore_OneStepCheckout/js/model/billing-address-state',
        'Magento_Checkout/js/action/select-billing-address'
    ],
    function (
        _,
        $,
        addressList,
        quote,
        addressConverter,
        customer,
        selectShippingAddress,
        registry,
        shippingRateService,
        BillingAddressState,
        selectBillingAddressAction
    ) {
        'use strict';
        return function () {
            if(addressList().length == 0){
                var shippingAddress = quote.shippingAddress();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var addressData = addressConverter.formAddressDataToQuoteAddress(
                        checkoutProvider.get('shippingAddress')
                    );
                    for (var field in addressData) {
                        if (addressData.hasOwnProperty(field) &&
                            shippingAddress.hasOwnProperty(field) &&
                            typeof addressData[field] != 'function' &&
                            _.isEqual(shippingAddress[field], addressData[field])
                        ) {
                            shippingAddress[field] = addressData[field];
                        } else if (typeof addressData[field] != 'function' &&
                            !_.isEqual(shippingAddress[field], addressData[field])) {
                            shippingAddress = addressData;
                            break;
                        }
                    }
                    if (customer.isLoggedIn()) {
                        shippingAddress.save_in_address_book = 1;
                    }
                    shippingRateService().stop(true);
                    selectShippingAddress(shippingAddress);
                    if (BillingAddressState.sameAsShipping() == true) {
                        selectBillingAddressAction(shippingAddress);
                    }
                });
            }
        };
    }
);
