# Folix Game Theme 导航重新设计方案

## 一、设计目标

符合游戏主题的 B 方案：
- PC 端：顶部栏 + 主导航栏
- 移动端：侧滑导航抽屉

## 二、结构设计

### PC 端

```
┌─────────────────────────────────────────────────────────┐
│ Top Bar（顶部栏）- 深蓝渐变背景，高度 40px                │
├─────────────────────────────────────────────────────────┤
│ [新闻] [奖励] [支持]         [APP] [语言] [登录/用户]   │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Main Bar（主导航栏）- 白色背景，高度 70px                 │
├─────────────────────────────────────────────────────────┤
│ [Logo]  [游戏充值] [新游] [特惠]  [搜索框]              │
└─────────────────────────────────────────────────────────┘
```

### 移动端（B 方案侧滑抽屉）

```
┌─────────────────────────────────────┐
│ FOLIX GAME              [×]         │ ← 顶部：品牌 + 关闭
├─────────────────────────────────────┤
│ [Menu]  [Account]                   │ ← 标签页
├─────────────────────────────────────┤
│                                     │
│ • 热门游戏 🌟                       │
│ • 新品上架 ⚡                       │
│ • 限时特惠 🎁                       │
│ • 充值中心 💳                       │
│                                     │ ← Menu 内容
│ • 我的账户                         │
│ • 我的订单                         │
│ • 退出登录                         │
│                                     │
├─────────────────────────────────────┤
│ [搜索框]                           │ ← 底部：搜索
└─────────────────────────────────────┘
```

## 三、布局配置

### 1. header.panel（顶部栏）

**保持不变**，包含：
- 左侧：新闻、奖励、支持链接
- 右侧：APP、语言、登录/用户（header.links）

```xml
<referenceContainer name="header.panel">
    <block class="Magento\Customer\Block\Account\Navigation" name="header.links">
        <arguments>
            <argument name="css_class" xsi:type="string">header links</argument>
        </arguments>
    </block>
</referenceContainer>
```

### 2. page.top（主导航栏）

**PC 端**：
- Logo
- 导航菜单（catalog.topnav）
- 搜索框

**移动端**：
- navigation.sections（侧滑抽屉）
  - Menu tab（导航菜单）
  - Account tab（移动端用户信息）

```xml
<referenceContainer name="page.top">
    <!-- PC 端导航 -->
    <block class="Magento\Theme\Block\Html\Topmenu"
           name="catalog.topnav"
           template="Magento_Theme::html/topmenu.phtml"
           ttl="3600" />

    <!-- 移动端侧滑导航抽屉 -->
    <block class="Magento\Framework\View\Element\Template"
           name="navigation.sections"
           template="Magento_Theme::html/sections.phtml"
           before="-">
        <arguments>
            <argument name="group_name" xsi:type="string">navigation-sections-mobile"></argument>
            <argument name="group_css" xsi:type="string">nav-sections mobile-sidebar"></argument>
        </arguments>

        <!-- Menu tab -->
        <block class="Magento\Framework\View\Element\Template"
               name="mobile.menu"
               group="navigation-sections-mobile"
               template="Magento_Theme::html/container.phtml">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Menu</argument>
            </arguments>
            <!-- 导航菜单 -->
            <block class="Magento\Theme\Block\Html\Topmenu"
                   name="mobile.catalog.topnav"
                   template="Magento_Theme::html/topmenu.phtml"
                   ttl="3600" />
        </block>

        <!-- Account tab -->
        <block class="Magento\Customer\Block\Account\Customer"
               name="mobile.account"
               group="navigation-sections-mobile"
               template="Magento_Theme::html/header/mobile-account.phtml">
            <arguments>
                <argument name="title" translate="true" xsi:type="string">Account</argument>
            </arguments>
        </block>
    </block>
</referenceContainer>
```

## 四、模板设计

### 1. sections.phtml（移动端侧滑抽屉）

```php
<?php
// 保留原生的 PHP 逻辑
$group = $block->getGroupName();
$groupCss = $block->getGroupCss();
?>
<?php if ($detailedInfoGroup = $block->getGroupChildNames($group)):?>
    <!-- 移动端侧滑导航抽屉 -->
    <div class="sections <?= $block->escapeHtmlAttr($groupCss) ?> mobile-sidebar">
        <?php $layout = $block->getLayout(); ?>

        <!-- 顶部：品牌标识 + 关闭按钮 -->
        <div class="mobile-sidebar-header">
            <div class="mobile-nav-logo">
                <span class="brand-name">FOLIX GAME</span>
            </div>
            <button type="button" class="mobile-nav-close"
                    aria-label="<?= $block->escapeHtmlAttr(__('Close')) ?>"
                    data-action="close-nav">
                <span>×</span>
            </button>
        </div>

        <!-- 标签页区域 -->
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
                <!-- 标签页标题 -->
                <div class="section-item-title <?= $block->escapeHtmlAttr($groupCss) ?>-item-title"
                     data-role="collapsible">
                    <a class="<?= $block->escapeHtmlAttr($groupCss) ?>-item-switch"
                       data-toggle="switch" href="#<?= $block->escapeHtmlAttr($alias) ?>">
                        <?= /* @noEscape */ $label ?>
                    </a>
                </div>
                <!-- 标签页内容 -->
                <div class="section-item-content <?= $block->escapeHtmlAttr($groupCss) ?>-item-content"
                     id="<?= $block->escapeHtmlAttr($alias) ?>"
                     data-role="content">
                    <?= /* @noEscape */ $html ?>
                </div>
            <?php endforeach;?>
        </div>

        <!-- 底部：搜索框 -->
        <div class="mobile-sidebar-footer">
            <div class="mobile-search">
                <?= $block->getChildHtml('mobile.search') ?>
            </div>
        </div>
    </div>
<?php endif; ?>
```

