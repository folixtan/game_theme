# FolixCode 模块总结

## 架构调整说明

按照你的要求，我已经重新调整了架构：

### 核心原则

**BaseSyncService** 只放最基础的、全局可用的功能

**ProductSync** 放业务逻辑，所有产品同步相关的功能都在这个模块

---

## 模块划分

### 1. BaseSyncService (基础设施层)

**职责**：提供基础服务，无业务逻辑

**包含内容**：

```
FolixCode/BaseSyncService/
├── Api/
│   ├── HttpClientInterface.php          # HTTP客户端接口
│   └── ExternalApiClientInterface.php    # 外部API客户端接口
├── Model/
│   ├── HttpClient.php                    # HTTP客户端实现（使用Guzzle）
│   └── VirtualGoodsApiClient.php        # 外部API客户端实现（通用）
├── Helper/
│   └── Data.php                          # 加密解密、配置管理
├── etc/
│   ├── config.xml                        # 默认配置
│   ├── adminhtml/
│   │   └── system.xml                    # 后台配置
│   └── di.xml                            # 依赖注入配置
└── README.md
```

**接口定义**：

```php
// HttpClientInterface - 基础HTTP请求
- get(string $url, array $params = [], array $headers = []): array
- post(string $url, array $data = [], array $headers = []): array
- put(string $url, array $data = [], array $headers = []): array
- delete(string $url, array $headers = []): array

// ExternalApiClientInterface - 外部API调用（自动处理加密和签名）
- get(string $url, array $params = [], array $headers = []): array
- post(string $url, array $data = [], array $headers = []): array
- put(string $url, array $data = [], array $headers = []): array
- delete(string $url, array $headers = []): array
```

---

### 2. ProductSync (业务逻辑层)

**职责**：所有产品同步相关的业务逻辑

**包含内容**：

```
FolixCode/ProductSync/
├── Api/
│   ├── VirtualGoodsApiInterface.php       # 虚拟商品API接口（业务）
│   └── Message/
│       ├── PublisherInterface.php         # 消息发布者接口
│       ├── ProductImportMessageInterface.php
│       ├── CategoryImportMessageInterface.php
│       └── ProductDetailMessageInterface.php
├── Service/
│   ├── SyncManager.php                    # 同步管理器（核心）
│   ├── VirtualGoodsApiService.php         # API服务（业务）
│   ├── ProductImporter.php                # 产品导入
│   ├── CategoryImporter.php               # 分类导入
│   └── ProductDetailImporter.php          # 产品详情导入
├── Model/
│   ├── MessageQueue/
│   │   ├── Publisher.php                  # 消息发布者
│   │   └── Consumer/
│   │       ├── ProductImportConsumer.php  # 产品导入消费者
│   │       ├── CategoryImportConsumer.php # 分类导入消费者
│   │       └── ProductDetailConsumer.php  # 产品详情消费者
│   └── Message/
│       ├── ProductImportMessage.php
│       ├── CategoryImportMessage.php
│       └── ProductDetailMessage.php
├── Console/
│   └── Command/
│       └── SyncCommand.php               # 命令行工具
├── Cron/
│   └── SyncProducts.php                  # 定时任务
├── etc/
│   ├── di.xml                             # 依赖注入配置
│   ├── crontab.xml                        # Cron配置
│   ├── communication.xml                  # 消息队列主题
│   ├── queue.xml                          # 队列配置
│   └── queue_consumer.xml                 # 消费者配置
└── README.md
```

**业务API接口**：

```php
// VirtualGoodsApiInterface - 实现语雀文档中的三个接口
- getProductList(int $limit = 100, int $page = 1, int $timestamp = 0): array
- getCategoryList(int $timestamp = 0): array
- getProductDetail(string $productId): array
```

---

## 实现的三个接口（语雀文档）

### 1. 商品列表查询
- **端点**：`/api/user-goods/list`
- **方法**：VirtualGoodsApiService::getProductList()
- **参数**：limit, page, timestamp（增量同步）

### 2. 商品分类查询
- **端点**：`/api/user-goods/category`
- **方法**：VirtualGoodsApiService::getCategoryList()
- **参数**：timestamp（增量同步）

### 3. 商品详情查询
- **端点**：`/api/user-goods/detail/{productId}`
- **方法**：VirtualGoodsApiService::getProductDetail()
- **参数**：productId

---

## 功能支持

### ✅ MQ（消息队列）

**实现位置**：ProductSync/Model/MessageQueue/

- Publisher: 统一管理消息发布
  - publishProductImport()
  - publishCategoryImport()
  - publishProductDetail()

- Consumers: 异步处理导入
  - ProductImportConsumer
  - CategoryImportConsumer
  - ProductDetailConsumer

**配置文件**：
- communication.xml - 定义消息主题
- queue.xml - 定义队列
- queue_consumer.xml - 定义消费者

### ✅ Crontab（定时任务）

**实现位置**：ProductSync/Cron/SyncProducts.php

