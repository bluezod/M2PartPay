/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/shipping-service'
], function ($, ko, Component, shippingService) {
    'use strict';
    var ShowLoader = {
        shipping:ko.observable(false),
        payment: ko.observable(false),
        review: ko.observable(false),
        all: ko.observable(false),
        initialize: function () {
            var self = this;
            self.shipping.subscribe(function(){
                if(self.shipping() == true){
                    $('#ajax-shipping').show();
                    $('#control_overlay_shipping').show();
                    $('body').addClass('oscHideLoader');
                }
                if(self.shipping() == false){
                    $('#ajax-shipping').hide();
                    $('#control_overlay_shipping').hide();
                }
            });
            self.payment.subscribe(function(){
                if(self.payment() == true){
                    $('#ajax-payment').show();
                    $('#control_overlay_payment').show();
                    $('body').addClass('oscHideLoader');
                }
                if(self.payment() == false){
                    $('#ajax-payment').hide();
                    $('#control_overlay_payment').hide();
                }
            });
            self.review.subscribe(function(){
                if(self.review() == true){
                    $('#ajax-review').show();
                    $('#control_overlay_review').show();
                    $('body').addClass('oscHideLoader');
                }
                if(self.review() == false){
                    $('#ajax-review').hide();
                    $('#control_overlay_review').hide();
                }
            });
            self.all.subscribe(function(){
                if(self.all() == true){
                    $('body').removeClass('oscHideLoader');
                }
            });

            self.loading = ko.pureComputed(function(){
                return (self.shipping() || self.payment() || self.review() || self.all())?true:false;
            });

            shippingService.isLoading.subscribe(function(){
                if(shippingService.isLoading() == true){
                    self.shipping(true);
                }
                if(shippingService.isLoading() == false){
                    self.shipping(false);
                }
            });

            return self;
        }
    };
    return ShowLoader.initialize();
});