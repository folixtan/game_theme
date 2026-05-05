define([
    'jquery',
    'Magento_ReCaptchaFrontendUi/js/registry'
], function ($, registry) {
    'use strict';

    var popupMixin = {
        /**
         * 重写 reloadCaptcha 方法，在刷新验证码时获取 reCAPTCHA token
         */
        reloadCaptcha: function (type, delay) {
            // 先调用原始方法（刷新原生 Captcha）
            this._super(type, delay);
            
            // 异步获取 reCAPTCHA token 并注入表单
            this._injectReCaptchaToken();
        },

        /**
         * 注入 reCAPTCHA token 到表单
         * @private
         */
        _injectReCaptchaToken: function () {
            // 检查 reCAPTCHA API 是否加载
            if (!window.grecaptcha) {
                console.log('[ReCaptcha] Google reCAPTCHA API not loaded');
                return;
            }
            
            // 从 registry 获取 widget ID
            var widgetId = this._getWidgetId();
            
            if (!widgetId) {
                console.warn('[ReCaptcha] Widget ID not found in registry');
                return;
            }
            
            console.log('[ReCaptcha] Executing reCAPTCHA for widget:', widgetId);
            
            // 执行验证
            try {
                window.grecaptcha.execute(widgetId);
                
                // 监听 token 返回（通过轮询 registry.tokenFields）
                this._waitForToken(widgetId);
            } catch (e) {
                console.error('[ReCaptcha] Failed to execute:', e);
            }
        },

        /**
         * 从 registry 获取 widget ID
         * @returns {String|null}
         * @private
         */
        _getWidgetId: function () {
            var ids = registry.ids();
            var captchaList = registry.captchaList();
            
            // 优先查找 'recaptcha-popup-login'
            var targetIndex = ids.indexOf('recaptcha-popup-login');
            
            if (targetIndex !== -1 && captchaList[targetIndex]) {
                return captchaList[targetIndex];
            }
            
            // 如果没有找到指定的，使用第一个可用的
            if (captchaList.length > 0) {
                return captchaList[0];
            }
            
            return null;
        },

        /**
         * 等待 token 返回并注入到表单
         * @param {String} widgetId
         * @private
         */
        _waitForToken: function (widgetId) {
            var self = this;
            var maxAttempts = 50; // 最多等待 5 秒（50 * 100ms）
            var attempts = 0;
            
            var checkToken = setInterval(function () {
                attempts++;
                
                // 检查所有 token fields
                var tokenFields = registry.tokenFields();
                var token = null;
                
                for (var i = 0; i < tokenFields.length; i++) {
                    if (tokenFields[i] && tokenFields[i].value) {
                        token = tokenFields[i].value;
                        break;
                    }
                }
                
                if (token) {
                    clearInterval(checkToken);
                    console.log('[ReCaptcha] Token received:', token.substring(0, 20) + '...');
                    
                    // 注入 token 到当前激活的表单
                    self._injectTokenToForm(token);
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkToken);
                    console.warn('[ReCaptcha] Timeout waiting for token');
                }
            }, 100);
        },

        /**
         * 将 token 注入到表单
         * @param {String} token
         * @private
         */
        _injectTokenToForm: function (token) {
            var fieldName = 'g-recaptcha-response';
            
            // 根据当前显示的表单类型注入 token
            var $targetForm = null;
            
            if (this.loginFormContent && this.loginFormContent.is(':visible')) {
                $targetForm = this.loginForm;
            } else if (this.createFormContent && this.createFormContent.is(':visible')) {
                $targetForm = this.createForm;
            } else if (this.forgotFormContent && this.forgotFormContent.is(':visible')) {
                $targetForm = this.forgotForm;
            }
            
            if ($targetForm && $targetForm.length) {
                // 移除旧的 token 字段（如果存在）
                $targetForm.find('input[name="' + fieldName + '"]').remove();
                
                // 添加新的 token 字段
                $targetForm.append(
                    $('<input>').attr({
                        type: 'hidden',
                        name: fieldName,
                        value: token
                    })
                );
                
                console.log('[ReCaptcha] Token injected to form');
            }
        }
    };

    return function (targetWidget) {
        // ✅ 按照 Magento 标准：先扩展 widget
        $.widget('mageplaza.socialpopup', targetWidget, popupMixin);
        
        // ✅ 返回父级 alias
        return $.mageplaza.socialpopup;
    };
});
