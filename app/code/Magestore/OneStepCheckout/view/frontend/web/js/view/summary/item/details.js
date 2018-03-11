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
        'uiComponent',
        'mage/storage',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote',
        'Magestore_OneStepCheckout/js/action/reload-shipping-method',
        'Magento_Checkout/js/action/get-payment-information',
        'Magestore_OneStepCheckout/js/model/gift-wrap',
        'Magento_Ui/js/modal/confirm',
        'Magento_Ui/js/modal/alert',
        'mage/translate',
        'Magento_Catalog/js/price-utils'
    ],
    function (
        $,
        Component,
        storage,
        customerData,
        getTotalsAction,
        totals,
        quote,
        reloadShippingMethod,
        getPaymentInformation,
        giftWrapModel,
        confirm,
        alertPopup,
        Translate,
        priceUtils
    ) {
        "use strict";
        return Component.extend({


            params: '',

            defaults: {
                template: 'Magestore_OneStepCheckout/summary/item/details'
            },


            getValue: function(quoteItem) {
                return quoteItem.name;
            },

            addQty: function (data) {
                this.updateQty(data.item_id, 'update', data.qty + 1);
            },

            minusQty: function (data) {
                this.updateQty(data.item_id, 'update', data.qty - 1);
            },

            updateNewQty: function (data) {
                this.updateQty(data.item_id, 'update', data.qty);
            },
            
            deleteItem: function (data) {
                var self = this;
                confirm({
                    content: Translate('Do you want to remove the item from cart?'),
                    actions: {
                        confirm: function () {
                            self.updateQty(data.item_id, 'delete', '');
                        },
                        always: function (event) {
                            event.stopImmediatePropagation();
                        }
                    }
                });

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

            updateTotal: function(point) {
                var listReward = {
                    '0': 'rewardpoint-earning',
                    '1': 'rewardpoint-spending',
                    '2': 'rewardpoint-use_point'
                };
                totals.isLoading(true);
                $.ajax({
                    url: rewardpointConfig.urlUpdateTotal,
                    type: 'POST',
                    data: {'reward_sales_rule': 'rate', 'reward_sales_point': point},
                    complete: function (data) {
                        var arrDataReward = $.map($.parseJSON(data.responseText), function (value, index) {
                            return [value];
                        });
                        $.dataReward = arrDataReward;
                        var deferred = $.Deferred();
                        getPaymentInformation(deferred);
                        $.when(deferred).done(function () {
                            $.each(listReward, function (key, val) {
                                $('tr.' + val).show();
                                $('tr.' + val + ' td.amount span').text($.dataReward[key]);
                            })
                            totals.isLoading(false);
                        });
                    },
                });
            },

            updateQty: function (itemId, type, qty) {
                var params = {
                    itemId: itemId,
                    qty: qty,
                    updateType: type
                };
                var self = this;
                this.showOverlay();
                storage.post(
                    'onestepcheckout/quote/update',
                    JSON.stringify(params),
                    false
                ).done(
                    function (result) {
                        var miniCart = $('[data-block="minicart"]');
                        miniCart.trigger('contentLoading');
                        var sections = ['cart'];
                        customerData.invalidate(sections);
                        customerData.reload(sections, true);
                        miniCart.trigger('contentUpdated');
                    }
                ).fail(
                    function (result) {

                    }
                ).always(
                    function (result) {
                        if (result.error) {
                            alertPopup({
                                content: Translate(result.error),
                                autoOpen: true,
                                clickableOverlay: true,
                                focus: "",
                                actions: {
                                    always: function(){

                                    }
                                }
                            });
                        }

                        if(result.cartEmpty || result.is_virtual){
                            window.location.reload();
                        }else{
                            if (result.giftwrap_amount && !result.error) {
                                giftWrapModel.setGiftWrapAmount(result.giftwrap_amount);
                            }
                            if (result.rewardpointsEarning) {
                                $('tr.rewardpoint-earning td.amount span').text(result.rewardpointsEarning);
                            }
                            if (result.rewardpointsSpending) {
                                $('tr.rewardpoint-spending td.amount span').text(result.rewardpointsSpending);
                            }
                            if (result.rewardpointsUsePoint) {
                                $('tr.rewardpoint-use_point td.amount span').text(result.rewardpointsUsePoint);
                            }
                            if (result.affiliateDiscount) {
                                $('tr td.amount span').each(function () {
                                    if ($(this).data('th') == Translate('Affiliateplus Discount')) {
                                        if (result.affiliateDiscount) {
                                            $(this).text(priceUtils.formatPrice(result.affiliateDiscount, quote.getPriceFormat()));
                                            $(this).show();
                                        } else {
                                            $(this).hide();
                                        }

                                    }
                                })
                            }
                            if (result.getRulesJson && window.checkoutConfig.isCustomerLoggedIn) {
                                var rewardSliderRules = $.parseJSON(result.getRulesJson).rate;
                                var $range = $("#range_reward_point");
                                var rewardpointConfig = result;
                                rewardpointConfig.checkMaxpoint = parseInt(rewardpointConfig.checkMaxpoint);
                                if(rewardpointConfig.checkMaxpoint){
                                    self.updateTotal(rewardSliderRules.sliderOption.maxPoints);
                                    $('#reward_sales_point').val(rewardSliderRules.sliderOption.maxPoints);
                                }
                                var slider = $range.data("ionRangeSlider");
                                if(typeof slider != "undefined") {
                                    slider.update({
                                        grid: true,
                                        grid_num: ((rewardSliderRules.sliderOption.maxPoints < 4) ? rewardSliderRules.sliderOption.maxPoints : 4),
                                        min: rewardSliderRules.sliderOption.minPoints,
                                        max: rewardSliderRules.sliderOption.maxPoints,
                                        step: rewardSliderRules.sliderOption.pointStep,
                                        from: ((rewardpointConfig.checkMaxpoint) ? rewardSliderRules.sliderOption.maxPoints : rewardpointConfig.usePoint),
                                        onUpdate: function (data) {
                                            if (rewardSliderRules.sliderOption.maxPoints == data.from) {
                                                $('#reward_max_points_used').attr('checked', 'checked');
                                            } else {
                                                $('#reward_max_points_used').removeAttr('checked');
                                            }
                                            $("#reward_sales_point").val(data.from);
                                            self.updateTotal(data.from);
                                        }
                                    });
                                }
                            }
                            reloadShippingMethod();
                            self.showPaymentOverlay();
                            getPaymentInformation().done(function () {

                                self.hidePaymentOverlay();
                                self.hideOverlay();
                            });
                        }

                    }
                );
            }
        });
    }
);
