var config = {
    map: {
        '*': {
            'Folix_ChargeTemplate/js/charge-template-customer-data': 'Folix_ChargeTemplate/js/charge-template-customer-data'
        }
    },
    config: {
        mixins: {
            // 扩展 Swatches Renderer（如果产品使用 Swatches）
            'Magento_Swatches/js/swatch-renderer': {
                'Folix_ChargeTemplate/js/swatch-renderer-charge-template-mixin': true
            },
            // 扩展原生 Configurable（如果产品使用传统下拉框）
            'Magento_ConfigurableProduct/js/configurable': {
                'Folix_ChargeTemplate/js/configurable-charge-template-mixin': true
            }
        }
    }
};
