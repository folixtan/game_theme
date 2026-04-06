# Virtual Checkout Installation Guide

## 1. Enable the Module

```bash
php bin/magento module:enable Folix_VirtualCheckout
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## 2. Add Recharge Form to Product Page

### Option 1: Via Layout XML

Add this to your theme's product page layout:

```xml
<!-- app/design/frontend/Folix/game-theme/Magento_Catalog/layout/catalog_product_view.xml -->
<referenceContainer name="product.info.main">
    <block class="Magento\Framework\View\Element\Template"
           name="product.recharge.form"
           template="Folix_VirtualCheckout::product-recharge-form.phtml"
           after="product.info.price"/>
</referenceContainer>
```

### Option 2: Directly in PHTML Template

Add to your product template:

```php
<?php echo $this->getLayout()
    ->createBlock('Magento\Framework\View\Element\Template')
    ->setTemplate('Folix_VirtualCheckout::product-recharge-form.phtml')
    ->toHtml();
?>
```

## 3. Add to Cart Controller Plugin

Create a plugin to save recharge info when adding to cart:

```php
<?php
// app/code/Folix/VirtualCheckout/Plugin/AddToCartPlugin.php
namespace Folix\VirtualCheckout\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\RequestInterface;

class AddToCartPlugin
{
    protected $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function afterAddProduct(Cart $subject, $result, $productInfo, $requestInfo = null)
    {
        $rechargeData = $this->request->getParam('recharge');
        
        if ($rechargeData && isset($rechargeData['userid'])) {
            $result->setRechargeUserid($rechargeData['userid']);
            $result->setRechargeServer($rechargeData['server']);
            $result->setRechargeAmount($rechargeData['amount']);
            $result->setRechargeType('direct');
            
            // Save to quote item
            $quote = $subject->getQuote();
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $result->getProductId()) {
                    $item->setRechargeUserid($rechargeData['userid']);
                    $item->setRechargeServer($rechargeData['server']);
                    $item->setRechargeAmount($rechargeData['amount']);
                    $item->setRechargeType('direct');
                    $item->save();
                    break;
                }
            }
        }
        
        return $result;
    }
}
```

Add to `etc/di.xml`:
```xml
<type name="Magento\Checkout\Model\Cart">
    <plugin name="folix_virtualcheckout_add_to_cart" type="Folix\VirtualCheckout\Plugin\AddToCartPlugin" sortOrder="10"/>
</type>
```

## 4. Database Tables (Optional)

If you want to store recharge info in separate tables, run:

```sql
CREATE TABLE `quote_item_recharge_info` (
  `item_id` int(11) NOT NULL,
  `recharge_userid` varchar(255) DEFAULT NULL,
  `recharge_server` varchar(255) DEFAULT NULL,
  `recharge_amount` varchar(255) DEFAULT NULL,
  `recharge_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `idx_recharge_userid` (`recharge_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sales_order_item_recharge_info` (
  `item_id` int(11) NOT NULL,
  `recharge_userid` varchar(255) DEFAULT NULL,
  `recharge_server` varchar(255) DEFAULT NULL,
  `recharge_amount` varchar(255) DEFAULT NULL,
  `recharge_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 5. Enable Google reCAPTCHA

Go to **Stores > Configuration > Security > Google reCAPTCHA** and:

1. Select "Frontend" for Storefront
2. Add your Site Key and Secret Key
3. Enable for "Create Customer" and "Checkout"

## 6. Configure Payment Methods

Enable your payment methods (WeChat Pay, Alipay, etc.) in:

**Stores > Configuration > Sales > Payment Methods**

## 7. Test the Flow

1. Go to a virtual product page
2. Fill in recharge form (User ID, Server, Amount)
3. Click "Add to Cart"
4. Go to Checkout
5. Verify:
   - Shipping address is hidden
   - Recharge info displays on checkout page
   - Login form shows for guest users
   - Google reCAPTCHA appears
   - Payment methods display correctly
   - Theme colors are applied

## 8. Troubleshooting

### Module Not Working
```bash
php bin/magento module:status
php bin/magento cache:clean
php bin/magento setup:upgrade
```

### Styles Not Loading
```bash
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

### Recharge Info Not Showing
- Check quote item has custom attributes set
- Verify LayoutProcessor plugin is running
- Clear cache

### Google reCAPTCHA Missing
- Check if `before-place-order` component exists in layout
- Verify reCAPTCHA is configured correctly in admin

## 9. Customization

### Modify Colors

Edit `view/frontend/web/css/source/_virtual-checkout.less`:

```less
// Change button color
.button.primary {
    background: #YOUR_COLOR;
}
```

### Add More Recharge Fields

1. Add fields to `product-recharge-form.phtml`
2. Update extension attributes in `etc/extension_attributes.xml`
3. Update LayoutProcessor plugin to pass to frontend

## Support

For issues or questions, check:
- System logs: `var/log/system.log`
- Exception logs: `var/log/exception.log`
- Enable developer mode for debugging: `php bin/magento deploy:mode:set developer`
