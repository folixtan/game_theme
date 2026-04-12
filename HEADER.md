# Folix Game Theme - Header Design (Final Version)

## 项目概述

基于 Magento 2 Luma 主题定制的游戏电商主题，采用电竞风格设计。

**设计特点：**
- 深色背景（#1a1a1a）+ 金色/橙色强调色（#ff9800）
- 响应式设计（768px 断点）
- PC 端和移动端不同布局

---

## 核心设计理念

### 基于用户指导

1. **不能只看 theme/default.xml**
   - 还要看 customer/default.xml、mageplaza-social-login/default.xml 等
   - 它们会合并成一个 XML

2. **各司其职**
   - Theme 模块：定义基本结构（Container + Block）
   - Customer 模块：定义登录/注册链接
   - Mageplaza：定义社交登录
   - Folix Theme：组织、删除、移动、定制

3. **任务：组织它们**
   - 删除：不需要的原生元素
   - 移动：移动元素到正确位置
   - 定制：修改模板和 class
   - 不创建父 Block（Container 只是 div 容器）

4. **用模板实现灵活性**
   - 避免 PC 端和移动端两套导航
   - 用模板控制显示/隐藏
   - 用 CSS 控制样式

5. **从 demo 出发**
   - 确认每个元素的位置
   - 严格按照原型实现

---

## 合并后的完整结构

### 模块分析

#### 1. 原生 Theme 模块（module-theme）

```xml
<header.panel.wrapper>
  <header.panel>
    <skip_to_content />
    <store_language />
    <top.links /> <!-- 由 Customer 模块添加子 block -->
  </header.panel>
</header.panel.wrapper>

<header-wrapper>
  <logo />
</header-wrapper>

<page.top>
  <navigation.sections>
    <store.menu>
      <catalog.topnav />
    </store.menu>
    <store.links />
    <store.settings>
      <store.settings.language />
      <store.settings.currency />
    </store.settings>
  </navigation.sections>
</page.top>
```

#### 2. 原生 Customer 模块（module-customer）

```xml
<top.links>
  <my-account-link />
  <register-link />
  <authorization-link />
</top.links>

<authentication-popup />
<customer.section.config />
```

#### 3. Mageplaza Social Login

```xml
<authentication-popup>
  <social-buttons />
</authentication-popup>

<social-login-popup />
```

---

## 最终实现

### 结构组织

#### Top Bar（header.panel）

```
header.panel
├── folix.top.links (自定义 Block)
│   └── News, Rewards, Support
├── folix.app.link (自定义 Block)
│   └── APP Download
├── store_language (原生，移动到右侧)
└── top.links (原生，由 Customer 模块添加)
    └── My Account, Register, Sign In
```

#### Main Bar（header-wrapper）

```
header-wrapper
├── logo (原生)
├── catalog.topnav (从 page.top 移来，PC 导航)
├── top.search (从 page.top 移来)
└── minicart (从 page.bottom 移来)
```

#### Mobile Navigation（page.top）

```
page.top
├── navigation.sections (保留，侧边栏样式)
│   ├── store.menu (Menu tab)
│   ├── store.links (Account tab)
│   └── store.settings (Settings tab)
├── mobile.nav.overlay (自定义，遮罩层)
└── mobile.nav.toggle (自定义，汉堡菜单)
```

---

## 文件结构

```
app/design/frontend/Folix/game-theme/
├── Magento_Theme/
│   ├── layout/
│   │   └── default.xml                      # 主布局文件
│   ├── templates/
│   │   ├── html/
│   │   │   ├── header/
│   │   │   │   ├── links-top-left.phtml    # 左侧链接
│   │   │   │   └── app-link.phtml          # APP 链接
│   │   │   ├── nav-overlay.phtml           # 遮罩层
│   │   │   └── nav-toggle.phtml            # 汉堡菜单
│   │   └── ...
│   ├── web/
│   │   ├── css/
│   │   │   └── source/
│   │   │       └── _header.less            # Header 样式
│   │   └── js/
│   │       └── mobile-nav.js               # 移动端导航
│   └── requirejs-config.js                 # RequireJS 配置
```

---

## 核心功能

### ✅ 已实现

- [x] **PC 端 Top Bar**
  - [x] 左侧：新闻、奖励、支持链接
  - [x] 右侧：APP 下载 + 语言切换
  - [x] 登录/注册/用户信息（Customer 模块）
- [x] **PC 端 Main Bar**
  - [x] Logo
  - [x] 水平导航菜单（catalog.topnav）
  - [x] 搜索框
  - [x] 购物车
- [x] **移动端简化 Header**
  - [x] Logo
  - [x] 汉堡菜单按钮
  - [x] 购物车
