define([
        'jquery',
        'uiComponent',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/checkout-data-resolver'
], function ($, Component, globalMessageList, stepNavigator,quote,setShippingInformationAction,checkoutDataResolver) {
    'use strict';
    return Component.extend({

    	/**
         * @return {exports}
         */
        initialize: function () {   
            this._super(); 
            quote.shippingMethod.subscribe(function () {
	            if(window.checkoutConfig.payment.kushki_pay !== undefined && window.checkoutConfig.payment.kushki_pay.kuski_error_message !== undefined && window.checkoutConfig.payment.kushki_pay.kuski_error_message != '')
	            {
					var error = {
					    message:window.checkoutConfig.payment.kushki_pay.kuski_error_message
					};
	            	globalMessageList.addErrorMessage(error);
	            	quote.billingAddress(null);
	                checkoutDataResolver.resolveBillingAddress();
	                setShippingInformationAction().done(
	                    function () {
	                        stepNavigator.next();
	                    }
	                );
	            }
	        });
        }
    });
});