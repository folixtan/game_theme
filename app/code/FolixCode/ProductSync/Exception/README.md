# API 同步异常使用指南

## 📋 概述

`ApiSyncException` 是 FolixCode ProductSync 模块的自定义异常类，专门用于处理游戏充值 API 同步过程中的业务异常。

## 🎯 特性

- ✅ 继承自 `Magento\Framework\Exception\LocalizedException`，支持国际化消息
- ✅ 支持传递额外的上下文数据（如 product_id、API 响应等）
- ✅ 符合 Magento 框架标准的异常处理方式
- ✅ 便于日志记录和调试

## 📦 文件位置

```
app/code/FolixCode/ProductSync/Exception/ApiSyncException.php
```

## 💡 使用示例

### 1. 基本用法

```php
use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;

// 抛出简单的异常
throw new ApiSyncException(
    new Phrase('Failed to sync product from API')
);
```

### 2. 带上下文数据的用法

```php
use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;

// 抛出带上下文数据的异常
throw new ApiSyncException(
    new Phrase('Product import failed: %1', [$productData['name']]),
    null,  // 原始异常（可选）
    400,   // 错误代码
    [
        'product_id' => $productData['id'],
        'sku' => $productData['sku'],
        'api_response' => $apiResponse
    ]
);
```

### 3. 包装其他异常

```php
use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;

try {
    // 执行可能失败的操作
    $this->apiClient->syncProduct($productData);
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    // 包装为 ApiSyncException
    throw new ApiSyncException(
        new Phrase('API request failed: %1', [$e->getMessage()]),
        $e,  // 原始异常
        $e->getCode(),
        [
            'endpoint' => '/api/products',
            'method' => 'POST',
            'product_id' => $productData['id']
        ]
    );
}
```

### 4. 在 Consumer 中使用

```php
namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;

class ProductImportConsumer
{
    public function process(OperationInterface $operation): void
    {
        try {
            // 业务逻辑
            $this->productImporter->import($productData);
            
        } catch (ApiSyncException $e) {
            // 记录详细的错误信息
            $this->logger->critical('API sync failed', [
                'message' => $e->getMessage(),
                'context' => $e->getContextData(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 抛出异常，让 Magento 框架处理重试
            throw $e;
        }
    }
}
```

### 5. 在服务层中使用

```php
namespace FolixCode\ProductSync\Service;

use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;

class ProductImporter
{
    public function import(array $productData): void
    {
        // 验证必填字段
        if (empty($productData['id'])) {
            throw new ApiSyncException(
                new Phrase('Product ID is required'),
                null,
                400,
                ['provided_data' => $productData]
            );
        }
        
        // 验证价格
        if (!isset($productData['price']) || $productData['price'] <= 0) {
            throw new ApiSyncException(
                new Phrase('Invalid product price: %1', [$productData['price'] ?? 'null']),
                null,
                422,
                [
                    'product_id' => $productData['id'],
                    'price' => $productData['price'] ?? null
                ]
            );
        }
        
        // ... 继续导入逻辑
    }
}
```

## 🔍 异常方法

### getContextData()

获取额外的上下文数据，用于日志记录和调试。

```php
try {
    // ...
} catch (ApiSyncException $e) {
    $context = $e->getContextData();
    // ['product_id' => 123, 'sku' => 'ABC', ...]
}
```

### setContextData()

动态设置或更新上下文数据。

```php
$exception = new ApiSyncException(new Phrase('Error'));
$exception->setContextData(['step' => 'validation']);

// 后续可以添加更多上下文
$data = $exception->getContextData();
$data['step'] = 'import';
$exception->setContextData($data);
```

## 📊 常见场景

### 场景 1: API 请求失败

```php
throw new ApiSyncException(
    new Phrase('External API request failed with status %1', [$statusCode]),
    $originalException,
    $statusCode,
    [
        'endpoint' => $endpoint,
        'method' => $method,
        'request_body' => $requestBody,
        'response_body' => $responseBody
    ]
);
```

