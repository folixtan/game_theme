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
         $orderItem = $observer->getEvent()->getItem();
         $data      = $observer->getEvent()->getData();
         $response  = $observer->getEvent()->getResponse();

         $this->template->setTemplateId('portgame_email_thirdparty_card');

         $templateVar = [
            'product_name' => $orderItem->getName(),
             'product_sku' => $orderItem->getSku(),
             'charge_account' => $data['charge_account'] ?? '',
              'charge_type' => $data['charge_type'] ?? '',
              'customer_name' => $response['customer_name'] ?? 'Guest',
              'charge_amount' => $data['charge_amount'] ?? '',
              'goods_type' => $data['goods_type'] ?? '',
              'order_increment_id' => $response['increment_id'] ?? '',
              'charge_region' => $data['charge_region'] ?? '',
              'card_number' => $data['card_no'] ?? '',
               'password' => $data['card_pwd'] ?? '',
               'expiry_date' => $data['card_deadline'] ?? ''
         ];
         $this->template->setTemplateVars($templateVar);

         $this->senderBuilder()->send( $response['email'], $response['customer_name'] ?? 'Guest');
   }

   private function senderBuilder()
   {
       return $this->senderBuilderFactory->create([
           'templateContainer' => $this->template
       ]);
   }
}