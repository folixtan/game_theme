/**
 * Folix Game Theme - Navigation Initialization
 *
 * 自动初始化导航组件
 */

require([
    'jquery'
], function ($) {
    'use strict';

    $(document).ready(function() {
        // 确保导航切换功能正常工作
        $('.nav-toggle[data-action="toggle-nav"]').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $sidebar = $('.sections.nav-sections');

            if ($sidebar.hasClass('active')) {
                _close();
            } else {
                _open();
            }
        });

        // 打开侧边栏
        function _open() {
            $('body').addClass('nav-open');
            $('.sections.nav-sections').addClass('active');
            $('body').css('overflow', 'hidden');
        }

        // 关闭侧边栏
        function _close() {
            $('body').removeClass('nav-open');
            $('.sections.nav-sections').removeClass('active');
            $('body').css('overflow', '');
        }

        // 点击关闭按钮关闭侧边栏
        $(document).on('click', '.section-item-title.nav-sections-item-title', function(e) {
            if (!$(e.target).closest('.nav-sections-item-switch').length) {
                _close();
            }
        });

        // 点击外部关闭侧边栏
        $(document).on('click', function(e) {
            if ($('.sections.nav-sections.active').length &&
                $(e.target).closest('.sections.nav-sections').length === 0 &&
                $(e.target).closest('.nav-toggle').length === 0) {
                _close();
            }
        });

        // ESC 键关闭侧边栏
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('.sections.nav-sections').hasClass('active')) {
                _close();
            }
        });
    });
});
