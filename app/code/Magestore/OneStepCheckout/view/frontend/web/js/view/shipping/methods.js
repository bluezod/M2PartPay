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
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/checkout-data',
        'Magento_Catalog/js/price-utils',
        'Magestore_OneStepCheckout/js/action/showLoader',
        'Magestore_OneStepCheckout/js/model/validate-shipping',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magestore_OneStepCheckout/js/action/save-default-payment'
    ],
    function (
        $,
        _,
        Component,
        ko,
        quote,
        shippingService,
        selectShippingMethodAction,
        setShippingInformationAction,
        checkoutData,
        priceUtils,
        showLoader,
        ValidateShipping,
        shippingRatesValidator,
        saveDefaultPayment
    ) {
        'use strict';


        return Component.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/shipping-method/list'
            },
            default_shipping_carrier: ko.observable(window.checkoutConfig.default_shipping),
            visible: ko.observable(!quote.isVirtual()),
            errorValidationMessage: ValidateShipping.errorValidationMessage,
            isLoading: shippingService.isLoading,
            loading: ko.observable(false),
            savedDefault: ko.observable(false),
            /**
             * @return {exports}
             */

            initialize: function () {
                var self = this;
                shippingRatesValidator.validateDelay = 500;
                self._super();
                if (window.checkoutConfig.selectedShippingMethod) {
                    selectShippingMethodAction(window.checkoutConfig.selectedShippingMethod);
                }
                self.hasShippingMethod = ko.pureComputed(function(){
                    var hasMethod = false;
                    if(quote.shippingMethod()){
                        var stillAvailable = self.isShippingOnList(quote.shippingMethod().carrier_code,quote.shippingMethod().method_code);
                        hasMethod = (stillAvailable)?true:false;
                    }
                    return hasMethod;
                }),
                quote.shippingMethod.subscribe(function () {
                    self.errorValidationMessage(false);
                });
                
                if(self.isLoading){
                    showLoader.shipping(true);
                }else{
                    showLoader.shipping(false);
                }
                shippingService.getShippingRates().subscribe(function(){
                    if(!self.loading() || self.loading() == false){
                        if(self.hasShippingMethod() == true){
                            self.selectShippingMethod(quote.shippingMethod());
                        }else{
                            var method = self.getDefaultMethod();
                            if(method !== false){
                                self.selectShippingMethod(method);
                            }
                        }
                    }
                });
                setTimeout(function(){
                    if(self.savedDefault() == false){
                        shippingService.getShippingRates().valueHasMutated();
                        self.savedDefault(true);
                    }
                },1000);
                return this;
            },

            /**
             * Shipping Method View
             */
            rates: shippingService.getShippingRates(),
            isSelected: ko.computed(function () {
                    return quote.shippingMethod() ?
                        quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code
                        : null;
                }
            ),

            /**
             * @param {Object} shippingMethod
             * @return {Boolean}
             */
            selectShippingMethod: function (shippingMethod) {
                selectShippingMethodAction(shippingMethod);
                checkoutData.setSelectedShippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code);
                this.setShippingInformation();
                return true;
            },

            /**
             * Set shipping information handler
             */
            setShippingInformation: function () {
                var self = this;
                self.loading(true);
                showLoader.payment(true);
                showLoader.review(true);
                setShippingInformationAction().done(
                    function () {
                        showLoader.payment(false);
                        showLoader.review(false);
                        self.loading(false);
                    }
                ).fail(
                    function () {
                        showLoader.payment(false);
                        showLoader.review(false);
                        self.loading(false);
                    }
                ).always(function(){
                    saveDefaultPayment();
                });
            },
            /**
             * @param {Object} shippingMethod
             * @return {Boolean}
             */
            getShippingList: function () {
                var list = [];
                var rates = this.rates();
                if(rates && rates.length > 0){
                    ko.utils.arrayForEach(rates, function(method) {
                        if(list.length > 0){
                            var notfound = true;
                            ko.utils.arrayForEach(list, function(carrier) {
                                if(carrier && carrier.code == method.carrier_code){
                                    carrier.methods.push(method);
                                    notfound = false;
                                }
                            });
                            if(notfound == true){
                                var carrier = {
                                    code:method.carrier_code,
                                    title:method.carrier_title,
                                    methods:[method]
                                }
                                list.push(carrier);
                            }
                        }else{
                            var carrier = {
                                code:method.carrier_code,
                                title:method.carrier_title,
                                methods:[method]
                            }
                            list.push(carrier);
                        }
                    });
                }
                return list;
            },
            isShippingOnList: function(carrier_code,method_code){
                var list = this.getShippingList();
                if(list.length > 0){
                    var carrier = ko.utils.arrayFirst(list, function(carrier) {
                        return (carrier.code == carrier_code);
                    });
                    if(carrier && carrier.methods.length > 0){
                        var method = ko.utils.arrayFirst(carrier.methods, function(method) {
                            return (method.method_code == method_code);
                        });
                        return (method)?true:false;
                    }else{
                        return false;
                    }
                }
                return false;
            },
            getDefaultMethod: function(){
                var self = this;
                var list = this.getShippingList();
                if(list.length > 0){
                    var carrier = ko.utils.arrayFirst(list, function(data) {
                        return (self.default_shipping_carrier())?(data.code == self.default_shipping_carrier()):true;
                    });
                    if(carrier && carrier.methods.length > 0){
                        var method = ko.utils.arrayFirst(carrier.methods, function() {
                            return true;
                        });
                        return (method)?method:false;
                    }else{
                        return false;
                    }
                }
                return false;
            },
            formatPrice: function(amount) {
                amount = parseFloat(amount);
                var priceFormat = window.checkoutConfig.priceFormat;
                return priceUtils.formatPrice(amount, priceFormat)
            }
        });
    }
);
