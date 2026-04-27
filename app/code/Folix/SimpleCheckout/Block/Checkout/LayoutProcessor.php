<?php
declare(strict_types=1);
namespace Folix\SimpleCheckout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as baseLayoutProcessor;


class LayoutProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{ 

/**
 * Undocumented function
 *
 * @param [type] $jsLayout
 * @return void
 */
   public function process($jsLayout)
   {
    
     if(isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form'])) {
        unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']);
     }
       //if(isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']))
      // $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id']['config']['elementTmpl'] = 'Folix_SimpleCheckout/element/country';

      //var_dump($jsLayout);exit;
       return $jsLayout;
   }
}

