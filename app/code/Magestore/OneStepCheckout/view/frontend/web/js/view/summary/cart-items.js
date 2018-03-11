/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/totals',
        'uiComponent',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/quote',
    ],
    function ($, ko, totals, Component, stepNavigator, quote) {
        'use strict';
        return Component.extend({
            initialize: function () {
                this._super();
                var self = this;
                totals.isLoading.subscribe(function () {
                    if (totals.isLoading() == true) {
                        self.showOverlay();
                    } else {
                        self.hideOverlay();
                    }
                });
            },
            defaults: {
                template: 'Magestore_OneStepCheckout/summary/cart-items'
            },
            totals: totals.totals(),
            getItems: totals.getItems(),
            getItemsQty: function() {
                return parseFloat(this.totals.items_qty);
            },

            showOverlay: function () {
                $('#ajax-loader3').show();
                $('#control_overlay_review').show();
            },

            hideOverlay: function () {
                $('#ajax-loader3').hide();
                $('#control_overlay_review').hide();
            },


            isItemsBlockExpanded: function () {
                return quote.isVirtual() || stepNavigator.isProcessed('shipping');
            }

        });
    }
);
