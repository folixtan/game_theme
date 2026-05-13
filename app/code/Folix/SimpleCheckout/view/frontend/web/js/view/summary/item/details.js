define([
    "Magento_Checkout/js/view/summary/item/details",
    'mage/translate'
], function (Component,$t){

   'use strict';

    return Component.extend({
         defaults: {
            template: 'Folix_SimpleCheckout/summary/item/details'
        },
        getChargeAccountInfo: function(quoteItem) {
            const items = window.checkoutConfig.quoteItemData;
            var additional_data = {};

            if (items && items.length) {
                items.forEach(function(item) {
                    if (item.item_id == quoteItem.item_id && item.additional_data && item.additional_data.length > 0) {
                        additional_data = JSON.parse(item.additional_data);
                    }
                });
            }

            if (!Object.keys(additional_data).length) return '';

            // 将 key 转换为可读名称：下划线替换为空格，首字母大写
            var htmlParts = [];
            Object.keys(additional_data).forEach(function(key) {
                // 下划线替换为空格
                var readableKey = key.replace(/_/g, ' ');
                // 首字母大写（每个单词）
                readableKey = readableKey.replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });

                var value = additional_data[key];
                if (value !== undefined && value !== null && value !== '') {
                    htmlParts.push('<span>' + readableKey + ':' + value + '</span>');
                }
            });

            return htmlParts.join(' | ');
        }
    });
})