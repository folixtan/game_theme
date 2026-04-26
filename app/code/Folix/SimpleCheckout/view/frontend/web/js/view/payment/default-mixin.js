/**
 * Folix One Step Checkout - Payment Default Mixin
 * 
 * 目的：为selectPaymentMethod添加验证逻辑
 */
define([
    'Magento_Checkout/js/model/quote',
    
], function (quote) {
    'use strict';

    var mixin = {
        /**
         * 重写selectPaymentMethod方法，添加验证逻辑
         */
        selectPaymentMethod: function () {
            var paymentData = this.getData();
            
            // 验证1：检查billingAddress是否存在
            if (!quote.billingAddress()) {
                console.warn('OneStepCheckout: Billing address is not set.');
                
                // 对于虚拟商品，触发billing address解析
                if (quote.isVirtual()) {
                    require(['Magento_Checkout/js/model/checkout-data-resolver'], function (resolver) {
                        resolver.resolveBillingAddress();
                    });
                }
            }
            
            // 验证2：检查支付数据是否完整
            if (!paymentData || !paymentData.method) {
                console.warn('OneStepCheckout: Payment method data is incomplete.');
                return false;
            }
            
            // 验证通过，调用父类方法
            return this._super();
        }
    };

    return function (PaymentDefault) {
        return PaymentDefault.extend(mixin);
    };
});
