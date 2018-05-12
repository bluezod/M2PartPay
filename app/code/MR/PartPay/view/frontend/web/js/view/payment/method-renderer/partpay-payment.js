/*browser:true*/
/*global define*/
define(
    [
        'jquery',
         'ko',
         'Magento_Checkout/js/view/payment/default',
        'MR_PartPay/js/action/set-payment',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
    ],
    function ($, ko, Component, setPaymentMethodAction, additionalValidators, customer, quote) {
        var partpayConfig = window.checkoutConfig.payment.paymentexpress;
        
        return Component.extend(
            {
                redirectToPartPay: function (parameters) {
                    window.location.replace(parameters.sessionId);
                },
                defaults: {
                    template: 'MR_PartPay/payment/partpay-payment'
                },

                getPaymentData: function () {
                    var additionalData = {};
                    
                    if (!customer.isLoggedIn()){
                        additionalData["cartId"] = quote.getQuoteId();
                        additionalData["guestEmail"] = quote.guestEmail;
                    }
                    
                    var data = {
                        'method': partpayConfig.method,
                        'additional_data' : additionalData
                    };

                    return data;
                },
                
                continueToPartPay: function() {
                    var isValid = jQuery('#co-payment-form').validate({
                                        errorClass: 'mage-error',
                                        errorElement: 'div',
                                        meta: 'validate',
                                        errorPlacement: function (error, element) {
                                            var errorPlacement = element;
                                            if (element.is(':checkbox') || element.is(':radio')) {
                                                errorPlacement = element.siblings('label').last();
                                            }
                                            errorPlacement.after(error);
                                        }
                                    }).form();
                    if (isValid && additionalValidators.validate()) {
                        setPaymentMethodAction(this.messageContainer, "pxpay2", this.getPaymentData(), this.redirectToPartPay, {});
                    }
                },
            }
        );
    }
);
