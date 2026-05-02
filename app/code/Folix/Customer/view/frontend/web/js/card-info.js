/**
 * Card Info Component - 卡密信息交互组件
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
            $container.on('click', '.toggle-btn', handleTogglePassword);
            initClipboard();
        }

        /**
         * 初始化 Clipboard.js
         */
        function initClipboard() {
            var copyButtons = $container.find('.copy-btn').get();
            
            copyButtons.forEach(function(button) {
                var clipboard = new ClipboardJS(button, {
                    text: function(trigger) {
                        var $trigger = $(trigger);
                        var field = $trigger.data('field');
                        var $cell = $trigger.closest('td');
                        
                        if (field === 'card_no') {
                            return $cell.find('.card-no').text().trim();
                        } else if (field === 'card_pwd') {
                            return $cell.find('.plain').text().trim();
                        }
                        
                        return '';
                    }
                });

                clipboard.on('success', function(e) {
                    showCopySuccess($(e.trigger));
                    showNotification('Copied to clipboard!', 'success');
                    e.clearSelection();
                });

                clipboard.on('error', function(e) {
                    console.error('Copy failed:', e);
                    showNotification('Failed to copy. Please try manually.', 'error');
                });

                clipboardInstances.push(clipboard);
            });
        }

        /**
         * 处理密码显示/隐藏切换
         */
        function handleTogglePassword() {
            var $btn = $(this);
            var $cell = $btn.closest('td');
            var $masked = $cell.find('.masked');
            var $plain = $cell.find('.plain');
            var $copyPwdBtn = $cell.find('.copy-pwd-btn');
            var $toggleText = $btn.find('.toggle-text');
            var $eyeOpen = $btn.find('.eye-open');
            var $eyeClosed = $btn.find('.eye-closed');

            if ($masked.is(':visible')) {
                $masked.hide();
                $plain.show();
                $toggleText.text('Hide');
                $eyeOpen.hide();
                $eyeClosed.show();
                $copyPwdBtn.prop('disabled', false);
                
                reinitClipboard($copyPwdBtn[0]);
            } else {
                $masked.show();
                $plain.hide();
                $toggleText.text('Show');
                $eyeOpen.show();
                $eyeClosed.hide();
                $copyPwdBtn.prop('disabled', true);
            }
        }

        /**
         * 重新初始化单个按钮的 Clipboard 实例
         */
        function reinitClipboard(buttonElement) {
            if (!buttonElement) return;

            var existingInstance = ClipboardJS.getInstance(buttonElement);
            if (existingInstance) {
                existingInstance.destroy();
            }

            var clipboard = new ClipboardJS(buttonElement, {
                text: function(trigger) {
                    var $cell = $(trigger).closest('td');
                    return $cell.find('.plain').text().trim();
                }
            });

            clipboard.on('success', function(e) {
                showCopySuccess($(e.trigger));
                showNotification('Copied to clipboard!', 'success');
                e.clearSelection();
            });

            clipboard.on('error', function(e) {
                console.error('Copy failed:', e);
                showNotification('Failed to copy. Please try manually.', 'error');
            });

            clipboardInstances.push(clipboard);
        }

        /**
         * 显示复制成功状态
         */
        function showCopySuccess($btn) {
            var $span = $btn.find('span');
            var originalText = $span.text();

            $span.text('Copied!');
            $btn.addClass('copied');

            setTimeout(function () {
                $span.text(originalText);
                $btn.removeClass('copied');
            }, 2000);
        }

        /**
         * 显示通知消息
         */
        function showNotification(message, type) {
            if (window.customerData && window.customerData.messages) {
                window.customerData.messages.add({
                    text: message,
                    type: type
                });
            } else {
                console.log('[' + type.toUpperCase() + '] ' + message);
                showToast(message, type);
            }
        }

        /**
         * 显示 Toast 提示
         */
        function showToast(message, type) {
            var existingToast = document.querySelector('.folix-toast');
            if (existingToast) {
                existingToast.remove();
            }

            var toast = document.createElement('div');
            toast.className = 'folix-toast folix-toast-' + type;
            toast.textContent = message;
            
            Object.assign(toast.style, {
                position: 'fixed',
                bottom: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                padding: '12px 24px',
                borderRadius: '8px',
                color: '#fff',
                fontSize: '14px',
                fontWeight: '500',
                zIndex: '9999',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                animation: 'fadeInUp 0.3s ease',
                maxWidth: '90%',
                textAlign: 'center'
            });

            if (type === 'success') {
                toast.style.background = '#28a745';
            } else if (type === 'error') {
                toast.style.background = '#dc3545';
            } else {
                toast.style.background = '#6c757d';
            }

            document.body.appendChild(toast);

            setTimeout(function() {
                toast.style.animation = 'fadeOutDown 0.3s ease';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // 添加动画关键帧
        var style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
            @keyframes fadeOutDown {
                from {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(-50%) translateY(20px);
                }
            }
        `;
        document.head.appendChild(style);

        init();
    };
});
