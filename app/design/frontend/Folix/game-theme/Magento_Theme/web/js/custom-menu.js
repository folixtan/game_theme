/**
 * Folix Game Theme - Custom Menu Widget
 * 
 * 扩展原生的 mage/menu widget，添加移动端侧边栏功能
 * 
 * 功能：
 * 1. 保留原生的PC端下拉菜单功能
 * 2. 增强移动端侧边栏功能
 * 3. 添加遮罩层支持
 * 4. 添加ESC键关闭
 * 5. 添加点击外部关闭
 */

define([
    'jquery',
    'matchMedia',
    'jquery-ui-modules/menu',
    'mage/translate',
    'mage/menu'
], function ($, mediaCheck) {
    'use strict';

    /**
     * Folix Custom Menu Widget
     * 继承原生的 mage.menu widget
     */
    $.widget('folix.menu', $.mage.menu, {
        options: {
            // 继承父类的所有选项
            responsive: true,
            expanded: true,
            mediaBreakpoint: '(max-width: 767px)',
            // 新增选项
            sidebarSelector: '.sections.nav-sections',
            overlaySelector: '.nav-overlay',
            transitionDuration: 300
        },

        /**
         * 创建时调用
         * @private
         */
        _create: function () {
            this._super();
            
            // 初始化遮罩层
            this._initOverlay();
            
            // 初始化关闭按钮
            this._initCloseButton();
        },

        /**
         * 初始化时调用
         * @private
         */
        _init: function () {
            this._super();
            
            // 添加ESC键关闭
            this._initEscClose();
            
            // 添加点击外部关闭
            this._initClickOutside();
        },

        /**
         * 重写移动端模式切换
         * @private
         */
        _toggleMobileMode: function () {
            // 调用父类方法
            this._super();
            
            // 添加我们的自定义逻辑
            this._initMobileSidebar();
        },

        /**
         * 初始化移动端侧边栏
         * @private
         */
        _initMobileSidebar: function () {
            // 不需要在JS中设置left，CSS已经处理
            // 移除任何行内样式
            $(this.options.sidebarSelector).css('left', '');
        },

        /**
         * 重写toggle方法，增强功能
         */
        toggle: function () {
            var self = this,
                $html = $('html'),
                $sidebar = $(this.options.sidebarSelector),
                $overlay = $(this.options.overlaySelector);

            if ($html.hasClass('nav-open')) {
                // 关闭
                this._closeSidebar();
            } else {
                // 打开
                this._openSidebar();
            }
        },

        /**
         * 打开侧边栏
         * @private
         */
        _openSidebar: function () {
            var $html = $('html'),
                $sidebar = $(this.options.sidebarSelector),
                $overlay = $(this.options.overlaySelector);

            $html.addClass('nav-before-open');
            
            setTimeout(function () {
                $html.addClass('nav-open');
            }, this.options.showDelay);

            // 添加我们的active类
            $sidebar.addClass('active');
            $overlay.addClass('active');
            
            // 禁止body滚动
            $('body').css('overflow', 'hidden');
        },

        /**
         * 关闭侧边栏
         * @private
         */
        _closeSidebar: function () {
            var $html = $('html'),
                $sidebar = $(this.options.sidebarSelector),
                $overlay = $(this.options.overlaySelector);

            $html.removeClass('nav-open');
            
            setTimeout(function () {
                $html.removeClass('nav-before-open');
            }, this.options.hideDelay);

            // 移除我们的active类
            $sidebar.removeClass('active');
            $overlay.removeClass('active');
            
            // 恢复body滚动
            $('body').css('overflow', '');
        },

        /**
         * 初始化遮罩层
         * @private
         */
        _initOverlay: function () {
            var self = this,
                $overlay = $(this.options.overlaySelector);

            // 点击遮罩层关闭
            $overlay.on('click', function (e) {
                e.preventDefault();
                self._closeSidebar();
            });
        },

        /**
         * 初始化关闭按钮
         * @private
         */
        _initCloseButton: function () {
            var self = this;

            // 点击section-item-title关闭（除了切换按钮本身）
            $(document).on('click', '.section-item-title.nav-sections-item-title', function (e) {
                if (!$(e.target).closest('.nav-sections-item-switch').length) {
                    self._closeSidebar();
                }
            });
        },

        /**
         * 初始化ESC键关闭
         * @private
         */
        _initEscClose: function () {
            var self = this;

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $('html').hasClass('nav-open')) {
                    self._closeSidebar();
                }
            });
        },

        /**
         * 初始化点击外部关闭
         * @private
         */
        _initClickOutside: function () {
            var self = this;

            $(document).on('click', function (e) {
                var $sidebar = $(self.options.sidebarSelector),
                    $toggle = $('[data-action="toggle-nav"]'),
                    $overlay = $(self.options.overlaySelector);

                if ($('html').hasClass('nav-open') &&
                    $(e.target).closest($sidebar).length === 0 &&
                    $(e.target).closest($toggle).length === 0 &&
                    $(e.target).closest($overlay).length === 0) {
                    self._closeSidebar();
                }
            });
        }
    });

    return $.folix.menu;
});
