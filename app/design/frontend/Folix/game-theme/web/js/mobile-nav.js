/**
 * Folix Game Theme - Mobile Navigation
 *
 * 控制移动端汉堡菜单和侧边栏
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            // Toggle Navigation
            $('.folix-nav-toggle').on('click', function (e) {
                e.preventDefault();
                toggleMobileNav();
            });

            // Close Navigation on Overlay Click
            $('.folix-nav-overlay').on('click', function (e) {
                e.preventDefault();
                closeMobileNav();
            });

            // Close Navigation on Resize
            $(window).on('resize', function () {
                if ($(window).width() > 768) {
                    closeMobileNav();
                }
            });

            // Toggle Section (Menu / Account / Settings)
            $('.section-item-title a').on('click', function (e) {
                var $this = $(this);
                var targetId = $this.attr('href');

                if ($(window).width() <= 768) {
                    e.preventDefault();

                    // Close all sections
                    $('.section-item-content').removeClass('active');
                    $('.section-item-title a').removeClass('active');

                    // Open target section
                    $(targetId).addClass('active');
                    $this.addClass('active');
                }
            });

            // Close Navigation
            function toggleMobileNav() {
                $('.folix-nav-toggle').toggleClass('active');
                $('.navigation.sections').toggleClass('active');
                $('.folix-nav-overlay').toggleClass('active');

                if ($('.navigation.sections').hasClass('active')) {
                    $('body').css('overflow', 'hidden');
                } else {
                    $('body').css('overflow', '');
                }
            }

            // Close Navigation
            function closeMobileNav() {
                $('.folix-nav-toggle').removeClass('active');
                $('.navigation.sections').removeClass('active');
                $('.folix-nav-overlay').removeClass('active');
                $('body').css('overflow', '');
            }
        });
    };
});
