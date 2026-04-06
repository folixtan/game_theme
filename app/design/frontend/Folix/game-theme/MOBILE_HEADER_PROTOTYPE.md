# 移动端Header原型设计

## 一、原生Luma结构分析

### 1.1 布局结构

```
page.top容器
├── navigation.sections (标签页容器)
│   ├── store.menu (Menu标签页)
│   │   └── catalog.topnav (顶级菜单)
│   ├── store.links (Account标签页)
│   │   └── <!-- Account links -->
│   └── store.settings (Settings标签页)
│       ├── store.settings.language (语言切换)
│       └── store.settings.currency (货币切换)

header.container
├── header.panel.wrapper (Top Bar)
│   └── header.panel
│       ├── skip_to_content
│       ├── store_language
│       └── top.links
└── header-wrapper (Main Bar)
    ├── logo
    ├── minicart
    └── top.search
```

### 1.2 原生特点

**优点**：
- ✅ 结构清晰，层次分明
- ✅ 移动端标签页切换机制完善
- ✅ 原生JS处理交互

**缺点**：
- ❌ 移动端header显示不够简洁
- ❌ 标签页导航不符合现代移动端体验
- ❌ 无法自定义菜单项布局

---

## 二、移动端Header设计（参考seagm.com）

### 2.1 设计目标

**基于seagm.com，但不照搬**：
- 参考布局思路（左中右）
- 参考菜单展开方式（侧边栏）
- 基于Folix主题颜色系统
- 保持Magento原生功能

### 2.2 布局设计

#### Top Bar (header.panel)

```
┌─────────────────────────────────────┐
│ [≡] Folix.Game          ZH/HKD [登录] │
└─────────────────────────────────────┘

移动端布局：
- 左侧：汉堡菜单图标 [≡]
- 中间：品牌名 "Folix.Game"
- 右侧：语言/货币切换 + 登录按钮

桌面端布局：
- 左侧：新闻、奖励、支持链接
- 右侧：APP下载 + 语言切换
```

#### Main Bar (header-wrapper)

```
移动端布局：
┌─────────────────────────────────────┐
│ [≡]         [🔍]        [👤]/[登录] │
└─────────────────────────────────────┘

- 左侧：汉堡菜单 [≡]
- 中间：搜索图标 [🔍]（点击展开搜索框）
- 右侧：用户图标 [👤]（已登录）/ 登录按钮（未登录）
- Logo：隐藏（在Top Bar显示）

桌面端布局：
┌─────────────────────────────────────┐
│ [Logo] [搜索框.........] [🛒] [登录] │
└─────────────────────────────────────┘

- Logo显示
- 搜索框展开
- 购物车 + 登录按钮
```

---

### 2.3 菜单设计

#### 移动端菜单（侧边栏）

**参考seagm.com的菜单项布局**：

```
┌─────────────────────────────────────┐
│ [×]                     ZH/HKD      │
├─────────────────────────────────────┤
│ [图标] 菜单项 1                      │
│ [图标] 菜单项 2                      │
│ [图标] 菜单项 3                      │
│ [图标] 菜单项 4                      │
│ ...                                 │
└─────────────────────────────────────┘
```

**Folix主题菜单项**：
- 首页
- 游戏（分类）
- 点卡充值
- 新闻
- 支持
- 关于我们

**Active状态**：
- 使用强调色 `@folix-secondary: #FF6B35`
- 左侧3px边框
- 背景色 `rgba(255, 107, 53, 0.1)`

---

### 2.4 搜索交互设计

**移动端搜索**：

```
状态1：默认状态
┌─────────────────────────────────────┐
│ [≡]         [🔍]        [👤]       │
└─────────────────────────────────────┘

状态2：点击搜索图标后
┌─────────────────────────────────────┐
│ [×] [搜索框....................] [🔍] │
└─────────────────────────────────────┘

交互：
1. 点击搜索图标 → 展开搜索框
2. 输入关键词
3. 点击搜索图标或回车 → 提交搜索
4. 点击 [×] → 关闭搜索框
```

---

## 三、实现方案分析

### 3.1 方案对比

#### 方案A：Clone菜单到移动端容器

**实现方式**：
```xml
<!-- 移动端菜单容器 -->
<container name="mobile.menu.container" htmlTag="div" htmlClass="mobile-menu-container">
    <block class="Magento\Theme\Block\Html\Topmenu" name="mobile.catalog.topnav" template="Magento_Theme::html/topmenu.phtml"/>
</container>

<!-- 用JS clone菜单项 -->
```

**优点**：
- ✅ 保留原生菜单数据
- ✅ 不影响桌面端菜单
- ✅ 可以自定义移动端样式

**缺点**：
- ❌ 需要JS处理
- ❌ 可能重复渲染
- ❌ 维护复杂度高

---

#### 方案B：重新渲染一个Topmenu（推荐）✅

