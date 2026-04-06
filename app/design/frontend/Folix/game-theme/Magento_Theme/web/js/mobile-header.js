/**
 * Folix Game Theme - Mobile Header Interactions
 * 
 * 全局自动执行模块（不需要绑定到特定元素）
 * 
 * 功能：
 * 1. 搜索框点击展开/收起
 * 2. 导航菜单交互优化
 */

define([
    'jquery',
    'mage/menu',
    'domReady!'
], function ($) {
    'use strict';

    console.log('[Folix] Mobile Header JS Loaded');

    var $html = $('html'),
        $body = $('body'),
        $header = $('.page-header'),
        $searchBlock = $('.block-search'),
        $searchLabel = $searchBlock.find('.label'),
        $searchControl = $searchBlock.find('.control'),
        $searchInput = $searchControl.find('input'),
        $navToggle = $('.nav-toggle'),
        $navSections = $('.nav-sections');

    var MOBILE_BREAKPOINT = 768;

    /**
     * 移动端搜索框交互
     */
    function initMobileSearch() {
        console.log('[Folix] Initializing mobile search');

        // 点击搜索图标
        $searchLabel.off('click.folixMobile').on('click.folixMobile', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $this = $(this);

            if ($this.hasClass('active')) {
                // 收起搜索框
                $this.removeClass('active');
            } else {
                // 展开搜索框
                $this.addClass('active');
                setTimeout(function() {
                    $searchInput.focus();
                }, 100);
            }
        });

        // 点击其他区域关闭搜索框
        $(document).off('click.folixMobile').on('click.folixMobile', function (e) {
            if (!$(e.target).closest('.block-search').length) {
                $searchLabel.removeClass('active');
            }
        });

        // ESC 键关闭搜索框
        $(document).off('keydown.folixMobile').on('keydown.folixMobile', function (e) {
            if (e.keyCode === 27) { // ESC
                $searchLabel.removeClass('active');
            }
        });
    }

    /**
     * 移动端导航交互（增强）
     */
    function initMobileNav() {
        console.log('[Folix] Initializing mobile nav');

        // 初始化：确保所有子菜单都是关闭状态
        $('.mobile-nav-container .navigation li.open').removeClass('open');

        // 确保导航切换按钮工作
        $navToggle.off('click.folixMobile').on('click.folixMobile', function (e) {
            e.preventDefault();
            e.stopPropagation();

            $html.toggleClass('nav-open');
            console.log('[Folix] Nav toggle, nav-open:', $html.hasClass('nav-open'));

            // 如果打开导航，初始化子菜单状态
            if ($html.hasClass('nav-open')) {
                $('.mobile-nav-container .navigation li.open').removeClass('open');
            }
        });

        // 关闭按钮 - 直接绑定到元素上
        $(document).on('click.folixNavClose', '.mobile-nav-close', function (e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[Folix] Mobile nav close button clicked');
            $html.removeClass('nav-open');
            $('.mobile-nav-container .navigation li.open').removeClass('open');
        });

        // ESC 键关闭导航
        $(document).off('keydown.folixMobileNav').on('keydown.folixMobileNav', function (e) {
            if (e.keyCode === 27 && $html.hasClass('nav-open')) {
                $html.removeClass('nav-open');
                $('.mobile-nav-container .navigation li.open').removeClass('open');
            }
        });

        // 处理导航遮罩（确保遮罩存在并正确绑定事件）
        var $navOverlay = $('.nav-overlay');

        // 如果遮罩不存在，创建它
        if ($navOverlay.length === 0) {
            $navOverlay = $('<div class="nav-overlay"></div>');
            $body.append($navOverlay);
        }

        // 绑定点击事件
        $navOverlay.off('click.folixMobileOverlay').on('click.folixMobileOverlay', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $html.removeClass('nav-open');
            $('.mobile-nav-container .navigation li.open').removeClass('open');
        });

        // 子菜单展开/折叠
        initSubmenuToggle();
    }

    /**
     * 子菜单展开/折叠
     */
    function initSubmenuToggle() {
        // 使用事件委托到 document 确保所有元素都能工作
        var $mobileNav = $('.mobile-nav-container');

        // 初始化：移除 jQuery UI 样式并关闭所有子菜单
        setTimeout(function() {
            $mobileNav.find('.submenu')
                .css('display', '')
                .removeClass('ui-menu ui-widget ui-widget-content ui-front expanded')
                .hide();
            $mobileNav.find('li.open').removeClass('open');
            $mobileNav.find('li.ui-state-active').removeClass('ui-state-active');
            $mobileNav.find('.ui-menu-icon').remove();
            console.log('[Folix] Submenus initialized and hidden');
        }, 100);

        // 使用 capture 阶段绑定，在 jQuery UI 之前拦截
        document.addEventListener('click', function(e) {
            // 检查是否点击在移动端导航的父级菜单链接上
            var $link = $(e.target).closest('.mobile-nav-container .navigation li.parent > a');
            if ($link.length) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                var $parent = $link.closest('li');
                var isOpen = $parent.hasClass('open');

                // 移除顶级菜单的所有 active 和 has-active 类
                $mobileNav.find('.navigation > ul > li').removeClass('active has-active');
                $parent.addClass('active');

                // 更新 has-active 类 - 为所有父级添加 has-active
                updateHasActive($parent);

                // 关闭同一层级的其他子菜单
                var $siblings = $parent.siblings('.open');
                $siblings.removeClass('open').find('> .submenu').slideUp(300);

                // 切换当前子菜单
                if (!isOpen) {
                    $parent.addClass('open');
                    $parent.find('> .submenu').slideDown(300);
                } else {
                    $parent.removeClass('open');
                    $parent.find('> .submenu').slideUp(300);
                }
            }
        }, true);

        /**
         * 更新 has-active 类
         * 为所有父级菜单项添加 has-active 类
         */
        function updateHasActive($activeItem) {
            // 移除所有 has-active 类
            $mobileNav.find('.navigation li').removeClass('has-active');

            // 为激活项的所有父级添加 has-active 类
            $activeItem.parents('li').addClass('has-active');
        }

        // 初始化 has-active 类
        setTimeout(function() {
            var $activeItem = $mobileNav.find('.navigation li.active').first();
            if ($activeItem.length) {
                updateHasActive($activeItem);
            }
        }, 100);
    }

    /**
     * 桌面端清理
     */
    function cleanupMobileEvents() {
        console.log('[Folix] Cleaning up mobile events');
        $searchLabel.off('.folixMobile');
        $(document).off('.folixMobile .folixNavClose .folixMobileNav .folixMobileOverlay .folixSubmenu');
        $navToggle.off('.folixMobile');
    }

    /**
     * 响应式处理
     */
    function initResponsive() {
        var mq = window.matchMedia('(max-width: ' + (MOBILE_BREAKPOINT - 1) + 'px)');

        function handleMediaChange(mql) {
            if (mql.matches) {
                console.log('[Folix] Entering mobile mode');
                initMobileSearch();
                initMobileNav();
            } else {
                console.log('[Folix] Exiting mobile mode');
                cleanupMobileEvents();
                $searchLabel.removeClass('active');
                $searchControl.show();
            }
        }

        // 初始检查
        handleMediaChange(mq);

        // 监听变化
        if (mq.addEventListener) {
            mq.addEventListener('change', handleMediaChange);
        } else {
            mq.addListener(handleMediaChange);
        }
    }

    // 初始化
    initResponsive();
});
