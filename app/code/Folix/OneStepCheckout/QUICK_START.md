# 虚拟充值一步结账 - 快速开始

## 功能概述

✅ 单页结账 - 所有步骤在一个页面
✅ 无需地址 - 针对虚拟产品优化
✅ 充值信息显示 - 只读显示用户填写的充值详情
✅ 登录表单 - 客人可登录/注册
✅ Google 验证码 - 保留原 Magento 功能
✅ 主题配色 - 使用 Folix 游戏主题色系

## 文件结构

```
app/code/Folix/VirtualCheckout/
├── etc/
│   ├── module.xml                    # 模块配置
│   ├── di.xml                        # 依赖注入配置
│   └── extension_attributes.xml      # 扩展属性
├── Plugin/
│   ├── LayoutProcessorPlugin.php     # 修改结账布局
│   └── QuoteItemPlugin.php           # 订单项处理
└── view/frontend/
    ├── layout/
    │   ├── checkout_index_index.xml  # 结账页面布局
    │   └── default.xml              # 样式引入
    ├── web/
    │   ├── css/source/
    │   │   └── _virtual-checkout.less  # 样式文件
    │   ├── js/view/
    │   │   ├── auth-form.js          # 登录表单
    │   │   ├── order-info.js         # 订单信息
    │   │   ├── recharge-info-display.js  # 充值信息显示
    │   │   └── payment-summary.js    # 支付摘要
    │   ├── template/
    │   │   ├── auth-form.html
    │   │   ├── order-info.html
    │   │   ├── recharge-info-display.html
    │   │   └── payment-summary.html
    │   └── js/
    │       └── recharge-form.js     # 产品页面充值表单
    └── templates/
        └── product-recharge-form.phtml  # 产品页面表单模板
```

## 安装步骤

### 1. 启用模块

```bash
php bin/magento module:enable Folix_VirtualCheckout
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

### 2. 将充值表单添加到产品页面

在主题布局文件中添加：

```xml
<!-- app/design/frontend/Folix/game-theme/Magento_Catalog/layout/catalog_product_view.xml -->
<referenceContainer name="product.info.main">
    <block class="Magento\Framework\View\Element\Template"
           name="product.recharge.form"
           template="Folix_VirtualCheckout::product-recharge-form.phtml"
           after="product.info.price"/>
</referenceContainer>
```

### 3. 创建添加到购物车插件

创建文件：`app/code/Folix/VirtualCheckout/Plugin/AddToCartPlugin.php`

```php
<?php
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
            
            $quote = $subject->getQuote();
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $result->getProductId()) {
                    $item->setRechargeUserid($rechargeData['userid']);
                    $item->setRechargeServer($rechargeData['server']);
                    $item->setRechargeAmount($rechargeData['amount']);
                    $item->setRechargeType('direct');
                    break;
                }
            }
            $quote->save();
        }
        
        return $result;
    }
}
```

在 `etc/di.xml` 中添加：

```xml
<type name="Magento\Checkout\Model\Cart">
    <plugin name="folix_virtualcheckout_add_to_cart" type="Folix\VirtualCheckout\Plugin\AddToCartPlugin" sortOrder="10"/>
</type>
```

### 4. 配置 Google reCAPTCHA

进入 **后台 > Stores > Configuration > Security > Google reCAPTCHA**

1. 选择 "Frontend"
2. 输入 Site Key 和 Secret Key
3. 启用 "Create Customer" 和 "Checkout"

## 使用流程

```
产品页面
├─ 用户填写充值信息
│  ├─ User ID
│  ├─ Server
│  └─ Amount
├─ 点击"加入购物车"
        ↓
购物车页面
├─ 显示商品
├─ 显示充值信息
└─ 点击"去结账"
        ↓
结账页面 (一步结账)
├─ 左侧 (60%)
│  ├─ 登录/注册表单 (客人显示)
│  ├─ 订单信息 (商品列表)
│  └─ 充值信息 (只读显示)
└─ 右侧 (40%)
   ├─ 支付方式 (微信/支付宝/银行卡)
   ├─ 订单摘要
   ├─ 立即购买按钮
   └─ Google 验证码
```

## 主题色系

- **按钮**: `#4A90E2` (蓝色)
- **选中项**: `#FF6B35` (橙红色)
- **文字**: `#1E293B` (深灰)
- **边框**: `#E2E8F0` (浅灰)

## 关键特性

### 1. 自动禁用配送
```php
// LayoutProcessorPlugin.php
unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']);
```

### 2. 保留 Google 验证码
```xml
<!-- checkout_index_index.xml -->
<item name="before-place-order" displayArea="before-place-order"/>
```

### 3. 充值信息只读显示
```javascript
// recharge-info-display.js
this.rechargeInfo = this.config.rechargeInfo; // 从 Quote 获取
```

## 测试清单

- [ ] 产品页面显示充值表单
- [ ] 加入购物车时保存充值信息
- [ ] 结账页面没有配送地址字段
- [ ] 充值信息在结账页面正确显示
- [ ] 登录表单对客人显示
- [ ] Google 验证码正常显示
- [ ] 支付方式显示正常
- [ ] 主题颜色正确应用
- [ ] 订单提交成功
- [ ] 订单中包含充值信息

## 常见问题

**Q: 充值信息没有显示？**
A: 检查 Quote Item 是否保存了自定义属性，清除缓存。

**Q: 验证码不显示？**
A: 检查 Google reCAPTCHA 配置，确认 Site Key 正确。

**Q: 布局没有变化？**
A: 运行 `setup:static-content:deploy -f` 并清除缓存。

## 文档

- `README.md` - 详细功能说明
- `INSTALLATION.md` - 完整安装指南
- `QUICK_START.md` - 本文件，快速开始指南

## 技术支持

查看日志：
- 系统日志：`var/log/system.log`
- 异常日志：`var/log/exception.log`

启用开发者模式：
```bash
php bin/magento deploy:mode:set developer
```

---

Copyright © Folix. All rights reserved.
