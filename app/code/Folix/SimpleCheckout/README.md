# Folix SimpleCheckout Module

## 📋 模块说明

**Folix_SimpleCheckout** 是一个专为虚拟商品(游戏充值)设计的简化版结账模块。

### **核心特性**

1. ✅ **无需配送地址** - 针对虚拟商品优化,隐藏shipping-step
2. ✅ **充值信息集成** - UID/服务器等信息直接显示在购物车Item中
3. ✅ **语义化HTML** - 使用`<ul>`, `<li>`, `<fieldset>`等原生标签
4. ✅ **主题配色** - 完全遵循Folix Game Theme设计规范
5. ✅ **响应式设计** - PC端Sidebar sticky,移动端fixed底部
6. ✅ **Mageplaza集成** - 支持社交登录弹窗

---

## 🎯 布局流程

```
1. Customer Email (联系信息)
   └── Login Notice (PC端提示卡片)

2. Cart Items (购物车商品)
   └── 每个Item包含:
       ├── 商品图片
       ├── 商品名称
       ├── 充值信息 (UID | Server)
       └── 价格

3. Payment Methods (支付方式)
   └── Alipay / WeChat / PayPal / Credit Card

4. Place Order Button (统一下单按钮)
   └── 位于Sidebar底部
```

---

## 📁 文件结构

```
app/code/Folix/SimpleCheckout/
├── registration.php
├── etc/
│   ├── module.xml
│   └── frontend/
│       └── layout/
│           └── checkout_index_index.xml
├── view/
│   └── frontend/
│       ├── web/
│       │   ├── css/
│       │   │   └── source/
│       │   │       └── _module.less
│       │   ├── js/
│       │   │   └── view/
│       │   │       ├── payment/
│       │   │       │   └── login-notice.js
│       │   │       └── place-order-button.js
│       │   └── template/
│       │       ├── payment/
│       │       │   └── login-notice.html
│       │       └── place-order-button.html
│       └── layout/
│           └── checkout_index_index.xml
├── demo_simple_checkout.html
└── DEMO_README.md
```

---

## 🚀 安装步骤

### **1. 启用模块**
```bash
cd /var/www/html/game/game
php bin/magento module:enable Folix_SimpleCheckout
php bin/magento setup:upgrade
```

### **2. 编译静态资源**
```bash
php bin/magento cache:clean
php bin/magento setup:static-content:deploy -f zh_Hans_CN en_US
```

### **3. 访问Checkout页面**
```
http://your-domain.com/checkout
```

---

## 🎨 设计参考

查看Demo页面了解完整设计:
- **文件路径**: `app/code/Folix/SimpleCheckout/demo_simple_checkout.html`
- **说明文档**: `app/code/Folix/SimpleCheckout/DEMO_README.md`

---

## 📝 开发规范

### **布局配置原则**
- ✅ 参考SalesRule模块的标准写法
- ✅ 只添加需要的组件,不破坏原有结构
- ✅ 严格遵循原生层级进行组件注入

### **CSS编写原则**
- ✅ 使用主题变量(`@folix-*`)
- ✅ 避免滥用`!important`
- ✅ 响应式设计必须包裹在媒体查询中

### **JS组件原则**
- ✅ 复用原生组件(email, payment)
- ✅ 使用Mixin扩展而非重写
- ✅ 调用原生Place Order Action

---

## 🔗 相关模块

- **Folix_ChargeTemplate** - PDP页面充值表单
- **Mageplaza_SocialLogin** - 社交登录集成
- **Magento_Checkout** - 原生结账模块

---

**版本:** 1.0.0  
**最后更新:** 2026-04-26  
**作者:** Folix Development Team
