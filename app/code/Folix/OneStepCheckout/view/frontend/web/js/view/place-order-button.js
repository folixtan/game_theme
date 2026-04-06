/**
 * Folix One Step Checkout - Place Order Button
 * 
 * 放在 sidebar 的 place-order region 中（在摘要底部）
 * 通过 uiRegistry 获取选中的支付方式组件，调用其 placeOrder 方法
 */
define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'uiRegistry',
    'mage/translate'
], function (
    ko,
    $,
    Component,
    quote,
    checkoutDataResolver,
    registry,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Folix_OneStepCheckout/place-order-button'
        },
        
        /** @inheritdoc */
        initialize: function () {
            this._super();
            
            // 解决账单地址（虚拟商品）
            checkoutDataResolver.resolveBillingAddress();
            
            // 监听支付方式变化
            var self = this;
            quote.paymentMethod.subscribe(function (method) {
                self.isPlaceOrderActionAllowed(method !== null);
            });
        },
        
        /**
         * Place Order 按钮是否可用
         */
        isPlaceOrderActionAllowed: ko.observable(false),
        
        /**
         * 获取当前选中的支付方式组件名称
         * @returns {String|null}
         */
        getSelectedPaymentComponentName: function () {
            var method = quote.paymentMethod();
            if (method) {
                // 支付方式组件路径: checkout.steps.billing-step.payment.payments-list.<method_code>
                return 'checkout.steps.billing-step.payment.payments-list.' + method.method;
            }
            return null;
        },
        
        /**
         * Place Order - 获取选中的支付方式组件，调用其 placeOrder 方法
         */
        placeOrder: function (data, event) {
            var self = this;
            var componentName = this.getSelectedPaymentComponentName();

            if (event) {
                event.preventDefault();
            }

            if (!componentName) {
                // 没有选中支付方式，使用 messageContainer
                this.showError($t('Please select a payment method.'));
                return false;
            }

            // 通过 registry 获取支付方式组件
            registry.get(componentName, function (paymentComponent) {
                if (paymentComponent && typeof paymentComponent.placeOrder === 'function') {
                    // 调用支付方式组件的 placeOrder 方法
                    // 这里传 null 给 data 参数，因为按钮事件不需要 data
                    paymentComponent.placeOrder(null, null);
                } else {
                    self.showError($t('Unable to process payment.'));
                }
            });

            return false;
        },
        
        /**
         * 显示错误消息
         * @param {String} message
         */
        showError: function (message) {
            // 简单提示
            alert(message);
        }
    });
});