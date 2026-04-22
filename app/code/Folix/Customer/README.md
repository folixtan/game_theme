# Folix_Customer Module

## 模块概述

游戏充值平台账户页面定制化模块，基于 Magento 原生 Customer 模块扩展。

## 设计原则

1. **渐进式改造**：不破坏原生功能，通过扩展实现定制
2. **保持兼容**：所有主题都可以复用此模块
3. **职责分离**：模块负责业务逻辑，主题负责样式覆盖
4. **预留接口**：利用原生预留接口扩展功能

## 模块结构

```
app/code/Folix/Customer/
├── etc/
│   ├── module.xml                          # 模块声明
│   ├── di.xml                              # 依赖注入配置
│   ├── events.xml                          # 事件监听配置
│   ├── crontab.xml                         # Cron任务配置
│   ├── db_schema.xml                       # 数据库表定义
│   ├── db_schema_whitelist.json            # 声明式Schema白名单
│   └── frontend/di.xml                     # 前端DI配置
├── Api/
│   └── CustomerStatsRepositoryInterface.php # 统计Repository接口
├── Model/
│   ├── CustomerStatsRepository.php         # 统计Repository实现
│   └── ResourceModel/
│       └── CustomerStats.php               # 统计ResourceModel
├── Block/
│   └── Account/Dashboard/
│       ├── Stats.php                       # 购买统计Block（优化版）
│       ├── RecentOrders.php                # 最近订单Block
│       ├── ActiveKeys.php                  # 活跃卡密Block（占位符）
│       └── Wishlist.php                    # 愿望清单Block（占位符）
├── Observer/
│   └── UpdateCustomerStats.php             # 订单完成事件监听
├── Cron/
│   └── RecalculateCustomerStats.php        # 定时校准任务
├── view/frontend/
│   ├── layout/
│   │   ├── customer_account.xml            # 账户页面通用布局
│   │   └── customer_account_index.xml      # Dashboard页面布局
│   ├── templates/account/dashboard/
│   │   ├── welcome.phtml                   # 欢迎横幅
│   │   ├── stats.phtml                     # 购买统计
│   │   ├── info.phtml                      # 账户信息
│   │   ├── recent_orders.phtml             # 最近订单
│   │   ├── wishlist.phtml                  # 愿望清单
│   │   └── active_keys.phtml               # 活跃卡密
│   └── web/css/source/
│       └── _module.less                    # 顶级样式（完整定义）
└── registration.php                        # 模块注册
```

## 功能特性

### 1. Header定制
- ✅ 移除主导航菜单
- ✅ 移除搜索框
- ✅ 保留Logo、Topbar、购物车

### 2. Dashboard功能（基于account-dashboard-v3.html）
- ✅ Welcome Banner（欢迎横幅）
- ✅ Purchase Stats（购买统计）
  - Total Orders（从统计表读取）
  - Total Spent（从统计表读取）
  - Active Keys（从统计表读取，TODO: 卡密模块）
- ✅ Account Information（账户信息卡片）
- ✅ Recent Orders（最近订单，限5条）
- ✅ My Wish List（愿望清单，限2条）
- ✅ Active Game Keys（活跃卡密，限2条）

### 3. 移除功能
- ✅ Address Book（地址簿 - 游戏充值平台不需要）
- ✅ Newsletter（邮件订阅 - 游戏充值平台不需要）

### 4. 待集成功能（TODO）
- ⏳ CardKeys模块（活跃卡密真实数据）
- ⏳ Magento_Wishlist模块（愿望清单真实数据）
- ⏳ Security Settings（安全设置）
- ⏳ Card Keys Management（卡密管理页面）

## 布局机制

### customer_account.xml（通用布局）
```xml
<referenceBlock name="navigation.sections" remove="true"/>
<referenceBlock name="top.search" remove="true"/>
<referenceContainer name="sidebar.additional" remove="true"/>
```

### customer_account_index.xml（Dashboard布局）
```xml
<update handle="customer_account"/>
<referenceBlock name="customer_account_dashboard_address" remove="true"/>
<referenceBlock name="customer_account_dashboard_info" remove="true"/>

<!-- 添加自定义Block到content容器 -->
<referenceContainer name="content">
    <block class="..." name="customer.account.dashboard.welcome" .../>
    <block class="..." name="customer.account.dashboard.stats" .../>
    <block class="..." name="customer.account.dashboard.info" .../>
    ...
</referenceContainer>
```

