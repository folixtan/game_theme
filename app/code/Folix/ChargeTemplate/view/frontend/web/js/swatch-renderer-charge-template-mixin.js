/**
 * Folix ChargeTemplate - Swatch Renderer Mixin
 * 
 * 扩展 Magento Swatches Renderer，支持配置产品的充值模板动态加载
 * 
 * @see https://developer.adobe.com/commerce/frontend-core/javascript/mixins
 */
define([
    'jquery',
    'underscore',
    'mage/translate',
    'mage/template',
    'priceUtils',
    'mage/validation/validation',
      'jquery-ui-modules/widget'
], function ($, _, $t, mageTemplate, priceUtils) {
    'use strict';

    return function (targetWidget) {
        /**
         * 定义 mixin 对象
         */
        var chargeTemplateMixin = {
            options: {
                childProductTemplates: {},
                   selectorProduct: '.product-form-container',
            },

            /**
             * 初始化时从全局变量加载配置数据并绑定事件
             * @private
             */
            _init: function () {
                // Count total attributes (must run BEFORE _super which triggers render)
                this._totalAttributes = Object.keys(this.options.jsonConfig.attributes || {}).length;
                this._renderIndex = 0;

                // 调用原生的 _init 方法
                this._super();

                // 从全局变量加载子产品模板数据（使用对象合并）
                if (window.folixChargeTemplateConfig && typeof window.folixChargeTemplateConfig === 'object') {
                    this.options.childProductTemplates = $.extend(
                        {},
                        this.options.childProductTemplates || {},
                        window.folixChargeTemplateConfig
                    );
                   
                }
           
                // 绑定 change 事件监听器
                this._bindChangeEvent();

                // 初始化时检查是否有预选的子产品
                this._loadChargeTemplateForCurrentSelection();
            },

            /**
             * 绑定 change 事件监听器
             * @private
             */
            _bindChangeEvent: function () {
                var self = this;

                // 监听所有 swatch input 的 change 事件
                this.element.on('change', '.' + this.options.classes.attributeInput, function () {
                    // 延迟执行，确保原生逻辑已完成
                    setTimeout(function () {
                        self._loadChargeTemplateForCurrentSelection();
                    }, 100);
                });
            },

            /**
             * 根据当前选择的选项加载充值模板
             * @private
             */
            _loadChargeTemplateForCurrentSelection: function () {
                var simpleProductId = this._getSimpleProductId();

                if (simpleProductId) {
                    this._renderChargeTemplate(simpleProductId);
                } else {
                    this._showTemplateSelectionMessage();
                }
            },

            /**
             * 获取当前选中的子产品 ID
             * @returns {number|null}
             * @private
             */
            _getSimpleProductId: function () {
                var $widget = this,
                    selectedControls = this.element.find('.' + this.options.classes.attributeClass + '[data-option-selected]'),
                    products = [];

                // 如果没有选中任何选项，返回 null
                if (selectedControls.length <= 0) {
                    return null;
                }

                // 检查是否所有属性都已选择
                var totalAttributes = this.element.find('.' + this.options.classes.attributeClass + '[data-attribute-id]').length;
                if (selectedControls.length < totalAttributes) {
                    return null;
                }

                // 使用原生的 _CalcProducts 方法获取产品列表
                products = this._CalcProducts();

                // 如果只有一个产品，返回该产品ID
                if (products.length === 1) {
                    return parseInt(products[0]);
                }

                // 如果有多个产品，尝试找到第一个有充值模板的产品
                for (var i = 0; i < products.length; i++) {
                    var productId = parseInt(products[i]);
                    if (this.options.childProductTemplates[productId]) {
                        return productId;
                    }
                }

                // 返回第一个产品ID
                return products.length > 0 ? parseInt(products[0]) : null;
            },

            /**
             * 根据选中的属性值查找子产品 ID
             * @param {Object} selectedOptions
             * @returns {number|null}
             * @private
             */
            _findSimpleProductId: function (selectedOptions) {
                var products = this.options.jsonConfig.products;

                for (var productId in products) {
                    if (!products.hasOwnProperty(productId)) {
                        continue;
                    }

                    var product = products[productId];
                    var match = true;

                    for (var attrId in selectedOptions) {
                        if (product[attrId] != selectedOptions[attrId]) {
                            match = false;
                            break;
                        }
                    }

                    if (match) {
                        return parseInt(productId);
                    }
                }

                return null;
            },

            /**
             * 渲染充值模板表单
             * @param {number} simpleProductId
             * @private
             */
            _renderChargeTemplate: function (simpleProductId) {
                var templateData = this.options.childProductTemplates[simpleProductId];

                if (!templateData) {
                    console.warn('Folix ChargeTemplate: No template found for product', simpleProductId);
                    return;
                }

                var $container = $('#charge-template-container');
                var $fieldsContainer = $container.find('.charge-template-fields');
                var $message = $container.find('.charge-template-message');

                if (!$container.length || !$fieldsContainer.length) {
                    console.warn('Folix ChargeTemplate: Container not found');
                    return;
                }

                // 隐藏提示信息
                $message.hide();

                // 构建表单字段 HTML
                var html = this._buildFieldsHtml(templateData.fields);

                // 插入表单字段
                $fieldsContainer.html(html);

                // 显示容器
                $container.show();

                // 初始化表单验证
                this._initFormValidation($fieldsContainer);

              
            },

            /**
             * 构建表单字段 HTML
             * @param {Array} fields
             * @returns {string}
             * @private
             */
            _buildFieldsHtml: function (fields) {
                var html = '';

                _.each(fields, function (field) {
                    var fieldCode = field.charge_field_name;
                    var fieldLabel = field.alias || field.charge_field_name || 'Field';
                    var fieldType = field.field_type || 'text';
                    var isRequired = field.is_required !== false;

                    html += '<div class="field ' + fieldCode + (isRequired ? ' required' : '') + '">';
                    html += '<label class="label" for="' + fieldCode + '">';
                    html += '<span>' + fieldLabel + '</span>';
                    html += '</label>';
                    html += '<div class="control">';

                    switch (fieldType) {
                        case 'text':
                            html += '<input type="text" ';
                            html += 'name="charge_template[' + fieldCode + ']" ';
                            html += 'id="' + fieldCode + '" ';
                            html += 'class="input-text" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += 'placeholder="' + (field.placeholder || '') + '" />';
                            break;

                        case 'select':
                            html += '<select name="charge_template[' + fieldCode + ']" ';
                            html += 'id="' + fieldCode + '" ';
                            html += 'class="select" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += '>';
                            html += '<option value="">' + $t('Please select...') + '</option>';

                            if (field.options && field.options.length > 0) {
                                _.each(field.options, function (option) {
                                    html += '<option value="' + (option.name || option.value) + '">' +
                                        (option.name || option.label) + '</option>';
                                }, this);
                            }

                            html += '</select>';
                            break;

                        case 'textarea':
                            html += '<textarea name="charge_template[' + fieldCode + ']" ';
                            html += 'id="' + fieldCode + '" ';
                            html += 'class="textarea" ';
                            html += 'rows="3" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += 'placeholder="' + (field.placeholder || '') + '"></textarea>';
                            break;

                        default:
                            html += '<input type="text" ';
                            html += 'name="charge_template[' + fieldCode + ']" ';
                            html += 'id="' + fieldCode + '" ';
                            html += 'class="input-text" ';
                            if (isRequired) {
                                html += 'data-validate="{required:true}" ';
                            }
                            html += 'placeholder="' + (field.placeholder || '') + '" />';
                    }

                    html += '</div></div>';
                }, this);

                return html;
            },

            /**
             * 显示选择提示信息
             * @private
             */
            _showTemplateSelectionMessage: function () {
                var $container = $('#charge-template-container');
                var $fieldsContainer = $container.find('.charge-template-fields');
                var $message = $container.find('.charge-template-message');

                if ($container.length) {
                    $message.show();
                    $fieldsContainer.empty();
                    $container.show();
                }
            },

            /**
             * 初始化表单验证
             * @param {jQuery} $container
             * @private
             */
            _initFormValidation: function ($container) {
                var form = $container.closest('form');

                // 确保表单验证已初始化
                form.validation({
                    submitHandler: function (form) {
                        form.submit();
                    }
                });
            },

            /**
             * HTML 转义，防止 XSS
             * @param {string} str
             * @returns {string}
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
            },

            /**
             * 覆盖 _OnClick：调整价格盒子选择路径
             * 
             * @param {Object} $this
             * @param {Object} $widget
             * @private
             */
            _OnClick: function ($this, $widget) {
                this._super($this, $widget);
                // 更新右侧面板中的 SKU 值
                $widget._updateSkuDisplay();
            },

            /**
             * 更新右侧面板中的 SKU 显示
             * @private
             */
            _updateSkuDisplay: function () {
                var simpleProductId = this._getSimpleProductId();
                var $skuValue = $('#product_addtocart_form .product.attribute.sku .value');

                if (!$skuValue.length) {
                    return;
                }

                if (simpleProductId && this.options.jsonConfig.sku && this.options.jsonConfig.sku[simpleProductId]) {
                    var sku = this.options.jsonConfig.sku[simpleProductId] || '';
                    $skuValue.text(sku);
                }
            },

           

            /**
             * 覆盖 _RenderSwatchOptions：直接在渲染时输出完整 HTML
             * 
             * @param {Object} config
             * @param {string} controlId
             * @returns {string}
             * @private
             */
            _RenderSwatchOptions: function (config, controlId) {
                var self = this,
                    optionConfig = this.options.jsonSwatchConfig[config.id],
                    optionClass = this.options.classes.optionClass,
                    sizeConfig = this.options.jsonSwatchImageSizeConfig,
                    moreLimit = parseInt(this.options.numberToShow, 10),
                    moreClass = this.options.classes.moreButton,
                    moreText = this.options.moreButtonText,
                    countAttributes = 0,
                    html = '';

                if (!this.options.jsonSwatchConfig.hasOwnProperty(config.id)) {
                    return '';
                }

                // Determine if this is the first attribute (→ pills) or later (→ product cards)
                // Only use Pill mode when there are 2+ attributes total
                var isFirstAttribute = (self._renderIndex === 0) && (self._totalAttributes > 1);
                self._renderIndex++;

                $.each(config.options, function (index) {
                    var id,
                        type,
                        value,
                        thumb,
                        label,
                        width,
                        height,
                        attr,
                        swatchImageWidth,
                        swatchImageHeight;

                    if (!optionConfig.hasOwnProperty(this.id)) {
                        return '';
                    }

                    // Add more button
                    if (moreLimit === countAttributes++) {
                        html += '<a href="#" class="' + moreClass + '"><span>' + moreText + '</span></a>';
                    }

                    id = this.id;
                    type = parseInt(optionConfig[id].type, 10);
                    value = optionConfig[id].hasOwnProperty('value') ?
                        $('<i></i>').text(optionConfig[id].value).html() : '';
                    thumb = optionConfig[id].hasOwnProperty('thumb') ? optionConfig[id].thumb : '';
                    width = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.width : 110;
                    height = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.height : 90;
                    label = this.label ? $('<i></i>').text(this.label).html() : '';
                    attr =
                        ' id="' + controlId + '-item-' + id + '"' +
                        ' index="' + index + '"' +
                        ' aria-checked="false"' +
                        ' aria-describedby="' + controlId + '"' +
                        ' tabindex="0"' +
                        ' data-option-type="' + type + '"' +
                        ' data-option-id="' + id + '"' +
                        ' data-option-label="' + label + '"' +
                        ' aria-label="' + label + '"' +
                        ' role="option"' +
                        ' data-thumb-width="' + width + '"' +
                        ' data-thumb-height="' + height + '"';

                    attr += thumb !== '' ? ' data-option-tooltip-thumb="' + thumb + '"' : '';
                    attr += value !== '' ? ' data-option-tooltip-value="' + value + '"' : '';

                    swatchImageWidth = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.width : 30;
                    swatchImageHeight = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.height : 20;

                    if (!this.hasOwnProperty('products') || this.products.length <= 0) {
                        attr += ' data-option-empty="true"';
                    }

                    if (type === 0) {
                        if (isFirstAttribute) {
                            // Region Pill — compact, no product name/price
                            html += '<div class="' + optionClass + ' text pdp-swatch-option pdp-swatch-pill" ' + attr + '>' +
                                '<span class="swatch-option__checkbox"></span>' +
                                '<span class="swatch-option__pill-label">' + self._escapeHtml(label) + '</span>' +
                                '</div>';
                        } else {
                            // Product Card — full layout with name + price
                            var productId = this.products && this.products.length > 0 ? this.products[0] : null;
                            var productName = productId && self.options.jsonConfig.productNames ? self.options.jsonConfig.productNames[productId] : '';
                            var priceHtml = '';

                            if (productId && self.options.jsonConfig.optionPrices && self.options.jsonConfig.optionPrices[productId]) {
                                var priceData = self.options.jsonConfig.optionPrices[productId];
                                var finalAmount = parseFloat(priceData.finalPrice?.amount) || 0;
                                var oldAmount = parseFloat(priceData.oldPrice?.amount) || 0;
                                var priceFormat = self.options.jsonConfig.priceFormat || {};
                                var finalPriceHtml = priceUtils.formatPrice(finalAmount, priceFormat);
                                if (oldAmount > finalAmount) {
                                    var oldPriceHtml = priceUtils.formatPrice(oldAmount, priceFormat);
                                    priceHtml = '<span class="swatch-option__old-price">' + oldPriceHtml + '</span>';
                                }
                                priceHtml += '<span class="swatch-option__final-price">' + finalPriceHtml + '</span>';
                            }

                            var leftHtml = '<span class="swatch-option__checkbox"></span>';
                            leftHtml += '<div class="swatch-option__info">';
                            leftHtml += '<span class="swatch-option__label">' + self._escapeHtml(productName || label) + '</span>';
                            if (label) {
                                leftHtml += '<span class="swatch-option__sku">' + self._escapeHtml(label) + '</span>';
                            }
                            leftHtml += '</div>';

                            html += '<div class="' + optionClass + ' text pdp-swatch-option"' + ' ' + attr + '>' +
                                '<div class="swatch-option__inner">' +
                                '<div class="swatch-option__left">' + leftHtml + '</div>' +
                                '<div class="swatch-option__right">' + priceHtml + '</div>' +
                                '</div>' +
                                '</div>';
                        }
                    } else if (type === 1) {
                        // Color
                        html += '<div class="' + optionClass + ' color" ' + attr +
                            ' style="background: ' + value +
                            ' no-repeat center; background-size: initial;">' + '' +
                            '</div>';
                    } else if (type === 2) {
                        // Image
                        html += '<div class="' + optionClass + ' image" ' + attr +
                            ' style="background: url(' + value + ') no-repeat center; background-size: initial;width:' +
                            swatchImageWidth + 'px; height:' + swatchImageHeight + 'px">' + '' +
                            '</div>';
                    } else if (type === 3) {
                        // Clear
                        html += '<div class="' + optionClass + '" ' + attr + '></div>';
                    } else {
                        // Default
                        html += '<div class="' + optionClass + '" ' + attr + '>' + label + '</div>';
                    }
                });

                return html;
            }
        };

        /**
         * 使用 $.widget 扩展原生 widget
         */
        $.widget('mage.swatchRenderer', targetWidget, chargeTemplateMixin);

        /**
         * 必须返回原生的 widget 引用
         */
        return $.mage.swatchRenderer;
    };
});
