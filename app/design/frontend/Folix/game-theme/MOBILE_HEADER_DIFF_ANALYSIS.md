# 移动端Header差异分析

## 一、原生Luma结构

### 1.1 布局结构

```xml
<!-- Top Bar -->
header.panel
├── skip_to_content
├── store_language (语言切换)
└── top.links (顶部链接)

<!-- Main Bar -->
header-wrapper
├── logo
├── minicart (购物车)
└── top.search (搜索)

<!-- Navigation (在page.top容器) -->
navigation.sections
├── store.menu (Menu标签页)
│   └── catalog.topnav (顶级菜单)
├── store.links (Account标签页)
└── store.settings (Settings标签页)
    ├── store.settings.language
    └── store.settings.currency
```

---

## 二、当前已实现的功能

### 2.1 桌面端（已完成）✅

**Top Bar**：
- ✅ 三列布局（左/中/右）
- ✅ 深蓝渐变背景
- ✅ 左侧：新闻、奖励、支持链接
- ✅ 右侧：APP下载 + 语言切换
- ✅ 高度约60px

**Main Bar**：
- ✅ 白色背景
- ✅ 橙色底边框3px
- ✅ 高度70px
- ✅ navigation.sections已移动到header-wrapper
- ✅ 搜索框已移动到header-wrapper
- ✅ 购物车已移动到header-wrapper
- ✅ 登录按钮已添加

**布局修改**：
```xml
<!-- Top Bar -->
<move element="store_language" destination="folix.top.right" />

<!-- Main Bar -->
<move element="navigation.sections" destination="header-wrapper" after="logo" />
<move element="top.search" destination="header-wrapper" before="minicart" />
```

---

### 2.2 移动端（当前状态）⚠️

**Top Bar**：
- ❌ 完全隐藏（不符合需求）
- ✅ 代码已存在：三列布局容器

**Main Bar**：
- ✅ 汉堡菜单（左侧）
- ✅ Logo居中（中间）
- ✅ 搜索图标（右侧）
- ❌ 没有登录按钮
- ❌ 没有用户图标

**Navigation**：
- ✅ navigation.sections在header-wrapper中
- ⚠️ 使用标签页形式（Menu/Account/Settings）
- ⚠️ 不符合seagm.com参考设计

**样式问题**：
- ❌ 使用硬编码颜色值
- ❌ 未使用_variables.less中的变量

---

## 三、需要做的差异（仅移动端）

### 3.1 Top Bar修改

**当前**：完全隐藏
**目标**：显示（参考seagm.com）

```
移动端Top Bar布局：
┌─────────────────────────────────────┐
│ [≡] Folix.Game          ZH/HKD [登录] │
└─────────────────────────────────────┘

- 左侧：汉堡菜单图标 [≡]
- 中间：品牌名 "Folix.Game"
- 右侧：语言/货币切换 + 登录按钮
```

**修改方式**：
- ❌ 不修改default.xml（桌面端布局已完善）
- ✅ 只修改移动端CSS（.media-width max）

---

### 3.2 Main Bar修改

**当前**：汉堡菜单 + Logo + 搜索
**目标**：汉堡菜单 + 搜索 + 用户/登录

```
移动端Main Bar布局：
┌─────────────────────────────────────┐
│ [≡]         [🔍]        [👤]/[登录] │
└─────────────────────────────────────┘

- 左侧：汉堡菜单 [≡]
- 中间：搜索图标 [🔍]（点击展开）
- 右侧：用户图标 [👤]（已登录）/ 登录按钮（未登录）
- Logo：隐藏（在Top Bar显示）
```

**修改方式**：
- ❌ 不修改default.xml（保持桌面端布局）
- ✅ 只修改移动端CSS
- ✅ 添加登录按钮的移动端样式

---

### 3.3 Navigation修改（关键）

**当前**：navigation.sections在header-wrapper，使用标签页
**目标**：移动端侧边栏菜单

