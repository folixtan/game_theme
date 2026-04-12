# 原生 Magento 导航系统结构分析

## 一、核心组件

### 1. sections.phtml（核心渲染器）

**位置**：`/workspace/projects/assets/module-theme/view/frontend/templates/html/sections.phtml`

**功能**：渲染所有 section（标签页）

**PHP 逻辑**：
```php
// 获取 group 配置
$group = $block->getGroupName();         // navigation-sections
$groupCss = $block->getGroupCss();       // nav-sections

// 获取所有子块
$detailedInfoGroup = $block->getGroupChildNames($group);

// 遍历子块，生成标题和内容
foreach ($detailedInfoGroup as $name) {
    // 渲染子块内容
    $html = $layout->renderElement($name);

    // 获取子块信息
    $alias = $layout->getElementAlias($name);    // store.menu, store.links
    $label = $block->getChildData($alias, 'title');  // Menu, Account

    // 生成 HTML
    <div class="section-item-title">
        <a href="#<?= $alias ?>"><?= $label ?></a>
    </div>
    <div class="section-item-content" id="<?= $alias ?>">
        <?= $html ?>
    </div>
}
```

**生成的 HTML**：
```html
<div class="sections nav-sections">
    <div class="section-items nav-sections-items"
         data-mage-init='{"tabs":{"openedState":"active"}}'>

        <!-- Menu tab -->
        <div class="section-item-title nav-sections-item-title">
            <a class="nav-sections-item-switch" href="#store.menu">
                Menu
            </a>
        </div>
        <div class="section-item-content nav-sections-item-content" id="store.menu">
            <!-- Menu 内容 -->
        </div>

        <!-- Account tab -->
        <div class="section-item-title nav-sections-item-title">
            <a class="nav-sections-item-switch" href="#store.links">
                Account
            </a>
        </div>
        <div class="section-item-content nav-sections-item-content" id="store.links">
            <!-- Account 内容 -->
        </div>

    </div>
</div>
```

---

## 二、布局配置（default.xml）

### 1. navigation.sections 配置

```xml
<referenceContainer name="page.top">
    <block class="Magento\Framework\View\Element\Template"
           name="navigation.sections"
           template="Magento_Theme::html/sections.phtml">
        <!-- Group 配置 -->
        <arguments>
            <argument name="group_name" xsi:type="string">navigation-sections</argument>
            <argument name="group_css" xsi:type="string">nav-sections</argument>
        </arguments>

        <!-- Menu 区块 -->
        <block class="Magento\Framework\View\Element\Template"
               name="store.menu"
               group="navigation-sections"
               template="Magento_Theme::html/container.phtml">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Menu</argument>
            </arguments>
            <!-- Menu 内容 -->
            <block class="Magento\Theme\Block\Html\Topmenu"
                   name="catalog.topnav"
                   template="Magento_Theme::html/topmenu.phtml"
                   ttl="3600" />
        </block>

        <!-- Account 区块 -->
        <block class="Magento\Framework\View\Element\Text"
               name="store.links"
               group="navigation-sections">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Account</argument>
                <argument name="use_force" xsi:type="boolean">true</argument>
            </arguments>
        </block>

        <!-- Settings 区块（可选） -->
        <block class="Magento\Framework\View\Element\Template"
               name="store.settings"
               group="navigation-sections"
               template="Magento_Theme::html/container.phtml">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Settings</argument>
            </arguments>
        </block>
    </block>
</referenceContainer>
```

---

## 三、关键点分析

### 1. sections.phtml 的关键

| 方法 | 说明 |
|------|------|
| `getGroupName()` | 获取 group name（navigation-sections） |
| `getGroupCss()` | 获取 group css（nav-sections） |
| `getGroupChildNames($group)` | 获取所有子块 |
| `renderElement($name)` | 渲染子块内容 |
| `getElementAlias($name)` | 获取子块别名 |
| `getChildData($alias, 'title')` | 获取子块的 title |

### 2. 子块配置的关键

| 属性 | 说明 |
|------|------|
| `group` | 指定属于哪个 group（navigation-sections） |
| `title` | 标签页标题 |
| `template` | 模板文件（可选） |

### 3. 布局结构

