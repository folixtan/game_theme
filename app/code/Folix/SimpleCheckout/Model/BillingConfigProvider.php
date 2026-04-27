<?php
namespace Folix\SimpleCheckout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class BillingConfigProvider implements ConfigProviderInterface
{
    

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
          return [
                'defaultBillingAddress' => [
                    'firstname' => "checkout",
                    'lastname' => "chceckout",
                    'street' =>[ "checkout street 123"],
                    'city' =>   "Hong Kong",
                    'postcode' => '123456',
                    'country_id' => 'CN',
                    'telephone' => '12345678',
                    'region' => 'CN-HK',
                 //   'region_id' => $billingAddress->getRegionId(),
                ]
            ];
    }
}