# BaseSyncService Module

## 概述

`FolixCode_BaseSyncService` 是一个基础同步服务模块，提供最基础的全局可用功能。

## 职责

本模块只提供以下基础功能：

1. **HTTP客户端** - 通用的HTTP请求能力
2. **外部API客户端** - 统一的外部API调用接口，自动处理加密和签名
3. **Helper** - 加密解密、配置管理、签名生成

## 架构特点

- **纯基础层**：不包含任何业务逻辑
- **全局可用**：可被多个模块复用
- **高度解耦**：与具体业务无关

## API接口

### HttpClientInterface
提供基础的HTTP请求方法：
- `get()` - GET请求
- `post()` - POST请求
- `put()` - PUT请求
- `delete()` - DELETE请求

### ExternalApiClientInterface
提供外部API调用能力，自动处理：
- 请求加密
- 签名生成
- 统一错误处理

## 使用示例

在业务模块中使用：

```php
use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;

class YourBusinessService
{
    private ExternalApiClientInterface $apiClient;

    public function __construct(
        ExternalApiClientInterface $apiClient
    ) {
        $this->apiClient = $apiClient;
    }

    public function callYourApi(): array
    {
        $url = 'https://api.example.com/your-endpoint';
        $params = ['param1' => 'value1'];

        return $this->apiClient->get($url, $params);
    }
}
```

## 配置

在 `app/code/FolixCode/BaseSyncService/etc/config.xml` 中配置：

```xml
<config>
    <default>
        <folixcode_basesyncservice>
            <api>
                <base_url>https://api.example.com</base_url>
                <secret_id>your-secret-id</secret_id>
                <secret_key>your-secret-key</secret_key>
                <enabled>1</enabled>
            </api>
        </folixcode_basesyncservice>
    </default>
</config>
```

## 注意事项

- 本模块不应包含任何业务逻辑
- 所有业务逻辑应该在具体的业务模块中实现（如 ProductSync）
- 本模块只提供基础设施和通用工具
