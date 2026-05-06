/**
 * Folix ChargeTemplate - Customer Data Item Processor
 * 
 * 处理购物车商品数据中的充值模板字段，自动填充表单
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    return Component.extend({
        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            
            // 监听 cart section 的数据变化
            this.cart = customerData.get('cart');
            this.cart.subscribe(this.updateChargeTemplateFields.bind(this));
            
            // 页面加载时立即执行一次
            this.updateChargeTemplateFields(this.cart());
        },

        /**
         * 当 cart 数据更新时调用
         * 
         * @param {Object} cartData
         */
        updateChargeTemplateFields: function (cartData) {
            if (!cartData || !cartData.items || cartData.items.length === 0) {
                return;
            }

            // 遍历所有购物车商品
            cartData.items.forEach(function (item) {
                // 检查是否有充值模板字段
                if (item.charge_template_fields && Object.keys(item.charge_template_fields).length > 0) {
                    this.fillChargeTemplateForm(item.charge_template_fields);
                }
            }.bind(this));
        },

        /**
         * 填充充值模板表单字段
         * 
         * @param {Object} fields - 充值字段数据
         */
        fillChargeTemplateForm: function (fields) {
            console.log('Filling charge template fields:', fields);

            // 遍历所有字段
            Object.keys(fields).forEach(function (fieldName) {
                var fieldValue = fields[fieldName];
                
                if (!fieldValue) {
                    return;
                }

                // 查找对应的输入框或选择框
                var inputElement = document.querySelector('[name="charge_template[' + fieldName + ']"]');
                
                if (!inputElement) {
                    console.warn('Input element not found for field:', fieldName);
                    return;
                }

                // 根据元素类型设置值
                if (inputElement.tagName === 'SELECT') {
                    // 下拉选择框：设置 selected option
                    this.setSelectValue(inputElement, fieldValue);
                } else if (inputElement.tagName === 'INPUT') {
                    // 文本输入框：直接设置 value
                    inputElement.value = fieldValue;
                    
                    // 触发 change 事件，确保 KnockoutJS 能检测到变化
                    var event = new Event('change', { bubbles: true });
                    inputElement.dispatchEvent(event);
                }
            }.bind(this));
        },

        /**
         * 设置下拉选择框的值
         * 
         * @param {HTMLSelectElement} selectElement
         * @param {String} value
         */
        setSelectValue: function (selectElement, value) {
            // 遍历所有 option
            var options = selectElement.options;
            var found = false;

            for (var i = 0; i < options.length; i++) {
                if (options[i].value === value) {
                    options[i].selected = true;
                    found = true;
                    break;
                }
            }

            if (!found) {
                console.warn('Option not found for value:', value, 'in select:', selectElement.id);
                // 如果没有找到匹配的选项，保持默认值（通常是第一个空选项）
                selectElement.selectedIndex = 0;
            }

            // 触发 change 事件
            var event = new Event('change', { bubbles: true });
            selectElement.dispatchEvent(event);
        }
    });
});
