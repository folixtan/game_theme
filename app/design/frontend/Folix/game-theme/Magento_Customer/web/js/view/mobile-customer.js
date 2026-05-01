define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.customer = customerData.get('customer');

            // 页面加载时主动拉取最新状态，解决跨PC登录的缓存问题
            customerData.reload(['customer'], true);
        }
    });
});