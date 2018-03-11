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
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'Magestore_OneStepCheckout/js/model/shipping-rate-service',
        'Magestore_OneStepCheckout/js/model/validate-shipping',
        'Magestore_OneStepCheckout/js/model/google-autocomplete-address'
    ],
    function (
        $,
        _,
        Component,
        ko,
        addressList,
        quote,
        createShippingAddress,
        selectShippingAddress,
        shippingRatesValidator,
        formPopUpState,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t,
        shippingRateService,
        ValidateShipping,
        GoogleAutocompleteAddress
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/shipping'
            },
            visible: ko.observable(!quote.isVirtual()),
            isFormPopUpVisible: formPopUpState.isVisible,
            isFormInline: addressList().length == 0,
            isNewAddressAdded: ko.observable(false),
            saveInAddressBook: 1,
            quoteIsVirtual: quote.isVirtual(),
            /**
             * @return {exports}
             */
            initialize: function () {
                var self = this,
                    hasNewAddress,
                    fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';
            
                self._super();
                if(ValidateShipping.validating() == false){
                    shippingRateService();
                    if(typeof shippingRatesValidator.initFields != 'undefined'){
                       shippingRatesValidator.initFields(fieldsetName); 
                    }

                    checkoutDataResolver.resolveShippingAddress();

                    hasNewAddress = addressList.some(function (address) {
                        return address.getType() == 'new-customer-address';
                    });

                    this.isNewAddressAdded(hasNewAddress);
                }
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();
                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }
                    checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                        checkoutData.setShippingAddressFromData(shippingAddressData);
                    });
                    self.source = checkoutProvider;
                });
                return this;
            },
            
            /**
             * Show address form popup
             */
            showFormAddress: function () {
                this.isFormPopUpVisible(true);
            },

            /**
             * Save new shipping address
             */
            saveNewAddress: function () {
                var addressData,
                    newShippingAddress;

                this.source.set('params.invalid', false);
                this.source.trigger('shippingAddress.data.validate');

                if (!this.source.get('params.invalid')) {
                    addressData = this.source.get('shippingAddress');
                    // if user clicked the checkbox, its value is true or false. Need to convert.
                    addressData.save_in_address_book = this.saveInAddressBook ? 1 : 0;

                    // New address must be selected as a shipping address
                    newShippingAddress = createShippingAddress(addressData);
                    selectShippingAddress(newShippingAddress);
                    checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                    checkoutData.setNewCustomerShippingAddress(addressData);
                    this.hideAddressForm();
                    this.isNewAddressAdded(true);
                }
            },
            
            /**
             * cancel new shipping address
             */
            hideAddressForm: function () {
                this.isFormPopUpVisible(false);
            },
            
            /**
             * sort the address field base on sortOrder
             * @param {UIclass} fields
             * @returns {Boolean}
             */
            sortFields: function(fields){
                if(fields.elems().length > 0){
                    var allFields = fields.elems();

                    var regionId, region;
                    $.each(allFields, function (index, value) {

                        if (value.inputName == 'region_id') {
                            regionId = value;
                        }
                        if (value.inputName == 'region') {
                            region = value;
                        }
                    });


                    if(regionId && region){
                        region.sortOrder = regionId.sortOrder;
                    }

                    fields.elems().sort(function(fieldOne, fieldTwo){
                        return parseFloat(fieldOne.sortOrder) > parseFloat(fieldTwo.sortOrder) ? 1 : -1
                    });
                }
                return true;
            },
            initElement: function(element) {
                if (element.index === 'shipping-address-fieldset') {
                    shippingRatesValidator.bindChangeHandlers(element.elems(), false);
                }
            },
            initGoogleAddress: function(){
                if(window.checkoutConfig.suggest_address == true && window.checkoutConfig.google_api_key ){
                    setTimeout(function(){
                        GoogleAutocompleteAddress.init('co-shipping-form','shipping');
                    },2000);
                }
            }
        });
    }
);
