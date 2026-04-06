# FolixCode 模块架构文档

## 模块概览

FolixCode包含两个模块：

1. **BaseSyncService** - 基础同步服务（基础设施层）
2. **ProductSync** - 产品同步业务（业务逻辑层）

## 设计原则

### 1. 关注点分离

- **BaseSyncService**：只提供基础设施和通用工具
- **ProductSync**：包含所有产品同步相关的业务逻辑

### 2. 单一职责

每个类只负责一个功能：
- HttpClient：HTTP请求
- VirtualGoodsApiClient：外部API调用
- VirtualGoodsApiService：业务API调用
- SyncManager：同步流程管理
- *Importer：数据导入

### 3. 依赖注入

通过接口实现松耦合：
```
Business Layer (ProductSync)
    ↓ 依赖接口
Service Layer (BaseSyncService)
    ↓ 依赖接口
Infrastructure (HTTP Client)
```

## 模块划分

### BaseSyncService 模块

**职责**：提供基础服务，无业务逻辑

**包含内容**：

```
FolixCode/BaseSyncService/
├── Api/
│   ├── HttpClientInterface.php          # HTTP客户端接口
│   └── ExternalApiClientInterface.php    # 外部API客户端接口
├── Model/
│   ├── HttpClient.php                    # HTTP客户端实现
│   └── VirtualGoodsApiClient.php        # 外部API客户端实现
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
interface HttpClientInterface {
    public function get(string $url, array $params = [], array $headers = []): array;
    public function post(string $url, array $data = [], array $headers = []): array;
    public function put(string $url, array $data = [], array $headers = []): array;
    public function delete(string $url, array $headers = []): array;
}

// ExternalApiClientInterface - 外部API调用
interface ExternalApiClientInterface {
    public function get(string $url, array $params = [], array $headers = []): array;
    public function post(string $url, array $data = [], array $headers = []): array;
    public function put(string $url, array $data = [], array $headers = []): array;
    public function delete(string $url, array $headers = []): array;
}
```

### ProductSync 模块

**职责**：所有产品同步相关的业务逻辑

**包含内容**：

```
FolixCode/ProductSync/
├── Api/
│   ├── VirtualGoodsApiInterface.php       # 虚拟商品API接口
│   └── Message/
│       ├── PublisherInterface.php         # 消息发布者接口
│       ├── ProductImportMessageInterface.php
│       ├── CategoryImportMessageInterface.php
│       └── ProductDetailMessageInterface.php
├── Service/
│   ├── SyncManager.php                    # 同步管理器（核心）
│   ├── VirtualGoodsApiService.php         # API服务
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
// VirtualGoodsApiInterface - 业务API调用
interface VirtualGoodsApiInterface {
    public function getProductList(int $limit = 100, int $page = 1, int $timestamp = 0): array;
    public function getCategoryList(int $timestamp = 0): array;
    public function getProductDetail(string $productId): array;
}
```

## 数据流

### 1. 手动同步流程

```
用户执行命令
    ↓
Console Command
    ↓
SyncManager::sync()
    ↓
VirtualGoodsApiService::getProductList()
    ↓
VirtualGoodsApiClient::get()
    ↓
HttpClient::get()
    ↓
外部API
    ↓
返回数据
    ↓
Publisher::publishProductImport()
    ↓
消息队列
    ↓
Consumer::process()
    ↓
ProductImporter::import()
    ↓
Magento数据库
```

### 2. Cron定时同步流程

```
Cron触发
    ↓
SyncProducts::execute()
    ↓
SyncManager::sync()
    ↓
（后续同手动同步流程）
```

## 依赖关系

### ProductSync 依赖 BaseSyncService

```
ProductSync (业务层)
    ↓ 依赖
BaseSyncService (基础设施层)
```

### 具体依赖

```xml
<!-- ProductSync/etc/di.xml -->
<type name="FolixCode\ProductSync\Service\VirtualGoodsApiService">
    <arguments>
        <argument name="apiClient" xsi:type="object">
            FolixCode\BaseSyncService\Model\VirtualGoodsApiClient
        </argument>
    </arguments>
</type>
```

## 扩展性

