/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/summary/abstract-total'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            /**
             * @return {*}
             */
            isDisplayed: function () {
                return this.isFullMode();
            }
        });
    }
);
