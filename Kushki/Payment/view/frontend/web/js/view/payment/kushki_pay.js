define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'kushki_pay',
                component: 'Kushki_Payment/js/view/payment/method-renderer/kushki_pay'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
