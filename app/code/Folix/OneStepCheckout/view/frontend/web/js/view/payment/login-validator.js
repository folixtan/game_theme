/**
 * Copyright © Folix All rights reserved.
 * 登录验证器注册组件 - 替代原生的 email-validator
 * 将 login-validator 注册到 additional-validators 中
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/additional-validators',
    '../model/login-validator'
], function (Component, additionalValidators, loginValidator) {
    'use strict';

    // 注册登录验证器到全局验证器数组
    additionalValidators.registerValidator(loginValidator);

    return Component.extend({});
});
