/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Checkout/js/view/form/element/email',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'
], function ($, Email, ko, customer, quote, checkoutData) {
    'use strict';

    var validatedEmail = checkoutData.getValidatedEmailValue();

    if (validatedEmail && !customer.isLoggedIn()) {
        quote.guestEmail = validatedEmail;
    }

    return Email.extend({
        defaults: {
            template: 'Magestore_OneStepCheckout/form/element/email',
            email: checkoutData.getInputFieldEmailValue(),
            emailFocused: false,
            isLoading: false,
            isPasswordVisible: false,
            listens: {
                email: 'emailHasChanged',
                emailFocused: 'validateEmail'
            }
        },
        checkDelay: 1000,
        initialize: function () {
            this._super();
            return this;
        },
        afterRenderEmail: function(){
            var self = this;
            if(self.email()){
                self.emailHasChanged();
            }
        },
        showLoginPopup: function(){
            $('#onestepcheckout-login-popup').show();
            $('#control_overlay').show();
            $('#onestepcheckout-return-login-link').click();
            $('#id_onestepcheckout_username').val(this.email());
            //$('#id_onestepcheckout_password').focus();
            this.scrollScreen();
        },
        forgotPassword: function(){
            $('#onestepcheckout-login-popup').show();
            $('#control_overlay').show();
            $('#onestepcheckout-forgot-password-link').click();
            $('#id_onestepcheckout_email').val(this.email());
            //$('#id_onestepcheckout_email').focus();
            this.scrollScreen();
        },
        scrollScreen: function(){
            $("html, body").animate({ scrollTop: 0 }, 500);
        },

        changeValue: function () {
            var self = this;
            if (self.email()) {
                $('#customer-email').addClass('email-has-data');
            } else {
                $('#customer-email').removeClass('email-has-data');
            }
        }
    });
});
