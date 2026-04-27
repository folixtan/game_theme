define([

    'underscore',
    'jquery',
    "Magento_Checkout/js/view/form/element/email",
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'mage/validation',
    'Magento_Checkout/js/checkout-data',
    'Folix_SimpleCheckout/js/action/check-email-availability'
], function (_, $,Component, quote, customer, validation,checkoutData, checkEmailAvailabilityAction) {

   
        return Component.extend({
            
             /**
         * Check email existing.
         */
        checkEmailAvailability: function () {
            this.validateRequest();
            this.isEmailCheckComplete = $.Deferred();
            // Clean up errors on email
            $(this.emailInputId).removeClass('mage-error').parent().find('.mage-error').remove();
            this.isLoading(true);
            this.checkRequest = checkEmailAvailabilityAction(this.isEmailCheckComplete, this.email());

            $.when(this.isEmailCheckComplete).done(function (res) {
                this.isPasswordVisible(false);
                console.log(res);
                checkoutData.setCheckedEmailValue('');
            }.bind(this)).fail(function (res) {
                this.isPasswordVisible(true);
                console.log(res,'fail');
                checkoutData.setCheckedEmailValue(this.email());
            }.bind(this)).always(function () {
                this.isLoading(false);
            }.bind(this));
        },
          
            
        });
    
});