# Folix Simple Checkout Demo 说明文档

## 📋 Demo概览

这是一个全新的OneStepCheckout设计方案Demo,基于以下核心原则:

### ✅ **设计原则**

1. **极简流程** - 不需要配送地址,专注于虚拟商品充值场景
2. **语义化HTML** - 使用`<ul>`, `<li>`, `<fieldset>`等原生标签,符合Magento架构
3. **主题配色** - 完全使用Folix Game Theme的色彩系统
4. **合理布局** - 支付方式在购物车商品下方,符合用户浏览习惯

---

## 🎨 布局结构

```
┌──────────────────────────────────────────────┐
│         Folix Simple Checkout Header         │
├─────────────────────┬────────────────────────┤
│                     │                        │
│   Main Content      │   Sidebar Summary      │
│   (左侧 ~65%)       │   (右侧 ~35%)          │
│                     │                        │
│ ┌─────────────────┐ │ ┌────────────────────┐ │
│ │ 1. Contact Info │ │ │ Order Summary      │ │
│ │    - Email      │ │ │ - Subtotal         │ │
│ │    - Login Hint │ │ │ - Shipping (Free)  │ │
│ └─────────────────┘ │ │ - Tax              │ │
│                     │ │ - Total            │ │
│ ┌─────────────────┐ │ │                    │ │
│ │ 2. Cart Items   │ │ │ [Place Order Btn]  │ │
│ │    - Item 1     │ │ │                    │ │
│ │      + Recharge │ │ │ 🔒 SSL Secure      │ │
│ │    - Item 2     │ │ └────────────────────┘ │
│ │      + Recharge │ │                        │
│ └─────────────────┘ │                        │
│                     │                        │
│ ┌─────────────────┐ │                        │
│ │ 3. Payment      │ │                        │
│ │    - Alipay     │ │                        │
│ │    - WeChat     │ │                        │
│ │    - PayPal     │ │                        │
│ │    - CreditCard │ │                        │
│ └─────────────────┘ │                        │
└─────────────────────┴────────────────────────┘
```

---

## 🎯 关键特性

### **1. 联系信息区域 (Customer Email)**

**功能:**
- 邮箱输入框(虚拟商品必需)
- PC端显示登录提示卡片
- 移动端隐藏登录提示(改用内嵌表单)

**HTML语义化:**
```html
<div class="customer-email-section">
    <h2 class="section-title">联系信息</h2>
    <input type="email" class="email-input" />
    
    <!-- Login Notice Card -->
    <div class="login-notice">
        <div class="login-notice-icon">...</div>
        <div class="login-notice-content">
            <div class="login-notice-title">已有账号?</div>
            <button class="login-trigger-btn">立即登录</button>
        </div>
    </div>
</div>
```

---

### **2. 购物车商品区域 (Cart Items with Recharge Info)**

**核心改进:**
- ✅ **充值信息直接显示在每个Item内部**,不是单独模块
- ✅ 使用`<ul>`和`<li>`语义化标签
- ✅ 每个Item包含:图片、名称、充值信息、价格

**HTML结构:**
```html
<ul class="cart-item-list">
    <li class="cart-item">
        <div class="cart-item-image">GAME</div>
        <div class="cart-item-details">
            <div class="cart-item-name">Premium Game Currency Pack</div>
            <div class="cart-item-options">
                UID: 123456789 | Server: Asia Pacific
            </div>
            <div class="cart-item-price">$49.99</div>
        </div>
    </li>
</ul>
```

**样式亮点:**
- 充值信息用橙色左边框突出显示(`border-left: 3px solid var(--folix-secondary)`)
- 悬停时边框变为主题蓝色,增加阴影反馈

---

### **3. 支付方式区域 (Payment Methods)**

**布局顺序:**
- ✅ **在购物车商品下方**,符合用户浏览逻辑
- ✅ 先确认商品信息,再选择支付方式

**交互设计:**
```javascript
// 点击任意位置选中支付方式
document.querySelectorAll('.payment-method-item').forEach(item => {
    item.addEventListener('click', function() {
        // 移除其他active状态
        // 添加当前active状态
        // 选中radio按钮
    });
});
```

**HTML语义化:**
```html
<ul class="payment-methods-list">
    <li class="payment-method-item active">
        <label class="payment-method-radio">
            <input type="radio" name="payment_method" value="alipay" checked>
            <div class="payment-method-label">
                <div class="payment-method-icon">💳</div>
                <div class="payment-method-info">
                    <div class="payment-method-name">支付宝 Alipay</div>
                    <div class="payment-method-desc">推荐使用,即时到账</div>
                </div>
            </div>
        </label>
    </li>
</ul>
```

---

### **4. 侧边栏摘要 (Sidebar Summary)**

**PC端:**
- Sticky定位,滚动时始终可见
- 固定在右上角

**移动端:**
- Fixed定位到底部
- 最大高度50vh,可滚动
- 为主内容留出300px底部空间

**内容:**
```
Order Summary
├── Subtotal: $79.98
├── Shipping: Free
├── Tax: $0.00
└── Total: $79.98 (橙色大字体突出)

[Place Order Button] (渐变橙色背景)

🔒 SSL加密保护,支付安全有保障
```

---

## 🎨 主题配色系统

### **Brand Colors**
```css
--folix-primary: #4A90E2;        /* 科技蓝 */
--folix-secondary: #FF6B35;      /* 活力橙 */
--folix-accent: #6C5CE7;         /* 电竞紫 */
```

