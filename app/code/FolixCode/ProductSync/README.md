# ProductSync Module

## 概述

`FolixCode_ProductSync` 是产品同步业务模块，专门为**游戏充值项目**设计，负责虚拟商品数据的同步、导入和处理。

## 项目特点

### 充值类型支持

支持两种游戏充值方式：

1. **直充** - 直接充值到游戏账户
2. **卡密** - 购买充值卡密

### 产品类型

- 所有产品自动设置为**虚拟产品**（Virtual Product）
- 不需要库存管理
- 不需要配送

## 职责

本模块负责所有与产品同步相关的业务逻辑：

1. **API服务** - 调用虚拟商品API（商品列表、分类、详情）
2. **同步管理** - 协调同步流程
3. **数据导入** - 将外部数据导入到Magento（支持游戏充值属性）
4. **消息队列** - 异步处理同步任务
5. **Cron任务** - 定时自动同步
6. **Console命令** - 手动触发同步

## 架构

```
Presentation Layer
├── Console/Command/SyncCommand.php    # 命令行工具
└── Cron/SyncProducts.php              # 定时任务

Business Logic Layer
├── Service/
│   ├── SyncManager.php                # 同步管理器（核心）
│   ├── VirtualGoodsApiService.php     # API服务
│   ├── ProductImporter.php            # 产品导入（支持游戏充值）
│   ├── CategoryImporter.php           # 分类导入
│   └── ProductDetailImporter.php      # 产品详情导入

Data Layer (Message Queue)
├── Model/MessageQueue/
│   └── Consumer/
│       ├── ProductImportConsumer.php  # 产品导入消费者
│       ├── CategoryImportConsumer.php # 分类导入消费者
│       └── ProductDetailConsumer.php  # 产品详情消费者
└── Model/MessageQueue/Publisher.php   # 消息发布者

Product Attributes
├── Setup/Patch/Data/AddProductAttributes.php  # 添加充值类型属性
├── Model/Product/Attribute/
│   ├── Source/ChargeType.php         # 充值类型选项
│   ├── Backend/ChargeType.php       # 充值类型后端验证
│   └── Frontend/ChargeType.php      # 充值类型前端显示
└── Helper/ChargeType.php             # 充值类型助手类
```

## API接口

### VirtualGoodsApiInterface

实现语雀文档中的三个接口：

```php
// 商品列表查询
public function getProductList(int $limit = 100, int $page = 1, int $timestamp = 0): array;

// 商品分类查询
public function getCategoryList(int $timestamp = 0): array;

// 商品详情查询
public function getProductDetail(string $productId): array;
```

## 产品属性

### 游戏充值专用属性

#### 1. 充值类型 (game_charge_type)

**属性代码**：`game_charge_type`

**类型**：Select（下拉选择）

**可选值**：
- `direct` - 直充
- `card` - 卡密

**用途**：区分不同的充值方式

#### 2. 产品类型

**类型**：`virtual`（虚拟产品）

**特性**：
- 不需要配送
- 不需要库存管理
- 重量为0

## 使用方式

### 1. 命令行同步

```bash
# 同步所有数据
php bin/magento folixcode:sync:products

# 同步商品列表
php bin/magento folixcode:sync:products --type=products --limit=100

# 同步分类列表
php bin/magento folixcode:sync:products --type=categories

# 增量同步
php bin/magento folixcode:sync:products --timestamp=1698765432
```

### 2. Cron定时同步

默认每30分钟自动执行一次同步，可在 `crontab.xml` 中修改：

```xml
<schedule>*/30 * * * *</schedule>
```

### 3. 消息队列

同步过程通过消息队列异步处理：

1. SyncManager 调用API获取数据
2. 发布消息到队列
3. Consumer异步消费并导入数据

### 4. 编程方式调用

```php
use FolixCode\ProductSync\Service\SyncManager;

class YourController
{
    private SyncManager $syncManager;

    public function __construct(SyncManager $syncManager)
    {
        $this->syncManager = $syncManager;
    }

    public function execute()
    {
        // 同步所有数据
        $results = $this->syncManager->sync('all', [
            'limit' => 100,
            'timestamp' => 0
        ]);

        // 只同步商品
        $results = $this->syncManager->sync('products', ['limit' => 50]);

        // 只同步分类
        $results = $this->syncManager->sync('categories');
    }
}
```

## 游戏充值产品导入

### 数据格式

#### 直充产品

```json
{
  "id": "1001",
  "name": "王者荣耀100点券",
  "price": "10.00",
  "description": "直充到指定游戏账户",
  "short_description": "100点券",
  "status": 1,
  "charge_type": "direct",
  "category_ids": [10, 15]
}
```

#### 卡密产品

```json
{
  "id": "2001",
  "name": "王者荣耀100点券卡密",
  "price": "10.00",
  "description": "购买充值卡密，自行充值",
  "short_description": "100点券卡密",
  "status": 1,
  "charge_type": "card",
  "category_ids": [10]
}
```

