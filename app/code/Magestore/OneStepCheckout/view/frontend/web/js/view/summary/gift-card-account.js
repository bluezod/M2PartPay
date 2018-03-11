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
        'ko',
        'Magento_GiftCardAccount/js/view/summary/gift-card-account',
        'Magestore_OneStepCheckout/js/action/remove-gift-card-from-quote'
    ],
    function ($, ko, GiftCartAccount, removeAction) {
        'use strict';

        return GiftCartAccount.extend({
            /**
             * @param {String} giftCardCode
             * @param {Object} event
             */
            removeGiftCard: function (giftCardCode, event) {
                event.preventDefault();

                if (giftCardCode) {
                    removeAction(giftCardCode);
                }
            }
        });
    }
);
