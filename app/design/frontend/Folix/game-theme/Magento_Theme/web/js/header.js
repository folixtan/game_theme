/**
 * Folix Game Theme - Header Interactions
 *
 * PC 端用户下拉菜单 + 移动端侧边栏
 */

define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    console.log('[Folix] Header JS Loaded');

    /**
     * PC 端用户下拉菜单
     */
    function initUserDropdown() {
        console.log('[Folix] Initializing user dropdown');

        var $dropdownTrigger = $('.user-dropdown-trigger');
        var $dropdownMenu = $('#user-dropdown-menu');

        if ($dropdownTrigger.length === 0 || $dropdownMenu.length === 0) {
            console.log('[Folix] User dropdown elements not found');
            return;
        }

        // 切换下拉菜单
        $dropdownTrigger.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var isActive = $dropdownMenu.hasClass('active');

            // 关闭所有其他下拉菜单
            $('.user-dropdown-menu').removeClass('active');
            $('.user-dropdown-trigger').removeClass('active');

            // 切换当前菜单
            if (!isActive) {
                $dropdownMenu.addClass('active');
                $(this).addClass('active');
            }

            console.log('[Folix] User dropdown toggled:', !isActive);
        });

        // 点击外部关闭下拉菜单
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.user-dropdown-wrapper').length) {
                $dropdownMenu.removeClass('active');
                $dropdownTrigger.removeClass('active');
            }
        });

        // ESC 键关闭下拉菜单
        $(document).on('keydown', function (e) {
            if (e.keyCode === 27) { // ESC
                $dropdownMenu.removeClass('active');
                $dropdownTrigger.removeClass('active');
            }
        });
    }

    /**
     * 移动端侧边栏
     */
    function initMobileSidebar() {
        console.log('[Folix] Initializing mobile sidebar');

        var $navToggle = $('.nav-toggle');
        var $mobileSidebar = $('.mobile-sidebar');
        var $mobileOverlay = $('.mobile-nav-overlay');
        var $navClose = $('.mobile-nav-close');
        var $body = $('body');

        if ($mobileSidebar.length === 0) {
            console.log('[Folix] Mobile sidebar not found');
            return;
        }

        // 打开侧边栏
        $navToggle.on('click', function (e) {
            e.preventDefault();
            $mobileSidebar.addClass('active');
            if ($mobileOverlay.length) {
                $mobileOverlay.addClass('active');
            }
            $body.css('overflow', 'hidden');
            console.log('[Folix] Mobile sidebar opened');
        });

        // 关闭侧边栏
        function closeSidebar() {
            $mobileSidebar.removeClass('active');
            if ($mobileOverlay.length) {
                $mobileOverlay.removeClass('active');
            }
            $body.css('overflow', '');
            console.log('[Folix] Mobile sidebar closed');
        }

        // 关闭按钮
        if ($navClose.length) {
            $navClose.on('click', closeSidebar);
        }

        // 遮罩层点击
        if ($mobileOverlay.length) {
            $mobileOverlay.on('click', closeSidebar);
        }

        // ESC 键关闭
        $(document).on('keydown', function (e) {
            if (e.keyCode === 27 && $mobileSidebar.hasClass('active')) {
                closeSidebar();
            }
        });
    }

    /**
     * 检测登录状态并添加 body class
     */
    function initLoginState() {
        var isLoggedIn = $('.header-links-logged').length > 0 && $('.header-links-logged').find('.user-dropdown-wrapper').length > 0;

        if (isLoggedIn) {
            $('body').addClass('customer-logged-in');
            console.log('[Folix] User is logged in');
        } else {
            $('body').removeClass('customer-logged-in');
            console.log('[Folix] User is not logged in');
        }
    }

    /**
     * 响应式处理
     */
    function initResponsive() {
        var MOBILE_BREAKPOINT = 767;
        var $window = $(window);

        function checkViewport() {
            var isMobile = $window.width() <= MOBILE_BREAKPOINT;

            console.log('[Folix] Viewport:', $window.width(), 'Mobile:', isMobile);

            // PC 端：初始化用户下拉菜单
            if (!isMobile) {
                initUserDropdown();
            }

            // 移动端：初始化侧边栏
            if (isMobile) {
                initMobileSidebar();
            }
        }

        // 初始检查
        checkViewport();

        // 监听窗口大小变化
        var resizeTimer;
        $window.on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(checkViewport, 250);
        });
    }

    // 初始化
    initLoginState();
    initResponsive();
});
