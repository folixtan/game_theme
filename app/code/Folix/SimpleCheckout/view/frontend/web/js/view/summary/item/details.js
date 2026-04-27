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
           var  additional_data = {};
            let  account = $t('Charge Account');
            let  region = $t('Charge Region');
            if(items &&  items.length) {
                       items.forEach(function(item) {
                           if(item.item_id == quoteItem.item_id && item.additional_data && item.additional_data.length > 0 ) {
                               additional_data = JSON.parse(item.additional_data);
                           }
                       })
            }
           
            if(!Object.keys(additional_data).length ) return '';

            return `<span>${account}:${additional_data.charge_account}</span>|<span> ${region}:${ additional_data.charge_region === undefined ? "" :  additional_data.charge_region}</span>`
          
        }
    });
})