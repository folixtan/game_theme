<?php
declare(strict_types=1);
namespace Folix\ChargeTemplate\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;
use Magento\Framework\Exception\LocalizedException;

class UpdateChargeTemplateInCart implements ObserverInterface
{ 

 public function __construct( 
  private  RequestInterface $request,
   private Json $jsonSerializer
 )
 {
 }
  public function execute(Observer $observer)
  {
      $quoteItem = $observer->getEvent()->getQuoteItem();
      $product = $quoteItem->getProduct();

      if(
        (int)$product->getData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE)
         === AddProductAttributes::CHARGE_TYPE_CARD
         ) 
         {
            return;
         }

     $templateArr = $this->request->getParam('charge_template');
    
     if(empty($templateArr)) throw new LocalizedException(__('Charge template is empty'));

     $templateStr = $quoteItem->getData('additional_data');
     $json = $this->jsonSerializer->serialize($templateArr);

     if($templateStr === $json) return;
    

      $quoteItem->setData('additional_data', $json);
  }
}