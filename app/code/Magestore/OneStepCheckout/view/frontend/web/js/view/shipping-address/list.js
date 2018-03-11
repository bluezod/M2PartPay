/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*global define*/
define([
    'jquery',
    'underscore',
    'ko',
    'mageUtils',
    'uiLayout',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/view/shipping-address/list'
], function ($, _, ko, utils, layout, addressList, List) {
    'use strict';
    var defaultRendererTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Magestore_OneStepCheckout/js/view/shipping-address/address-renderer/default'
    };

    return List.extend({
        defaults: {
            template: 'Magestore_OneStepCheckout/shipping-address/list',
            visible: addressList().length > 0,
            rendererTemplates: []
        },

        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Create new component that will render given address in the address list
         *
         * @param address
         * @param index
         */
        createRendererComponent: function (address, index) {
            if (index in this.rendererComponents) {
                this.rendererComponents[index].address(address);
            } else {
                // rendererTemplates are provided via layout
                var rendererTemplate = (address.getType() != undefined && this.rendererTemplates[address.getType()] != undefined)
                    ? utils.extend({}, defaultRendererTemplate, this.rendererTemplates[address.getType()])
                    : defaultRendererTemplate;
                var templateData = {
                    parentName: this.name,
                    name: index
                };
                var rendererComponent = utils.template(rendererTemplate, templateData);
                utils.extend(rendererComponent, {address: ko.observable(address)});
                layout([rendererComponent]);
                this.rendererComponents[index] = rendererComponent;
            }
        },
        autoSelectAddress: function(){
            if($('.shipping-address-items .shipping-address-item').length > 0 && $('.shipping-address-items .shipping-address-item.selected-item').length == 0){
                $('.shipping-address-items .shipping-address-item')[0].click();
            }
        }
    });
});
