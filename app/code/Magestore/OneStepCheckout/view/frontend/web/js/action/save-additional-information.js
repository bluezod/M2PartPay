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
        'ko',
        'jquery',
        'Magestore_OneStepCheckout/js/view/gift-message',
        'mage/storage'
    ],
    function (ko, $, giftMessageView, storage) {
        'use strict';
        return function () {
            var deferred = $.Deferred();
            var deliveryDate, oscComment, newsletter, securityCode = '';
            if ($('#delivery_date').length > 0) {
                deliveryDate = $('#delivery_date').val();
            } else {
                deliveryDate = '';
            }

            if ($('#onestepcheckout_comment').length > 0) {
                oscComment = $('#onestepcheckout_comment').val();
            } else {
                oscComment = '';
            }

            if ($('#security_code').length > 0) {
                securityCode = $('#security_code').val();
            } else {
                securityCode = '';
            }

            if ($('#newsletter_subscriber_checkbox').length > 0) {
                if ($('#newsletter_subscriber_checkbox').attr('checked') == 'checked') {
                    newsletter = 1;
                } else {
                    newsletter = 0;
                }
            }
            var deliveryTime = '';
            if ($('#delivery_time').length > 0) {
                if ($('#delivery_time').val()) {
                    deliveryTime = $('#delivery_time').val();
                } else {
                    deliveryTime = '';
                }
            }
            
            var params = {
                'osc_delivery_date': deliveryDate,
                'osc_comment': oscComment,
                'osc_newsletter': newsletter,
                'osc_security_code': securityCode,
                'osc_delivery_time': deliveryTime
            };

            if (deliveryDate || oscComment || newsletter || securityCode || deliveryTime) {
                storage.post(
                    'onestepcheckout/index/saveCustomCheckoutData',
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
                        deferred.resolve(result);
                    }
                );
            } else {
                deferred.resolve('');
            }


            return deferred;
        };
    }
);
