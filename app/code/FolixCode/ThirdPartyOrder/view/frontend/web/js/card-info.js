/**
 * Card Info Component - 卡密信息交互组件
 * 
 * 功能：
 * 1. 密码显示/隐藏切换
 * 2. 复制单个字段（卡号、密码）
 * 3. 复制所有卡密信息
 * 4. 适配深色/浅色主题
 * 5. 完美支持移动端（iOS/Android）
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
            $container.on('click', '.toggle-btn', handleTogglePassword);
            
            // 初始化 Clipboard.js 实例
            initClipboard();
            
            // 复制所有卡密
            $container.on('click', '#copyAllCards', handleCopyAllCards);
        }

        /**
         * 初始化 Clipboard.js
         */
        function initClipboard() {
            // 为每个复制按钮创建 Clipboard 实例
            var copyButtons = $container.find('.copy-btn').get();
            
            copyButtons.forEach(function(button) {
                var clipboard = new ClipboardJS(button, {
                    text: function(trigger) {
                        var $trigger = $(trigger);
                        var field = $trigger.data('field');
                        var $wrapper = $trigger.closest('.card-value-wrapper');
                        
                        if (field === 'card_no') {
                            return $wrapper.find('.card-no').text().trim();
                        } else if (field === 'card_pwd') {
                            return $wrapper.find('.plain').text().trim();
                        }
                        
                        return '';
                    }
                });

                // 复制成功事件
                clipboard.on('success', function(e) {
                    showCopySuccess($(e.trigger));
                    showNotification('Copied to clipboard!', 'success');
                    e.clearSelection();
                });

                // 复制失败事件
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
            var $wrapper = $btn.closest('.card-value-wrapper');
            var $masked = $wrapper.find('.masked');
            var $plain = $wrapper.find('.plain');
            var $copyPwdBtn = $wrapper.find('.copy-pwd-btn');
            var $toggleText = $btn.find('.toggle-text');
            var $eyeOpen = $btn.find('.eye-open');
            var $eyeClosed = $btn.find('.eye-closed');

            if ($masked.is(':visible')) {
                // 显示密码
                $masked.hide();
                $plain.show();
                $toggleText.text('Hide');
                $eyeOpen.hide();
                $eyeClosed.show();
                $copyPwdBtn.prop('disabled', false);
                
                // 重新初始化该按钮的 Clipboard 实例
                reinitClipboard($copyPwdBtn[0]);
            } else {
                // 隐藏密码
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

            // 销毁旧实例
            var existingInstance = ClipboardJS.getInstance(buttonElement);
            if (existingInstance) {
                existingInstance.destroy();
            }

            // 创建新实例
            var clipboard = new ClipboardJS(buttonElement, {
                text: function(trigger) {
                    var $wrapper = $(trigger).closest('.card-value-wrapper');
                    return $wrapper.find('.plain').text().trim();
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
         * 处理复制所有卡密
         */
        function handleCopyAllCards() {
            var $btn = $(this);
            var cards = [];

            $container.find('.folix-card-info').each(function (index) {
                var $card = $(this);
                var cardNo = $card.find('.card-no').text().trim();
                var cardPwd = $card.find('.plain').text().trim();
                var deadline = $card.find('.deadline-value').text().trim().replace('EXPIRED', '').trim();

                cards.push(
                    'Card #' + (index + 1) + ':\n' +
                    'Number: ' + cardNo + '\n' +
                    'Password: ' + (cardPwd ? cardPwd : '[Hidden - Click Show to reveal]') + '\n' +
                    'Expires: ' + deadline
                );
            });

            var allCardsText = cards.join('\n\n---\n\n');
            
            // 使用临时 textarea 复制到剪贴板
            copyToClipboardWithFallback(allCardsText, $btn);
        }

        /**
         * 使用降级方案复制到剪贴板（适用于"复制全部"等复杂场景）
         */
        function copyToClipboardWithFallback(text, $btn) {
            if (!text) {
                showNotification('Nothing to copy', 'error');
                return;
            }

            // 创建临时 textarea
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '-9999px';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            // 选中并复制
            textarea.focus();
            textarea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess($btn);
                    showNotification('Copied to clipboard!', 'success');
                } else {
                    showNotification('Failed to copy', 'error');
                }
            } catch (err) {
                console.error('Fallback copy failed:', err);
                showNotification('Failed to copy. Please try manually.', 'error');
            }

            document.body.removeChild(textarea);
        }

        /**
         * 显示复制成功状态
         */
        function showCopySuccess($btn) {
            var $copyText = $btn.find('.copy-text');
            var originalText = $copyText.text();

            $copyText.text('Copied!');
            $btn.addClass('copied');

            setTimeout(function () {
                $copyText.text(originalText);
                $btn.removeClass('copied');
            }, 2000);
        }

        /**
         * 显示通知消息
         */
        function showNotification(message, type) {
            // 尝试使用 Magento 的通知系统
            if (window.customerData && window.customerData.messages) {
                window.customerData.messages.add({
                    text: message,
                    type: type
                });
            } else {
                // 简单的 console 日志作为备选
                console.log('[' + type.toUpperCase() + '] ' + message);
                
                // 在移动端显示 toast 提示
                showToast(message, type);
            }
        }

        /**
         * 显示 Toast 提示（移动端友好）
         */
        function showToast(message, type) {
            // 检查是否已存在 toast
            var existingToast = document.querySelector('.folix-toast');
            if (existingToast) {
                existingToast.remove();
            }

            // 创建 toast 元素
            var toast = document.createElement('div');
            toast.className = 'folix-toast folix-toast-' + type;
            toast.textContent = message;
            
            // 添加样式
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

            // 根据类型设置背景色
            if (type === 'success') {
                toast.style.background = '#28a745';
            } else if (type === 'error') {
                toast.style.background = '#dc3545';
            } else {
                toast.style.background = '#6c757d';
            }

            document.body.appendChild(toast);

            // 3秒后自动消失
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

        // 初始化
        init();
    };
});