### 2. mobile-account.phtml（移动端用户信息）

```php
<?php
/**
 * Folix Game Theme - Mobile Account Tab
 *
 * @var $block \Magento\Customer\Block\Account\Customer
 */
?>

<?php if ($block->isLoggedIn()): ?>
    <!-- 已登录 -->
    <div class="mobile-account-info">
        <div class="account-header">
            <div class="account-avatar">
                <?php
                $customer = $block->getCustomer();
                $firstName = $customer->getFirstname();
                $lastName = $customer->getLastname();
                $initial = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                ?>
                <span class="avatar-text"><?= $block->escapeHtml($initial) ?></span>
            </div>
            <div class="account-name">
                <span class="greeting">Welcome,</span>
                <span class="username"><?= $block->escapeHtml($firstName . ' ' . $lastName) ?></span>
            </div>
        </div>

        <div class="account-links">
            <a href="<?= $block->escapeUrl($block->getUrl('customer/account')) ?>"
               class="account-link">
                <span class="icon">👤</span>
                <span><?= $block->escapeHtml(__('My Account')) ?></span>
            </a>
            <a href="<?= $block->escapeUrl($block->getUrl('sales/order/history')) ?>"
               class="account-link">
                <span class="icon">📦</span>
                <span><?= $block->escapeHtml(__('My Orders')) ?></span>
            </a>
            <a href="<?= $block->escapeUrl($block->getUrl('customer/account/logout')) ?>"
               class="account-link logout">
                <span class="icon">🚪</span>
                <span><?= $block->escapeHtml(__('Sign Out')) ?></span>
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- 未登录 -->
    <div class="mobile-account-guest">
        <div class="guest-message">
            <p><?= $block->escapeHtml(__('Sign in to access your account')) ?></p>
        </div>

        <div class="guest-actions">
            <a href="#social-login-popup"
               class="action primary social-login-btn"
               data-effect="mfp-move-from-top">
                <span><?= $block->escapeHtml(__('Sign In')) ?></span>
            </a>
            <a href="#social-login-popup"
               class="action secondary social-login-btn"
               data-effect="mfp-move-from-top">
                <span><?= $block->escapeHtml(__('Create an Account')) ?></span>
            </a>
        </div>
    </div>
<?php endif; ?>
```

## 五、CSS 样式

### 移动端侧滑抽屉

```less
.mobile-sidebar {
    position: fixed;
    top: 0;
    left: -100%;
    width: 300px;
    height: 100vh;
    background: @folix-bg-dark;
    transition: left 0.3s ease;
    z-index: 1000;
    display: flex;
    flex-direction: column;

    &.active {
        left: 0;
    }

    .mobile-sidebar-header {
        padding: 20px;
        background: @folix-gradient-dark;
        border-bottom: 2px solid @folix-secondary;
        display: flex;
        justify-content: space-between;
        align-items: center;

        .mobile-nav-logo {
            .brand-name {
                color: @folix-secondary;
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
        }

        .mobile-nav-close {
            background: transparent;
            border: none;
            color: @folix-text-secondary;
            font-size: 30px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    .section-items {
        flex: 1;
        overflow-y: auto;

        .section-item-title {
            padding: 15px 20px;
            background: @folix-bg-darker;
            border-bottom: 1px solid @folix-border;

            .nav-sections-item-switch {
                color: @folix-text-primary;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
        }

        .section-item-content {
            padding: 10px 0;
        }
    }

    .mobile-sidebar-footer {
        padding: 15px 20px;
        background: @folix-bg-darker;
        border-top: 1px solid @folix-border;
    }
}
```

## 六、总结

✅ **PC 端**：header.panel 保留登录按钮
✅ **移动端**：侧滑抽屉，两个 tab（Menu、Account）
✅ **登录状态**：使用 Customer Block 自动处理
✅ **符合游戏主题**：电竞风设计

---

**设计时间**：2025-01-XX
**设计人**：AI Assistant
**状态**：待实施
