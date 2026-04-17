/**
 * Folix Game Theme - Mobile Navigation Toggle
 *
 * 使用 data-mage-init 方式初始化的简单组件
 */

define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return function(config, element) {
        var $element = $(element);
        var $sidebar = $('.sections.nav-sections');

        // 切换侧边栏
        $element.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if ($sidebar.hasClass('active')) {
                _close();
            } else {
                _open();
            }
        });

        // 打开侧边栏
        function _open() {
            $('body').addClass('nav-open');
            $sidebar.addClass('active');
            $('body').css('overflow', 'hidden');
        }

        // 关闭侧边栏
        function _close() {
            $('body').removeClass('nav-open');
            $sidebar.removeClass('active');
            $('body').css('overflow', '');
        }

        // 点击关闭按钮
        $(document).on('click', '.section-item-title.nav-sections-item-title', function(e) {
            if (!$(e.target).closest('.nav-sections-item-switch').length) {
                _close();
            }
        });

        // 点击外部关闭
        $(document).on('click', function(e) {
            if ($(e.target).closest($sidebar).length === 0 &&
                $(e.target).closest($element).length === 0) {
                if ($('body').hasClass('nav-open')) {
                    _close();
                }
            }
        });

        // ESC 键关闭
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $sidebar.hasClass('active')) {
                _close();
            }
        });
    };
});
