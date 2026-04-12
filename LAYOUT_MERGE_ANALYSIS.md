# Magento 2 布局合并分析

## 一、所有模块的 default.xml 合并后的完整结构

### 1. 原生 Theme 模块（module-theme）

```xml
<referenceContainer name="header.panel.wrapper">
  <container name="header.panel" htmlClass="panel header">
    <block name="skip_to_content" />
    <block name="store_language" />
    <block name="top.links" class="Magento\Customer\Block\Account\Navigation">
      <argument name="css_class" xsi:type="string">header links</argument>
    </block>
  </container>
</referenceContainer>

<referenceContainer name="header-wrapper" htmlClass="header content">
  <block name="logo" />
</referenceContainer>

<referenceContainer name="page.top">
  <block name="navigation.sections" template="sections.phtml">
    <block name="store.menu" group="navigation-sections">
      <block name="catalog.topnav" />
    </block>
    <block name="store.links" group="navigation-sections" />
    <block name="store.settings" group="navigation-sections">
      <block name="store.settings.language" />
      <block name="store.settings.currency" />
    </block>
  </block>
</referenceContainer>
```

### 2. 原生 Customer 模块（module-customer）

```xml
<referenceBlock name="top.links">
  <block name="my-account-link" />
  <block name="register-link" />
  <block name="authorization-link" />
</referenceBlock>

<referenceContainer name="content">
  <block name="authentication-popup" />
  <block name="customer.section.config" />
  <block name="customer.customer.data" />
</referenceContainer>
```

### 3. Luma 主题（theme-frontend-luma）

```xml
<referenceContainer name="header.panel">
  <block name="header.links" class="Magento\Framework\View\Element\Html\Links">
    <argument name="css_class" xsi:type="string">header links</argument>
  </block>
</referenceContainer>
```

### 4. Mageplaza Social Login（mageplaza）

```xml
<referenceBlock name="authentication-popup">
  <!-- 添加 social-buttons 子组件 -->
</referenceBlock>

<referenceContainer name="content">
  <block name="social-login-popup" />
</referenceContainer>
```

### 5. Folix Theme（当前）

```xml
<!-- 创建了父 Block（问题） -->
<referenceContainer name="header.panel">
  <block name="folix.header.top">
    <block name="folix.top.links" />
    <block name="folix.app.link" />
    <block name="top.links" />
    <block name="header.customer" />
  </block>
</referenceContainer>

<referenceContainer name="header-wrapper">
  <block name="folix.header.main">
    <block name="logo" />
    <block name="pc.topnav" />
    <block name="top.search" />
    <block name="minicart" />
  </block>
</referenceContainer>
```

---

## 二、合并后的最终结构

### header.panel Container（Top Bar）

```
header.panel.wrapper
└── header.panel
    ├── skip_to_content（原生）
    ├── store_language（原生）
    ├── top.links（原生，Customer 模块添加子 block）
    │   ├── my-account-link（Customer）
    │   ├── register-link（Customer）
    │   └── authorization-link（Customer）
    └── header.links（Luma 添加）
```

### header-wrapper Container（Main Bar）

```
header-wrapper
└── logo（原生）
```

### page.top Container（移动端导航）

```
page.top
└── navigation.sections
    ├── store.menu (group="navigation-sections")
    │   └── catalog.topnav
    ├── store.links (group="navigation-sections")
    │   └── <!-- 内容由 Customer 模块的 top.links 提供 -->
    └── store.settings (group="navigation-sections")
        ├── store.settings.language
        └── store.settings.currency
```

### content Container

```
content
├── authentication-popup（Customer + Mageplaza social-buttons）
├── social-login-popup（Mageplaza）
└── customer.section.config（Customer）
```

---

## 三、用户的原始设计（基于 demo）

### PC 端

**Top Bar（40px，深蓝渐变）**
- 左侧：新闻、奖励、支持链接
- 右侧：APP 下载、语言切换、登录按钮

**Main Bar（70px，白色，底部橙色边框3px）**
- Logo
- 导航菜单
- 搜索框
- 登录按钮（已登录显示用户名）

### 移动端

**简化 Header**
- Logo
- 汉堡菜单按钮
- 购物车

**侧边栏导航**
- 使用 navigation.sections
- Menu tab、Account tab、Settings tab

---

## 四、问题分析

### 当前方案的问题

1. ❌ **创建父 Block**
   - 违反了"只能在 Block 模板中修改"的原则
   - Container 只是 div 容器，不应该有父 Block