### 产品导入特性

自动设置以下属性：

1. **产品类型**：`virtual`（虚拟产品）
2. **充值类型**：`direct` 或 `card`
3. **库存管理**：不启用
4. **库存状态**：有库存
5. **重量**：0
6. **可见性**：搜索和目录中可见

## 安装和配置

### 1. 安装模块

```bash
# 清除缓存
php bin/magento cache:clean

# 启用模块
php bin/magento module:enable FolixCode_BaseSyncService FolixCode_ProductSync

# 升级数据库（执行Data Patch创建自定义属性）
php bin/magento setup:upgrade

# 清理静态文件
php bin/magento setup:static-content:deploy

# 清除编译
php bin/magento setup:di:compile
```

### 2. 验证属性创建

登录Magento后台，进入：
`Stores > Attributes > Product`

确认以下属性已创建：
- `game_charge_type` - 充值类型

### 3. 配置消息队列

```bash
# 创建消息队列表
php bin/magento setup:db-schema:upgrade

# 启动消费者
php bin/magento queue:consumers:start folixcode.product.import.consumer &
php bin/magento queue:consumers:start folixcode.category.import.consumer &
php bin/magento queue:consumers:start folixcode.product.detail.consumer &
```

## 配置管理

配置项继承自 BaseSyncService：

- `folixcode_basesyncservice/api/base_url` - API基础URL
- `folixcode_basesyncservice/api/secret_id` - Secret ID
- `folixcode_basesyncservice/api/secret_key` - Secret Key
- `folixcode_basesyncservice/api/enabled` - 是否启用
- `folixcode_basesyncservice/api/sync_interval` - 同步间隔（分钟）

## 充值类型助手类

`FolixCode\ProductSync\Helper\ChargeType`

提供充值类型相关的工具方法：

```php
use FolixCode\ProductSync\Helper\ChargeType;

// 获取所有类型
$types = ChargeType::getAllTypes();
// ['direct' => '直充', 'card' => '卡密']

// 获取类型标签
$label = ChargeType::getTypeLabel('direct');
// '直充'

// 验证类型
$isValid = ChargeType::isValidType('direct');
// true

// 判断是否为直充
$isDirect = ChargeType::isDirect('direct');
// true

// 判断是否为卡密
$isCard = ChargeType::isCard('card');
// true
```

## 批量导入

### 批量导入产品

```php
use FolixCode\ProductSync\Service\ProductImporter;

class BulkImport
{
    private ProductImporter $productImporter;

    public function __construct(ProductImporter $productImporter)
    {
        $this->productImporter = $productImporter;
    }

    public function importBatch(array $productsData)
    {
        $results = $this->productImporter->importBatch($productsData);

        echo "成功: " . $results['success'] . "\n";
        echo "失败: " . $results['failed'] . "\n";

        foreach ($results['errors'] as $error) {
            echo "SKU: " . $error['sku'] . ", 错误: " . $error['error'] . "\n";
        }
    }
}
```

## 错误处理

所有错误都会记录到日志：

- 同步失败：`system.log`
- API调用错误：`exception.log`
- 导入错误：`system.log`

## 扩展

### 1. 添加新的API端点

在 `VirtualGoodsApiService` 中添加方法：

```php
public function getGameAccount(string $accountId): array
{
    $url = $this->apiClient->getApiBaseUrl() . '/api/game/account/' . $accountId;
    return $this->apiClient->get($url);
}
```

### 2. 添加新的充值类型

在 `Helper/ChargeType` 中添加：

```php
public const GIFT_CARD = 'gift_card';

public static function getAllTypes(): array
{
    return [
        self::DIRECT => __('直充'),
        self::CARD => __('卡密'),
        self::GIFT_CARD => __('礼品卡')
    ];
}
```

### 3. 自定义产品导入逻辑

继承 `ProductImporter` 并重写 `import` 方法：

```php
class CustomProductImporter extends ProductImporter
{
    public function import(array $productData): void
    {
        // 自定义逻辑
        parent::import($productData);
    }
}
```

## 测试

### 单元测试

```bash
# 运行ProductSync测试
php bin/magento dev:tests:run unit --filter FolixCode\ProductSync
```

### 集成测试

```bash
# 运行集成测试
php bin/magento dev:tests:run integration --filter FolixCode\ProductSync
```

### 手动测试

1. 运行安装脚本创建属性
2. 手动创建测试产品
3. 设置充值类型
4. 测试导入流程
5. 验证前台显示

## 相关文档

- **docs/GAME_CHARGE.md** - 游戏充值项目详细文档
- **BaseSyncService/README.md** - 基础服务模块文档
- **ARCHITECTURE.md** - 架构设计文档

## 技术栈

- **HTTP客户端**：Guzzle
- **消息队列**：Magento Message Queue
- **定时任务**：Magento Cron
- **产品类型**：Virtual Product
- **产品属性**：自定义Select属性

## 许可证

请遵守相关开源协议。
