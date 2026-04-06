/**
 * Copyright © Folix. All rights reserved.
 * Test Runner Configuration for Virtual Checkout Module
 */

var allTestFiles = [
    'view/**/*.js'
];

require.config({
    baseUrl: '../../../../../view/frontend/web/js',
    paths: {
        'jquery': '../../../../../../../../../vendor/magento/magento2-base/lib/web/jquery',
        'knockout': '../../../../../../../../../vendor/magento/magento2-base/lib/web/knockout/knockout',
        'Magento_Ui': '../../../../../../../../../vendor/magento/magento2-base/app/code/Magento/Ui/view/base/web/js',
        'Magento_Checkout': '../../../../../../../../../vendor/magento/magento2-base/app/code/Magento/Checkout/view/frontend/web/js',
        'Magento_Catalog': '../../../../../../../../../vendor/magento/magento2-base/app/code/Magento/Catalog/view/frontend/web/js',
        'Magento_Customer': '../../../../../../../../../vendor/magento/magento2-base/app/code/Magento/Customer/view/frontend/web/js',
        'squirejs': '../../../../../../../../../vendor/magento/magento2-base/node_modules/squirejs/src/Squire',
        'ko': 'knockout',
        'uiComponent': 'Magento_Ui/js/lib/core/collapsible',
        'text': 'Magento_Ui/js/lib/core/requirejs/text'
    },
    shim: {
        'squirejs': {
            exports: 'Squire'
        }
    },
    map: {
        '*': {
            'ko': 'knockout'
        }
    },
    // Load test specs
    deps: allTestFiles,
    // Have Jasmine start tests once specs are loaded
    callback: function () {
        window.onload();
    }
});
