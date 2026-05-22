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
            // 扩展 catalogAddToCart，支持一步结账
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Folix_ChargeTemplate/js/catalog-add-to-cart-mixin': true
            }
        }
    }
};
