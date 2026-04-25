# 简洁架构设计

## 🎯 核心思想

遵循Magento标准模式：**两个接口 + DI配置**，不做过度设计。

---

## 📋 核心组件

### 1. VendorConfigInterface（配置接口）

```php
interface VendorConfigInterface
{
    public function getApiBaseUrl(): string;
    public function getSecretId(): string;
    public function getSecretKey(): string;
    public function getEncryptionMethod(): string;
}
```

**职责**: 业务模块实现此接口提供配置信息

### 2. EncryptionStrategyInterface（加密策略接口）

```php
interface EncryptionStrategyInterface
{
    public function encrypt(array $data): string;
    public function decrypt(string $encryptedData): array;
    public function generateSignature(array $data, string $timestamp): string;
    public function getMethodName(): string;
}
```

**职责**: 定义加密解密的标准方法

### 3. ApiClient（统一客户端）

```php
class ApiClient implements ExternalApiClientInterface
{
    public function __construct(
        VendorConfigInterface $vendorConfig,
        EncryptionStrategyInterface $encryptionStrategy,
        LoggerInterface $logger,
        array $guzzleConfig = []
    ) { ... }
}
```

**职责**: 接受配置和策略，处理HTTP请求

---

## 🔧 使用方式

### ThirdPartyOrder模块示例

#### 1. 实现VendorConfig

```php
namespace FolixCode\ThirdPartyOrder\Model;

class VendorConfig implements VendorConfigInterface
{
    private ThirdPartyHelper $helper;
    private EncryptorInterface $encryptor;

    public function __construct(
        ThirdPartyHelper $helper,
        EncryptorInterface $encryptor
    ) {
        $this->helper = $helper;
        $this->encryptor = $encryptor;
    }

    public function getApiBaseUrl(): string {
        return $this->helper->getApiBaseUrl();
    }

    public function getSecretId(): string {
        return $this->helper->getAppKey() ?: 'default-id';
    }

    public function getSecretKey(): string {
        $encryptedKey = $this->helper->getSecretKey();
        return $this->encryptor->decrypt($encryptedKey);
    }

    public function getEncryptionMethod(): string {
        return 'AES-256-CBC';
    }
}
```

#### 2. 在Service中注入ApiClient

```php
class OrderSyncService
{
    private ExternalApiClientInterface $apiClient;

    public function __construct(
        ExternalApiClientInterface $apiClient,
        // ... 其他依赖
    ) {
        $this->apiClient = $apiClient;
    }

    public function syncOrder(OrderInterface $order): bool
    {
        $response = $this->apiClient->post('/api/endpoint', $data);
        // 处理响应
    }
}
```

#### 3. 配置di.xml

```xml
<!-- ThirdPartyOrder专用的ApiClient实例 -->
<virtualType name="ThirdPartyOrderApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="vendorConfig" xsi:type="object">FolixCode\ThirdPartyOrder\Model\VendorConfig</argument>
        <argument name="encryptionStrategy" xsi:type="object">FolixCode\BaseSyncService\Api\EncryptionStrategyInterface</argument>
        <argument name="logger" xsi:type="object">FolixCodeThirdPartyOrderLogger</argument>
    </arguments>
</virtualType>

<!-- 注入到Service -->
<type name="FolixCode\ThirdPartyOrder\Model\OrderSyncService">
    <arguments>
        <argument name="apiClient" xsi:type="object">ThirdPartyOrderApiClient</argument>
    </arguments>
</type>
```

---

## 💡 优势

### 1. 符合Magento标准
- ✅ 使用`virtualType`创建专用实例
- ✅ 通过DI配置依赖关系
- ✅ 自动生成Factory类（如需要）

### 2. 简洁明了
- ✅ 只有2个接口
- ✅ 没有多余的工厂类
- ✅ 配置清晰易懂

### 3. 灵活扩展
- ✅ 新增供应商：实现VendorConfig + 配置virtualType
- ✅ 新加密算法：实现EncryptionStrategy + 更新preference

### 4. 配置驱动
- ✅ VendorConfig从后台配置读取
- ✅ 不同模块可有不同的配置
- ✅ 无需修改核心代码

---

## 📊 与之前对比

| 维度 | 之前的过度设计 | 现在的简洁设计 |
|------|--------------|--------------|
| **接口数量** | 3个接口 | 2个接口 |
| **工厂类** | ApiClientFactory + EncryptionStrategyFactory | 无（使用Magento DI） |
| **配置复杂度** | 高（多层抽象） | 低（直接注入） |
| **代码行数** | ~500行 | ~200行 |
| **理解难度** | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **维护成本** | 高 | 低 |

---

## 🚀 扩展示例

### 添加新供应商（RSA加密）

```php
// 1. 创建新的VendorConfig
class RsaVendorConfig implements VendorConfigInterface
{
    public function getEncryptionMethod(): string {
        return 'RSA';
    }
    // ... 其他方法
}

// 2. 创建RSA加密策略
class RsaStrategy implements EncryptionStrategyInterface
{
    public function encrypt(array $data): string {
        openssl_public_encrypt(...);
    }
    // ... 其他方法
}

// 3. 配置di.xml
<preference for="FolixCode\BaseSyncService\Api\EncryptionStrategyInterface" 
            type="Your\Module\Model\Encryption\RsaStrategy"/>

<virtualType name="RsaVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="vendorConfig" xsi:type="object">RsaVendorConfig</argument>
        <argument name="encryptionStrategy" xsi:type="object">RsaStrategy</argument>
    </arguments>
</virtualType>
```

**完成！无需修改任何核心代码。**

---

## 🎓 总结

这才是Magento的正确打开方式：
1. **定义清晰的接口**
2. **实现具体类**
3. **通过DI配置依赖关系**
4. **利用Magento的ObjectManager自动处理实例化**

不要重复造轮子，Magento的DI容器已经是最优雅的工厂模式了！
