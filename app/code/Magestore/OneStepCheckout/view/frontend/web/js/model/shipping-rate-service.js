/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-rate-processor/new-address',
        'Magento_Checkout/js/model/shipping-rate-processor/customer-address'
    ],
    function($, Component, ko, quote, defaultProcessor, customerAddressProcessor) {
        'use strict';

        return Component.extend({
            processors: [],
            stop: ko.observable(false),
            initialize: function () {
                this._super();
                var self = this;
                self.processors.default =  defaultProcessor;
                self.processors['customer-address'] = customerAddressProcessor;

                quote.shippingAddress.subscribe(function () {
                    if(self.stop() == false){
                        var type = quote.shippingAddress().getType();
                        if (self.processors[type]) {
                            self.processors[type].getRates(quote.shippingAddress());
                        } else {
                            self.processors.default.getRates(quote.shippingAddress());
                        }
                    }
                });
            }
        });
    }
);