```
page.top
└── navigation.sections (sections.phtml)
    └── section-items
        ├── store.menu (group="navigation-sections", title="Menu")
        │   └── catalog.topnav (topmenu.phtml)
        ├── store.links (group="navigation-sections", title="Account")
        │   └── header.links
        └── store.settings (group="navigation-sections", title="Settings")
            ├── store.settings.language
            └── store.settings.currency
```

---

## 四、B 方案改造思路

### 目标
- 保留 navigation.sections 结构
- 只有两个 tab：Menu、Account
- 使用 CSS 控制为侧滑弹窗样式

### 改造方案

#### 1. 修改布局（default.xml）

```xml
<referenceContainer name="page.top">
    <block class="Magento\Framework\View\Element\Template"
           name="navigation.sections"
           template="Magento_Theme::html/sections.phtml">
        <arguments>
            <argument name="group_name" xsi:type="string">navigation-sections</argument>
            <argument name="group_css" xsi:type="string">nav-sections mobile-sidebar"></argument>
        </arguments>

        <!-- Menu 区块 -->
        <block class="Magento\Framework\View\Element\Template"
               name="store.menu"
               group="navigation-sections"
               template="Magento_Theme::html/container.phtml">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Menu</argument>
            </arguments>
            <block class="Magento\Theme\Block\Html\Topmenu"
                   name="catalog.topnav"
                   template="Magento_Theme::html/topmenu.phtml"
                   ttl="3600" />
        </block>

        <!-- Account 区块 -->
        <block class="Magento\Framework\View\Element\Text"
               name="store.links"
               group="navigation-sections">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Account</argument>
                <argument name="use_force" xsi:type="boolean">true</argument>
            </arguments>
        </block>
    </block>
</referenceContainer>
```

#### 2. 覆盖 sections.phtml

```php
<?php
// 保持原生的 PHP 逻辑
$group = $block->getGroupName();
$groupCss = $block->getGroupCss();
?>
<?php if ($detailedInfoGroup = $block->getGroupChildNames($group)):?>
    <!-- 添加移动端侧滑导航的 class -->
    <div class="sections <?= $block->escapeHtmlAttr($groupCss) ?> mobile-sidebar">
        <div class="mobile-sidebar-header">
            <!-- 品牌 + 关闭按钮 -->
        </div>
        <?php $layout = $block->getLayout(); ?>
        <div class="section-items <?= $block->escapeHtmlAttr($groupCss) ?>-items"
             data-mage-init='{"tabs":{"openedState":"active"}}'>
            <?php foreach ($detailedInfoGroup as $name):?>
                <?php
                    $html = $layout->renderElement($name);
                if (!($html !== null && trim($html)) && ($block->getUseForce() != true)) {
                    continue;
                }
                    $alias = $layout->getElementAlias($name);
                    $label = $block->getChildData($alias, 'title');
                ?>
                <div class="section-item-title <?= $block->escapeHtmlAttr($groupCss) ?>-item-title"
                     data-role="collapsible">
                    <a class="<?= $block->escapeHtmlAttr($groupCss) ?>-item-switch"
                       data-toggle="switch" href="#<?= $block->escapeHtmlAttr($alias) ?>">
                        <?= /* @noEscape */ $label ?>
                    </a>
                </div>
                <div class="section-item-content <?= $block->escapeHtmlAttr($groupCss) ?>-item-content"
                     id="<?= $block->escapeHtmlAttr($alias) ?>"
                     data-role="content">
                    <?= /* @noEscape */ $html ?>
                </div>
            <?php endforeach;?>
        </div>
    </div>
<?php endif; ?>
```

#### 3. CSS 控制样式

```less
.mobile-sidebar {
    // 侧滑弹窗样式
    position: fixed;
    top: 0;
    left: -100%;
    width: 300px;
    height: 100vh;
    background: @folix-bg-dark;
    transition: left 0.3s ease;

    &.active {
        left: 0;
    }
}
```

---

## 五、总结

### 原生导航系统的关键

1. **sections.phtml 是核心渲染器**
2. **通过 group 收集子块**
3. **自动生成标签页结构**
4. **使用 tabs widget 控制切换**

### B 方案改造的关键

1. **保留 navigation.sections 结构**
2. **只改 sections.phtml 的 HTML 结构**
3. **添加 CSS class 控制样式**
4. **使用 JavaScript 控制侧滑效果**

---

**分析时间**：2025-01-XX
**分析人**：AI Assistant
**状态**：已理解原生结构，准备实施改造
