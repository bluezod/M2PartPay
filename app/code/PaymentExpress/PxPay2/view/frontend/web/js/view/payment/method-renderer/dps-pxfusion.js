/*browser:true*/
/*global define*/
define([ 'jquery', 'ko', 'Magento_Checkout/js/view/payment/default',
        'PaymentExpress_PxPay2/js/action/set-payment',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'mage/translate',
        'mage/url'],

function($, ko, Component, setPaymentMethodAction, additionalValidators, customer, quote, cardNumberValidator, $t, urlBuilder){
    'use strict';

    var pxFusionConfig = window.checkoutConfig.payment.paymentexpress.pxfusion;

    pxFusionConfig.expiryMonths = [];
    pxFusionConfig.expiryYears = [];

    // convert 1 to 01
    function pad(source){
        if (source.length < 2)
            return "0" + source;
        return source;
    }

    var i, strTemp;
    for (i = 1; i <= 12; ++i) {
        strTemp = pad(i.toString());
        pxFusionConfig.expiryMonths.push(strTemp);
    }
    var today = new Date();
    var currentYear = today.getFullYear();
    var currentMonth = today.getMonth() + 1;
    for (i = 0; i < 10; ++i) {
        strTemp = pad((currentYear + i).toString());
        pxFusionConfig.expiryYears.push(strTemp);
    }

    var addBillCardEnabled = ko.observable(true);
    var rebillingTokenEnabled = ko.observable(false); // process with billing token
    var cardEnteringEnabled = ko.observable(true);

    var paymentOption = ko.observable("withoutRebillToken");

    var cardNumber = ko.observable();
    var cardHolderName = ko.observable();
    var expiryMonth = ko.observable();
    var expiryYear = ko.observable();
    var cvc = ko.observable();

    // http://stackoverflow.com/questions/19590607/set-checkbox-when-radio-button-changes-with-knockout
    function paymentOptionChanged() {
        var paymentOptionValue = paymentOption();
        addBillCardEnabled(paymentOptionValue == "withoutRebillToken");
        rebillingTokenEnabled(paymentOptionValue == "withRebillToken");
        cardEnteringEnabled(paymentOptionValue == "withoutRebillToken");

        if (paymentOptionValue == "withRebillToken") {
            cardNumber("");
            cardHolderName("");
            expiryMonth(pad(currentMonth.toString()));
            expiryYear(currentYear.toString());
        }
    }
    paymentOption.subscribe(paymentOptionChanged);


    return Component.extend({
        payemntExpressLogo : pxFusionConfig.payemntExpressLogo,

        creditCardType : ko.observable(),
        cardNumber : cardNumber,
        cardHolderName : cardHolderName,
        expiryMonth : expiryMonth,
        expiryYear : expiryYear,
        expiryMonths : pxFusionConfig.expiryMonths,
        expiryYears : pxFusionConfig.expiryYears,
        cvc : ko.observable(),

        showCardOptions: pxFusionConfig.showCardOptions,
        isRebillEnabled: pxFusionConfig.isRebillEnabled,
        containsSavedCards: pxFusionConfig.savedCards.length > 0,
        savedCards: pxFusionConfig.savedCards,
        cardEnteringEnabled: cardEnteringEnabled,

        paymentOption: paymentOption,
        addBillCardEnabled: addBillCardEnabled,
        enableAddBillCard: ko.observable(),

        rebillingTokenEnabled: rebillingTokenEnabled,
        billingId: ko.observable(),

        defaults : {
            template : 'PaymentExpress_PxPay2/payment/dps-pxfusion'
        },
        
        initObservable: function () {
            this._super()
                .observe([
                    'creditCardType',
                    'expiryYear',
                    'expiryMonth',
                    'cardNumber',
                    'cvc',
                    'cardHolderName'
                ]);
            return this;
        },
        
        initialize: function() {
            var self = this;
            this._super();
            
            self.expiryMonth(pad(currentMonth.toString()));
            self.expiryYear(currentYear.toString());
            
            console.log(self.expiryMonth());
            console.log(self.expiryYear());
            
            //Set credit card number to credit card data object
            this.cardNumber.subscribe(function(value) {
                var result;

                if (value == '' || value == null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.isValid) {
                    self.creditCardType(result.card.type);
                }
            });
        },
        
        getCvvImageUrl: function() {
            return "";
        },
        getCvvImageHtml: function() {
            return '<img src="' + this.getCvvImageUrl()
                + '" alt="' + $t('Card Verification Number Visual Reference')
                + '" title="' + $t('Card Verification Number Visual Reference')
                + '" />';
        },

        getPaymentData : function(){
            var additionalData = {
                sessionId : pxFusionConfig.sessionId,
                transactionId : pxFusionConfig.transactionId,
                enableAddBillCard: this.enableAddBillCard(),
                useSavedCard: this.rebillingTokenEnabled(),
                billingId: this.billingId(),
            };
            
            if (!customer.isLoggedIn()){
                additionalData["cartId"] = quote.getQuoteId();
                additionalData["guestEmail"] = quote.guestEmail;
            }
            
            return {
                'method': pxFusionConfig.method,
                'additional_data' : additionalData
            };
        },

        getCardData : function(){
            return {
                cardNumber : this.cardNumber(),
                cvc : this.cvc(),
                cardHolderName : this.cardHolderName(),
                expiryMonth : this.expiryMonth(),
                expiryYear : this.expiryYear() - 2000,
                sessionId : pxFusionConfig.sessionId
            };
        },

        continueToPxFusion : function(){
            console.log("continueToPxFusion");
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
                if (this.rebillingTokenEnabled() && this.billingId())
                    setPaymentMethodAction(this.messageContainer, "pxfusion", this.getPaymentData(), this.redirectToResult, {});
                else
                    setPaymentMethodAction(this.messageContainer, "pxfusion", this.getPaymentData(), this.postCreditCardData, this.getCardData());
            }
        },

        postCreditCardData : function(postData){
            // http://stackoverflow.com/questions/8003089/dynamically-create-and-submit-form
            // create a form to submit. is it any better way?
            //var form = $(document.createElement('form'));
            var form = $("<form></form>");
            form.attr("action", pxFusionConfig.postUrl);
            form.attr("method", "POST");

            var cardNumberInput = $("<input>").attr("type", "hidden").attr(
                    "name", "CardNumber").val(postData.cardNumber);
            form.append(cardNumberInput);

            var cvcInput = $("<input>").attr("type", "hidden").attr("name",
                    "Cvc2").val(postData.cvc);
            form.append(cvcInput);
            
            var cardHolderNameInput = $("<input>").attr("type", "hidden").attr(
                    "name", "CardHolderName").val(
                    postData.cardHolderName);
            form.append(cardHolderNameInput);

            var expiryMothInput = $("<input>").attr("type", "hidden").attr(
                    "name", "ExpiryMonth").val(postData.expiryMonth);
            form.append(expiryMothInput);
            
            var expiryYearInput = $("<input>").attr("type", "hidden").attr(
                    "name", "ExpiryYear").val(postData.expiryYear);
            form.append(expiryYearInput);

            var sessionIdInput = $("<input>").attr("type", "hidden").attr(
                    "name", "SessionId").val(postData.sessionId);
            form.append(sessionIdInput);
            
            // must add a submit button in the form, other not submit in Firefox.
            // http://stackoverflow.com/questions/31265218/cannot-submit-form-from-javascript-on-firefox
            var submitButton = $("<button>").attr("type", "submit").attr(
                    "name", "ClickMe").val("Click Me");
            form.append(submitButton);
            
            form.appendTo('body');

            form.submit();

            return;
        },

        redirectToResult: function(postData) {
        	var url = urlBuilder.build(postData.sessionId);
        	$.mage.redirect(url);
        }
    });
});