**用户思路**：
```xml
<!-- 1. 删除navigation.sections -->
<referenceBlock name="navigation.sections" remove="true"/>

<!-- 2. 把topmenu block放到header-wrapper -->
<referenceContainer name="header-wrapper">
    <block class="Magento\Theme\Block\Html\Topmenu" name="catalog.topnav" template="Magento_Theme::html/topmenu.phtml" before="-"/>
</referenceContainer>
```

**为什么这样做**：
- ✅ desktop端已经实现了navigation.sections的功能
- ✅ 把topmenu放到header-wrapper可以保留桌面端功能
- ✅ 移动端可以独立控制菜单样式
- ✅ 不破坏已有功能

---

### 3.4 样式优化

**当前问题**：
- 使用硬编码颜色值（#1E293B, #FF6B35等）
- 未使用_variables.less中的变量

**修改方式**：
- ✅ 替换所有硬编码颜色为变量
- ✅ 遵循移动优先原则

---

## 四、修改方案（不破坏已有功能）

### 4.1 default.xml修改

**仅添加移动端菜单**：
```xml
<!-- 移动端：删除navigation.sections，把topmenu放到header-wrapper -->
<!-- 桌面端：保持已有功能不变 -->

<!-- 这行已存在，不需要修改 -->
<move element="navigation.sections" destination="header-wrapper" after="logo" />

<!-- 需要添加：移动端侧边栏菜单容器 -->
<referenceContainer name="header-wrapper">
    <!-- 移动端菜单（覆盖原生的标签页样式） -->
    <block class="Magento\Theme\Block\Html\Topmenu" name="mobile.menu" template="Magento_Theme::html/header/mobile-menu.phtml" before="logo"/>
</referenceContainer>
```

**或者按照用户思路**：
```xml
<!-- 删除navigation.sections -->
<referenceBlock name="navigation.sections" remove="true"/>

<!-- 重新添加topmenu到header-wrapper -->
<referenceContainer name="header-wrapper">
    <block class="Magento\Theme\Block\Html\Topmenu" name="catalog.topnav" template="Magento_Theme::html/topmenu.phtml" before="-"/>
</referenceContainer>
```

---

### 4.2 _header.less修改（移动优先）

**原则**：
- 公共基础样式写在移动端区域
- 桌面端样式写在媒体查询内
- 替换硬编码颜色为变量

**修改结构**：
```less
& when (@media-common = true) {
    // 公共基础样式（移动端优先）
}

.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 移动端样式
    // Top Bar显示
    // Main Bar重构
    // 菜单侧边栏
}

.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    // 桌面端样式（保持已有功能不变）
}
```

---

## 五、总结

### 5.1 已实现功能（保留）

**桌面端**：
- ✅ Top Bar三列布局
- ✅ Main Bar完整功能
- ✅ navigation.sections已移动
- ✅ 登录按钮已添加

**移动端**：
- ✅ 汉堡菜单基础功能
- ✅ Logo居中显示
- ✅ 搜索图标显示

### 5.2 需要做的事（差异）

**移动端**：
1. ✅ Top Bar显示（汉堡菜单 + 品牌名 + 语言/货币 + 登录）
2. ✅ Main Bar重构（汉堡菜单 + 搜索 + 用户/登录）
3. ✅ 菜单侧边栏（删除navigation.sections，重新渲染topmenu）
4. ✅ 替换硬编码颜色为变量

**桌面端**：
- ✅ 保持不变

### 5.3 关键原则

1. ✅ **移动优先**：基础样式写在移动端区域
2. ✅ **不破坏已有功能**：桌面端功能完全保留
3. ✅ **只做差异**：只修改移动端需要的部分
4. ✅ **变量化**：使用_variables.less中的变量

---

## 六、下一步

**等待用户确认**：
1. Top Bar移动端布局是否正确？
2. Main Bar移动端布局是否正确？
3. 菜单修改方案（删除navigation.sections + 重新渲染topmenu）是否正确？
4. 是否开始编写代码？
