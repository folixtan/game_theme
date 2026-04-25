# BaseSyncService 重构说明

## 📋 重构目标

支持**多供应商架构**，使不同业务模块可以使用不同的加密解密策略和配置。

---

## ✅ 已完成的工作

### 1. 创建加密策略接口

**文件**: `Api/EncryptionStrategyInterface.php`

定义了统一的加密解密策略接口：
- `encrypt(array $data): string` - 加密请求数据
- `decrypt(string $encryptedData): array` - 解密响应数据
- `generateSignature(array $data, string $timestamp): string` - 生成签名
- `getMethodName(): string` - 获取加密方法名称（用于日志）

### 2. 实现AES-256-CBC加密策略

**文件**: `Model/Encryption/Aes256CbcStrategy.php`

从原有的Helper中提取加密解密逻辑，实现策略接口：
- 按照API文档规范实现AES-256-CBC加密
- 支持随机IV生成
- 完整的错误处理和日志记录

### 3. 重构ApiClient

**文件**: `Model/ApiClient.php`

**主要变更**:
- ❌ 移除对 `BaseHelper` 的依赖
- ✅ 注入 `EncryptionStrategyInterface` 策略
- ✅ 通过构造函数接收配置参数（apiBaseUrl, secretId）
- ✅ 使用策略进行加密/解密操作
- ✅ 在日志中记录使用的加密方法

**优势**:
- 支持不同供应商使用不同的加密算法
- 配置驱动，每个业务模块可独立配置
- 符合开闭原则，新增加密方式无需修改现有代码

### 4. 简化BaseSyncService Helper

**文件**: `Helper/Data.php`

**移除的方法**:
- ❌ `getSyncInterval()` - 已标记为@deprecated，移至业务层
- ❌ `getLastSyncTimestamp()` - 已标记为@deprecated，移至业务层
- ❌ `setLastSyncTimestamp()` - 已标记为@deprecated，移至业务层
- ❌ `encryptRequestData()` - 移至Aes256CbcStrategy
- ❌ `decryptResponseData()` - 移至Aes256CbcStrategy
- ❌ `generateSignature()` - 移至Aes256CbcStrategy

**保留的方法**:
- ✅ `getApiBaseUrl()` - 读取API基础URL配置
- ✅ `getSecretId()` - 读取Secret ID配置
- ✅ `getSecretKey()` - 读取并解密Secret Key配置
- ✅ `isEnabled()` - 检查模块是否启用

**职责**: 仅负责基础配置管理，不包含任何业务逻辑

### 5. 更新依赖注入配置

#### BaseSyncService di.xml

**新增虚拟类型**:
```xml
<!-- ProductSync 专用的 ApiClient -->
<virtualType name="ProductSyncVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="encryptionStrategy" xsi:type="object">FolixCode\BaseSyncService\Api\EncryptionStrategyInterface</argument>
        <argument name="apiBaseUrl" xsi:type="string">https://playsentral.qr67.com</argument>
        <argument name="secretId" xsi:type="string">81097469c53704b748e</argument>
    </arguments>
</virtualType>

<!-- ThirdPartyOrder 专用的 ApiClient -->
<virtualType name="ThirdPartyOrderVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="encryptionStrategy" xsi:type="object">FolixCode\BaseSyncService\Api\EncryptionStrategyInterface</argument>
        <argument name="apiBaseUrl" xsi:type="string">https://playsentral.qr67.com</argument>
        <argument name="secretId" xsi:type="string">81097469c53704b748e</argument>
    </arguments>
</virtualType>
```

#### ProductSync di.xml

**变更**:
```xml
<!-- 之前 -->
<argument name="apiClient" xsi:type="object">FolixCode\BaseSyncService\Api\ExternalApiClientInterface</argument>

<!-- 之后 -->
<argument name="apiClient" xsi:type="object">ProductSyncVendorApiClient</argument>
```

#### ThirdPartyOrder di.xml

**变更**:
```xml
<type name="FolixCode\ThirdPartyOrder\Model\OrderSyncService">
    <arguments>
        <argument name="apiClient" xsi:type="object">ThirdPartyOrderVendorApiClient</argument>
        <argument name="logger" xsi:type="object">FolixCodeThirdPartyOrderLogger</argument>
    </arguments>
</type>
```

### 6. 更新OrderSyncService

**文件**: `ThirdPartyOrder/Model/OrderSyncService.php`

