define([
        'jquery',
        'ko',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Customer/js/model/customer',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/address-converter',
        "kushkicheckout",
        'Magento_Ui/js/modal/prompt',
        'Magento_Ui/js/modal/alert',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/validation'
    ],
    function(
        $, 
        ko, 
        Component, 
        customer, 
        globalMessageList,
        additionalValidators, 
        fullScreenLoader,         
        $t, 
        customerData,
        quote,
        addressConverter,
        kushkicheckout, 
        prompt, 
        alert
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Kushki_Payment/payment/kushkipay',
                cardHolderName:'',
                kushkiToken: null,
            },
            placeOrderHandler: null,
            paymentConfig: window.checkoutConfig.payment,
            
            /**
             * @return {exports}
             */
            initialize: function () {   
                this._super(); 
                if(window.checkoutConfig.payment.kushki_pay.kuski_error_message)
                {                    
                    var error = {
                        message:window.checkoutConfig.payment.kushki_pay.kuski_error_message
                    };
                    globalMessageList.addErrorMessage(error);
                }
            },
            /** @inheritdoc */
            initObservable: function () {
                this._super()
                    .observe([ 'cardHolderName','kushkiToken' ]);
                return this;
            },
            getKushkiErrorMessage: function()
            {
                if(window.checkoutConfig.payment.kushki_pay.kuski_error_message)
                {
                    $('#kushki_pay').click();
                    return window.checkoutConfig.payment.kushki_pay.kuski_error_message;
                }
                return '';
            },

            initkushkicheckout: function () {

                $('#kushki_pay-form').attr('action',BASE_URL + 'kushki_confirm/payment/confirm');                 
                $('#kushki_form_key').val(window.checkoutConfig.payment.kushki_pay.form_key);
                var billingAddress = addressConverter.quoteAddressToFormAddressData(quote.billingAddress());                
                $('#kushki_billing_address').val(JSON.stringify(billingAddress));

                if (!customer.isLoggedIn()) {
                     $('#kushki_guest_email').val(quote.guestEmail);
                }
                console.log({
                    form: "kushki_pay-form",
                    merchant_id: window.checkoutConfig.payment.kushki_pay.merchant_id,
                    amount: quote.totals()['base_grand_total'],
                    currency: quote.totals()['base_currency_code'],
                    payment_methods:["credit-card"], // Payments Methods enabled
                    is_subscription: false, // Optional
                    inTestEnvironment: window.checkoutConfig.payment.kushki_pay.mode, 
                    regional:false // Optional
                });
                var kushki = new KushkiCheckout({
                    form: "kushki_pay-form",
                    merchant_id: window.checkoutConfig.payment.kushki_pay.merchant_id,
                    amount: quote.totals()['base_grand_total'],
                    currency: quote.totals()['base_currency_code'],
                    payment_methods:["credit-card"], // Payments Methods enabled
                    is_subscription: false, // Optional
                    inTestEnvironment: window.checkoutConfig.payment.kushki_pay.mode, 
                    regional:false // Optional
                });

            },
          
            /** @inheritdoc */
            context: function() {
                return this;
            },

            /**
             * @param {Function} handler
             */
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },

            /** @inheritdoc */
            getCode: function() {
                return 'kushki_pay';
            },
            
        });
    }
);