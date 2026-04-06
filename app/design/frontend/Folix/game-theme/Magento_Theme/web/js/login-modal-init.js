/**
 * Folix Game Theme - Login Modal Initialization
 * 
 * 初始化登录弹窗的 tabs 切换和触发器
 */
define([
    'jquery'
], function($) {
    'use strict';
    
    return function() {
        var $modal = $('[data-role=login-modal]');
        var $tabs = $modal.find('.modal-tab');
        var $panels = $modal.find('.modal-panel');
        
        // Tabs switching
        $tabs.on('click', function() {
            var tabName = $(this).data('tab');
            
            $tabs.removeClass('active');
            $(this).addClass('active');
            
            $panels.removeClass('active');
            $modal.find('#' + tabName + '-panel').addClass('active');
        });
        
        // Trigger buttons
        $('[data-trigger=login-modal]').on('click', function(e) {
            e.preventDefault();
            $modal.modal('openModal');
        });
    };
});