### **Gradients**
```css
--folix-gradient-primary: linear-gradient(135deg, #4A90E2 0%, #6C5CE7 100%);
--folix-gradient-secondary: linear-gradient(135deg, #FF6B35 0%, #EA580C 100%);
```

### **应用示例:**
- **标题**: Primary渐变文字
- **价格**: Secondary橙色
- **选中状态**: Primary蓝色边框 + Blue→Purple渐变背景
- **下单按钮**: Secondary渐变背景 + 橙色阴影

---

## 📱 响应式设计

### **Desktop (≥ 768px)**
```css
.checkout-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
}

.checkout-sidebar {
    position: sticky;
    top: 20px;
}
```

### **Mobile (< 768px) - 方案A: 固定在底部**

**设计决策理由:**
- ✅ **符合电商惯例** - Amazon、淘宝、京东等主流平台都采用此设计
- ✅ **下单按钮始终可见** - 用户随时可以点击,无需滚动到底部
- ✅ **提升转化率** - 减少用户犹豫时间,总价信息始终展示

**实现方式:**
```css
@media (max-width: 767px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
    
    .checkout-sidebar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
        max-height: 50vh; /* 限制高度,可滚动 */
        overflow-y: auto;
        border-radius: 12px 12px 0 0; /* 顶部圆角 */
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
        
        /* 顶部渐变阴影,增强层次感 */
        &::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(to top, 
                rgba(0, 0, 0, 0.05) 0%, 
                transparent 100%
            );
            pointer-events: none;
        }
    }
    
    .checkout-main {
        margin-bottom: 320px; /* 为固定Sidebar留出空间 */
        
        /* 最后一个section添加额外间距,防止被遮挡 */
        .payment-methods-section {
            margin-bottom: 36px;
        }
    }
    
    /* 移动端隐藏PC端登录提示 */
    .login-notice {
        display: none !important;
    }
}
```

**视觉效果:**
```
┌─────────────────────┐
│  Main Content       │
│  (可滚动)            │
│                     │
│  • Email            │
│  • Cart Items       │
│  • Payment Methods  │
│                     │
│  (padding-bottom)   │ ← 防止被遮挡
│                     │
├━━━━━━━━━━━━━━━━━━━━━┤ ← 分隔线+渐变阴影
│  Order Summary      │ ← Fixed定位
│  Total: $79.98      │   始终可见
│  [Place Order Btn]  │
│  🔒 SSL Secure      │
└━━━━━━━━━━━━━━━━━━━━━┘
```

---

## 🔧 与Magento实现的对应关系

| Demo组件 | Magento位置 | 实现方式 |
|---------|------------|---------|
| Customer Email | `payment.customer-email` | 复用原生`Magento_Checkout/js/view/form/element/email` |
| Login Notice | `before-login-form` | 自定义JS组件,PC端显示 |
| Cart Items | `beforeMethods` | 复用原生`cart-items`,修改模板显示充值信息 |
| Payment Methods | `payments-list` | 自定义模板,保持原生list.js逻辑 |
| Place Order Button | `sidebar.summary.itemsAfter` | 自定义JS组件,调用原生place-order action |
| Sidebar | `checkout.sidebar` | 自定义模板,添加sticky/fixed定位 |

---

## 📝 下一步开发计划

### **阶段1: 创建新模块骨架**
```
app/code/Folix/SimpleCheckout/
├── registration.php
├── etc/
│   ├── module.xml
│   └── frontend/
│       ├── di.xml
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
```

### **阶段2: 布局配置**
参考SalesRule模块的标准写法:
```xml
<referenceBlock name="checkout.root">
    <arguments>
        <argument name="jsLayout" xsi:type="array">
            <item name="components" xsi:type="array">
                <item name="checkout" xsi:type="array">
                    <item name="children" xsi:type="array">
                        <!-- 只添加需要的组件,不破坏原有结构 -->
                    </item>
                </item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

### **阶段3: CSS样式**
- 使用主题变量(`@folix-*`)
- 隐藏shipping-step和progressBar
- 实现响应式布局

### **阶段4: JS组件**
- Login Notice组件(PC/移动端切换)
- Place Order Button组件(统一下单入口)
- 复用原生email和payment组件

---

## 🚀 如何查看Demo

### **方法1: 直接打开文件**
```bash
# 文件路径
/var/www/html/game/game/app/code/Folix/SimpleCheckout/demo_simple_checkout.html

# 双击打开或在浏览器中拖入
```

### **方法2: 通过Web服务器访问**
```bash
# 创建软链接
cd /var/www/html/game/game
ln -s app/code/Folix/SimpleCheckout/demo_simple_checkout.html pub/demo_simple_checkout.html

# 访问URL
http://your-domain.com/demo_simple_checkout.html
```

---

## 💡 设计亮点总结

1. ✅ **充值信息集成到Item内部** - 更符合业务逻辑
2. ✅ **支付方式在商品下方** - 符合用户浏览顺序
3. ✅ **语义化HTML标签** - 与原生Magento架构一致
4. ✅ **主题配色系统** - 统一的视觉风格
5. ✅ **响应式Sidebar** - PC端sticky,移动端fixed
6. ✅ **简洁的交互反馈** - 悬停、点击都有动画效果

---

**最后更新:** 2026-04-26  
**作者:** Folix Development Team
