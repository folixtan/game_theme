# Folix Customer 模块架构说明

## 🎯 架构设计理念

### 核心策略：渐进式覆盖而非完全重写

我们采用**覆盖原生Block**的方式，而不是创建全新的路由和控制器。

### 为什么这样做？6大优势

#### 1️⃣ **完美兼容Magento核心架构**
- ✅ Block名称不变（`customer_account_dashboard_info`）
- ✅ 其他模块的Plugin/Observer仍然可以hook到
- ✅ 事件系统完整（`core_layout_block_create_after`等）
- ✅ 依赖注入不变

#### 2️⃣ **最小化侵入性**
- ✅ 不破坏原生流程（只是替换模板和Block类）
- ✅ 其他页面不受影响（`customer/account/edit`等仍然使用原生）
- ✅ 回滚容易（只需删除模块即可恢复原生）

#### 3️⃣ **灵活的扩展点**
- ✅ 子Block通过`as`别名管理
- ✅ 保留原生预留接口（`customer.account.dashboard.info.blocks`）
- ✅ 可随时增减内容，不影响外层结构

#### 4️⃣ **符合原生HTML结构**
- ✅ 外层结构不变：`<div class="block block-dashboard-info">`
- ✅ CSS选择器兼容（主题样式继续生效）
- ✅ 响应式布局完整（原生Grid/Flex布局保持）

#### 5️⃣ **清晰的职责分离**
- ✅ **Block类**：负责业务逻辑（继承原生方法 + 添加新逻辑）
- ✅ **主模板**：负责整体布局（保留原生结构 + 添加自定义区域）
- ✅ **子模板**：负责局部内容（welcome, stats, orders, keys）

#### 6️⃣ **易于维护和升级**
- ✅ Magento升级安全（不修改核心文件）
- ✅ 代码清晰（一看就知道是覆盖原生的）
- ✅ 团队协作友好（遵循Magento标准模式）

## 📊 Block继承关系（重要！）

### ✅ **正确的继承层次**

```
主Block（覆盖原生）:
Folix\Customer\Block\Dashboard 
    → 继承 Magento\Customer\Block\Account\Dashboard\Info
    → 目的：保留原生方法和模板变量（getCustomer, getName等）
    → 职责：主容器，渲染整体布局

子Block（自定义功能）:
Folix\Customer\Block\Account\Dashboard\Stats
Folix\Customer\Block\Account\Dashboard\RecentOrders
Folix\Customer\Block\Account\Dashboard\ActiveKeys
Folix\Customer\Block\Account\Dashboard\Wishlist
    → 全部继承 Magento\Framework\View\Element\Template
    → 目的：轻量级，只负责自己的业务逻辑
    → 职责：独立功能模块，通过getChildHtml()渲染
```

###  **为什么这样做？**

#### 1️⃣ **主Block必须继承原生Info**
- ✅ 需要访问原生的方法：`getCustomer()`, `getName()`, `getChangePasswordUrl()`
- ✅ 需要原生模板变量：`$block->isNewsletterEnabled()`, `$block->getIsSubscribed()`
- ✅ 保持与原生模板的兼容性
- ✅ 其他模块的Plugin/Observer仍然可以hook到

#### 2️⃣ **子Block继承基础Template**
- ✅ **职责单一**：每个子Block只负责自己的数据（Stats只管统计，Orders只管订单）
- ✅ **轻量级**：不需要加载原生的所有依赖（SubscriberFactory, View Helper等）
- ✅ **灵活性**：可以注入自己需要的Repository/Service
- ✅ **可测试性**：独立Unit Test更容易
- ✅ **性能更好**：避免不必要的依赖注入和初始化

### ❌ **错误的做法**

```php
// ❌ 错误：子Block继承原生的Dashboard
class Stats extends Dashboard
{
    // 这样会加载所有原生的依赖（SubscriberFactory, View Helper等）
    // 但Stats根本不需要这些！
}
```

### ✅ **正确的做法**

```php
// ✅ 正确：子Block继承基础Template
class Stats extends Template
{
    // 只注入自己需要的依赖
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        CustomerStatsRepositoryInterface $customerStatsRepository,
        array $data = []
    ) {
        // 轻量、高效、职责单一
    }
}
```

## 📋 文件结构

```
app/code/Folix/Customer/
├── Block/
│   ├── Dashboard.php                    # 主Block（继承原生Info）
│   └── Account/Dashboard/
│       ├── Stats.php                    # 子Block（继承Template）
│       ├── RecentOrders.php             # 子Block（继承Template）
│       ├── ActiveKeys.php               # 子Block（继承Template）
│       └── Wishlist.php                 # 子Block（继承Template）
├── view/frontend/
│   ├── layout/
│   │   ├── customer_account.xml         # 通用配置（移除导航、搜索框等）
│   │   └── customer_account_index.xml   # Dashboard页面（覆盖原生Block）
│   └── templates/
│       └── account/
│           ├── dashboard.phtml          # 主模板（复制原生info.phtml + 自定义）
│           └── dashboard/
│               ├── welcome.phtml        # 欢迎横幅
│               ├── stats.phtml          # 统计卡片
│               ├── recent_orders.phtml  # 最近订单
│               ├── wishlist.phtml       # 愿望清单
│               └── active_keys.phtml    # 游戏卡密
└── etc/
    ├── module.xml
    ├── di.xml
    └── ...
```

## 🔄 布局XML架构

### customer_account_index.xml

