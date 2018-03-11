/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

/**
 * Customer store credit(balance) application
 */
/*global define,alert*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/error-processor',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/action/get-payment-information',
        'Magento_Checkout/js/model/totals',
        'Magestore_OneStepCheckout/js/action/showLoader'
    ],
    function (
        $,
        ko,
        quote,
        urlBuilder,
        errorProcessor,
        storage,
        messageList,
        $t,
        getPaymentInformationAction,
        totals,
        showLoader
    ) {
        'use strict';
        return function () {
            var message = $t('Your store credit was successfully applied');
            messageList.clear();
            showLoader.payment(true);
            showLoader.review(true);
            return storage.post(
                urlBuilder.createUrl('/carts/mine/balance/apply', {})
            ).done(
                function (response) {
                    if (response) {
                        var deferred = $.Deferred();
                        totals.isLoading(true);
                        getPaymentInformationAction(deferred);
                        $.when(deferred).done(function () {
                            totals.isLoading(false);
                        });
                        messageList.addSuccessMessage({'message': message});
                    }
                }
            ).fail(
                function (response) {
                    totals.isLoading(false);
                    errorProcessor.process(response);
                }
            ).always(
                function() {
                    showLoader.payment(false);
                    showLoader.review(false);
                }
            );
        };
    }
);
