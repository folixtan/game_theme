/**
 * Folix Game Theme - validate-product override
 *
 * 扩展原生 validate-product，检测 Buy Now 按钮触发时传递 checkoutUrl 给 catalogAddToCart
 */
define([
    'jquery',
    'mage/mage',
    'Magento_Catalog/product/view/validation',
    'catalogAddToCart'
], function ($) {
    'use strict';

    $.widget('mage.productValidate', {
        options: {
            bindSubmit: false,
            radioCheckboxClosest: '.nested',
            addToCartButtonSelector: '.action.tocart'
        },

        /**
         * @private
         */
        _create: function () {
            var bindSubmit = this.options.bindSubmit;

            this.element.validation({
                radioCheckboxClosest: this.options.radioCheckboxClosest,

                /**
                 * @param {Object} form
                 * @returns {Boolean}
                 */
                submitHandler: function (form) {
                    var $form = $(form),
                        isBuyNow = $form.attr('data-buynow') === '1',
                        checkoutUrl = $form.attr('data-checkout-url') || '',
                        jqForm = $form.catalogAddToCart({
                            bindSubmit: bindSubmit,
                            addToCartButtonSelector: isBuyNow ? '#product-buynow-button' : '.action.tocart',
                            checkoutUrl: isBuyNow ? checkoutUrl : ''
                        });

                    jqForm.catalogAddToCart('submitForm', jqForm);

                    return false;
                }
            });
            $(this.options.addToCartButtonSelector).attr('disabled', false);
        }
    });

    return $.mage.productValidate;
});
