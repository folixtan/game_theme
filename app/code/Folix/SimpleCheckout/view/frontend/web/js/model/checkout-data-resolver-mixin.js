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
     'Magento_Checkout/js/action/select-billing-address',
     'Magento_Checkout/js/action/set-billing-address',
    'Magento_Checkout/js/action/create-billing-address',

], function (wrapper,quote,customer, checkoutData,selectBillingAddress,setBillingAddressAction,createBillingAddress) {
    'use strict';

    


    return function (checkoutDataResolver) {
      checkoutDataResolver.resolvePaymentMethod = wrapper.wrapSuper(
        checkoutDataResolver.resolvePaymentMethod,
         function () {
              var self = this;
            if(!customer.isLoggedIn()){
                  var defaultBillingAddress = window.checkoutConfig.defaultBillingAddress;

                    if(!checkoutData.getBillingAddressFromData()) {
                        checkoutData.setSelectedBillingAddress(window.checkoutConfig.defaultBillingAddress)
                    }
                    if(quote.isVirtual() && !quote.billingAddress()) {
                        selectBillingAddress(defaultBillingAddress);
                        setBillingAddressAction(defaultBillingAddress);
                 
                    }
                return;
            }
        
        // 对于虚拟商品，不自动选择支付方式
            if (quote.isVirtual()) {
                var selectedPaymentMethod = checkoutData.getSelectedPaymentMethod();
                
                if (!quote.paymentMethod() && selectedPaymentMethod) {
                
                    setTimeout(function () {
                        self._super();
                    }, 100);
                }
                return;
            }
            
            // 非虚拟商品保持原生逻辑
           return  this._super();
      });
      checkoutDataResolver.applyBillingAddress = wrapper.wrapSuper(
        checkoutDataResolver.applyBillingAddress,
         function () {
             var defaultBillingAddress = window.checkoutConfig.defaultBillingAddress;

            if(!checkoutData.getBillingAddressFromData()) {
                 checkoutData.setSelectedBillingAddress(window.checkoutConfig.defaultBillingAddress)
            }
            if(quote.isVirtual() && !quote.billingAddress()) {
                selectBillingAddress(defaultBillingAddress);
                setBillingAddressAction(defaultBillingAddress);
               return;
            }

          
        // 对于虚拟商品，不自动选择支付方式
            return  this._super();
         }
      )
        return checkoutDataResolver;
    };
});
