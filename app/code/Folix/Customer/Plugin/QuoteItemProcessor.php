<?php
declare(strict_types=1);
namespace Folix\Customer\Plugin;
use Magento\Quote\Model\Quote\Item;


class QuoteItemProcessor
{
    
/**
 * Undocumented function
 *
 * @param \Magento\Quote\Model\Quote\Item\Processor $subject
 * @param \Closure $proceed
 * @param \Magento\Catalog\Model\Product $product
 * @param \Magento\Framework\DataObject $request
 * @return void
 */
   public function aroundInit(
       \Magento\Quote\Model\Quote\Item\Processor $subject,
       \Closure $proceed,
       \Magento\Catalog\Model\Product $product,
       \Magento\Framework\DataObject $request
   ):Item {
       $item = $proceed($product, $request);
         if(!$item->getData('additional_data') && $request->getData('charge_template')) {
            $item->setData('additional_data', json_encode( $request->getData('charge_template')));
        }
        return $item;
   }
}