### 场景 2: 数据验证失败

```php
throw new ApiSyncException(
    new Phrase('Invalid product data: %1', [implode(', ', $errors)]),
    null,
    422,
    [
        'product_id' => $productId,
        'validation_errors' => $errors
    ]
);
```

### 场景 3: 产品已存在

```php
throw new ApiSyncException(
    new Phrase('Product with SKU %1 already exists', [$sku]),
    null,
    409,  // Conflict
    [
        'sku' => $sku,
        'existing_product_id' => $existingProductId
    ]
);
```

### 场景 4: 分类不存在

```php
throw new ApiSyncException(
    new Phrase('Category not found: %1', [$categoryName]),
    null,
    404,
    [
        'category_name' => $categoryName,
        'category_path' => $categoryPath
    ]
);
```

## 🎨 最佳实践

### ✅ 推荐做法

1. **始终提供有意义的错误消息**
   ```php
   // ✅ 好
   throw new ApiSyncException(
       new Phrase('Failed to import product %1: Invalid price', [$productId])
   );
   
   // ❌ 不好
   throw new ApiSyncException(new Phrase('Error'));
   ```

2. **传递相关上下文数据**
   ```php
   throw new ApiSyncException(
       new Phrase('Import failed'),
       null,
       500,
       [
           'product_id' => $productId,
           'step' => 'price_update',
           'attempt' => $retryCount
       ]
   );
   ```

3. **包装原始异常**
   ```php
   try {
       $this->apiClient->call();
   } catch (\Exception $e) {
       throw new ApiSyncException(
           new Phrase('API call failed: %1', [$e->getMessage()]),
           $e,  // 保留原始异常栈
           $e->getCode()
       );
   }
   ```

4. **使用合适的 HTTP 状态码作为错误代码**
   - 400: 请求参数错误
   - 404: 资源不存在
   - 409: 冲突（如重复创建）
   - 422: 数据验证失败
   - 500: 服务器内部错误

### ❌ 避免的做法

1. **不要吞掉异常**
   ```php
   // ❌ 错误
   try {
       $this->import($data);
   } catch (ApiSyncException $e) {
       $this->logger->error($e->getMessage());
       // 没有重新抛出
   }
   
   // ✅ 正确
   try {
       $this->import($data);
   } catch (ApiSyncException $e) {
       $this->logger->critical('Import failed', [
           'error' => $e->getMessage(),
           'context' => $e->getContextData()
       ]);
       throw $e;  // 重新抛出
   }
   ```

2. **不要使用通用异常消息**
   ```php
   // ❌ 不好
   throw new ApiSyncException(new Phrase('Something went wrong'));
   
   // ✅ 好
   throw new ApiSyncException(
       new Phrase('Failed to update product %1 stock quantity', [$productId])
   );
   ```

## 📝 与 Magento 框架集成

### 在 di.xml 中配置（如果需要）

通常不需要特殊配置，因为 `ApiSyncException` 直接继承自 `LocalizedException`。

### 在队列消费者中使用

根据项目规范，Consumer 中应该：
1. 捕获 `ApiSyncException`
2. 记录详细日志（包括上下文数据）
3. 重新抛出异常，让 Magento 框架处理重试逻辑

```php
public function process(OperationInterface $operation): void
{
    try {
        // 业务逻辑
        
    } catch (ApiSyncException $e) {
        $this->logger->critical('API sync error', [
            'message' => $e->getMessage(),
            'context' => $e->getContextData(),
            'operation_id' => $operation->getId()
        ]);
        throw $e;  // 让框架处理重试
    }
}
```

## 🔗 相关文档

- [Magento Exception Handling](https://devdocs.magento.com/guides/v2.4/php/bk-php.html#error-handling)
- [Magento Message Queue Framework](https://devdocs.magento.com/guides/v2.4/message-queues/message-queue-framework.html)
- [FolixCode ProductSync README](../README.md)

---

**创建日期**: 2026-04-19  
**版本**: 1.0.0  
**维护者**: FolixCode Team
