/**
 * ChargeTemplate Catalog Add to Cart Mixin
 * 
 * 扩展 Magento 原生的 catalogAddToCart widget，在提交前收集充值模板数据
 * 
 * 使用方法：
 * Magento 的 mixin 机制会自动将这个模块混合到原始的 catalog-add-to-cart.js 中
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/alert'
], function ($, _, alert) {
    'use strict';

    return function (catalogAddToCart) {
        /**
         * 扩展 catalogAddToCart widget
         */
        $.widget('mage.catalogAddToCart', catalogAddToCart, {
            /**
             * 重写 submitForm 方法，在提交前处理充值模板数据
             * 
             * @param {jQuery} form
             */
            submitForm: function (form) {
                var self = this;
                
                // 查找充值模板字段
                var chargeTemplateContainer = $('.product-charge-template');
                
                if (chargeTemplateContainer.length > 0) {
                    // 验证充值字段
                    if (!this._validateChargeFields(chargeTemplateContainer)) {
                        return false; // 阻止表单提交
                    }
                    
                    // 将充值数据添加到表单
                    this._addChargeFieldsToForm(form, chargeTemplateContainer);
                }
                
                // 调用原始的 submitForm 方法
                this._super(form);
            },

            /**
             * 验证充值模板字段
             * 
             * @param {jQuery} container
             * @return {Boolean}
             * @private
             */
            _validateChargeFields: function (container) {
                var isValid = true;
                var errors = [];
                
                container.find('.charge-field-input').each(function () {
                    var $field = $(this);
                    var value = $field.val().trim();
                    var $label = $field.closest('.charge-field').find('.charge-field-label');
                    var fieldName = $label.text().replace('*', '').trim();
                    
                    // 检查必填字段
                    if ($field.attr('data-validate') && !value) {
                        isValid = false;
                        errors.push(fieldName);
                        $field.addClass('mage-error').removeClass('mage-valid');
                    } else {
                        $field.removeClass('mage-error').addClass('mage-valid');
                    }
                });
                
                if (!isValid) {
                    alert({
                        title: $.mage.__('Required Information'),
                        content: $.mage.__('Please fill in: %1').replace('%1', errors.join(', '))
                    });
                }
                
                return isValid;
            },

            /**
             * 将充值字段数据添加到表单（作为隐藏字段）
             * 
             * @param {jQuery} form
             * @param {jQuery} container
             * @private
             */
            _addChargeFieldsToForm: function (form, container) {
                var self = this;
                
                // 移除之前添加的隐藏字段（避免重复）
                form.find('input[name^="charge_template"]').remove();
                
                // 为每个充值字段创建隐藏输入
                container.find('.charge-field-input').each(function () {
                    var $field = $(this);
                    var name = $field.attr('name');
                    var value = $field.val();
                    
                    if (value) {
                        $('<input>')
                            .attr('type', 'hidden')
                            .attr('name', name)
                            .val(value)
                            .appendTo(form);
                    }
                });
            }
        });

        return $.mage.catalogAddToCart;
    };
});