## Block层次结构

### 原生Customer模块架构
```
customer_account.xml (通用布局)
└── sidebar.main
    └── customer_account_navigation (左侧导航)
        ├── SortLinkInterface (可排序链接)
        └── Delimiter (分隔线)

customer_account_index.xml (Dashboard)
└── content
    ├── customer_account_dashboard_info (账户信息)
    │   ├── customer.account.dashboard.info.extra (预留接口1)
    │   └── additional_blocks (预留接口2 - Container)
    └── customer_account_dashboard_address (地址簿)
```

### Folix_Customer扩展
```
customer_account_index.xml (覆盖)
└── content
    ├── customer.account.dashboard.welcome (欢迎横幅)
    ├── customer.account.dashboard.stats (购买统计)
    ├── customer.account.dashboard.info (账户信息 - 自定义模板)
    ├── customer.account.dashboard.recent_orders (最近订单)
    ├── customer.account.dashboard.wishlist (愿望清单)
    └── customer.account.dashboard.active_keys (活跃卡密)
```

## 样式系统

### _module.less（顶级样式）
- 完整定义所有账户页面样式
- 基于 `account-dashboard-v3.html` 设计
- 使用Magento标准LESS mixin（`media-width`）
- 遵循移动优先响应式设计

### 样式优先级
1. 原生Magento样式（不修改）
2. `_module.less`（模块顶级样式，完整覆盖）
3. 主题 `_extend.less`（可选，用于特定主题微调）

## 安装与启用

```bash
# 1. 启用模块
bin/magento module:enable Folix_Customer

# 2. 升级数据库
bin/magento setup:upgrade

# 3. 编译DI（生产模式）
bin/magento setup:di:compile

# 4. 清理缓存
bin/magento cache:clean

# 5. 部署静态内容（生产模式）
bin/magento setup:static-content:deploy -f
```

## 开发指南

### 添加新Block
1. 在 `Block/Account/Dashboard/` 创建Block类
2. 在 `templates/account/dashboard/` 创建PHTML模板
3. 在 `customer_account_index.xml` 中注册Block
4. 在 `_module.less` 中添加样式

### 利用预留接口
```xml
<!-- 在customer_account_index.xml中 -->
<referenceBlock name="customer_account_dashboard_info">
    <block class="Your\Block" name="your.block.name" template="Your_Module::your_template.phtml"/>
</referenceBlock>
```

### 扩展原生Block
```php
namespace Folix\Customer\Block\Account\Dashboard;

use Magento\Customer\Block\Account\Dashboard\Info;

class YourBlock extends Info
{
    // 继承原生方法
    // 添加自定义方法
}
```

## 兼容性

- ✅ Magento 2.4+
- ✅ PHP 8.1+
- ✅ 所有主题通用
- ✅ 向后兼容原生功能

## 统计数据系统（新增）

### 为什么需要统计表？

**问题**：
- ❌ Dashboard每次加载都实时查询Order表（COUNT/SUM）
- ❌ 订单表可能有数十万条记录，性能差
- ❌ 高频访问导致数据库压力大

**解决方案**：
- ✅ 创建专用统计表 `folix_customer_stats`
- ✅ Observer监听订单完成事件，实时更新统计
- ✅ Cron定时校准数据（每天凌晨2点）
- ✅ Block读取统计表（主键查询 < 1ms）

### 数据库表结构

```sql
CREATE TABLE `folix_customer_stats` (
  `customer_id` INT UNSIGNED NOT NULL COMMENT 'Customer ID',
  `total_orders` INT UNSIGNED DEFAULT 0 COMMENT '总订单数',
  `total_spent` DECIMAL(12,4) DEFAULT 0.0000 COMMENT '总消费金额',
  `active_keys` INT UNSIGNED DEFAULT 0 COMMENT '活跃卡密数',
  `last_order_at` DATETIME DEFAULT NULL COMMENT '最后订单时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  CONSTRAINT `FK_CUSTOMER_STATS_CUSTOMER` FOREIGN KEY (`customer_id`) 
    REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 数据流

```
订单完成 (sales_order_save_after)
    ↓