**变更**:
- ❌ 移除 `BaseSyncHelper` 依赖
- ✅ 使用 `ExternalApiClientInterface` 接口类型
- ✅ 通过DI注入专用的ApiClient实例

---

## 🎯 架构优势

### 1. 多供应商支持

未来可以轻松添加新供应商：

```php
// 1. 创建新的加密策略
class RsaEncryptionStrategy implements EncryptionStrategyInterface { ... }

// 2. 配置新的ApiClient
<virtualType name="NewVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="encryptionStrategy" xsi:type="object">RsaEncryptionStrategy</argument>
        <argument name="apiBaseUrl" xsi:type="string">https://new-vendor.com</argument>
        <argument name="secretId" xsi:type="string">xxx</argument>
    </arguments>
</virtualType>

// 3. 在业务模块中使用
<type name="YourBusinessService">
    <arguments>
        <argument name="apiClient" xsi:type="object">NewVendorApiClient</argument>
    </arguments>
</type>
```

### 2. 清晰的职责分离

| 层级 | 模块 | 职责 |
|------|------|------|
| **基础设施层** | BaseSyncService | HTTP客户端、加密策略、配置管理 |
| **业务层** | ProductSync | 产品同步业务逻辑、Cron任务 |
| **业务层** | ThirdPartyOrder | 订单同步业务逻辑、事件监听 |

### 3. 配置驱动

- 每个业务模块有独立的ApiClient实例
- 通过di.xml配置不同的加密策略和API端点
- 无需修改代码即可切换供应商

### 4. 向后兼容

- 保留了`ExternalApiClientInterface`接口
- 现有代码只需更新DI配置即可工作
- 加密策略接口允许未来扩展

---

## 📝 待完成事项

### 高优先级

1. **清理废弃代码**
   - 删除ProductSync中对已废弃Helper方法的调用
   - 确保所有模块只使用新的ApiClient

2. **测试验证**
   - 运行单元测试验证加密策略
   - 集成测试验证ApiClient调用
   - 端到端测试产品和订单同步流程

3. **更新文档**
   - 更新ProductSync README
   - 更新ThirdPartyOrder README
   - 添加多供应商架构说明

### 中优先级

4. **增强配置管理**
   - 考虑将供应商配置移到后台配置界面
   - 支持动态切换供应商（无需修改di.xml）

5. **添加监控**
   - 记录API调用成功率
   - 记录加密/解密失败次数
   - 集成告警系统

### 低优先级

6. **性能优化**
   - 考虑加密结果缓存
   - HTTP连接池优化
   - 异步批量处理

---

## 🔧 使用说明

### 业务模块如何使用新的ApiClient

#### 方式1: 直接注入虚拟类型（推荐）

```php
use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;

class YourService
{
    private ExternalApiClientInterface $apiClient;
    
    public function __construct(
        @Named("ProductSyncVendorApiClient") ExternalApiClientInterface $apiClient
    ) {
        $this->apiClient = $apiClient;
    }
    
    public function fetchData(): array
    {
        return $this->apiClient->post('/api/endpoint', ['param' => 'value']);
    }
}
```

#### 方式2: 在di.xml中配置

```xml
<type name="Your\Module\Service\YourService">
    <arguments>
        <argument name="apiClient" xsi:type="object">ProductSyncVendorApiClient</argument>
    </arguments>
</type>
```

### 如何添加新的加密策略

1. 创建策略类实现 `EncryptionStrategyInterface`
2. 在di.xml中注册新的虚拟类型
3. 配置业务模块使用该策略

示例：
```php
namespace Your\Module\Model\Encryption;

use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;

class CustomEncryptionStrategy implements EncryptionStrategyInterface
{
    public function encrypt(array $data): string
    {
        // 自定义加密逻辑
    }
    
    public function decrypt(string $encryptedData): array
    {
        // 自定义解密逻辑
    }
    
    public function generateSignature(array $data, string $timestamp): string
    {
        // 自定义签名逻辑
    }
    
    public function getMethodName(): string
    {
        return 'CUSTOM-ENCRYPTION';
    }
}
```

---

## 🚀 下一步计划

根据你的要求，我们已经完成了**基础层的完善**。接下来应该：

1. **联调测试** - 验证ProductSync和ThirdPartyOrder与新的ApiClient配合正常
2. **订单模块简化** - 确认不需要Cron任务，保持事件驱动+MQ异步架构
3. **产品同步优化** - 最后再处理产品同步相关的细节

请告诉我是否需要：
- 运行测试验证当前改动
- 继续优化某个特定模块
- 或者有其他需求
