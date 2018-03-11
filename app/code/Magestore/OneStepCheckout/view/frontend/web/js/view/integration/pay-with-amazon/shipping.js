/*global define*/
define(
    [
        'Amazon_Payment/js/view/shipping',
        'Magestore_OneStepCheckout/js/model/google-autocomplete-address'
    ],
    function (
        Component,
        GoogleAutocompleteAddress
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/integration/pay-with-amazon/shipping'
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