- [x] **移动端侧边栏**
  - [x] 使用 navigation.sections
  - [x] Menu、Account、Settings 标签页
  - [x] 遮罩层
- [x] **Mageplaza Social Login**
  - [x] 使用原生 `authentication-popup`
  - [x] 使用原生 `top.links`
  - [x] 兼容社交登录按钮
- [x] **响应式设计**
  - [x] PC 端布局（> 768px）
  - [x] 移动端布局（≤ 768px）
- [x] **基于 Magento 2 原生结构**
  - [x] 不创建父 Block
  - [x] 只删除、移动、定制
  - [x] 保留所有模块功能

---

## 关键技术点

### 1. 不创建父 Block

```xml
<!-- ✅ 错误做法：创建父 Block -->
<referenceContainer name="header.panel">
  <block name="folix.header.top" template="top-bar.phtml">
    <block name="folix.top.links" />
  </block>
</referenceContainer>

<!-- ✅ 正确做法：直接在 Container 中定义 Block -->
<referenceContainer name="header.panel">
  <block name="folix.top.links" template="links-top-left.phtml" />
  <block name="folix.app.link" template="app-link.phtml" />
</referenceContainer>
```

### 2. 使用 move 移动元素

```xml
<!-- ✅ 移动 catalog.topnav 到 header-wrapper -->
<move element="catalog.topnav" destination="header-wrapper" after="logo" />

<!-- ✅ 移动 top.search 到 header-wrapper -->
<move element="top.search" destination="header-wrapper" after="catalog.topnav" />

<!-- ✅ 移动 minicart 到 header-wrapper -->
<move element="minicart" destination="header-wrapper" after="top.search" />
```

### 3. 保留原生功能

```xml
<!-- ✅ 保留 top.links（由 Customer 模块添加子 block） -->
<!-- 不需要手动定义 my-account-link、register-link 等 -->

<!-- ✅ 保留 navigation.sections（移动端侧边栏） -->
<!-- 不需要删除，用 CSS 控制显示 -->
```

---

## 样式变量

```less
@folix-header-bg: #1a1a1a;
@folix-header-border: #2d2d2d;
@folix-text-color: #e0e0e0;
@folix-text-hover: #ff9800;
@folix-accent-color: #ff9800;
@folix-accent-hover: #ffb74d;

@folix-top-bar-height: 40px;
@folix-main-bar-height: 70px;
@folix-mobile-breakpoint: 768px;
```

---

## JavaScript 控制

### mobile-nav.js

```javascript
// Toggle Navigation
$('.folix-nav-toggle').on('click', function () {
    toggleMobileNav();
});

// Close Navigation on Overlay Click
$('.folix-nav-overlay').on('click', function () {
    closeMobileNav();
});

// Toggle Section (Menu / Account / Settings)
$('.section-item-title a').on('click', function () {
    // Open target section
    $(targetId).addClass('active');
});
```

---

## 第三方兼容

### Mageplaza Social Login

**集成方式：**
1. 使用原生 `authentication-popup` Block
2. Mageplaza 通过 `referenceBlock` 添加 `social-buttons` 子组件
3. 本主题完全兼容，无需额外配置

**CSS 控制：**
```less
// Header Links Social Login
.header.panel .header.links .authorization-link a {
    display: flex;
    align-items: center;
    gap: 6px;
}

// Authentication Popup Social Buttons
.authentication-popup .social-buttons {
    margin-bottom: 20px;
}
```

---

## 安装与部署

```bash
# 清理缓存
php bin/magento cache:flush

# 清理静态内容
php bin/magento setup:static-content:deploy

# 清理 generated 目录
php bin/magento setup:upgrade
```

---

## 浏览器兼容性

- Chrome/Edge (最新版本)
- Firefox (最新版本)
- Safari (最新版本)
- Mobile Safari (iOS 12+)
- Chrome Mobile (Android 8+)

---

## 总结

### 关键设计决策

1. **不创建父 Block** - 只在 Container 中直接定义 Block
2. **使用 move 移动元素** - 保持原生结构不变
3. **用模板控制显示** - PC 端和移动端共用一套导航
4. **保留原生功能** - Customer 模块、Mageplaza Social Login 完全兼容
5. **从 demo 出发** - 严格按照原型实现

### 开发规范

- ✅ 只修改主题的 default.xml
- ✅ 不修改模块的 default.xml
- ✅ 只删除、移动、定制，不创建父 Block
- ✅ 理解模块合并机制
- ✅ 各司其职，各模块只负责自己的功能

---

**状态：✅ 已完成基于原生结构的正确实现**