### 添加新的同步类型

1. 在 `VirtualGoodsApiInterface` 中添加方法
2. 在 `VirtualGoodsApiService` 中实现方法
3. 在 `SyncManager` 中添加处理逻辑
4. 创建对应的Importer类
5. 创建对应的Consumer类
6. 更新消息队列配置

### 添加新的业务模块

创建新的模块（如 `UserSync`）：

```
FolixCode/UserSync/
├── Api/
│   └── UserApiInterface.php
├── Service/
│   ├── UserApiService.php
│   ├── UserImporter.php
│   └── SyncManager.php
└── ...
```

新模块可以直接使用 BaseSyncService 的 HTTP 客户端和 API 客户端。

## 配置管理

### 共享配置（BaseSyncService）

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

### 业务配置（ProductSync）

ProductSync 继承 BaseSyncService 的配置，无需额外配置。

## 性能优化

### 1. 消息队列异步处理

- 同步获取数据时立即返回
- 导入操作异步执行，不阻塞

### 2. 增量同步

- 基于时间戳只获取变更数据
- 减少网络传输和数据库操作

### 3. 批量处理

- 支持批量获取和导入
- 减少API调用次数

## 错误处理

### 1. 重试机制

消息队列自带重试机制，失败消息会重新入队。

### 2. 日志记录

所有操作都有详细的日志记录：
- 成功操作：info级别
- 错误操作：error级别
- 异常：exception级别

### 3. 监控

可以通过日志文件监控同步状态：
```bash
tail -f var/log/system.log
tail -f var/log/exception.log
```

## 安全性

### 1. 加密传输

所有请求数据都经过加密：
```php
$encryptedData = $this->baseHelper->encryptRequestData($params);
```

### 2. 签名验证

支持请求签名：
```php
$signature = $this->baseHelper->generateSignature($params);
```

### 3. 访问控制

通过配置控制是否启用同步：
```php
if (!$this->baseHelper->isEnabled()) {
    return;
}
```

## 测试

### 单元测试

```bash
# 运行BaseSyncService测试
php bin/magento dev:tests:run unit --filter FolixCode\BaseSyncService

# 运行ProductSync测试
php bin/magento dev:tests:run unit --filter FolixCode\ProductSync
```

### 集成测试

```bash
# 运行集成测试
php bin/magento dev:tests:run integration --filter FolixCode
```

## 部署

### 1. 安装模块

```bash
# 清除缓存
php bin/magento cache:clean

# 启用模块
php bin/magento module:enable FolixCode_BaseSyncService FolixCode_ProductSync

# 升级数据库
php bin/magento setup:upgrade

# 清理静态文件
php bin/magento setup:static-content:deploy

# 清除编译
php bin/magento setup:di:compile
```

### 2. 配置消息队列

```bash
# 创建消息队列表
php bin/magento setup:db-schema:upgrade

# 启动消费者
php bin/magento queue:consumers:start folixcode.product.import.consumer &
php bin/magento queue:consumers:start folixcode.category.import.consumer &
php bin/magento queue:consumers:start folixcode.product.detail.consumer &
```

### 3. 配置Cron

确保Cron已启动：
```bash
crontab -e
# 添加Magento Cron任务
```

## 维护

### 日志位置

- 系统日志：`var/log/system.log`
- 异常日志：`var/log/exception.log`
- 调试日志：`var/log/debug.log`

### 常见问题

1. **同步失败**
   - 检查API配置是否正确
   - 查看日志文件
   - 检查网络连接

2. **消息队列不工作**
   - 确保消费者已启动
   - 检查队列配置
   - 查看队列状态

3. **Cron不执行**
   - 确保Cron任务已配置
   - 检查Cron日志
   - 手动执行Cron测试

## 总结

该架构遵循Magento最佳实践，实现了：

- ✅ 关注点分离
- ✅ 单一职责
- ✅ 依赖注入
- ✅ 接口驱动
- ✅ 可扩展性
- ✅ 可维护性
- ✅ 性能优化
- ✅ 安全性

BaseSyncService提供基础设施，ProductSync处理业务逻辑，两者完全解耦，易于维护和扩展。
