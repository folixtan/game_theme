/**
 * Charge Template Manager — load & render charge template forms
 * Composed by swatch-renderer-charge-template-mixin.
 */
define([
    'jquery',
    'mage/translate',
    'underscore'
], function ($, $t, _) {
    'use strict';

    return {

        /**
         * Determine simple product id from the swatch widget
         * @param  {Object} widget  Swatch renderer widget instance
         * @return {number|null}
         */
        getSimpleProductId: function (widget) {
            var selectedControls = widget.element.find(
                    '.' + widget.options.classes.attributeClass + '[data-option-selected]'
                ),
                totalAttributes = widget.element.find(
                    '.' + widget.options.classes.attributeClass + '[data-attribute-id]'
                ).length;

            if (selectedControls.length <= 0 || selectedControls.length < totalAttributes) {
                return null;
            }

            var products = widget._CalcProducts();

            if (products.length === 1) {
                return parseInt(products[0]);
            }

            // Prefer first product that has a charge template
            for (var i = 0; i < products.length; i++) {
                var id = parseInt(products[i]);
                if (widget.options.childProductTemplates[id]) {
                    return id;
                }
            }

            return products.length > 0 ? parseInt(products[0]) : null;
        },

        /**
         * Load and render charge template for current selection
         * @param {Object} widget  Swatch renderer widget instance
         */
        loadForCurrentSelection: function (widget) {
            var simpleProductId = this.getSimpleProductId(widget);

            if (simpleProductId) {
                this._render(widget, simpleProductId);
            } else {
                this._showMessage();
            }
        },

        /**
         * Render charge template form into #charge-template-container
         * @private
         * @param {Object} widget
         * @param {number} simpleProductId
         */
        _render: function (widget, simpleProductId) {
            var templateData = widget.options.childProductTemplates[simpleProductId];

            if (!templateData) {
                return;
            }

            var $container = $('#charge-template-container'),
                $fields = $container.find('.charge-template-fields'),
                $message = $container.find('.charge-template-message');

            if (!$container.length || !$fields.length) {
                return;
            }

            $message.hide();
            $fields.html(this._buildFieldsHtml(templateData.fields));
            $container.show();

            this._initValidation($fields);
        },

        /**
         * Build field HTML from template definition
         * @private
         * @param  {Array} fields
         * @return {string}
         */
        _buildFieldsHtml: function (fields) {
            var html = '';

            _.each(fields, function (field) {
                var code = field.charge_field_name,
                    label = field.alias || code || 'Field',
                    type = field.field_type || 'text',
                    required = field.is_required !== false,
                    reqAttr = required ? ' data-validate="{required:true}"' : '';

                html += '<div class="field ' + code + (required ? ' required' : '') + '">';
                html += '<label class="label" for="' + code + '"><span>' + label + '</span></label>';
                html += '<div class="control">';

                switch (type) {
                    case 'select':
                        html += '<select name="charge_template[' + code + ']" id="' + code +
                                '" class="select"' + reqAttr + '>';
                        html += '<option value="">' + $t('Please select...') + '</option>';
                        _.each(field.options || [], function (opt) {
                            html += '<option value="' + (opt.name || opt.value) + '">' +
                                    (opt.name || opt.label) + '</option>';
                        });
                        html += '</select>';
                        break;

                    case 'textarea':
                        html += '<textarea name="charge_template[' + code + ']" id="' + code +
                                '" class="textarea" rows="3"' + reqAttr +
                                ' placeholder="' + (field.placeholder || '') + '"></textarea>';
                        break;

                    case 'text':
                    default:
                        html += '<input type="text" name="charge_template[' + code + ']" id="' + code +
                                '" class="input-text"' + reqAttr +
                                ' placeholder="' + (field.placeholder || '') + '" />';
                }

                html += '</div></div>';
            });

            return html;
        },

        /**
         * Show "please select" message, clear fields
         * @private
         */
        _showMessage: function () {
            var $container = $('#charge-template-container'),
                $fields = $container.find('.charge-template-fields'),
                $message = $container.find('.charge-template-message');

            if ($container.length) {
                $message.show();
                $fields.empty();
                $container.show();
            }
        },

        /**
         * Init Magento form validation on container
         * @private
         * @param {jQuery} $container
         */
        _initValidation: function ($container) {
            var form = $container.closest('form');
            form.validation({
                submitHandler: function (form) {
                    form.submit();
                }
            });
        }
    };
});
