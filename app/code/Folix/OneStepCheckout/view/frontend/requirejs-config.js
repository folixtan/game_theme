/**
 * Copyright © Folix. All rights reserved.
 */
var config = {
    config: {
        mixins: {
            // Cart items mixin - 自定义购物车商品显示
            'Magento_Checkout/js/view/summary/cart-items': {
                'Folix_OneStepCheckout/js/view/summary/cart-items-mixin': true,
            },
            
            // Payment view mixin - 修复支付方式显示问题（核心修复）
            'Magento_Checkout/js/view/payment': {
                'Folix_OneStepCheckout/js/view/payment-mixin': true
            },
            
            // Checkout data resolver mixin - 延迟支付方式选择，避免过早请求后端
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Folix_OneStepCheckout/js/model/checkout-data-resolver-mixin': true
            },
            
            // Payment default mixin - 添加selectPaymentMethod验证逻辑
            'Magento_Checkout/js/view/payment/default': {
                'Folix_OneStepCheckout/js/view/payment/default-mixin': true
            }
        }
    }
};
