/**
 * Folix Game Theme - Modal Component
 * 
 * 登录注册弹窗组件
 */
define([
    'jquery',
    'jquery/ui-modules/widget',
    'domReady!'
], function($) {
    'use strict';
    
    $.widget('folix.modal', {
        options: {},
        
        _create: function() {
            this.overlay = this.element.find('[data-role=modal-overlay]');
            this.content = this.element.find('[data-role=modal-content]');
            this.closeBtn = this.element.find('[data-role=modal-close]');
            this.tabs = this.element.find('.modal-tab');
            this.panels = this.element.find('.modal-panel');
            
            this._initModal();
        },
        
        _initModal: function() {
            var self = this;
            
            // Close button
            this.closeBtn.on('click', function() {
                self.close();
            });
            
            // Overlay click
            this.overlay.on('click', function() {
                self.close();
            });
            
            // Tab switching
            this.tabs.on('click', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.element.is(':visible')) {
                    self.close();
                }
            });
            
            // Trigger buttons
            $('[data-trigger=login-modal]').on('click', function(e) {
                e.preventDefault();
                self.open();
            });
        },
        
        open: function() {
            this.element.show();
            this.overlay.fadeIn(200);
            this.content.fadeIn(200);
            $('body').addClass('modal-open');
        },
        
        close: function() {
            var self = this;
            this.content.fadeOut(200);
            this.overlay.fadeOut(200, function() {
                self.element.hide();
            });
            $('body').removeClass('modal-open');
        },
        
        switchTab: function(tabName) {
            this.tabs.removeClass('active');
            this.tabs.filter('[data-tab=' + tabName + ']').addClass('active');
            
            this.panels.removeClass('active');
            this.panels.filter('#' + tabName + '-panel').addClass('active');
        }
    });
    
    return $.folix.modal;
});
