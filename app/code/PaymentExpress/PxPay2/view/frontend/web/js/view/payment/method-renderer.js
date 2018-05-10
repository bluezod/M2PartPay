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
                type: 'paymentexpress_pxfusion',
                component: 'PaymentExpress_PxPay2/js/view/payment/method-renderer/dps-pxfusion'
            }
        );
        rendererList.push(
            {
                type: 'paymentexpress_pxpay2',
                component: 'PaymentExpress_PxPay2/js/view/payment/method-renderer/dps-payment'
            }
        );

        return Component.extend({});
    }
);
