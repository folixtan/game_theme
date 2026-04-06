/**
 * Folix Game Theme - Hero Slider Component
 * 
 * 主页轮播组件
 */
define([
    'jquery',
    'jquery/ui-modules/widget',
    'domReady!'
], function($) {
    'use strict';
    
    $.widget('folix.slider', {
        options: {
            autoplay: true,
            autoplaySpeed: 5000,
            dots: true,
            arrows: true
        },
        
        _create: function() {
            this.slides = this.element.find('.slide');
            this.dots = this.element.find('.slider-dot');
            this.prevBtn = this.element.find('.slider-arrow-prev');
            this.nextBtn = this.element.find('.slider-arrow-next');
            this.currentIndex = 0;
            this.autoplayTimer = null;
            
            this._initSlider();
        },
        
        _initSlider: function() {
            var self = this;
            
            // Dot click handlers
            this.dots.on('click', function() {
                var index = $(this).data('slide-index');
                self.goToSlide(index);
            });
            
            // Arrow click handlers
            if (this.options.arrows) {
                this.prevBtn.on('click', function() {
                    self.prevSlide();
                });
                
                this.nextBtn.on('click', function() {
                    self.nextSlide();
                });
            }
            
            // Start autoplay
            if (this.options.autoplay) {
                this._startAutoplay();
                
                // Pause on hover
                this.element.on('mouseenter', function() {
                    self._stopAutoplay();
                }).on('mouseleave', function() {
                    self._startAutoplay();
                });
            }
        },
        
        goToSlide: function(index) {
            if (index < 0) {
                index = this.slides.length - 1;
            } else if (index >= this.slides.length) {
                index = 0;
            }
            
            this.slides.removeClass('active');
            this.slides.eq(index).addClass('active');
            
            this.dots.removeClass('active');
            this.dots.eq(index).addClass('active');
            
            this.currentIndex = index;
        },
        
        nextSlide: function() {
            this.goToSlide(this.currentIndex + 1);
        },
        
        prevSlide: function() {
            this.goToSlide(this.currentIndex - 1);
        },
        
        _startAutoplay: function() {
            var self = this;
            
            this.autoplayTimer = setInterval(function() {
                self.nextSlide();
            }, this.options.autoplaySpeed);
        },
        
        _stopAutoplay: function() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
            }
        }
    });
    
    return $.folix.slider;
});
