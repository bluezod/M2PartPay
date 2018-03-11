/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/shipping-rate-processor/new-address'
    ],
    function (quote, rateRegistry, defaultProcessor) {
        'use strict';

        return function () {
            var address = quote.shippingAddress();
            rateRegistry.set(address.getCacheKey(),'');
            defaultProcessor.getRates(address);
        };
    }
);
