/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*jshint browser:true jquery:true*/
/*global alert*/
/**
 * Customer balance summary block info
 */
define(
    [
        'mage/storage',
        'Magento_CustomerBalance/js/view/cart/summary/customer-balance',
        'Magento_Checkout/js/action/get-payment-information',
        'Magestore_OneStepCheckout/js/action/showLoader'
    ],
    function (storage, CustomerBalance, getPaymentInformation, showLoader) {
        'use strict';
        return CustomerBalance.extend({

            removeBalanceFromQuote: function () {
                var url = this.getRemoveUrl();
                var params = {};
                showLoader.payment(true);
                showLoader.review(true);
                storage.post(
                    url,
                    JSON.stringify(params),
                    false
                ).done(
                    function (result) {

                    }
                ).fail(
                    function (result) {

                    }
                ).always(
                    function (result) {
                        getPaymentInformation().done(function () {
                            showLoader.payment(false);
                            showLoader.review(false);
                        });
                    }
                );
            }
        });
    }
);
