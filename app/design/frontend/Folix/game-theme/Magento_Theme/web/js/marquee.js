/**
 * Folix Game Theme - Marquee Component
 * 
 * 跑马灯公告组件
 */
define([
    'jquery',
    'jquery/ui-modules/widget',
    'domReady!'
], function($) {
    'use strict';
    
    $.widget('folix.marquee', {
        options: {
            speed: 30,
            pauseOnHover: true
        },
        
        _create: function() {
            this.container = this.element.find('.marquee-container');
            this.content = this.element.find('.marquee-content');
            this.isAnimating = false;
            this.animationId = null;
            
            this._initMarquee();
        },
        
        _initMarquee: function() {
            var self = this;
            
            // 克隆内容以实现无缝滚动
            this.content.append(this.content.html());
            
            // 开始动画
            this._startAnimation();
            
            // 悬停暂停
            if (this.options.pauseOnHover) {
                this.element.on('mouseenter', function() {
                    self._stopAnimation();
                }).on('mouseleave', function() {
                    self._startAnimation();
                });
            }
        },
        
        _startAnimation: function() {
            var self = this;
            var position = 0;
            var contentWidth = this.content[0].scrollWidth / 2;
            
            this.isAnimating = true;
            
            function animate() {
                if (!self.isAnimating) return;
                
                position -= 1;
                
                if (Math.abs(position) >= contentWidth) {
                    position = 0;
                }
                
                self.content.css('transform', 'translateX(' + position + 'px)');
                
                self.animationId = requestAnimationFrame(animate);
            }
            
            animate();
        },
        
        _stopAnimation: function() {
            this.isAnimating = false;
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
            }
        }
    });
    
    return $.folix.marquee;
});
