/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*jshint browser:true*/
/*global define*/
define(
    [
        'Magento_Reward/js/view/payment/reward'
    ],
    function (Reward) {
        'use strict';
        return Reward.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/payment/reward'
            },
            initialize: function () {
                this._super();
            }
        });
    }
);
