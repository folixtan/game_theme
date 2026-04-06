# Folix VirtualCheckout Module

## Overview

This module implements a one-page checkout experience for virtual products (game top-ups, virtual currencies, etc.).

## Features

- **Single-page checkout**: All checkout steps on one page
- **No shipping address**: Optimized for virtual products
- **Recharge information display**: Shows user-provided recharge details (read-only)
- **Auth integration**: Login/Register form for guest customers
- **Google reCAPTCHA**: Preserved from Magento default checkout
- **Theme colors**: Uses Folix game theme color scheme

## Installation

1. Enable the module:
```bash
php bin/magento module:enable Folix_VirtualCheckout
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:clean
```

## Configuration

### Product Page Setup

Add recharge information fields to your product page template. These will be saved to quote items:

```php
// Add custom fields to product form
<input type="text" name="recharge_userid" id="recharge-userid"/>
<select name="recharge_server" id="recharge-server">
    <option value="global">Global Server</option>
    <option value="asia">Asia Server</option>
</select>
```

### Quote Extension

Extend quote item with custom attributes:

```php
// app/code/Folix/VirtualCheckout/etc/extension_attributes.xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Api/etc/extension_attributes.xsd">
    <extension_attributes for="Magento\Quote\Api\Data\CartItemInterface">
        <attribute code="recharge_userid" type="string"/>
        <attribute code="recharge_server" type="string"/>
        <attribute code="recharge_amount" type="string"/>
    </extension_attributes>
</config>
```

## Layout Structure

### Left Panel (60%)
- Auth Form (login/register for guests)
- Order Information (product list with details)
- Recharge Information (read-only display)

### Right Panel (40%)
- Payment Methods (WeChat, Alipay, Credit Card)
- Order Summary (totals)
- Place Order Button
- Google reCAPTCHA

## Theme Colors

- Primary Button: `#4A90E2` (Blue)
- Accent/Selected: `#FF6B35` (Orange-Red)
- Text: `#1E293B` (Dark Gray)
- Border: `#E2E8F0` (Light Gray)

## Files Structure

```
app/code/Folix/VirtualCheckout/
├── Plugin/
│   └── LayoutProcessorPlugin.php      # Modifies checkout layout
├── view/frontend/
│   ├── layout/
│   │   ├── checkout_index_index.xml   # Checkout page layout
│   │   └── default.xml              # CSS inclusion
│   ├── web/
│   │   ├── css/
│   │   │   └── source/
│   │   │       └── _virtual-checkout.less  # Styles
│   │   ├── js/view/
│   │   │   ├── auth-form.js
│   │   │   ├── order-info.js
│   │   │   ├── recharge-info-display.js
│   │   │   └── payment-summary.js
│   │   └── template/
│   │       ├── auth-form.html
│   │       ├── order-info.html
│   │       ├── recharge-info-display.html
│   │       └── payment-summary.html
└── etc/
    ├── module.xml
    ├── di.xml
    └── registration.php
```

## Testing

1. Add a virtual product to cart with recharge information
2. Proceed to checkout
3. Verify:
   - No shipping address fields shown
   - Recharge info displays correctly
   - Login form shows for guests
   - Google reCAPTCHA appears
   - Theme colors are applied

## Troubleshooting

**Recharge info not showing**: Ensure quote items have the custom attributes set before checkout.

**Layout not updating**: Run `setup:static-content:deploy` and clear cache.

**Google reCAPTCHA missing**: Check that the `before-place-order` component is preserved in layout.

## License

Copyright © Folix. All rights reserved.
