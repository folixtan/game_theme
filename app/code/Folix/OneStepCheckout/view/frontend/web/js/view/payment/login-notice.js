/**
 * Copyright © Folix All rights reserved.
 * 登录提示组件 - 与Mageplaza社交登录集成
 * 
 * 功能:
 * 1. 监听customer登录状态变化
 * 2. 未登录时显示友好的提示信息
 * 3. 点击登录按钮触发Mageplaza弹窗
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Customer/js/model/customer',
    'rjsResolver'
], function (Component, ko, $, customer, resolver) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Folix_OneStepCheckout/payment/login-notice'
        },

        initialize: function () {
            this._super();
            
            var self = this;
            
            // 等待DOM加载完成后绑定事件
            resolver(function () {
                self.bindLoginTrigger();
            });
            
            return this;
        },

        /**
         * 是否显示登录提示
         * @returns {Boolean}
         */
        shouldShowNotice: ko.computed(function () {
            return !customer.isLoggedIn();
        }),

        /**
         * 绑定登录触发器
         * 点击登录按钮时触发Mageplaza社交登录弹窗
         */
        bindLoginTrigger: function () {
            var self = this;
            
            // 使用事件委托,确保动态元素也能响应
            $(document).on('click', '.folix-login-trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                // 尝试触发Mageplaza登录弹窗
                var socialPopup = $('#social-login-popup');
                
                if (socialPopup.length > 0 && typeof socialPopup.socialpopup === 'function') {
                    // Mageplaza弹窗存在,显示登录界面
                    socialPopup.socialpopup('showLogin');
                    socialPopup.socialpopup('loadApi');
                } else {
                    // 降级方案: 跳转到传统登录页
                    console.warn('Mageplaza social login popup not found, redirecting to login page');
                    window.location.href = window.checkoutConfig?.urls?.loginUrl || '/customer/account/login';
                }
            });
        }
    });
});
