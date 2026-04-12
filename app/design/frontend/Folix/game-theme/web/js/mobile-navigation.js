/**
 * Folix Game Theme - Mobile Navigation
 *
 * 功能：
 * 1. 汉堡菜单按钮点击 - 打开侧边栏
 * 2. 关闭按钮点击 - 关闭侧边栏
 * 3. 遮罩层点击 - 关闭侧边栏
 * 4. 父级菜单点击 - 展开/折叠子菜单（手风琴效果）
 */

define([
    'jquery'
], function ($) {
    'use strict';

    /**
     * Mobile Navigation Module
     */
    return function (config) {
        // ============================================
        //  Selectors - 选择器
        //  ============================================

        var selectors = {
            navToggle: '.nav-toggle',
            mobileSidebar: '.mobile-sidebar',
            navOverlay: '.nav-overlay',
            closeSidebar: '.close-sidebar',
            mobileContent: '.mobile-sidebar-content .navigation',
            parentLink: '.mobile-sidebar-content .navigation ul > li.parent > a',
            body: 'body'
        };

        // ============================================
        //  State - 状态
        //  ============================================

        var isSidebarOpen = false;

        // ============================================
        //  Methods - 方法
        //  ============================================

        /**
         * Open sidebar - 打开侧边栏
         */
        function openSidebar() {
            $(selectors.mobileSidebar).addClass('active');
            $(selectors.navOverlay).addClass('active');
            $(selectors.body).addClass('nav-open');
            isSidebarOpen = true;

            // 禁止 body 滚动
            $(selectors.body).css('overflow', 'hidden');
        }

        /**
         * Close sidebar - 关闭侧边栏
         */
        function closeSidebar() {
            $(selectors.mobileSidebar).removeClass('active');
            $(selectors.navOverlay).removeClass('active');
            $(selectors.body).removeClass('nav-open');
            isSidebarOpen = false;

            // 恢复 body 滚动
            $(selectors.body).css('overflow', '');
        }

        /**
         * Toggle sidebar - 切换侧边栏
         */
        function toggleSidebar() {
            if (isSidebarOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        /**
         * Toggle submenu - 展开/折叠子菜单（手风琴效果）
         * @param {jQuery} $link - 点击的父级菜单链接
         */
        function toggleSubmenu($link) {
            var $parent = $link.parent('li');
            var $submenu = $parent.children('.submenu');

            // 检查是否已经展开
            var isOpen = $parent.hasClass('open');

            // 如果是手风琴模式（展开一个时关闭其他），可以取消注释以下代码
            /*
            // 关闭同级其他子菜单
            $parent.siblings('.open').each(function () {
                var $sibling = $(this);
                $sibling.removeClass('open');
                $sibling.children('.submenu').slideUp(200);
            });
            */

            // 切换当前子菜单
            if (isOpen) {
                // 折叠
                $parent.removeClass('open');
                $submenu.slideUp(200);
            } else {
                // 展开
                $parent.addClass('open');
                $submenu.slideDown(200);
            }

            // 阻止默认链接跳转
            return false;
        }

        /**
         * Initialize parent links - 初始化父级菜单链接
         */
        function initParentLinks() {
            // 为父级菜单链接添加点击事件
            $(selectors.parentLink).on('click', function (e) {
                e.preventDefault();
                toggleSubmenu($(this));
            });

            // 为有子菜单的链接添加标记类
            $(selectors.mobileContent).find('ul > li').each(function () {
                var $li = $(this);
                if ($li.children('.submenu').length > 0) {
                    $li.addClass('parent');
                    $li.children('a').addClass('has-submenu');
                }
            });
        }

        /**
         * Initialize event listeners - 初始化事件监听
         */
        function initEventListeners() {
            // 汉堡菜单按钮点击
            $(selectors.navToggle).on('click', function (e) {
                e.preventDefault();
                toggleSidebar();
            });

            // 关闭按钮点击
            $(selectors.closeSidebar).on('click', function (e) {
                e.preventDefault();
                closeSidebar();
            });

            // 遮罩层点击
            $(selectors.navOverlay).on('click', function (e) {
                e.preventDefault();
                closeSidebar();
            });

            // ESC 键关闭侧边栏
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && isSidebarOpen) {
                    closeSidebar();
                }
            });

            // 滚动时固定头部（可选）
            var $header = $('.page-header');
            var headerHeight = $header.outerHeight();

            $(window).on('scroll', function () {
                var scrollTop = $(this).scrollTop();

                if (scrollTop > headerHeight) {
                    $header.addClass('sticky-header');
                } else {
                    $header.removeClass('sticky-header');
                }
            });
        }

        /**
         * Initialize - 初始化
         */
        function init() {
            // 检查元素是否存在
            if ($(selectors.navToggle).length === 0) {
                console.warn('[MobileNavigation] Nav toggle not found');
                return;
            }

            if ($(selectors.mobileSidebar).length === 0) {
                console.warn('[MobileNavigation] Mobile sidebar not found');
                return;
            }

            if ($(selectors.mobileContent).length === 0) {
                console.warn('[MobileNavigation] Mobile navigation not found');
                return;
            }

            // 初始化父级菜单链接
            initParentLinks();

            // 初始化事件监听
            initEventListeners();

            console.log('[MobileNavigation] Initialized');
        }

        // ============================================
        //  Run - 执行初始化
        //  ============================================

        // 等待 DOM 准备完成
        $(document).ready(function () {
            init();
        });
    };
});
