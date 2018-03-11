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
        'ko',
        'Magento_Checkout/js/view/summary/item/details/thumbnail'
    ],
    function (ko, Component) {
        return Component.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/summary/item/details/thumbnail'
            },
            isShowImage: ko.observable(window.checkoutConfig.enable_items_image)
        });
    }
);
