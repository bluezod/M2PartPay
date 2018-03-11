/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*global define*/
define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'mage/storage',
        'Magento_Catalog/js/price-utils',
        'Magestore_OneStepCheckout/js/view/summary/gift-wrap',
        'Magestore_OneStepCheckout/js/action/reload-shipping-method',
        'Magento_Checkout/js/action/get-payment-information',
        'Magestore_OneStepCheckout/js/model/gift-wrap'
    ],
    function($, Component, ko, storage, priceUtils, giftWrap, reloadShippingMethod, getPaymentInformation, giftWrapModel) {
        'use strict';
        return Component.extend({
            initialize: function () {
                this._super();
                var self = this;
                this.giftWrapAmountPrice = ko.computed(function () {
                    var priceFormat = window.checkoutConfig.priceFormat;
                    return priceUtils.formatPrice(self.giftWrapValue(), priceFormat)
                });
            },

            isGiftWrap: ko.observable(window.checkoutConfig.enable_giftwrap),

            giftWrapValue: ko.computed(function () {
                return giftWrapModel.getGiftWrapAmount();
            }),
       
            defaults: {
                template: 'Magestore_OneStepCheckout/gift-wrap'
            },

            formatPrice: function(amount) {
                amount = parseFloat(amount);
                var priceFormat = window.checkoutConfig.priceFormat;
                return priceUtils.formatPrice(amount, priceFormat)
            },

            setGiftWrapValue: function (amount) {
                this.giftWrapValue(amount);
            },

            showOverlay: function () {
                $('#ajax-loader3').show();
                $('#control_overlay_review').show();
            },

            hideOverlay: function () {
                $('#ajax-loader3').hide();
                $('#control_overlay_review').hide();
            },

            showPaymentOverlay: function () {
                $('#control_overlay_payment').show();
                $('#ajax-payment').show();
            },

            hidePaymentOverlay: function () {
                $('#control_overlay_payment').hide();
                $('#ajax-payment').hide();
            },

            addGiftWrap: function () {
                var params = {
                    isChecked: !this.isChecked()
                };
                var self = this;
                this.showOverlay();
                storage.post(
                    'onestepcheckout/giftwrap/process',
                    JSON.stringify(params),
                    false
                ).done(
                    function (result) {
                        window.checkoutConfig.giftwrap_amount = result;
                        reloadShippingMethod();

                        self.showPaymentOverlay();
                        getPaymentInformation().done(function () {
                            if (self.isChecked()) {
                                giftWrapModel.setGiftWrapAmount(result);
                                giftWrapModel.setIsWrap(true);
                            } else {
                                giftWrapModel.setIsWrap(false);
                            }

                            self.hidePaymentOverlay();
                            self.hideOverlay();
                        });
                    }
                ).fail(
                    function (result) {

                    }
                ).always(
                    function (result) {
                        self.hideOverlay();

                    }
                );
                return true;
            },

            isChecked: ko.observable(window.checkoutConfig.has_giftwrap)
            
        });
    }
);
