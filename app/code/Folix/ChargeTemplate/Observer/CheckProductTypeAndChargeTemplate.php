<?php
declare(strict_types=1);
namespace Folix\ChargeTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;
use Magento\Framework\Exception\LocalizedException;
class CheckProductTypeAndChargeTemplate implements ObserverInterface
{ 

    private $jsonSerializer;

   


    public function __construct(
        Json $jsonSerializer
    ) {
        $this->jsonSerializer = $jsonSerializer;
    }

    public function execute(Observer $observer):void
    {
        $product = $observer->getProduct();

        if($product->getTypeId() != 'virtual') {
             return;
        }
        $info   = $observer->getInfo();
        //var_dump($info,$product->getData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE) );exit;
        if((int)$product->getData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE) == AddProductAttributes::CHARGE_TYPE_DIRECT) {
            //charge_template
            if(empty($info['charge_template'])) {
             //   $product->setOptionsValidationFail(true);
                throw new LocalizedException(__('Please select charge template'));
            }
        }
        

    }
}