2. ❌ **PC 和移动端导航分离**
   - 用户不想用两套导航
   - 用模板可以灵活控制

3. ❌ **不明确哪些要删除、哪些要移动**
   - 没有先看所有模块的 default.xml
   - 不知道合并后的最终结构

### 正确做法

1. ✅ **先看所有模块的 default.xml**
   - module-theme
   - module-customer
   - module-catalog-search
   - mageplaza-social-login
   - 理解合并后的最终结构

2. ✅ **明确删除、移动、定制**
   - 删除：不需要的原生元素（skip_to_content、currency 等）
   - 移动：移动元素到正确位置（top.search、minicart）
   - 定制：修改模板和 class（catalog.topnav、header.links）

3. ✅ **使用模板控制显示**
   - PC 端：显示完整的 header.panel 和 header-wrapper
   - 移动端：隐藏部分元素，显示汉堡菜单
   - 侧边栏：使用 navigation.sections

4. ✅ **保留原生结构**
   - Container 保持不变
   - Block 通过 move 到正确位置
   - 不创建父 Block

---

## 五、用户的需求（基于对话）

### 关键点

1. **不想用两套导航**
   - PC 端和移动端共用一套导航数据
   - 用模板和 CSS 控制显示方式

2. **用模板实现灵活性**
   - 可以在模板中控制哪些元素显示/隐藏
   - 添加自定义 HTML 结构

3. **先确认哪些要移动**
   - 从 demo 出发，确认每个元素的位置
   - 理解合并后的 XML 结构

4. **各司其职**
   - Theme 模块：定义基本结构
   - Customer 模块：定义登录/注册链接
   - Folix Theme：组织、删除、移动、定制

---

## 六、正确的方案

### 步骤 1：确定要删除的元素

- `skip_to_content` - 无障碍功能，但 demo 中不需要
- `report.bugs` - Luma 移除了
- `currency` - demo 中没有货币切换
- `store_switcher` - demo 中没有店铺切换

### 步骤 2：确定要移动的元素

- `top.search` - 需要移动到 header-wrapper
- `minicart` - 需要移动到 header-wrapper
- `store_language` - 从 header.panel 移到 Top Bar 右侧

### 步骤 3：确定要定制的元素

- `header.panel` - 需要添加左侧链接和 APP 链接
- `header-wrapper` - 需要添加导航、搜索、购物车
- `top.links` - 需要修改样式
- `catalog.topnav` - 需要为 PC 端定制样式

### 步骤 4：确定模板定制

**header.phtml（可选）**
- 定义完整的 header 结构
- 控制 PC 端和移动端显示

**navigation.phtml（可选）**
- 定制移动端侧边栏
- 添加关闭按钮

---

## 七、建议的实现方案

### 方案：使用模板 + 原生结构

```xml
<!-- Magento_Theme/default.xml -->

<!-- 删除不需要的元素 -->
<referenceBlock name="skip_to_content" remove="true" />
<referenceBlock name="currency" remove="true" />
<referenceBlock name="report.bugs" remove="true" />

<!-- 移动元素 -->
<move element="top.search" destination="header-wrapper" before="minicart" />
<move element="minicart" destination="header-wrapper" />

<!-- 添加自定义链接到 header.panel -->
<referenceContainer name="header.panel">
  <block name="folix.top.links" template="links-top-left.phtml" before="store_language" />
  <block name="folix.app.link" template="app-link.phtml" after="folix.top.links" />
</referenceContainer>

<!-- 定制 header-wrapper -->
<referenceContainer name="header-wrapper">
  <block name="pc.topnav" after="logo" />
</referenceContainer>

<!-- 保留 navigation.sections 用于移动端 -->
<referenceContainer name="page.top">
  <block name="navigation.sections">
    <!-- 保持不变 -->
  </block>
</referenceContainer>
```

```php
<!-- header.phtml（可选）-->
<div class="page-header">
  <div class="header.panel.wrapper">
    <div class="panel header">
      <!-- Top Bar 内容 -->
    </div>
  </div>

  <div class="header.content">
    <!-- Main Bar 内容 -->
  </div>
</div>
```

---

## 八、下一步

需要根据用户确认：
1. 是否使用 header.phtml 定制完整结构？
2. 是否删除 navigation.sections？
3. PC 端和移动端导航如何共用？
4. 登录按钮位置（Top Bar 还是 Main Bar）？