```xml
<referenceContainer name="content">
    <!-- 覆盖原生的customer_account_dashboard_info -->
    <referenceBlock name="customer_account_dashboard_info" 
                    class="Folix\Customer\Block\Dashboard"  
                    template="Folix_Customer::account/dashboard.phtml">
        
        <!-- 自定义子Block：使用专门的Block类 -->
        <block class="Folix\Customer\Block\Account\Dashboard\Stats" 
               name="folix.customer.stats" 
               as="stats" 
               template="Folix_Customer::account/dashboard/stats.phtml"/>
        
        <block class="Folix\Customer\Block\Account\Dashboard\RecentOrders" 
               name="folix.customer.orders" 
               as="orders" 
               template="Folix_Customer::account/dashboard/recent_orders.phtml"/>
        
        <block class="Folix\Customer\Block\Account\Dashboard\ActiveKeys" 
               name="folix.customer.keys" 
               as="keys" 
               template="Folix_Customer::account/dashboard/active_keys.phtml"/>
        
        <block class="Folix\Customer\Block\Account\Dashboard\Wishlist" 
               name="folix.customer.wishlist" 
               as="wishlist" 
               template="Folix_Customer::account/dashboard/wishlist.phtml"/>
        
        <!-- 保留原生的预留接口 -->
        <container name="customer.account.dashboard.info.blocks" as="additional_blocks"/>
    </referenceBlock>
    
    <!-- 移除不需要的原生Block -->
    <referenceBlock name="customer_account_dashboard_address" remove="true"/>
</referenceContainer>
```

## 🎨 模板结构

### dashboard.phtml（主模板）

```php
<?php /** @var \Folix\Customer\Block\Dashboard $block */ ?>

<!-- 保持原生外层结构 -->
<div class="block block-dashboard-info">
    <div class="block-title"><strong><?= $block->escapeHtml(__('Account Information')) ?></strong></div>
    <div class="block-content">
        
        <!-- 自定义：Welcome Banner -->
        <?= $block->getChildHtml('welcome') ?>
        
        <!-- 自定义：Purchase Stats -->
        <?= $block->getChildHtml('stats') ?>
        
        <!-- 原生：Contact Information -->
        <div class="box box-information">
            <!-- 原生结构 -->
        </div>
        
        <!-- 原生：Newsletter -->
        <?php if ($block->isNewsletterEnabled()): ?>
            <!-- 原生结构 -->
        <?php endif; ?>
        
        <!-- 自定义：Dashboard Grid -->
        <div class="dashboard-grid">
            <?= $block->getChildHtml('orders') ?>
            <?= $block->getChildHtml('wishlist') ?>
            <?= $block->getChildHtml('keys') ?>
        </div>
        
        <!-- 原生：预留接口 -->
        <?= $block->getChildHtml('additional_blocks'); ?>
    </div>
</div>
```

### stats.phtml（子模板）

```php
<?php /** @var \Folix\Customer\Block\Account\Dashboard\Stats $block */ ?>

<!-- Purchase Stats -->
<div class="purchase-stats">
    <div class="stat-item">
        <div class="stat-number"><?= (int)$block->getTotalOrders() ?></div>
        <div class="stat-label"><?= $block->escapeHtml(__('Total Orders')) ?></div>
    </div>
    <!-- ... -->
</div>
```

## 📊 Block类继承关系图

```
Magento\Framework\View\Element\Template  (基础类)
    ├── Magento\Customer\Block\Account\Dashboard\Info  (原生Dashboard)
    │   └── Folix\Customer\Block\Dashboard  (主Block)
    │       ├── 继承：getCustomer(), getName(), getChangePasswordUrl()
    │       ├── 继承：isNewsletterEnabled(), getIsSubscribed()
    │       └── 新增：getTotalOrders(), getTotalSpent(), getActiveKeysCount()
    │
    └── Folix\Customer\Block\Account\Dashboard\Stats  (子Block)
    └── Folix\Customer\Block\Account\Dashboard\RecentOrders  (子Block)
    └── Folix\Customer\Block\Account\Dashboard\ActiveKeys  (子Block)
    └── Folix\Customer\Block\Account\Dashboard\Wishlist  (子Block)
```

## 🎯 关键CSS类名（使用原生）

所有模板都使用Magento原生的CSS类名：

- `.block.block-dashboard-info` - 通用信息Block
- `.block-title` - 卡片标题
- `.block-content` - 卡片内容
- `.box` - 信息区块
- `.box-title` - 区块标题
- `.box-content` - 区块内容
- `.box-actions` - 操作按钮区
- `.info-list`, `.info-item` - 信息列表
- `.purchase-stats`, `.stat-item` - 统计卡片
- `.welcome-banner` - 欢迎横幅
- `.dashboard-grid` - Dashboard网格
- `.order-item` - 订单项
- `.wishlist-items`, `.wishlist-item` - 愿望清单
- `.keys-preview`, `.key-item` - 游戏卡密

## 🚀 部署步骤

1. 启用模块：
```bash
bin/magento module:enable Folix_Customer
```

2. 运行安装：
```bash
bin/magento setup:upgrade
```

3. 编译DI：
```bash
bin/magento setup:di:compile
```

4. 部署静态资源：
```bash
bin/magento setup:static-content:deploy -f
```

5. 清理缓存：
```bash
bin/magento cache:clean
```

## 📝 后续优化

- [ ] 集成Folix_CardKeys模块获取真实卡密数据
- [ ] 集成原生Wishlist模块获取真实愿望清单
- [ ] 集成原生Sales模块获取真实订单列表
- [ ] 添加缓存机制提升性能

## 📚 参考

- Magento原生Customer模块：`vendor/magento/module-customer/`
- Magento原生Wishlist模块：`vendor/magento/module-wishlist/`
- 原生Dashboard布局：`vendor/magento/module-customer/view/frontend/layout/customer_account_index.xml`
- 原生Info模板：`vendor/magento/module-customer/view/frontend/templates/account/dashboard/info.phtml`
