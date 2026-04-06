/**
 * Folix Game Theme - Login Dropdown Component
 * 
 * 使用 jQuery UI Widget 模式
 * 
 * 功能：
 * 1. PC端：hover展开/关闭
 * 2. 移动端：click展开/关闭
 * 3. 登录/注册表单切换
 * 4. 点击外部关闭
 * 5. 第三方登录预留接口
 */

define([
    'jquery',
    'jquery/ui-modules/widget',
    'domReady!'
], function ($) {
    'use strict';

    $.widget('folix.loginDropdown', {
        options: {
            hoverDelay: 200,
            mobileBreakpoint: 768
        },

        /**
         * 构造函数 - Widget 初始化
         * @private
         */
        _create: function () {
            this.$wrapper = this.element.closest('.login-dropdown-wrapper');
            this.$trigger = this.$wrapper.find('.login-trigger');
            this.$dropdown = this.element.hasClass('login-dropdown') ? this.element : this.$wrapper.find('.login-dropdown');
            this.$body = $('body');
            this.hoverTimeout = null;
            this.isMobile = false;

            console.log('[Folix] Login Dropdown initialized', {
                breakpoint: this.options.mobileBreakpoint,
                element: this.element[0]
            });

            this._initCommonEvents();
            this._initResponsive();
        },

        /**
         * 初始化公共事件
         * @private
         */
        _initCommonEvents: function () {
            var self = this;

            // 点击下拉菜单内部（阻止冒泡）
            this.$dropdown.on('click.folixLoginDropdown', function (e) {
                e.stopPropagation();
            });

            // 点击外部关闭
            $(document).on('click.folixLoginDropdown', function (e) {
                if (self.$dropdown.hasClass('open')) {
                    if (!$(e.target).closest(self.$wrapper).length) {
                        self.close();
                    }
                }
            });

            // 标签切换
            this.$dropdown.find('.dropdown-tab').on('click.folixLoginDropdown', function (e) {
                e.preventDefault();
                self.switchTab($(this).data('tab'));
            });

            // 遮罩点击关闭（移动端）
            this.$body.on('click.folixLoginDropdownOverlay', '.login-overlay', function () {
                self.close();
            });

            // ESC 键关闭
            $(document).on('keydown.folixLoginDropdown', function (e) {
                if (e.keyCode === 27 && self.$dropdown.hasClass('open')) {
                    self.close();
                }
            });

            // 第三方登录按钮（预留）
            this.$dropdown.find('.btn-social').on('click.folixLoginDropdown', function (e) {
                e.preventDefault();
                var provider = $(this).hasClass('btn-google') ? 'google' : 'facebook';
                self.element.trigger('socialLoginClick', [provider]);
            });

            // 触发按钮键盘支持
            this.$trigger.on('keydown.folixLoginDropdown', function (e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    self.toggle();
                }
            });
        },

        /**
         * 初始化响应式
         * @private
         */
        _initResponsive: function () {
            var self = this;

            console.log('[Folix] Login Dropdown responsive, breakpoint:', this.options.mobileBreakpoint);

            // 使用 window.matchMedia 替代 mediaCheck（更可靠）
            var mq = window.matchMedia('(min-width: ' + this.options.mobileBreakpoint + 'px)');
            
            var handleMediaChange = function (mql) {
                if (mql.matches) {
                    console.log('[Folix] Login Dropdown: desktop mode');
                    self._initDesktopEvents();
                } else {
                    console.log('[Folix] Login Dropdown: mobile mode');
                    self._initMobileEvents();
                }
            };

            // 初始检查
            handleMediaChange(mq);

            // 监听变化
            if (mq.addEventListener) {
                mq.addEventListener('change', handleMediaChange);
            } else {
                // 兼容旧浏览器
                mq.addListener(handleMediaChange);
            }
        },

        /**
         * 初始化PC端事件（hover）
         * @private
         */
        _initDesktopEvents: function () {
            var self = this;

            this.isMobile = false;

            // 移除可能存在的click事件
            this.$trigger.off('click.folixLoginDropdown');

            // 鼠标移入展开
            this.$wrapper.off('mouseenter.folixLoginDropdown mouseleave.folixLoginDropdown')
                .on('mouseenter.folixLoginDropdown', function () {
                    clearTimeout(self.hoverTimeout);
                    self.open();
                })
                .on('mouseleave.folixLoginDropdown', function () {
                    self.hoverTimeout = setTimeout(function () {
                        self.close();
                    }, self.options.hoverDelay);
                });
        },

        /**
         * 初始化移动端事件（click）
         * @private
         */
        _initMobileEvents: function () {
            var self = this;

            this.isMobile = true;

            // 移除hover事件
            this.$wrapper.off('mouseenter.folixLoginDropdown mouseleave.folixLoginDropdown');

            // 点击触发按钮
            this.$trigger.off('click.folixLoginDropdown').on('click.folixLoginDropdown', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggle();
            });
        },

        /**
         * 切换下拉菜单
         */
        toggle: function () {
            if (this.$dropdown.hasClass('open')) {
                this.close();
            } else {
                this.open();
            }
        },

        /**
         * 打开下拉菜单
         */
        open: function () {
            this.$dropdown.addClass('open');
            this.$trigger.addClass('active');

            // 添加遮罩（移动端）
            if (this.isMobile) {
                this._addOverlay();
            }

            this.element.trigger('loginDropdownOpen');
        },

        /**
         * 关闭下拉菜单
         */
        close: function () {
            this.$dropdown.removeClass('open');
            this.$trigger.removeClass('active');
            this._removeOverlay();

            this.element.trigger('loginDropdownClose');
        },

        /**
         * 切换标签（登录/注册）
         * @param {String} tabName - 标签名称
         */
        switchTab: function (tabName) {
            var $tabs = this.$dropdown.find('.dropdown-tab'),
                $panels = this.$dropdown.find('.dropdown-panel');

            // 更新标签状态
            $tabs.removeClass('active');
            $tabs.filter('[data-tab="' + tabName + '"]').addClass('active');

            // 更新面板状态
            $panels.removeClass('active');
            $panels.filter('#' + tabName + '-panel').addClass('active');

            this.element.trigger('loginDropdownTabSwitch', [tabName]);
        },

        /**
         * 添加遮罩（移动端）
         * @private
         */
        _addOverlay: function () {
            if ($('.login-overlay').length === 0) {
                this.$body.append('<div class="login-overlay"></div>');
            }
        },

        /**
         * 移除遮罩
         * @private
         */
        _removeOverlay: function () {
            $('.login-overlay').remove();
        },

        /**
         * 销毁 Widget
         */
        _destroy: function () {
            this.$wrapper.off('.folixLoginDropdown');
            this.$trigger.off('.folixLoginDropdown');
            this.$dropdown.off('.folixLoginDropdown');
            $(document).off('.folixLoginDropdown');
            this.$body.off('.folixLoginDropdownOverlay');
            this._removeOverlay();
        }
    });

    return $.folix.loginDropdown;
});
