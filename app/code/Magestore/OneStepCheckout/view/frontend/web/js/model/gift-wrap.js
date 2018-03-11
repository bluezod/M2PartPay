/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
define(
    [
        'ko',
        'Magento_Catalog/js/price-utils'
    ],
    function (ko, priceUtils) {
        'use strict';
        var giftWrapAmount = ko.observable(window.checkoutConfig.giftwrap_amount);
        var hasWrap = ko.observable(window.checkoutConfig.has_giftwrap);
        return {
            giftWrapAmount: giftWrapAmount,
            hasWrap: hasWrap,

            getGiftWrapAmount: function() {
                return this.giftWrapAmount();
            },
            
            getIsWrap: function () {
                return this.hasWrap();
            },

            setGiftWrapAmount: function (amount) {
                this.giftWrapAmount(amount);
            },

            setIsWrap: function (isWrap) {
                return this.hasWrap(isWrap);
            }
        };
    }
);
