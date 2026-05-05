define([
    'jquery',
    'Magento_ReCaptchaFrontendUi/js/registry'
], function ($, registry) {
    'use strict';

    /**
     * 检查 recaptcha-popup-login 是否已启用并可用，同时提取所需数据
     * @returns {Object|null} 返回包含所有必要数据的对象，如果未启用则返回 null
     */
    function getReCaptchaData() {
        // 检查 1: Google reCAPTCHA API 是否加载
        if (!window.grecaptcha) {
            console.log('[ReCaptcha] Google reCAPTCHA API not loaded');
            return null;
        }
        
        // 获取 registry 数据
        var ids = registry.ids();
        var captchaList = registry.captchaList();
        var tokenFields = registry.tokenFields();
        
        // 检查 2: 查找 recaptcha-popup-login 是否存在
        var targetIndex = ids.indexOf('recaptcha-popup-login');
        
        if (targetIndex === -1) {
            console.log('[ReCaptcha] recaptcha-popup-login not found in registry (may be disabled for social login)');
            return null;
        }
        
        // 检查 3: 对应的 widget ID 是否有效
        var widgetId = captchaList[targetIndex];
        if (!widgetId) {
            console.warn('[ReCaptcha] Widget ID is null for recaptcha-popup-login');
            return null;
        }
        
        // 检查 4: token fields 是否可用
        if (!tokenFields || tokenFields.length === 0) {
            console.log('[ReCaptcha] No token fields available in registry');
            return null;
        }
        
        // ✅ 一次性提取所有需要的数据
        return {
            grecaptcha: window.grecaptcha,
            widgetId: widgetId,
            tokenFields: tokenFields
        };
    }

    return function (targetWidget) {
        // ✅ jQuery Widget Mixin 的正确写法
        return $.widget('mageplaza.socialpopup', targetWidget, {
            /**
             * 重写 reloadCaptcha 方法，在刷新验证码时获取 reCAPTCHA token
             */
            reloadCaptcha: function (type, delay) {
                // 先调用原始方法（刷新原生 Captcha）
                this._super(type, delay);
                
                // ✅ 一次性获取所有 reCAPTCHA 数据
                var recaptchaData = getReCaptchaData();
                
                if (!recaptchaData) {
                    console.log('[ReCaptcha] Not enabled or not initialized, skipping token injection');
                    return;
                }
                
                // 异步获取 reCAPTCHA token 并注入表单
                this._injectReCaptchaToken(recaptchaData);
            },

            /**
             * 注入 reCAPTCHA token 到表单
             * @param {Object} recaptchaData - 预提取的 reCAPTCHA 数据
             * @private
             */
            _injectReCaptchaToken: function (recaptchaData) {
                console.log('[ReCaptcha] Executing reCAPTCHA for widget:', recaptchaData.widgetId);
                
                // 执行验证
                try {
                    recaptchaData.grecaptcha.execute(recaptchaData.widgetId);
                    
                    // 监听 token 返回（通过轮询 registry.tokenFields）
                    this._waitForToken(recaptchaData);
                } catch (e) {
                    console.error('[ReCaptcha] Failed to execute:', e);
                }
            },

            /**
             * 等待 token 返回并注入到表单
             * @param {Object} recaptchaData - 预提取的 reCAPTCHA 数据
             * @private
             */
            _waitForToken: function (recaptchaData) {
                var self = this;
                var maxAttempts = 50; // 最多等待 5 秒（50 * 100ms）
                var attempts = 0;
                
                var checkToken = setInterval(function () {
                    attempts++;
                    
                    // ✅ 直接从预提取的数据中读取 token
                    var token = null;
                    for (var i = 0; i < recaptchaData.tokenFields.length; i++) {
                        if (recaptchaData.tokenFields[i] && recaptchaData.tokenFields[i].value) {
                            token = recaptchaData.tokenFields[i].value;
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
        });
    };
});
