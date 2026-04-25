# 架构对比：重构前 vs 重构后

## 🔄 重构前的架构（耦合）

```
┌─────────────────────────────────────────────────────┐
│              ProductSync / ThirdPartyOrder           │
│                 (业务模块)                            │
└──────────────────┬──────────────────────────────────┘
                   │ 直接依赖
                   ↓
┌─────────────────────────────────────────────────────┐
│         BaseSyncService\Helper\Data                  │
│         (Helper承担过多职责)                          │
│  - 配置管理 ✅                                       │
│  - 加密解密 ❌ (应该是策略)                           │
│  - 签名生成 ❌ (应该是策略)                           │
│  - 业务状态管理 ❌ (如同步时间戳)                      │
└──────────────────┬──────────────────────────────────┘
                   │ 硬编码AES-256-CBC
                   ↓
┌─────────────────────────────────────────────────────┐
│         BaseSyncService\Model\ApiClient              │
│         (固定使用Helper的加密方法)                     │
└─────────────────────────────────────────────────────┘

问题：
❌ 所有业务模块共享同一个ApiClient实例
❌ 无法为不同供应商配置不同的加密方式
❌ Helper职责混乱，包含业务逻辑
❌ 难以扩展新的加密算法
```

---

## ✨ 重构后的架构（解耦）

```
┌─────────────────────────────────────────────────────┐
│              ProductSync Module                      │
│  ┌──────────────────────────────────────────────┐   │
│  │ VirtualGoodsApiService                       │   │
│  └──────────────┬───────────────────────────────┘   │
│                 │ 注入                               │
│                 ↓                                    │
│  ┌──────────────────────────────────────────────┐   │
│  │ ProductSyncVendorApiClient (VirtualType)     │   │
│  │ - apiBaseUrl: https://playsentral.qr67.com   │   │
│  │ - secretId: 81097469c53704b748e              │   │
│  │ - encryptionStrategy: Aes256CbcStrategy      │   │
│  └──────────────┬───────────────────────────────┘   │
└─────────────────┼──────────────────────────────────┘
                  │
┌─────────────────┼──────────────────────────────────┐
│                 │                                   │
│  ┌──────────────▼──────────────────────────────┐   │
│  │         BaseSyncService (基础层)             │   │
│  │                                             │   │
│  │  ┌────────────────────────────────────┐    │   │
│  │  │ EncryptionStrategyInterface        │    │   │
│  │  │ (策略接口)                          │    │   │
│  │  └──────────────┬─────────────────────┘    │   │
│  │                 │ 实现                      │   │
│  │  ┌──────────────▼─────────────────────┐    │   │
│  │  │ Aes256CbcStrategy                  │    │   │
│  │  │ RsaStrategy (未来扩展)              │    │   │
│  │  │ CustomStrategy (未来扩展)           │    │   │
│  │  └────────────────────────────────────┘    │   │
│  │                                             │   │
│  │  ┌────────────────────────────────────┐    │   │
│  │  │ ApiClient                          │    │   │
│  │  │ - 接受策略注入                      │    │   │
│  │  │ - 配置驱动                          │    │   │
│  │  └────────────────────────────────────┘    │   │
│  │                                             │   │
│  │  ┌────────────────────────────────────┐    │   │
│  │  │ Helper/Data                        │    │   │
│  │  │ - 仅负责配置管理                    │    │   │
│  │  │ - 无业务逻辑                        │    │   │
│  │  └────────────────────────────────────┘    │   │
└─────────────────────────────────────────────────┘
                  │
┌─────────────────┼──────────────────────────────────┐
│                 │                                   │
│  ┌──────────────▼──────────────────────────────┐   │
│  │         ThirdPartyOrder Module               │   │
│  │  ┌──────────────────────────────────────┐   │   │
│  │  │ OrderSyncService                     │   │   │
│  │  └──────────────┬───────────────────────┘   │   │
│  │                 │ 注入                       │   │
│  │                 ↓                            │   │
│  │  ┌──────────────────────────────────────┐   │   │
│  │  │ ThirdPartyOrderVendorApiClient       │   │   │
│  │  │ (可配置不同的加密策略和API端点)         │   │   │
│  │  └──────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘

优势：
✅ 每个业务模块有独立的ApiClient实例
✅ 通过DI配置不同的加密策略
✅ Helper职责单一，只管理配置
✅ 轻松扩展新的加密算法
✅ 支持多供应商架构
```

---

## 📊 关键变更对比表

| 维度 | 重构前 | 重构后 |
|------|--------|--------|
| **加密策略** | 硬编码在Helper中 | 策略模式，可插拔 |
| **ApiClient配置** | 全局单例 | 每个模块独立实例 |
| **Helper职责** | 配置+加密+业务状态 | 仅配置管理 |
| **扩展性** | 需修改核心代码 | 只需添加新策略类 |
| **测试性** | 难以mock加密逻辑 | 可独立测试策略 |
| **多供应商** | 不支持 | 原生支持 |
| **配置灵活性** | 低 | 高（DI驱动） |

---

## 🎯 实际应用场景示例

### 场景1: 当前供应商（AES-256-CBC）

```xml
<!-- di.xml -->
<virtualType name="CurrentVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="encryptionStrategy" xsi:type="object">Aes256CbcStrategy</argument>
        <argument name="apiBaseUrl" xsi:type="string">https://current-vendor.com</argument>
        <argument name="secretId" xsi:type="string">xxx</argument>
    </arguments>
</virtualType>
```

### 场景2: 新增供应商（RSA加密）

