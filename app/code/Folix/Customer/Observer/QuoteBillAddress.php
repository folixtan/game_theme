<?php
declare(strict_types=1);
namespace Folix\Customer\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Folix\SimpleCheckout\Model\BillingConfigProvider;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Address;

class QuoteBillAddress implements ObserverInterface
{

  public function __construct(
     private  BillingConfigProvider $billingConfigProvider,
     private AddressFactory $addressFactory
    )
  {
  }
    public function execute(Observer $observer):void
    {
        /**
         * @var \Magento\Quote\Model\Quote $quote;
         */
        $quote = $observer->getEvent()->getQuote();
        $billingAddress = $quote->getBillingAddress();
         $billingAddress->setAddressType(Address::ADDRESS_TYPE_BILLING);
        /**
         * @var \Magento\Quote\Api\Data\AddressInterface| \Magento\Quote\Model\Quote\Address $billingAddress
         */
       if(!(
        $billingAddress->getFirstname() 
       || $billingAddress->getCountryId())) {
           $config = $this->billingConfigProvider->getConfig()['defaultBillingAddress'];
            /**
             *  [
              *      'firstname' => "checkout",
             *       'lastname' => "chceckout",
             *       'street' =>[ "checkout street 123"],
              *      'city' =>   "Hong Kong",
             *       'postcode' => '123456',
             *       'country_id' => 'CN',
             *       'telephone' => '12345678',
             *       'region' => 'CN-HK',
             *    //   'region_id' => $billingAddress->getRegionId(),
             *   ]
             */
             $billingAddress->setFirstname($config['firstname'])
             ->setLastname($config['lastname'])
             ->setCountryId($config['country_id'])
             ->setStreet($config['street'])
             ->setCity($config['city'])
             ->setPostcode($config['postcode'])
             ->setRegion($config['region'])
             ->setTelephone($config['telephone']);
        }
         
           
    } 
}