- 默认每30分钟自动执行一次
- 支持增量同步
- 支持同步间隔配置

**配置文件**：
- crontab.xml - 定义定时任务

### ✅ Console命令（命令行工具）

**实现位置**：ProductSync/Console/Command/SyncCommand.php

**使用方式**：
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

---

## 技术栈

### HTTP客户端：Guzzle

使用 Guzzle HTTP Client 替代 Magento 的 Curl 客户端：

```php
// BaseSyncService/Model/HttpClient.php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpClient implements HttpClientInterface
{
    private Client $guzzleClient;

    // ... 实现 GET, POST, PUT, DELETE 方法
}
```

**优点**：
- 更现代化的HTTP客户端
- 更好的异步支持
- 更完善的错误处理
- 更活跃的社区支持

**配置**（di.xml）：
```xml
<type name="FolixCode\BaseSyncService\Model\HttpClient">
    <arguments>
        <argument name="config" xsi:type="array">
            <item name="timeout" xsi:type="number">30</item>
            <item name="connect_timeout" xsi:type="number">10</item>
            <item name="http_errors" xsi:type="boolean">false</item>
            <item name="verify" xsi:type="boolean">false</item>
        </argument>
    </arguments>
</type>
```

---

## 解耦设计

### 层次结构

```
Presentation Layer (命令/Cron)
    ↓
Business Logic Layer (SyncManager)
    ↓
Service Layer (API Service)
    ↓
Infrastructure Layer (BaseSyncService)
    ↓
Data Layer (Consumers)
```

### 依赖关系

```
ProductSync (业务层)
    ↓ 依赖
BaseSyncService (基础设施层)
```

### 职责分离

- **BaseSyncService**：
  - HTTP请求能力（Guzzle）
  - 外部API调用（通用）
  - 加密解密
  - 配置管理
  - **无业务逻辑**

- **ProductSync**：
  - 业务API调用（具体接口）
  - 同步流程管理
  - 数据导入
  - 消息队列处理
  - Cron任务
  - Console命令
  - **所有业务逻辑**

---

## 配置管理

所有配置都在 BaseSyncService 的 config.xml 中：

```xml
<config>
    <default>
        <folixcode_basesyncservice>
            <api>
                <base_url>https://api.example.com</base_url>
                <secret_id>your-secret-id</secret_id>
                <secret_key>your-secret-key</secret_key>
                <enabled>1</enabled>
                <sync_interval>60</sync_interval>
            </api>
        </folixcode_basesyncservice>
    </default>
</config>
```

---

## 扩展性

### 添加新的业务模块

创建新模块（如 `UserSync`），可以直接复用 BaseSyncService：

```php
namespace FolixCode\UserSync\Service;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;

class UserApiService
{
    private ExternalApiClientInterface $apiClient;

    public function __construct(ExternalApiClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function getUserList(): array
    {
        $url = $this->apiClient->getApiBaseUrl() . '/api/user/list';
        return $this->apiClient->get($url);
    }
}
```

---

## 部署步骤

```bash
# 1. 清除缓存
php bin/magento cache:clean

# 2. 启用模块
php bin/magento module:enable FolixCode_BaseSyncService FolixCode_ProductSync

# 3. 升级数据库
php bin/magento setup:upgrade

# 4. 清理编译
php bin/magento setup:di:compile

# 5. 清理静态文件
php bin/magento setup:static-content:deploy

# 6. 创建消息队列表
php bin/magento setup:db-schema:upgrade

# 7. 启动消费者
php bin/magento queue:consumers:start folixcode.product.import.consumer &
php bin/magento queue:consumers:start folixcode.category.import.consumer &
php bin/magento queue:consumers:start folixcode.product.detail.consumer &
```

---

## 文档

- **ARCHITECTURE.md** - 详细的架构说明文档
- **BaseSyncService/README.md** - BaseSyncService 模块文档
- **ProductSync/README.md** - ProductSync 模块文档

---

## 清理说明

已删除的文件和目录：

- 删除所有旧的Service文件（CategoryApiService、ProductDetailApiService、ProductListApiService）
- 删除旧的Model文件（ExternalApiFactory、ExternalProduct）
- 删除旧的Controller和View目录
- 删除旧的Api接口（ExternalApiFactoryInterface、SyncServiceInterface、ExternalProductInterface）
- 删除旧的Consumer（ImportConsumer）
- 删除旧的composer.json
- 删除空目录

---

## 总结

✅ BaseSyncService 只提供基础功能（HTTP-Guzzle、加密、配置），无业务逻辑

✅ ProductSync 包含所有产品同步相关的业务逻辑

✅ 实现了语雀文档中的三个接口

✅ 支持MQ消息队列异步处理

✅ 支持Crontab定时任务

✅ 支持Console命令行工具

✅ 使用Guzzle替代Magento Curl客户端

✅ 删除所有不需要的文件

✅ 高度解耦，易于扩展和维护
