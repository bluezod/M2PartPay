/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        "underscore",
        'ko',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'mage/translate',
        'Magento_Checkout/js/view/payment',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/method-list',
        'Magestore_OneStepCheckout/js/action/save-default-payment'
    ],
    function (
        $,
        _,
        ko,
        paymentService,
        methodConverter,
        $t,
        Payment,
        quote,
        methodList,
        saveDefaultPayment
    ) {
        'use strict';

        /** Set payment methods to collection */
        paymentService.setPaymentMethods(methodConverter(window.checkoutConfig.paymentMethods));

        return Payment.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/payment',
                activeMethod: ''
            },
            initialize: function () {
                this.beforeInitPayment();
                this._super();
                this.navigate();
                methodList.subscribe(function () {
                    saveDefaultPayment();
                });
                return this;
            },
            beforeInitPayment: function(){
                /*
                 * 10/09/2016 - Daniel
                 * fix conflict js braintree 
                 */
                quote.shippingAddress.subscribe(function(){
                    if(quote.shippingAddress() && !quote.shippingAddress().street){
                        quote.shippingAddress().street = ['',''];
                    }
                });
                /* End */
            }
        });
    }
);
