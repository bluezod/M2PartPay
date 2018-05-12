define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'mr_partpay',
                component: 'MR_PartPay/js/view/payment/method-renderer/partpay-payment'
            }
        );

        return Component.extend({});
    }
);