**实现方式**：
```xml
<!-- 移除原生的navigation.sections -->
<referenceBlock name="navigation.sections" remove="true"/>

<!-- 创建新的移动端菜单容器 -->
<container name="mobile.menu.container" htmlTag="div" htmlClass="mobile-menu-container">
    <block class="Magento\Theme\Block\Html\Topmenu" name="mobile.catalog.topnav" template="Magento_Theme::html/topmenu.phtml"/>
</container>

<!-- 桌面端菜单保持原位置 -->
<referenceContainer name="header-wrapper">
    <block class="Magento\Theme\Block\Html\Topmenu" name="desktop.catalog.topnav" template="Magento_Theme::html/topmenu.phtml"/>
</referenceContainer>
```

**优点**：
- ✅ 结构清晰，易于维护
- ✅ 移动端和桌面端分离
- ✅ 可以独立定制样式
- ✅ 不需要JS clone

**缺点**：
- ❌ 需要渲染两次菜单（性能影响小）

---

### 3.2 推荐方案：方案B（重新渲染）

**理由**：
1. **灵活性最高**：移动端和桌面端完全分离，可以独立定制
2. **维护简单**：不需要JS处理，纯布局配置
3. **性能可接受**：菜单项通常不多，二次渲染影响小
4. **符合Magento规范**：使用block渲染，不依赖JS

---

## 四、default.xml修改方案

### 4.1 移除原生标签页导航

```xml
<!-- 移除原生的navigation.sections -->
<referenceBlock name="navigation.sections" remove="true"/>
```

### 4.2 重构Top Bar

```xml
<referenceContainer name="header.panel">
    <!-- 移动端：汉堡菜单 -->
    <block class="Magento\Framework\View\Element\Template" name="mobile.nav.toggle" template="Magento_Theme::html/header/mobile-nav-toggle.phtml" before="-"/>
    
    <!-- 移动端：品牌名 -->
    <block class="Magento\Framework\View\Element\Template" name="mobile.brand" template="Magento_Theme::html/header/mobile-brand.phtml" after="mobile.nav.toggle"/>
    
    <!-- 移动端：语言/货币 + 登录 -->
    <block class="Magento\Framework\View\Element\Template" name="mobile.top.right" template="Magento_Theme::html/header/mobile-top-right.phtml" after="mobile.brand"/>
</referenceContainer>
```

### 4.3 重构Main Bar

```xml
<referenceContainer name="header-wrapper">
    <!-- 移动端：搜索 -->
    <referenceBlock name="top.search" template="Magento_Theme::html/header/mobile-search.phtml"/>
    
    <!-- 移动端：用户/登录按钮 -->
    <block class="Magento\Framework\View\Element\Template" name="mobile.user.button" template="Magento_Theme::html/header/mobile-user-button.phtml" after="minicart"/>
    
    <!-- 移动端：隐藏logo -->
    <referenceBlock name="logo" display="false"/>
    
    <!-- 移动端菜单（重新渲染） -->
    <block class="Magento\Theme\Block\Html\Topmenu" name="mobile.catalog.topnav" template="Magento_Theme::html/topmenu.phtml" before="-"/>
</referenceContainer>
```

---

## 五、样式设计原则

### 5.1 CSS组织

**原则**：
- 公共样式写在移动端区域
- 桌面端样式写在媒体查询内

```less
& when (@media-common = true) {
    // 公共基础样式（移动端优先）
    .page-header {
        // 移动端样式
    }
}

.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    // 桌面端样式
    .page-header {
        // 桌面端样式
    }
}
```

### 5.2 颜色使用

**Active状态**：
- 主色：`@folix-primary: #4A90E2`
- 强调色：`@folix-secondary: #FF6B35`（用于active状态）

**菜单项active**：
```less
.navigation {
    .level0 {
        &.active {
            > a {
                border-left: 3px solid @folix-secondary;
                background: rgba(255, 107, 53, 0.1);
                color: @folix-secondary;
            }
        }
    }
}
```

---

## 六、交互设计

### 6.1 汉堡菜单交互

**状态**：
1. 默认：显示 [≡] 图标
2. 点击：展开侧边栏，图标变为 [×]
3. 点击遮罩：关闭侧边栏

### 6.2 搜索交互

**状态**：
1. 默认：显示搜索图标 [🔍]
2. 点击：展开搜索框，图标变为 [×]
3. 输入：显示输入框
4. 提交：点击搜索图标或回车

### 6.3 用户登录状态

**未登录**：
- 显示"登录"按钮

**已登录**：
- 显示用户图标 [👤]
- 点击显示下拉菜单（我的账户、登出）

---

## 七、总结

### 7.1 设计要点

1. **移动端优先**：基础样式写在移动端区域
2. **布局分离**：移动端和桌面端独立布局
3. **覆盖优先**：覆盖原生结构，更灵活
4. **重新渲染**：菜单渲染两次，移动端和桌面端分离

### 7.2 下一步

1. 确认设计方案
2. 编写default.xml
3. 编写模板文件
4. 编写样式文件
5. 测试验证

---

**请确认以上设计方案，我将继续编写代码。**