Observer: UpdateCustomerStats
    ↓
Repository: CustomerStatsRepository::recalculate()
    ↓
ResourceModel: 重新计算COUNT/SUM
    ↓
统计表: folix_customer_stats (INSERT ON DUPLICATE KEY UPDATE)
    ↓
Dashboard加载 (Stats Block)
    ↓
读取统计表 (主键查询 < 1ms)
```

### Cron校准任务

- **执行时间**：每天凌晨2:00
- **作用**：重新计算所有用户的统计数据
- **防止**：Observer遗漏或失败导致的数据不一致

### 性能对比

| 方案 | 查询方式 | 响应时间 | 数据库压力 |
|------|---------|---------|-----------|
| 实时查询 | COUNT/SUM全表扫描 | 50-200ms | 高 |
| 统计表 | 主键查询 | < 1ms | 极低 |

## 卡密模块架构设计（TODO）

### 概述

卡密模块 (`Folix_CardKeys`) 是游戏充值平台的核心功能，用于管理从第三方API获取的游戏卡密。

### 数据库表设计

**表名**: `folix_card_keys`

```sql
CREATE TABLE `folix_card_keys` (
  `entity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `order_id` INT UNSIGNED NOT NULL COMMENT 'Sales Order ID',
  `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT 'Sales Order Item ID',
  `customer_id` INT UNSIGNED NOT NULL COMMENT 'Customer ID',
  `product_id` INT UNSIGNED NOT NULL COMMENT 'Product ID',
  `product_name` VARCHAR(255) DEFAULT NULL COMMENT 'Product Name',
  
  -- 卡密信息
  `key_code` VARCHAR(500) NOT NULL COMMENT 'Card Key Code (加密存储)',
  `key_format` VARCHAR(50) DEFAULT 'standard' COMMENT 'Key Format (standard, multi-line, json)',
  
  -- 第三方订单信息
  `third_party_order_id` VARCHAR(100) DEFAULT NULL COMMENT 'Third Party Order ID',
  `third_party_response` TEXT DEFAULT NULL COMMENT 'Third Party API Response (JSON)',
  `third_party_status` VARCHAR(50) DEFAULT 'pending' COMMENT 'Third Party Status',
  
  -- 状态管理
  `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'Key Status (pending, active, used, expired, failed)',
  `expires_at` DATETIME DEFAULT NULL COMMENT 'Key Expiration Date',
  `activated_at` DATETIME DEFAULT NULL COMMENT 'Key Activated At',
  `used_at` DATETIME DEFAULT NULL COMMENT 'Key Used At',
  
  -- 元数据
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
  
  PRIMARY KEY (`entity_id`),
  UNIQUE KEY `FOLIX_CARD_KEYS_ORDER_ITEM_ID` (`order_item_id`),
  KEY `FOLIX_CARD_KEYS_ORDER_ID` (`order_id`),
  KEY `FOLIX_CARD_KEYS_CUSTOMER_ID` (`customer_id`),
  KEY `FOLIX_CARD_KEYS_STATUS` (`status`),
  KEY `FOLIX_CARD_KEYS_EXPIRES_AT` (`expires_at`),
  
  CONSTRAINT `FK_FOLIX_CARD_KEYS_ORDER_ID_SALES_ORDER_ENTITY_ID` 
    FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`entity_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_FOLIX_CARD_KEYS_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID` 
    FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Folix Card Keys';
```

### 关联关系

```
sales_order (1) ←→ (N) folix_card_keys
    ↓
order_item (1) ←→ (1) folix_card_keys (通过order_item_id唯一索引)
```

### 第三方API流程

```
用户下单付款
    ↓
订单状态变为 processing/complete
    ↓
触发卡密获取流程 (Observer/Cron)
    ↓
调用第三方API (传递订单信息)
    ↓
第三方返回卡密数据 (JSON)
    ↓
保存到 folix_card_keys 表
    ↓
Dashboard显示卡密 (用户可复制)
```

### 关键设计点

1. **外键关联**：
   - `order_id` → `sales_order.entity_id` (CASCADE DELETE)
   - `customer_id` → `customer_entity.entity_id` (CASCADE DELETE)
   - `order_item_id` 唯一索引 (一个订单项对应一个卡密)

2. **第三方响应存储**：
   - `third_party_response` 字段存储完整的第三方API返回结果 (JSON)
   - 便于问题排查和数据追溯
   - 用户可以直接复制原始响应数据

3. **状态管理**：
   - `pending`: 等待第三方返回
   - `active`: 卡密可用
   - `used`: 卡密已使用
   - `expired`: 卡密已过期
   - `failed`: 第三方返回失败

4. **数据安全**：
   - `key_code` 建议加密存储
   - 第三方API密钥通过环境变量配置
   - 访问控制：用户只能查看自己的卡密

### 模块结构（规划中）

```
app/code/Folix/CardKeys/
├── etc/
│   ├── module.xml
│   ├── db_schema.xml
│   ├── events.xml
│   ├── crontab.xml
│   └── di.xml
├── Api/
│   ├── CardKeyRepositoryInterface.php
│   └── Data/CardKeyInterface.php
├── Model/
│   ├── CardKey.php
│   ├── CardKeyRepository.php
│   ├── ResourceModel/CardKey.php
│   └── ThirdParty/ApiClient.php
├── Observer/
│   ├── ProcessCardKey.php
│   └── SaveThirdPartyResponse.php
├── Cron/
│   ├── SyncCardKeys.php
│   └── CleanExpiredKeys.php
├── Block/
│   └── Account/CardKeyList.php
├── Controller/
│   └── Account/CardKeys.php
└── view/frontend/
    ├── layout/folix_cardkeys_account_index.xml
    └── templates/account/
        ├── card_keys.phtml
        └── card_keys_list.phtml
```

### 与 Folix_Customer 模块的集成

**当前状态**: 占位符数据  
**集成计划**:
1. 创建 `Folix_CardKeys` 模块
2. 实现 `CardKeyRepositoryInterface`
3. 在 `Folix_Customer/Block/Account/Dashboard/ActiveKeys` 中注入 Repository
4. 替换示例数据为真实数据

**集成代码示例**:
```php
// Folix_Customer/Block/Account/Dashboard/ActiveKeys.php

use Folix\CardKeys\Api\CardKeyRepositoryInterface;

class ActiveKeys extends Dashboard
{
    private $cardKeyRepository;
    
    public function __construct(
        ...,
        CardKeyRepositoryInterface $cardKeyRepository
    ) {
        $this->cardKeyRepository = $cardKeyRepository;
        parent::__construct(...);
    }
    
    public function getActiveKeys(): array
    {
        $customerId = $this->customerSession->getCustomerId();
        
        // 获取活跃卡密（限制2条）
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->addFilter('status', 'active')
            ->setPageSize(2)
            ->create();
        
        $result = $this->cardKeyRepository->getList($searchCriteria);
        
        $keys = [];
        foreach ($result->getItems() as $cardKey) {
            $keys[] = [
                'key_code' => $cardKey->getKeyCode(),
                'product_name' => $cardKey->getProductName(),
                'status' => $cardKey->getStatus(),
                'expires_at' => $cardKey->getExpiresAt(),
                'third_party_response' => $cardKey->getThirdPartyResponse()
            ];
        }
        
        return $keys;
    }
}
```

## 后续计划

1. **集成CardKeys模块**：实现真实卡密数据
2. **集成Wishlist模块**：实现真实愿望清单
3. **添加Security Settings**：密码、邮箱、手机等安全设置
4. **Card Keys Management**：独立卡密管理页面
5. **消息中心**：替代Newsletter

## 技术债务

- TODO: Stats Block中的Active Keys需要从CardKeys模块获取
- TODO: Wishlist Block需要集成Magento_Wishlist
- TODO: ActiveKeys Block需要集成CardKeys模块
- TODO: 添加消息中心功能
- TODO: 为统计表添加Redis缓存（可选，进一步降低数据库查询）

## License

Folix Game Theme - All rights reserved.
