/**
 * Folix ChargeTemplate - Configurable Product Mixin
 * 
 * 扩展原生 Magento Configurable Widget，添加充值模板动态加载功能
 * 
 * 实现方式（遵循 Adobe 官方文档）：
 * 1. 使用 mixins 配置（不是 map）
 * 2. 返回工厂函数，接收 targetWidget 参数
 * 3. 使用 $.widget('mage.configurable', targetWidget, mixin) 扩展
 * 4. 返回 $.mage.configurable（原 widget 引用）
 */
define([
    'jquery',
    'underscore',
    'mage/translate'
], function ($, _, $t) {
    'use strict';

    return function (targetWidget) {
        /**
         * 定义 mixin 对象
         * 只包含需要增强的方法和选项
         */
        var chargeTemplateMixin = {
            options: {
                // 充值模板容器选择器
                chargeTemplateContainerSelector: '#charge-template-container',
                // 充值模板消息容器
                chargeTemplateMessageSelector: '.charge-template-message',
                // 充值模板字段容器
                chargeTemplateFieldsSelector: '.charge-template-fields',
                // 子产品充值模板映射（从 PHP 传递）
                childProductTemplates: {}
            },

            /**
             * 初始化时检查是否有预选的子产品
             * @private
             */
            _create: function () {
                // 调用原生的 _create 方法
                this._super();

                // 从全局变量加载子产品模板数据（使用对象合并）
                if (window.folixChargeTemplateConfig && typeof window.folixChargeTemplateConfig === 'object') {
                    this.options.childProductTemplates = $.extend(
                        {},
                        this.options.childProductTemplates || {},
                        window.folixChargeTemplateConfig
                    );
                    console.log('Folix ChargeTemplate: Loaded child product templates', this.options.childProductTemplates);
                }

                // 初始化时检查是否有预选的子产品
                if (this.simpleProduct) {
                    this._loadChargeTemplateForSimpleProduct(this.simpleProduct);
                } else {
                    this._showTemplateSelectionMessage();
                }
            },

            /**
             * 重写 _configureElement 方法
             * 在用户选择配置选项后，动态加载充值模板
             * 
             * @param {jQuery} element - 触发变更的选择器元素
             */
            _configureElement: function (element) {
                // 调用原生的 _configureElement 方法
                this._super(element);

                // ==============================
                // Folix ChargeTemplate 增强逻辑
                // ==============================
                // 原生逻辑：如果所有选项都已选择，this.simpleProduct 会被设置
                // 此时动态加载对应子产品的充值模板
                if (this.simpleProduct) {
                    this._loadChargeTemplateForSimpleProduct(this.simpleProduct);
                } else {
                    // 用户还未选择完所有属性，显示提示信息
                    this._showTemplateSelectionMessage();
                }
            },

            /**
             * 为选中的子产品加载充值模板
             * 
             * @param {Number} simpleProductId - 子产品 ID
             * @private
             */
            _loadChargeTemplateForSimpleProduct: function (simpleProductId) {
                var templateData = this.options.childProductTemplates[simpleProductId];
                var $container = $(this.options.chargeTemplateContainerSelector);

                if (!templateData) {
                    // 该子产品没有充值模板，隐藏表单容器
                    $container.hide();
                    return;
                }

                // 隐藏提示信息
                $(this.options.chargeTemplateMessageSelector).hide();

                // 动态渲染充值表单
                this._renderChargeTemplate(templateData, $container);

                // 显示表单容器
                $container.show();

                // 重新初始化 Magento 表单验证
                this._initFormValidation($container);
            },

            /**
             * 显示"请先选择套餐"提示信息
             * @private
             */
            _showTemplateSelectionMessage: function () {
                var $container = $(this.options.chargeTemplateContainerSelector);
                var $message = $(this.options.chargeTemplateMessageSelector);
                var $fields = $(this.options.chargeTemplateFieldsSelector);

                // 显示提示消息
                $message.show();

                // 清空并隐藏字段区域
                $fields.empty().hide();
            },

            /**
             * 渲染充值模板表单
             * 
             * @param {Object} templateData - 充值模板数据
             * @param {jQuery} $container - 容器元素
             * @private
             */
            _renderChargeTemplate: function (templateData, $container) {
                var $fieldsContainer = $(this.options.chargeTemplateFieldsSelector);
                var html = this._buildTemplateHtml(templateData);

                // 清空旧内容并插入新内容
                $fieldsContainer.empty().html(html);
            },

            /**
             * 构建充值模板 HTML
             * 
             * @param {Object} templateData - 充值模板数据
             * @returns {String} HTML 字符串
             * @private
             */
            _buildTemplateHtml: function (templateData) {
                var self = this;
                var html = '';

                // 如果没有字段定义，返回空字符串
                if (!templateData.fields || !templateData.fields.length) {
                    return html;
                }

                // 遍历字段，生成表单
                $.each(templateData.fields, function (index, field) {
                    var fieldName = field.charge_field_name || '';
                    var fieldLabel = field.alias || field.charge_field_name || 'Field';
                    var isRequired = field.is_required || false;
                    var fieldType = field.field_type || 'text';

                    html += '<div class="field ' + fieldName + ' required">';
                    html += '<label class="label" for="' + fieldName + '">';
                    html += '<span>' + self._escapeHtml(fieldLabel) + '</span>';
                    html += '</label>';
                    html += '<div class="control">';

                    // 根据字段类型生成不同的输入控件
                    switch (fieldType) {
                        case 'textarea':
                            html += '<textarea name="charge_template[' + fieldName + ']" ';
                            html += 'id="' + fieldName + '" ';
                            html += 'class="input-text" ';
                            html += 'rows="3" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += '></textarea>';
                            break;

                        case 'select':
                            html += '<select name="charge_template[' + fieldName + ']" ';
                            html += 'id="' + fieldName + '" ';
                            html += 'class="select" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += '>';
                            html += '<option value="">' + self._escapeHtml($t('Please select')) + '</option>';

                            // 如果有选项定义
                            if (field.options && field.options.length) {
                                $.each(field.options, function (optIndex, option) {
                                    html += '<option value="' + self._escapeHtml(option.value) + '">';
                                    html += self._escapeHtml(option.label);
                                    html += '</option>';
                                });
                            }

                            html += '</select>';
                            break;

                        default:
                            // 默认文本输入框
                            html += '<input type="text" ';
                            html += 'name="charge_template[' + fieldName + ']" ';
                            html += 'id="' + fieldName + '" ';
                            html += 'class="input-text" ';
                            html += 'value="" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += '/>';
                            break;
                    }

                    html += '</div></div>';
                });

                return html;
            },

            /**
             * 初始化表单验证
             * 使用 Magento 原生的 validation 机制
             * 
             * @param {jQuery} $container - 容器元素
             * @private
             */
            _initFormValidation: function ($container) {
                // 触发 Magento 表单验证初始化
                // Magento 会自动扫描 data-validate 属性并绑定验证规则
                if (typeof $.validator !== 'undefined') {
                    var $form = $container.closest('form');
                    if ($form.length) {
                        $form.validation('validate');
                    }
                }
            },

            /**
             * HTML 转义（防止 XSS）
             * 
             * @param {String} str - 要转义的字符串
             * @returns {String} 转义后的字符串
             * @private
             */
            _escapeHtml: function (str) {
                if (typeof str !== 'string') {
                    return str;
                }

                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#x27;',
                    '/': '&#x2F;'
                };

                return str.replace(/[&<>"'/]/g, function (char) {
                    return map[char];
                });
            }
        };

        /**
         * 使用 $.widget 扩展原生 widget
         * 第一个参数必须与原生 widget 的别名相同：'mage.configurable'
         */
        $.widget('mage.configurable', targetWidget, chargeTemplateMixin);

        /**
         * 必须返回原生的 widget 引用
         * 这是 Adobe 官方文档的标准做法
         */
        return $.mage.configurable;
    };
});
