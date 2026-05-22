/**
 * Swatch Dropdown Manager — handles dropdown init / toggle / search / close.
 * Composed by swatch-renderer-charge-template-mixin.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return {

        /**
         * Initialize country-region dropdown events on the swatch element.
         * Safe to call multiple times (guarded).
         * @param {Object} widget  Swatch renderer widget instance
         */
        init: function (widget) {
            if (widget._countryDropdownInitialized) { return; }
            widget._countryDropdownInitialized = true;

            var self = this,
                el = widget.element;

            // Trigger toggle
            el.on('click.pdpDropdown', '.pdp-swatch-dropdown__trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self._toggle($(this).closest('.pdp-swatch-dropdown-wrapper'));
            });

            // Pill click → update display label and close
            el.on('click.pdpDropdown', '.pdp-swatch-dropdown__list .pdp-swatch-pill', function () {
                var $pill = $(this);
                setTimeout(function () {
                    if ($pill.hasClass('selected')) {
                        var label = $pill.attr('data-option-label') || '';
                        var $wrap = $pill.closest('.pdp-swatch-dropdown-wrapper');
                        $wrap.find('.pdp-swatch-dropdown__display').text(label);
                        self._close($wrap);

                        // Sync to mobile <select>
                        $wrap.parent().find('.pdp-swatch-select').val($pill.data('option-id'));
                    }
                }, 50);
            });

            // Search filter
            var $searchInputs = el.find('.pdp-swatch-dropdown__search-input');
            $searchInputs.on('input.pdpDropdown keyup.pdpDropdown', function () {
                var val = $.trim($(this).val()).toLowerCase();
                var $list = $(this).closest('.pdp-swatch-dropdown-wrapper').find('.pdp-swatch-dropdown__list');
                $list.find('.pdp-swatch-pill').each(function () {
                    var text = ($(this).attr('data-option-label') || '').toLowerCase();
                    $(this)[val === '' || text.indexOf(val) !== -1 ? 'show' : 'hide']();
                });
            });

            // Click outside → close
            $(document).on('click.pdpDropdownClose', function (e) {
                if (!$(e.target).closest('.pdp-swatch-dropdown-wrapper').length) {
                    self._closeAll(el);
                }
            });

            // Mobile <select> → click corresponding pill
            el.on('change.pdpDropdown', '.pdp-swatch-select', function () {
                var optionId = $(this).find(':selected').data('option-id');
                if (optionId && widget._countryDropdownControlId) {
                    var $pill = el.find('#' + widget._countryDropdownControlId + '-item-' + optionId);
                    if ($pill.length) {
                        $pill.click();
                        el.find('.pdp-swatch-dropdown__display').text($pill.attr('data-option-label') || '');
                    }
                }
            });

            // Sync pre-selected option label
            var $wrapper = el.find('.pdp-swatch-dropdown-wrapper');
            setTimeout(function () {
                var $selected = $wrapper.find('.pdp-swatch-pill.selected');
                if ($selected.length) {
                    $wrapper.find('.pdp-swatch-dropdown__display').text($selected.first().attr('data-option-label') || '');
                    el.find('.pdp-swatch-select').val($selected.first().data('option-id'));
                }
            }, 100);
        },

        /**
         * Toggle dropdown panel open/close.
         * @private
         */
        _toggle: function ($wrapper) {
            var $panel = $wrapper.find('.pdp-swatch-dropdown__panel');
            if ($panel.is(':visible')) {
                this._close($wrapper);
            } else {
                this._closeAll($wrapper.closest('.swatch-opt'));
                $panel.slideDown(200);
                $wrapper.addClass('pdp-swatch-dropdown-wrapper--open');
                $wrapper.find('.pdp-swatch-dropdown__search-input').focus();
            }
        },

        /**
         * Close a single dropdown panel.
         * @private
         */
        _close: function ($wrapper) {
            $wrapper.find('.pdp-swatch-dropdown__panel').slideUp(200);
            $wrapper.removeClass('pdp-swatch-dropdown-wrapper--open');
            $wrapper.find('.pdp-swatch-dropdown__search-input').val('');
            $wrapper.find('.pdp-swatch-pill').show();
        },

        /**
         * Close all dropdown panels within a search scope.
         * @private
         */
        _closeAll: function ($scope) {
            $scope.find('.pdp-swatch-dropdown__panel').slideUp(200);
            $scope.find('.pdp-swatch-dropdown-wrapper--open').removeClass('pdp-swatch-dropdown-wrapper--open');
        }
    };
});