```php
// 1. 创建RSA加密策略
namespace FolixCode\BaseSyncService\Model\Encryption;

class RsaStrategy implements EncryptionStrategyInterface
{
    public function encrypt(array $data): string
    {
        // RSA加密实现
        openssl_public_encrypt(...);
    }
    
    public function decrypt(string $encryptedData): array
    {
        // RSA解密实现
        openssl_private_decrypt(...);
    }
}
```

```xml
<!-- 2. 配置新的ApiClient -->
<virtualType name="NewVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="encryptionStrategy" xsi:type="object">RsaStrategy</argument>
        <argument name="apiBaseUrl" xsi:type="string">https://new-vendor.com</argument>
        <argument name="secretId" xsi:type="string">yyy</argument>
    </arguments>
</virtualType>

<!-- 3. 业务模块使用 -->
<type name="Your\Module\Service\YourService">
    <arguments>
        <argument name="apiClient" xsi:type="object">NewVendorApiClient</argument>
    </arguments>
</type>
```

**无需修改任何现有代码！** 🎉

### 场景3: 同一供应商，不同环境配置

```xml
<!-- 开发环境 -->
<virtualType name="DevVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="apiBaseUrl" xsi:type="string">https://dev-vendor.com</argument>
        <argument name="secretId" xsi:type="string">dev-key</argument>
    </arguments>
</virtualType>

<!-- 生产环境 -->
<virtualType name="ProdVendorApiClient" type="FolixCode\BaseSyncService\Model\ApiClient">
    <arguments>
        <argument name="apiBaseUrl" xsi:type="string">https://prod-vendor.com</argument>
        <argument name="secretId" xsi:type="string">prod-key</argument>
    </arguments>
</virtualType>
```

---

## 🔍 代码调用示例

### 重构前（耦合）

```php
// 所有模块都使用同一个ApiClient
class OldService
{
    private ApiClient $apiClient;
    
    public function __construct(ApiClient $apiClient)
    {
        // 无法区分是哪个供应商的配置
        $this->apiClient = $apiClient;
    }
}
```

### 重构后（解耦）

```php
// ProductSync模块
class ProductSyncService
{
    private ExternalApiClientInterface $apiClient;
    
    public function __construct(
        @Named("ProductSyncVendorApiClient") ExternalApiClientInterface $apiClient
    ) {
        // 明确知道使用的是ProductSync供应商的配置
        $this->apiClient = $apiClient;
    }
}

// ThirdPartyOrder模块
class OrderSyncService
{
    private ExternalApiClientInterface $apiClient;
    
    public function __construct(
        @Named("ThirdPartyOrderVendorApiClient") ExternalApiClientInterface $apiClient
    ) {
        // 明确知道使用的是ThirdPartyOrder供应商的配置
        $this->apiClient = $apiClient;
    }
}
```

---

## 📈 性能影响分析

| 指标 | 重构前 | 重构后 | 说明 |
|------|--------|--------|------|
| **HTTP请求** | 相同 | 相同 | Guzzle客户端复用 |
| **加密开销** | 相同 | 相同 | 算法未变 |
| **内存占用** | 较低 | 略高 | 多个ApiClient实例 |
| **初始化时间** | 较快 | 略慢 | DI容器解析稍复杂 |
| **可维护性** | ⭐⭐ | ⭐⭐⭐⭐⭐ | 显著提升 |
| **可扩展性** | ⭐⭐ | ⭐⭐⭐⭐⭐ | 显著提升 |

**结论**: 性能影响微乎其微，但可维护性和可扩展性大幅提升。

---

## 🚦 迁移检查清单

- [x] 创建 `EncryptionStrategyInterface` 接口
- [x] 实现 `Aes256CbcStrategy` 策略类
- [x] 重构 `ApiClient` 接受策略注入
- [x] 简化 `Helper/Data` 移除业务逻辑
- [x] 更新 `BaseSyncService/etc/di.xml` 添加虚拟类型
- [x] 更新 `ProductSync/etc/di.xml` 使用专用ApiClient
- [x] 更新 `ThirdPartyOrder/etc/di.xml` 使用专用ApiClient
- [x] 更新 `OrderSyncService` 移除对Helper的依赖
- [ ] 运行单元测试验证
- [ ] 运行集成测试验证
- [ ] 更新README文档
- [ ] 清理废弃代码

---

## 💡 最佳实践建议

### 1. 命名规范

```
{ModuleName}VendorApiClient  // 虚拟类型命名
例如：ProductSyncVendorApiClient, ThirdPartyOrderVendorApiClient
```

### 2. 配置管理

```xml
<!-- 推荐：在di.xml中集中配置 -->
<virtualType name="XXXVendorApiClient" type="...">
    <arguments>
        <argument name="apiBaseUrl" xsi:type="string">...</argument>
        <argument name="secretId" xsi:type="string">...</argument>
    </arguments>
</virtualType>
```

### 3. 日志记录

```php
// ApiClient自动记录加密方法
$this->logger->debug('Request', [
    'encryption_method' => $this->encryptionStrategy->getMethodName()
]);
```

### 4. 错误处理

```php
try {
    $response = $this->apiClient->post('/api/endpoint', $data);
} catch (\RuntimeException $e) {
    // 记录详细的错误上下文
    $this->logger->error('API call failed', [
        'vendor' => 'ProductSync',
        'encryption_method' => $this->apiClient->getEncryptionMethod(),
        'error' => $e->getMessage()
    ]);
}
```

---

## 🎓 总结

这次重构实现了：

1. ✅ **策略模式** - 加密算法可插拔
2. ✅ **依赖注入** - 配置驱动，灵活切换
3. ✅ **职责分离** - Helper只管理配置
4. ✅ **多供应商支持** - 为未来扩展奠定基础
5. ✅ **向后兼容** - 现有代码最小化改动

**下一步**: 联调测试，确保ProductSync和ThirdPartyOrder正常工作。
