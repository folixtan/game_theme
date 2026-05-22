/**
 * Folix ChargeTemplate - catalogAddToCart Mixin
 *
 * 扩展 catalogAddToCart widget，支持 checkoutUrl 选项：
 * 添加购物车成功后 → 验证登录 → 跳转 checkout
 *
 * @see vendor/magento/module-catalog/view/frontend/web/js/catalog-add-to-cart.js
 * @see vendor/magento/module-checkout/view/frontend/web/js/proceed-to-checkout.js
 */
define([
    'jquery',
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data',
    'jquery-ui-modules/widget'
], function ($, authenticationPopup, customerData) {
    'use strict';

    return function (widget) {

        $.widget('mage.catalogAddToCart', widget, {

            /**
             * 新增选项
             */
            options: {
                checkoutUrl: ''
            },

            /**
             * 覆盖 ajaxSubmit：先提交加购，再执行一步结账
             *
             * @param {jQuery} form
             */
            ajaxSubmit: function (form) {
                var self = this,
                    checkoutUrl = self.options.checkoutUrl;

                // 第一步：提交数据加入购物车
                this._super(form);

                // 第二步：加购成功后执行登录验证 + 跳转
                if (checkoutUrl) {
                    $(document)
                        .one('ajax:addToCart.folixBuyNow', function (e, data) {
                            $(form).removeAttr('data-buynow').removeAttr('data-checkout-url');
                            if (data.response && !data.response.error) {
                                self._proceedToCheckout(checkoutUrl);
                            }
                        })
                        .one('ajax:addToCart:error.folixBuyNow', function () {
                            $(form).removeAttr('data-buynow').removeAttr('data-checkout-url');
                        });
                }
            },

            /**
             * 一步结账：验证登录 + 跳转 checkout
             * 逻辑与 Magento_Checkout/js/proceed-to-checkout 一致
             *
             * @param {String} checkoutUrl
             */
            _proceedToCheckout: function (checkoutUrl) {
                var cart = customerData.get('cart'),
                    customer = customerData.get('customer');

                if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
                    authenticationPopup.showModal();
                    return;
                }

                location.href = checkoutUrl;
            }
        });

        return $.mage.catalogAddToCart;
    };
});
