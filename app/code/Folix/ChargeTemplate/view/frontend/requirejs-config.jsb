/**
 * ChargeTemplate RequireJS Configuration
 * 
 * 使用 mixin 机制扩展 Magento 原生的 catalogAddToCart widget
 */

var config = {
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Folix_ChargeTemplate/js/catalog-add-to-cart-mixin': true
            }
        }
    }
};
