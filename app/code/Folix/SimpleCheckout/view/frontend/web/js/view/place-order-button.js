/**
 * Copyright © Folix. All rights reserved.
 * See LICENSE.txt for license details.
 */

/**
 * Place Order Button Component
 * 
 * 统一下单按钮,位于Sidebar底部
 * PC端sticky定位,移动端fixed定位
 * 
 * 核心改进(参考OneStepCheckout):
 * 1. 调用paymentComponent.placeOrder()而非原生action
 * 2. 集成登录验证和客户信息验证
 * 3. 动态按钮状态管理
 * 4. 保留SSL安全提示
 */
define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Customer/js/model/customer',
    'uiRegistry',
    'Magento_Checkout/js/action/set-billing-address',
    'mage/translate'
], function (
    ko,
    $,
    Component,
    quote,
    checkoutDataResolver,
    customer,
    registry,
    setBillingAddressAction,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Folix_SimpleCheckout/place-order-button'
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            
            // 解决账单地址(虚拟商品)
           // checkoutDataResolver.resolveBillingAddress();
            
            // 监听支付方式变化,动态更新按钮状态
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
         * Place Order - 集成完整验证流程
         * 
         * @param {Object} data
         * @param {Event} event
         * @returns {Boolean}
         */
        placeOrder: function (data, event) {
            var self = this;
            var componentName = this.getSelectedPaymentComponentName();

            if (event) {
                event.preventDefault();
            }

            

            // ========== 验证流程 ==========
            
            // 1. 验证是否选中支付方式
            if (!componentName) {
                this.showError($t('Please select a payment method.'));
                return false;
            }

            // 2. 验证登录状态(游客可结账,跳过此验证)
            // 如需强制登录,取消注释以下代码:
            // if (!this.validateLogin()) {
            //     return false;
            // }

            // 3. 验证客户信息(email)
            if (!this.validateCustomerInfo()) {
                return false;
            }

            // ========== 所有验证通过,执行下单 ==========
            registry.get(componentName, function (paymentComponent) {
                if (paymentComponent && typeof paymentComponent.placeOrder === 'function') {
                    // 调用支付方式组件的 placeOrder 方法
                    paymentComponent.isPlaceOrderActionAllowed(true);
                    paymentComponent.placeOrder(data, event);
                } else {
                    self.showError($t('Unable to process payment.'));
                }
            });

            return false;
        },

        /**
         * 验证登录状态
         * 
         * @returns {Boolean} true=已登录, false=未登录
         */
        validateLogin: function () {
            if (!customer.isLoggedIn()) {
                // 未登录,触发登录提示
                this.triggerLoginNotice();
                return false;
            }
            return true;
        },

        /**
         * 验证客户信息
         * 支持游客结账:未登录时使用Email输入框的值
         * 
         * @returns {Boolean}
         */
        validateCustomerInfo: function () {
            // 如果已登录,验证customer数据
            if (customer.isLoggedIn()) {
                var customerData = customer.customerData;
                
                if (!customerData) {
                    this.showError($t('Customer information is not available. Please refresh the page.'));
                    return false;
                }
                
                // 验证email(必须)
                var email = customerData.email;
                if (!email || !this.isValidEmail(email)) {
                    this.showError($t('Your account email is invalid. Please update your account information.'));
                    return false;
                }
                
                return true;
            }
            
            // 游客结账:验证Email输入框
            var guestEmail = $('#customer-email').val();
            if (!guestEmail || !this.isValidEmail(guestEmail)) {
                this.showError($t('Please enter a valid email address.'));
                return false;
            }
            
            return true;
        },

        /**
         * 验证邮箱格式
         * 
         * @param {String} email
         * @returns {Boolean}
         */
        isValidEmail: function (email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        /**
         * 触发登录提示
         * 尝试打开Mageplaza社交登录弹窗
         */
        triggerLoginNotice: function () {
            // 尝试触发Mageplaza登录弹窗
            var socialPopup = $('#social-login-popup');
            
            if (socialPopup.length > 0 && typeof socialPopup.socialpopup === 'function') {
                // Mageplaza弹窗存在,显示登录界面
                socialPopup.socialpopup('showLogin');
                socialPopup.socialpopup('loadApi');
            } else {
                // 降级方案: 显示提示并跳转
                alert($t('Please log in to continue.'));
                window.location.href = window.checkoutConfig?.urls?.loginUrl || '/customer/account/login';
            }
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
