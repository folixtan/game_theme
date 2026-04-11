/**
 * Folix One Step Checkout - Checkout Data Resolver Mixin
 * 
 * 目的：延迟支付方式选择，避免在初始化时立即请求后端
 */
define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
     'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/checkout-data',

], function (wrapper,quote,customer, checkoutData) {
    'use strict';

   
        /**
         * 重写resolvePaymentMethod方法
         */
        function resolvePaymentMethod() {
          
        }


    return function (checkoutDataResolver) {
      checkoutDataResolver.resolvePaymentMethod = wrapper.wrapSuper(
        checkoutDataResolver.resolvePaymentMethod,
         function () {
        
            if(!customer.isLoggedIn()){
                return;
            }
        
        // 对于虚拟商品，不自动选择支付方式
            if (quote.isVirtual()) {
                var selectedPaymentMethod = checkoutData.getSelectedPaymentMethod();
                
                if (!quote.paymentMethod() && selectedPaymentMethod) {
                    var self = this;
                    setTimeout(function () {
                        self._super();
                    }, 100);
                }
                return;
            }
            
            // 非虚拟商品保持原生逻辑
           //return  this._super();
      });
        return checkoutDataResolver;
    };
});
