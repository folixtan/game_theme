<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PortGame\Email\Model\Container\Template;
use PortGame\Email\Model\SenderBuilderFactory;

class OrderPlaceAfter implements ObserverInterface
{ 

   public  function __construct(
    private Template $template, 
    private SenderBuilderFactory $senderBuilderFactory
    )
   {
   }

   public function execute(Observer $observer)
   {
    /**
     * @var \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     */
         $data      = $observer->getEvent()->getItem();
      

         $templateVar = [
            'product_name' => $data['product_name'] ?? '',
             'product_sku' => $data['product_sku'] ??'',
             'entity_id' =>  $data['entity_id'] ?? '',
             'charge_account' => $data['charge_account'] ?? '',
              'charge_type' => $data['charge_type'] ?? '',
              'customer_name' => $data['customer_name'] ?? 'Guest',
              'charge_amount' => $data['charge_amount'] ?? '',
              'goods_type' => $data['goods_type'] ?? '',
              'order_increment_id' => $data['increment_id'] ?? '',
              'charge_region' => $data['charge_region'] ?? '',
              'card_number' => $data['card_no'] ?? '',
               'password' => $data['card_pwd'] ?? '',
               'expiry_date' => $data['card_deadline'] ?? ''
         ];
         $this->template->setTemplateVars($templateVar);

         $this->senderBuilder()->send( $data['customer_email'], $data['customer_name'] ?? 'Guest');
   }

   private function senderBuilder()
   {
       return $this->senderBuilderFactory->create([
           'templateContainer' => $this->template
       ]);
   }
}