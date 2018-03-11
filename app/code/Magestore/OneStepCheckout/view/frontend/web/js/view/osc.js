/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magestore_OneStepCheckout/js/action/validate-shipping-info',
        'Magestore_OneStepCheckout/js/action/showLoader',
        'Magestore_OneStepCheckout/js/action/save-shipping-address',
        'Magestore_OneStepCheckout/js/action/set-shipping-information',
        'Magestore_OneStepCheckout/js/model/shipping-rate-service',
        'Magestore_OneStepCheckout/js/action/save-additional-information',
        'Magento_Ui/js/modal/alert',
        'Magestore_OneStepCheckout/js/view/gift-message'
    ],
    function (
        $,
        Component,
        ko,
        $t,
        quote,
        ValidateShippingInfo,
        Loader,
        SaveAddressBeforePlaceOrder,
        setShippingInformationAction,
        shippingRateService,
        saveAdditionalInformation,
        alertPopup,
        giftMessageView
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/onestepcheckout'
            },
            errorMessage: ko.observable(),
            paymentMethodAllowPopup: ['braintree_paypal'],
            isVirtual:quote.isVirtual,
            enableCheckout: ko.pureComputed(function(){
                return (Loader.loading())?false:true;
            }),
            placingOrder: ko.observable(false),
            initialize: function () {
                this._super();
            },
            prepareToPlaceOrder: function(){
                var self = this;
                if (!quote.paymentMethod()) {
                    alertPopup({
                        content: $t('Please choose a payment method!'),
                        autoOpen: true,
                        clickableOverlay: true,
                        focus: "",
                        actions: {
                            always: function(){

                            }
                        }
                    });
                }
                if(self.validateInformation() == true){
                    self.placingOrder(true);
                    Loader.all(true);
                    var deferred = saveAdditionalInformation();
                    var payment = quote.paymentMethod();
                    var paymentMethod = payment.method;
                    if (self.paymentMethodAllowPopup.indexOf(paymentMethod) > -1) {
                        self.placeOrder();
                    } else {
                        deferred.done(function () {
                            if ($('#allow_gift_messages').length > 0) {
                                var giftMessageDeferred;
                                if ($('#allow_gift_messages').attr('checked') == 'checked') {
                                    giftMessageDeferred = giftMessageView().submitOptions();
                                    giftMessageDeferred.done(function () {
                                        self.placeOrder();
                                    });
                                } else {
                                    giftMessageDeferred = giftMessageView().deleteOptions();
                                    giftMessageDeferred.done(function () {
                                        self.placeOrder();
                                    });
                                }
                            } else {
                                self.placeOrder();
                            }
                        });
                    }
                }else{
                    //false
                }
            },

            placeOrder: function () {
                var self = this;
                var payment = quote.paymentMethod();
                var paymentMethod = payment.method;
                var checkoutButton = $("#co-payment-form ._active button[type='submit']");
              //  SaveAddressBeforePlaceOrder();
                if(this.isVirtual()){
                    if(checkoutButton.length > 0){
                        if (paymentMethod == 'braintree_paypal') {
                            self.braintreePaypalCheckout();
                        } else {
                            checkoutButton.click();
                        }
                        self.placingOrder(false);
                        Loader.all(false);
                    }
                }else{
                    if (self.paymentMethodAllowPopup.indexOf(paymentMethod) > -1) {
                        setShippingInformationAction().always(
                            function () {
                                shippingRateService().stop(false);
                            }
                        );
                        if(checkoutButton.length > 0){
                            if (paymentMethod == 'braintree_paypal') {
                                self.braintreePaypalCheckout();
                            } else {
                                checkoutButton.click();
                            }

                            self.placingOrder(false);
                            Loader.all(false);
                        }
                    } else {
                        setShippingInformationAction().always(
                            function () {
                                shippingRateService().stop(false);
                                if(checkoutButton.length > 0){
                                    checkoutButton.click();
                                    self.placingOrder(false);
                                    Loader.all(false);
                                }
                            }
                        );
                    }
                }
            },

            validateInformation: function(){
                var shipping = (this.isVirtual())?true:ValidateShippingInfo();
                var billing = this.validateBillingInfo();
                return shipping && billing;
            },
            
            afterRender: function(){
                $('#checkout-loader').removeClass('show');
            },
            
            validateBillingInfo: function(){
                if($("#co-payment-form ._active button[type='submit']").length > 0){
                    if($("#co-payment-form ._active button[type='submit']").hasClass('disabled')){
                        if($("#co-payment-form ._active button.update-address-button").length > 0){
                            this.showErrorMessage($t('Please update your billing address'));
                        }
                        return false;
                    }else{
                        return true;
                    }
                }
                return false;
            },
            showErrorMessage: function(message){
                var self = this;
                var timeout = 5000;
                self.errorMessage($t(message));
                setTimeout(function(){
                    self.errorMessage('');
                },timeout);
            },

            braintreePaypalCheckout: function () {
                var checkoutButton = $("#co-payment-form ._active button[type='submit']");
                var element = checkoutButton.get(0);
                var viewModel = ko.dataFor(element);

                if ($('.payment-method-description').is(":visible")) {
                    viewModel.placeOrder();
                } else {
                    viewModel.payWithPayPal();
                }
            }
        });
    }
);
