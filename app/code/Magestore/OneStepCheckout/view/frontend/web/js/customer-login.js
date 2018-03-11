/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

define([
    'jquery',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'mage/mage',
    'mage/validation',
    'jquery/ui'
], function ($, storage, localStorage) {
    $.widget("magestore.customerLogin", {
        options: {},

        /**
         * customerLogin creation
         * @protected
         */
        _create: function () {
            var self = this, options = this.options;

            $.extend(this, {
                $loginLinkControl: $('#onestepcheckout-login-link'),
                $forgotPasswordLink: $('#onestepcheckout-forgot-password-link'),
                $returnLoginLink: $('#onestepcheckout-return-login-link'),
                $registerLink: $('#onestepcheckout-register-link'),
                $returnLoginLinkFromRegister: $('#onestepcheckout-return-login-link-2'),

                $overlayControl: $('#control_overlay'),

                $loginButton: $('#onestepcheckout-login-button'),
                $registerButton: $('#onestepcheckout-register-button'),
                $sendPasswordButton: $('#onestepcheckout-forgot-button'),

                $loadingContol: $('#onestepcheckout-login-loading'),
                $forgotPasswordLoading: $('#onestepcheckout-forgot-loading'),
                $registerLoading: $('#onestepcheckout-register-loading'),

                $loginError: $('#onestepcheckout-login-error'),
                $forgotPasswordError: $('#onestepcheckout-forgot-error'),
                $registerError: $('#onestepcheckout-register-error'),

                $forgotPasswordSuccess: $('#onestepcheckout-forgot-success'),

                $usernameInput: $('#id_onestepcheckout_username'),
                $passwordInput: $('#id_onestepcheckout_password'),


                $forgotPasswordContent: $('#onestepcheckout-login-popup-contents-forgot'),
                $loginContent: $('#onestepcheckout-login-popup-contents-login'),
                $registerContent: $('#onestepcheckout-login-popup-contents-register'),
                $forgotPasswordTitle: $('.title-forgot'),

                $closeButton: $('.close'),

                $loginPopup: $('#onestepcheckout-login-popup'),
                $emailForgot: $('#id_onestepcheckout_email'),


                $firstNameReg: $('#id_onestepcheckout_firstname'),
                $lastNameReg: $('#id_onestepcheckout_lastname'),
                $userNameReg: $('#id_onestepcheckout_register_username'),
                $passwordReg: $('#id_onestepcheckout_register_password'),
                $confirmReg: $('#id_onestepcheckout_register_confirm_password'),

                $loginTable: $('#onestepcheckout-login-table'),
                $forgotPasswordTable: $('#onestepcheckout-forgot-table'),
                $registerTable: $('#onestepcheckout-register-table')


            });

            this.$loginLinkControl.click(function () {
                self.resetFormLogin();
                $(self.element).show();
                self.$overlayControl.show();
            });

            this.$overlayControl.click(function () {
                $(self.element).hide();
                $(this).hide();
                $('#onestepcheckout-toc-popup').hide();
            });

            self.validateLoginForm();
            self.validateRegisterForm();
            self.validateForgotForm();

            $(document).keypress(function (e) {
                if (e.which == 13) {
                    if (self.$loginContent.is(':visible')) {
                        $('#onestepcheckout-login-form').submit(function () {
                            return false;
                        });
                        self.validateLoginForm();
                    } else if (self.$forgotPasswordContent.is(':visible')) {
                        $('#onestepcheckout-register-form').submit(function () {
                            return false;
                        });
                        self.validateRegisterForm();
                    } else if (self.$registerContent.is(':visible')) {
                        $('#onestepcheckout-forgot-form').submit(function () {
                            return false;
                        });
                        self.validateForgotForm();
                    }
                }
            });
            this.$forgotPasswordLink.click(function () {
                self.$loginContent.hide();
                self.$forgotPasswordContent.show();
            });

            this.$returnLoginLink.click(function () {
                self.$forgotPasswordContent.hide();
                self.$loginContent.show();
            });

            this.$registerLink.click(function () {
                self.$loginContent.hide();
                self.$loginPopup.addClass('absolute-box');
                self.$registerContent.show();
            });

            this.$returnLoginLinkFromRegister.click(function () {
                self.$registerContent.hide();
                self.$loginPopup.removeClass('absolute-box');
                self.$loginPopup.addClass('fixed-box');
                self.$loginContent.show();
            });

            this.$closeButton.click(function () {
                self.$loginPopup.hide();
                self.$overlayControl.hide();
                $('#onestepcheckout-toc-popup').hide();
            });

            $('#onestepcheckout-toc-link').click(function (e) {
                self.$overlayControl.show();
                e.preventDefault();
                $('#onestepcheckout-toc-popup').show();
            })

        },
        validateLoginForm: function () {
            var self = this;
            $('#onestepcheckout-login-form').mage('validation', {
                submitHandler: function (form) {
                    self.ajaxLogin();
                }
            });
        },
        validateRegisterForm: function () {
            var self = this;
            $('#onestepcheckout-register-form').mage('validation', {
                submitHandler: function (form) {
                    self.ajaxRegister();
                }
            });
        },
        validateForgotForm: function () {
            var self = this;
            $('#onestepcheckout-forgot-form').mage('validation', {
                submitHandler: function (form) {
                    self.ajaxForgotPassword();
                }
            });
        },
        ajaxLogin: function () {
            var self = this, options = this.options;
            self.$loadingContol.show();
            self.$loginTable.hide();
            self.$loginError.hide();
            var params = {
                username: self.$usernameInput.val(),
                password: self.$passwordInput.val()
            };
            storage.post(
                'onestepcheckout/account/login',
                JSON.stringify(params),
                false
            ).done(
                function (result) {
                    var sections = ['cart','checkout-data','customer'];
                    localStorage.invalidate(sections);
                    localStorage.reload(sections, true);
                    var errors = result.errors;
                    if (errors == false) {
                        self.$loadingContol.show();
                        self.setShippingAddressFromData({});
                        window.location.reload();
                    } else {
                        self.$loadingContol.hide();
                        self.$loginTable.show();
                        self.$loginError.html(result.message);
                        self.$loginError.show();
                    }
                }
            ).fail(
                function (result) {

                }
            );
        },

        setShippingAddressFromData: function (data) {
            var cacheKey = 'checkout-data';
            var obj = localStorage.get(cacheKey)();
            obj.shippingAddressFromData = data;
            localStorage.set(cacheKey, obj);
        },

        ajaxRegister: function () {
            var self = this, options = this.options;
            if (self.$passwordReg.val() == self.$confirmReg.val()) {
                self.$registerLoading.show();
                self.$registerTable.hide();
                self.$registerError.hide();
                var params = {
                    firstname: self.$firstNameReg.val(),
                    lastname: self.$lastNameReg.val(),
                    email: self.$userNameReg.val(),
                    password: self.$passwordReg.val(),
                    password_confirmation: self.$confirmReg.val()
                };
                storage.post(
                    'onestepcheckout/account/register',
                    JSON.stringify(params),
                    false
                ).done(
                    function (result) {
                        var sections = ['cart','checkout-data','customer'];
                        localStorage.invalidate(sections);
                        localStorage.reload(sections, true);
                        self.$registerLoading.hide();
                        var success = result.success;
                        if (!result.error) {
                            self.setShippingAddressFromData({});
                            window.location.reload();
                        } else {
                            self.$registerTable.show();
                            self.$registerError.html(result.error);
                            self.$registerError.show();
                        }
                    }
                ).fail(
                    function (result) {

                    }
                );
            } else {
                alert("Please Re-Enter Confirmation Password !");
            }
        },
        ajaxForgotPassword: function () {
            var self = this, options = this.options;
            self.$forgotPasswordError.hide();
            self.$forgotPasswordLoading.show();
            self.$forgotPasswordTable.hide();
            var params = {
                email: self.$emailForgot.val()
            };
            storage.post(
                'onestepcheckout/account/forgotPassword',
                JSON.stringify(params),
                false
            ).done(
                function (result) {
                    self.$forgotPasswordLoading.hide();
                    var success = result.success;
                    if (success == 'true') {
                        self.$forgotPasswordSuccess.show();
                        self.$forgotPasswordTable.hide();
                        self.$forgotPasswordTitle.hide();
                    } else {
                        self.$forgotPasswordTable.show();
                        self.$forgotPasswordError.html(result.errorMessage);
                        self.$forgotPasswordError.show();
                    }
                }
            ).fail(
                function (result) {

                }
            );
        },
        resetFormLogin: function () {
            var self = this;
            self.$loginTable.show();
            self.$forgotPasswordTable.show();
            self.$registerTable.show();

            self.$loadingContol.hide();
            self.$forgotPasswordLoading.hide();
            self.$registerLoading.hide();

            self.$loginError.hide();
            self.$forgotPasswordError.hide();
            self.$registerError.hide();

            self.$loginContent.show();
            self.$forgotPasswordContent.hide();
            self.$registerContent.hide();
        }
    });

    return $.magestore.customerLogin;
});
