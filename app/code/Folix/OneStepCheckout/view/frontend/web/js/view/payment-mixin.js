/**
 * Folix One Step Checkout - Payment View Mixin
 * 
 * 目的：让支付方式始终显示，不受shipping步骤影响
 */
define([
    'Magento_Checkout/js/action/get-payment-information',
    'ko',
    
], function (getPaymentInformation, ko) {
    'use strict';

    var mixin = {
        /**
         * 覆盖isVisible的初始值
         */
        isVisible: ko.observable(true),
        
        /**
         * 重写initialize方法
         */
        initialize: function () {
            var self = this;
            
            // 调用父类initialize
            this._super();
            this.isVisible(false);
            getPaymentInformation().done(function () {
                self.isVisible(true);
            });
            return this;
        },
        
        /**
         * 重写navigate方法
         */
        navigate: function () {
            var self = this;
         
        },
        
        /**
         * 覆盖hasShippingMethod
         */
        hasShippingMethod: function () {
            return true;
        }
    };

    return function (PaymentComponent) {
        return PaymentComponent.extend(mixin);
    };
});
