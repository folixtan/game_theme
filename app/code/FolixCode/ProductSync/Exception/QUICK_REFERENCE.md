# ApiSyncException 快速参考

## 🚀 快速开始

### 导入
```php
use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;
```

### 基本用法
```php
throw new ApiSyncException(
    new Phrase('Error message'),
    $cause,      // 原始异常（可选）
    $code,       // 错误代码（可选）
    $contextData // 上下文数据（可选）
);
```

## 📋 常用场景

### 1️⃣ 简单错误
```php
throw new ApiSyncException(new Phrase('Product not found'));
```

### 2️⃣ 带参数
```php
throw new ApiSyncException(
    new Phrase('Failed to import product %1', [$productId])
);
```

### 3️⃣ 带上下文
```php
throw new ApiSyncException(
    new Phrase('Import failed'),
    null,
    400,
    ['product_id' => $id, 'sku' => $sku]
);
```

### 4️⃣ 包装异常
```php
try {
    $this->apiCall();
} catch (\Exception $e) {
    throw new ApiSyncException(
        new Phrase('API error: %1', [$e->getMessage()]),
        $e,
        $e->getCode()
    );
}
```

## 🔍 可用方法

| 方法 | 说明 | 示例 |
|------|------|------|
| `getMessage()` | 获取错误消息 | `$e->getMessage()` |
| `getCode()` | 获取错误代码 | `$e->getCode()` |
| `getContextData()` | 获取上下文数据 | `$e->getContextData()` |
| `setContextData()` | 设置上下文数据 | `$e->setContextData([...])` |
| `getRawMessage()` | 获取原始消息模板 | `$e->getRawMessage()` |
| `getParameters()` | 获取消息参数 | `$e->getParameters()` |
| `getPrevious()` | 获取原始异常 | `$e->getPrevious()` |

## 💡 错误代码建议

| 代码 | 含义 | 使用场景 |
|------|------|---------|
| 400 | Bad Request | 请求参数错误 |
| 404 | Not Found | 资源不存在 |
| 409 | Conflict | 资源冲突（如重复） |
| 422 | Unprocessable Entity | 数据验证失败 |
| 500 | Internal Server Error | 服务器内部错误 |

## ✅ 最佳实践

1. **提供清晰的错误消息**
   ```php
   // ✅ 好
   new Phrase('Product %1 import failed: invalid price', [$productId])
   
   // ❌ 不好
   new Phrase('Error')
   ```

2. **传递相关上下文**
   ```php
   ['product_id' => $id, 'step' => 'validation', 'attempt' => $retry]
   ```

3. **包装原始异常**
   ```php
   new ApiSyncException($message, $originalException, $code)
   ```

4. **在 Consumer 中重新抛出**
   ```php
   catch (ApiSyncException $e) {
       $this->logger->critical('Error', ['context' => $e->getContextData()]);
       throw $e;  // 让框架处理重试
   }
   ```

## 📁 文件位置

- **异常类**: `app/code/FolixCode/ProductSync/Exception/ApiSyncException.php`
- **使用文档**: `app/code/FolixCode/ProductSync/Exception/README.md`
- **单元测试**: `app/code/FolixCode/ProductSync/Test/Unit/Exception/ApiSyncExceptionTest.php`

---

**最后更新**: 2026-04-19
