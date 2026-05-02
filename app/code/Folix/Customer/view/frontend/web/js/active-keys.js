/**
 * Active Keys Component - 活跃卡密交互组件
 * 
 * 核心功能：
 * 1. 密码显示/隐藏切换
 * 2. 复制卡号、密码
 */
define([
    'jquery',
    'clipboard'
], function ($, ClipboardJS) {
    'use strict';

    return function (config, element) {
        var $container = $(element);
        var clipboardInstances = [];

        /**
         * 初始化
         */
        function init() {
            // 密码显示/隐藏切换
            $container.on('click', '.btn-toggle', handleTogglePassword);
            
            // 初始化 Clipboard.js 实例
            initClipboard();
        }

        /**
         * 初始化 Clipboard.js
         */
        function initClipboard() {
            var copyButtons = $container.find('.btn-copy').get();
            
            copyButtons.forEach(function(button) {
                createClipboardInstance(button);
            });
        }

        /**
         * 创建 Clipboard 实例
         */
        function createClipboardInstance(buttonElement) {
            if (!buttonElement) return;

            var clipboard = new ClipboardJS(buttonElement, {
                text: function(trigger) {
                    var $trigger = $(trigger);
                    var field = $trigger.data('field');
                    var $item = $trigger.closest('.key-item');
                    
                    if (field === 'card_no') {
                        return $item.find('.key-code').text().trim();
                    } else if (field === 'card_pwd') {
                        return $item.find('.key-password-plain').text().trim();
                    }
                    
                    return '';
                }
            });

            // 复制成功事件
            clipboard.on('success', function(e) {
                showCopySuccess($(e.trigger));
                e.clearSelection();
            });

            // 复制失败事件
            clipboard.on('error', function(e) {
                console.error('Copy failed:', e);
            });

            clipboardInstances.push(clipboard);
        }

        /**
         * 处理密码显示/隐藏切换
         */
        function handleTogglePassword() {
            var $btn = $(this);
            var $item = $btn.closest('.key-item');
            var $masked = $item.find('.key-password-masked');
            var $plain = $item.find('.key-password-plain');
            var $copyPwdBtn = $item.find('.btn-copy-pwd');
            var $toggleText = $btn.find('.toggle-text');

            if ($masked.is(':visible')) {
                // 显示密码
                $masked.hide();
                $plain.show();
                $toggleText.text('Hide');
                $copyPwdBtn.prop('disabled', false);
                
                // 重新初始化该按钮的 Clipboard 实例
                reinitClipboard($copyPwdBtn[0]);
            } else {
                // 隐藏密码
                $masked.show();
                $plain.hide();
                $toggleText.text('Show');
                $copyPwdBtn.prop('disabled', true);
            }
        }

        /**
         * 重新初始化单个按钮的 Clipboard 实例
         */
        function reinitClipboard(buttonElement) {
            if (!buttonElement) return;

            // 查找并销毁旧的实例
            for (var i = clipboardInstances.length - 1; i >= 0; i--) {
                if (clipboardInstances[i]._action && clipboardInstances[i]._action.container === buttonElement) {
                    clipboardInstances[i].destroy();
                    clipboardInstances.splice(i, 1);
                    break;
                }
            }

            // 创建新实例
            createClipboardInstance(buttonElement);
        }

        /**
         * 显示复制成功状态
         */
        function showCopySuccess($btn) {
            var originalText = $btn.html();
            $btn.html('✓ Copied!');

            setTimeout(function () {
                $btn.html(originalText);
            }, 2000);
        }

        // 初始化
        init();
    };
});
