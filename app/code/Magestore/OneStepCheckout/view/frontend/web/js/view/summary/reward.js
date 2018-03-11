/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
define(
    [
        'mage/storage',
        'Magento_Reward/js/view/cart/reward',
        'Magento_Checkout/js/action/get-payment-information',
        'Magestore_OneStepCheckout/js/action/showLoader'
    ],
    function (storage, Reward, getPaymentInformation, showLoader) {
        'use strict';

        return Reward.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/summary/reward'
            },
            initialize: function () {
                this._super();
            },
            removeRewardFromQuote: function () {
                var url = this.rewardPointsRemoveUrl;
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
