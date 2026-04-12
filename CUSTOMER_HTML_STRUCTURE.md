# Magento 2 Customer 模块 - 原生 HTML 结构分析

## 原生模板文件路径

### 1. 头部 Customer 菜单
- **路径**: `app/code/Magento/Customer/view/frontend/templates/account/customer.phtml`
- **说明**: 控制登录/注册按钮的显示

### 2. Customer 导航菜单（登录后）
- **路径**: `app/code/Magento/Customer/view/frontend/templates/account/navigation.phtml`
- **说明**: 控制下拉菜单内容（我的账户、订单、购物车等）

### 3. Customer 头部链接
- **路径**: `app/code/Magento/Customer/view/frontend/templates/account/header/links.phtml`
- **说明**: 头部链接容器

### 4. 登录表单模态框
- **路径**: `app/code/Magento/Customer/view/frontend/web/templates/authentication-popup.html`
- **说明**: 登录弹窗的 HTML 结构

## 原生 HTML 结构（参考）

### 登录按钮结构
```html
<li class="customer-welcome">
    <span class="customer-name" data-bind="text: customer().fullname"></span>
    <button type="button" class="action switch" tabindex="-1">
        <span>Change</span>
    </button>
    <div class="customer-menu" data-target="dropdown">
        <ul class="customer-links">
            <li><a href="/customer/account/">My Account</a></li>
            <li><a href="/customer/account/logout/">Sign Out</a></li>
        </ul>
    </div>
</li>
```

### 未登录状态
```html
<li class="authorization-link">
    <a href="/customer/account/login/referer/">
        Sign In
    </a>
</li>
```

### 登录弹窗结构
```html
<div id="authentication-popup" data-bind="scope: 'authentication-popup'">
    <!-- 登录表单 -->
</div>
```

## Folix 主题定制方案

### 1. 复制模板到主题目录
```
app/design/frontend/Folix/game-theme/Magento_Customer/
├── templates/
│   ├── account/
│   │   ├── customer.phtml           # 头部登录按钮
│   │   ├── header/
│   │   │   └── links.phtml          # 头部链接容器
│   │   └── navigation.phtml         # 登录后下拉菜单
│   └── form/
│       └── login.phtml              # 登录表单
├── layout/
│   └── customer_account.xml         # Customer 账户布局
└── web/
    └── css/
        └── source/
            └── Magento_Customer/
                └── _module.less     # Customer 模块样式
```

### 2. 电竞风样式设计
- 登录按钮：金色渐变按钮 + 深蓝背景
- 下拉菜单：深蓝背景 + 金色边框
- 登录弹窗：电竞风卡片 + 渐变按钮

### 3. 功能需求
- [x] 登录按钮样式定制
- [x] 创建账户链接定制
- [x] 登录后下拉菜单内容
- [x] 登录弹窗布局
