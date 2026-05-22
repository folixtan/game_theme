/**
 * Swatch Option HTML Builder — pure functions, no DOM / widget dependency.
 * Composed by swatch-renderer-charge-template-mixin.
 */
define([
    'jquery',
    'underscore',
    'priceUtils'
], function ($, _, priceUtils) {
    'use strict';

    /**
     * HTML-escape a string (safe to call on non-string).
     * @param  {string} str
     * @return {string}
     */
    function escapeHtml(str) {
        if (typeof str !== 'string') { return str; }
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;', '/': '&#x2F;' };
        return str.replace(/[&<>"'/]/g, function (c) { return map[c]; });
    }

    /**
     * Build standard attribute string for a swatch option.
     * @param  {Object} option       Loop item (has id, label, products)
     * @param  {Object} optionConfig optionConfig[id]
     * @param  {Object} sizeConfig   jsonSwatchImageSizeConfig
     * @param  {string} controlId
     * @param  {number} index
     * @return {string}
     */
    function buildOptionAttr(option, optionConfig, sizeConfig, controlId, index) {
        var id = option.id,
            type = parseInt(optionConfig[id].type, 10),
            value = optionConfig[id].value || '',
            thumb = optionConfig[id].thumb || '',
            label = escapeHtml(option.label || ''),
            width = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.width : 110,
            height = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.height : 90;

        var attr =
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

        if (thumb) { attr += ' data-option-tooltip-thumb="' + thumb + '"'; }
        if (value) { attr += ' data-option-tooltip-value="' + value + '"'; }
        if (!option.products || option.products.length <= 0) {
            attr += ' data-option-empty="true"';
        }

        return attr;
    }

    /**
     * Build a Region Pill (<6 attrib total or dropdown item).
     */
    function buildPill(optionClass, attr, label) {
        return '<div class="' + optionClass + ' text pdp-swatch-option pdp-swatch-pill" ' + attr + '>' +
            '<span class="swatch-option__checkbox"></span>' +
            '<span class="swatch-option__pill-label">' + escapeHtml(label) + '</span>' +
            '</div>';
    }

    /**
     * Build a Product Card (name + price).
     */
    function buildProductCard(option, optionClass, attr, jsonConfig) {
        var productId = (option.products && option.products.length > 0) ? option.products[0] : null,
            productName = (productId && jsonConfig.productNames) ? jsonConfig.productNames[productId] : '',
            label = option.label || '',
            priceHtml = '';

        if (productId && jsonConfig.optionPrices && jsonConfig.optionPrices[productId]) {
            var pd = jsonConfig.optionPrices[productId],
                finalAmount = parseFloat(pd.finalPrice && pd.finalPrice.amount) || 0,
                oldAmount = parseFloat(pd.oldPrice && pd.oldPrice.amount) || 0,
                fmt = jsonConfig.priceFormat || {};

            if (oldAmount > finalAmount) {
                priceHtml = '<span class="swatch-option__old-price">' + priceUtils.formatPrice(oldAmount, fmt) + '</span>';
            }
            priceHtml += '<span class="swatch-option__final-price">' + priceUtils.formatPrice(finalAmount, fmt) + '</span>';
        }

        var leftHtml = '<span class="swatch-option__checkbox"></span>' +
            '<div class="swatch-option__info">' +
            '<span class="swatch-option__label">' + escapeHtml(label) + '</span>';
        if (productName) {
            leftHtml += '<span class="swatch-option__sku">' + escapeHtml(productName) + '</span>';
        }
        leftHtml += '</div>';

        return '<div class="' + optionClass + ' text pdp-swatch-option" ' + attr + '>' +
            '<div class="swatch-option__inner">' +
            '<div class="swatch-option__left">' + leftHtml + '</div>' +
            '<div class="swatch-option__right">' + priceHtml + '</div>' +
            '</div>' +
            '</div>';
    }

    /**
     * Build the full dropdown wrapper HTML for >6 option attributes.
     * @param  {string} pillsHtml     Pre-built pill <div> tags
     * @param  {string} selectOptions <option> tags for mobile select
     * @param  {string} configLabel   Attribute label (e.g. "Country Region")
     * @return {string}
     */
    function buildDropdownHTML(pillsHtml, selectOptions, configLabel) {
        var placeholderText = '-- ' + escapeHtml(configLabel) + ' --';
        var displayText = escapeHtml(configLabel);
        return '<select class="pdp-swatch-select">' +
            '<option value="">' + placeholderText + '</option>' + selectOptions +
            '</select>' +
            '<div class="pdp-swatch-dropdown-wrapper">' +
            '<div class="pdp-swatch-dropdown__trigger">' +
            '<span class="pdp-swatch-dropdown__display">' + displayText + '</span>' +
            '<span class="pdp-swatch-dropdown__arrow">&#9660;</span>' +
            '</div>' +
            '<div class="pdp-swatch-dropdown__panel" style="display:none">' +
            '<div class="pdp-swatch-dropdown__search">' +
            '<input type="text" class="pdp-swatch-dropdown__search-input" placeholder="Search...">' +
            '</div>' +
            '<div class="pdp-swatch-dropdown__list">' + pillsHtml + '</div>' +
            '</div>' +
            '</div>';
    }

    return {
        buildOptionAttr:   buildOptionAttr,
        buildPill:         buildPill,
        buildProductCard:  buildProductCard,
        buildDropdownHTML: buildDropdownHTML,
        escapeHtml:        escapeHtml
    };
});
