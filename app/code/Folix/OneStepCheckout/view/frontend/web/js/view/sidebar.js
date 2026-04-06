/**
 * Folix One Step Checkout - Sidebar Component
 * 
 * 扩展原生 sidebar，添加移动端折叠功能
 */
define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Checkout/js/model/sidebar'
], function (Component, ko, $, sidebarModel) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Folix_OneStepCheckout/sidebar'
        },
        
        /**
         * 订单摘要是否展开（移动端）
         */
        isSummaryExpanded: ko.observable(false),
        
        /**
         * 切换图标
         */
        toggleIcon: null,

        /**
         * 初始化
         */
        initialize: function () {
            this._super();
            
            // 初始化 computed（在 this 绑定后）
            this.toggleIcon = ko.computed(function() {
                return this.isSummaryExpanded() 
                    ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6 1.41 1.41z"/></svg>';
            }, this);
            
            // 默认展开（桌面端）
            if ($(window).width() >= 768) {
                this.isSummaryExpanded(true);
            }
            
            return this;
        },
        
        /**
         * 切换订单摘要展开/折叠
         */
        toggleSummary: function() {
            var isExpanded = this.isSummaryExpanded();
            this.isSummaryExpanded(!isExpanded);
            
            // 在移动端，同时滚动到顶部以便看到内容
            if (!isExpanded && $(window).width() < 768) {
                var $sidebar = $('#opc-sidebar');
                $sidebar.scrollTop(0);
            }
        },
        
        /**
         * @param {HTMLElement} element
         */
        setModalElement: function (element) {
            var $element = $(element);
            
            // 直接调用，不检查 data 属性
            // 与原生代码逻辑一致
            // 兼容任何主题的 widget namespace
            setTimeout(function() {
                sidebarModel.setPopup($element);
            }, 0);
        }
    });
});