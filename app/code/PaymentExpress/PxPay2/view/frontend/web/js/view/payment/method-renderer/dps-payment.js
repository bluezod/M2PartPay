/*browser:true*/
/*global define*/
define(
    [
        'jquery',
         'ko',
         'Magento_Checkout/js/view/payment/default',
        'PaymentExpress_PxPay2/js/action/set-payment',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
    ],
    function ($, ko, Component, setPaymentMethodAction, additionalValidators, customer, quote) {
        var pxpayConfig = window.checkoutConfig.payment.paymentexpress;
        var paymentOption = ko.observable("withoutRebillToken");

        var addBillCardEnabled = ko.observable(true);
        var rebillingTokenEnabled = ko.observable(false); // process with billing token

        // http://stackoverflow.com/questions/19590607/set-checkbox-when-radio-button-changes-with-knockout
        function paymentOptionChanged() {
            var paymentOptionValue = paymentOption();
            addBillCardEnabled(paymentOptionValue == "withoutRebillToken");
            rebillingTokenEnabled(paymentOptionValue == "withRebillToken");
        }
        paymentOption.subscribe(paymentOptionChanged);
        
        var merchantLogos = pxpayConfig.merchantUICustomOptions.logos;
        var index = 0, logoItem;
        for (index = 0; index < merchantLogos.length; ++index) {
            logoItem = merchantLogos[index];
            if (!logoItem.Width) {
                logoItem.Width = 80;
            }
            if (!logoItem.Height) {
                logoItem.Height = 45;
            }
        }
        
        return Component.extend(
            {
                redirectToPxPay: function (parameters) {
                    window.location.replace(parameters.sessionId);
                },
                defaults: {
                    template: 'PaymentExpress_PxPay2/payment/dps-payment'
                },

                showCardOptions: pxpayConfig.showCardOptions,
                isRebillEnabled: pxpayConfig.isRebillEnabled,
                contiansSavedCards: pxpayConfig.savedCards.length > 0,
                savedCards: pxpayConfig.savedCards,

                paymentOption: paymentOption,

                addBillCardEnabled: addBillCardEnabled,
                enableAddBillCard: ko.observable(),

                rebillingTokenEnabled: rebillingTokenEnabled,
                billingId: ko.observable(),
            
                merchantLinkData: pxpayConfig.merchantUICustomOptions.linkData,
                merchantLogos: merchantLogos,
                merchantText : pxpayConfig.merchantUICustomOptions.text,
                payemntExpressLogo : pxpayConfig.payemntExpressLogo,

                getPaymentData: function () {
                    var additionalData = {
                        paymentexpress_enableAddBillCard: this.enableAddBillCard(),
                        paymentexpress_useSavedCard: this.rebillingTokenEnabled(),
                        paymentexpress_billingId: this.billingId(),
                    };
                    
                    if (!customer.isLoggedIn()){
                        additionalData["cartId"] = quote.getQuoteId();
                        additionalData["guestEmail"] = quote.guestEmail;
                    }
                    
                    var data = {
                        'method': pxpayConfig.method,
                        'additional_data' : additionalData
                    };

                    return data;
                },
                
                continueToPxPay: function() {
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
                        setPaymentMethodAction(this.messageContainer, "pxpay2", this.getPaymentData(), this.redirectToPxPay, {});
                    }
                },
            }
        );
    }
);
