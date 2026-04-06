/**
 * Copyright © Folix. All rights reserved.
 * Cart Items List Mixin - Add getImageData method
 */
define([], function () {
    'use strict';

    return function (target) {
        return target.extend({
            /**
             * Get image data for an item
             * @param {Object} item
             * @return {Object}
             */
            getImageData: function (item) {
                var imageData = window.checkoutConfig.imageData;
                if (imageData && imageData[item.item_id]) {
                    return imageData[item.item_id];
                }
                return {};
            }
        });
    };
});
