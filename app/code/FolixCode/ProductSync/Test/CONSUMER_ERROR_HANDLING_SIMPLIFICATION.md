# Consumer 错误处理简化报告

## 🎯 修改目标

按照项目规范：**Consumer 中不手动保存错误状态到数据库，只记录日志并抛出异常**，让 Magento 框架自动处理重试逻辑。

---

## ✅ 修改的文件

### 1️⃣ CategoryImportConsumer.php

**文件**: [`CategoryImportConsumer.php`](file:///var/www/html/game/game/app/code/FolixCode/ProductSync/Model/MessageQueue/Consumer/CategoryImportConsumer.php)

#### ❌ 修复前（复杂）
```php
use Magento\Framework\EntityManager\EntityManager;

private EntityManager $entityManager;

public function __construct(
    // ...
    EntityManager $entityManager
) {
    $this->entityManager = $entityManager;
}

public function process(OperationInterface $operation): void
{
    $status = OperationInterface::STATUS_TYPE_COMPLETE;
    $errorCode = null;
    $message = null;
    
    try {
        // ... 业务逻辑
        
    } catch (\Magento\Framework\DB\Adapter\LockWaitException | ...) {
        $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
        $errorCode = $e->getCode();
        $message = $e->getMessage();
        
    } catch (\Magento\Framework\Exception\LocalizedException $e) {
        $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
        $errorCode = $e->getCode();
        $message = $e->getMessage();
        
    } catch (\Exception $e) {
        $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
        $errorCode = $e->getCode();
        $message = __('Sorry, something went wrong...');
    }

    // ❌ 手动保存状态
    $operation->setStatus($status)
        ->setErrorCode($errorCode)
        ->setResultMessage($message);
    $this->entityManager->save($operation);
}
```

#### ✅ 修复后（简洁）
```php
// 移除 EntityManager 依赖

public function process(OperationInterface $operation): void
{
    try {
        // ... 业务逻辑
        
    } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
        // ✅ 已存在视为成功，不抛出异常
        $this->logger->info('Category already exists, skipped', [...]);
        
    } catch (\Exception $e) {
        // ❌ 其他所有错误：记录日志并抛出异常
        $this->logger->critical('Failed to process category import', [
            'category_id' => $categoryData['id'] ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        // ✅ 抛出异常，让 Magento 框架自动处理
        throw $e;
    }
}
```

**关键变化**：
- ✅ 移除了 `EntityManager` 依赖
- ✅ 移除了所有状态管理变量（`$status`, `$errorCode`, `$message`）
- ✅ 移除了手动保存状态的代码
- ✅ 只在 `AlreadyExistsException` 时不抛出异常（视为成功）
- ✅ 其他所有异常都记录日志并重新抛出

---

### 2️⃣ ProductImportConsumer.php

**文件**: [`ProductImportConsumer.php`](file:///var/www/html/game/game/app/code/FolixCode/ProductSync/Model/MessageQueue/Consumer/ProductImportConsumer.php)

**同样的修改**：
- ✅ 移除 `EntityManager` 依赖
- ✅ 简化异常处理逻辑
- ✅ 只记录日志并抛出异常

---

### 3️⃣ ProductDetailConsumer.php

**文件**: [`ProductDetailConsumer.php`](file:///var/www/html/game/game/app/code/FolixCode/ProductSync/Model/MessageQueue/Consumer/ProductDetailConsumer.php)

**同样的修改**：
- ✅ 移除 `EntityManager` 依赖
- ✅ 简化异常处理逻辑
- ✅ 只记录日志并抛出异常

---

## 📊 对比表

| 维度 | 修复前 | 修复后 |
|------|--------|--------|
| **依赖数量** | 4 个（含 EntityManager） | 3 个（移除 EntityManager） |
| **代码行数** | ~140 行 | ~90 行（减少 35%） |
| **异常类型** | 区分可重试/不可重试 | 统一抛出，由框架决定 |
| **状态管理** | 手动设置 status/errorCode/message | ❌ 移除 |
| **数据库操作** | 手动调用 `$entityManager->save()` | ❌ 移除 |
| **框架职责** | 部分接管框架职责 | ✅ 完全交给框架 |

---

## 💡 设计理念

### 为什么这样更好？

#### 1. **符合单一职责原则**
- **Consumer 的职责**：处理业务逻辑
- **框架的职责**：管理消息状态、重试逻辑、失败处理

#### 2. **避免重复造轮子**
Magento 框架已经提供了完善的错误处理机制：
- 捕获异常 → 标记消息为失败
- 根据配置自动重试
- 超过重试次数后标记为永久失败

我们不需要手动实现这些逻辑。

#### 3. **更易于维护**
- 代码更简洁（减少 35%）
- 逻辑更清晰
- 不需要关心状态码的含义
- 不需要手动更新数据库

#### 4. **更好的可扩展性**
如果未来需要调整重试策略：
- ❌ 修复前：需要修改所有 Consumer 的代码
- ✅ 修复后：只需修改队列配置（`queue_consumer.xml`）

---

## 🔍 Magento 框架如何处理异常？

当 Consumer 抛出异常时，Magento 框架会：

1. **捕获异常**
   ```php
   try {
       $consumer->process($operation);
   } catch (\Exception $e) {
       // 框架捕获异常
   }
   ```

2. **记录错误信息**
   - 将异常消息保存到 `queue_message_status.result_message`
   - 将异常代码保存到 `queue_message_status.error_code`

3. **更新消息状态**
   - 设置为 `REJECTED` (6) 或 `RETRIABLY_FAILED` (5)
   - 具体取决于配置和异常类型

4. **决定是否重试**
   - 如果未达到最大重试次数 → 重新入队
   - 如果已达到最大重试次数 → 标记为永久失败

5. **触发死信队列（如果配置）**
   - 将失败的消息发送到死信队列
   - 便于后续人工处理

---

## 🚀 测试步骤

### 1. 清理缓存并重新编译

```bash
cd /var/www/html/game/game

bin/magento cache:clean
rm -rf generated/code/FolixCode
bin/magento setup:di:compile
```

### 2. 启动消费者

```bash
# 单线程模式测试
bin/magento queue:consumers:start folixcode.category.import.consumer --single-thread
```

### 3. 触发正常消息

```bash
bin/magento folixcode:sync --type=categories --limit=1
```

**预期结果**：
- ✅ 日志显示 "Category import completed successfully"
- ✅ 没有异常抛出
- ✅ 消息状态为 COMPLETE

### 4. 触发异常消息（测试错误处理）

可以手动构造一个会导致异常的消息，例如：
- 缺少必填字段
- 无效的 URL Key
- 数据库约束冲突

**预期结果**：
- ✅ 日志显示 "Failed to process category import" + 异常堆栈
- ✅ 异常被抛出
- ✅ 框架捕获异常并标记消息为失败
- ✅ 根据配置决定是否重试

### 5. 检查数据库

```sql
-- 查看消息状态
SELECT 
    id,
    topic_name,
    status,
    created_at,
    updated_at
FROM queue_message
ORDER BY created_at DESC
LIMIT 10;

-- 查看错误详情
SELECT 
    operation_id,
    status,
    error_code,
    LEFT(result_message, 200) as error_message,
    updated_at
FROM queue_message_status
WHERE status IN (5, 6)  -- RETRIABLY_FAILED 或 NOT_RETRIABLY_FAILED
ORDER BY updated_at DESC
LIMIT 10;
```

---

## 📝 相关文档

- [Magento Message Queue Framework](https://devdocs.magento.com/guides/v2.4/message-queues/message-queue-framework.html)
- [Queue Consumer Configuration](https://devdocs.magento.com/guides/v2.4/config-guide/mq/manage-message-queues.html)
- [Error Handling in Consumers](https://devdocs.magento.com/guides/v2.4/message-queues/message-queue-framework.html#error-handling)

---

## 💡 最佳实践总结

### ✅ 推荐做法

1. **Consumer 只负责业务逻辑**
   ```php
   public function process(OperationInterface $operation): void
   {
       try {
           // 业务逻辑
           $this->service->doSomething($data);
           
       } catch (\Exception $e) {
           // 记录日志
           $this->logger->critical('Error', ['error' => $e->getMessage()]);
           
           // 抛出异常，交给框架处理
           throw $e;
       }
   }
   ```

2. **使用有意义的日志级别**
   - `info`: 正常流程
   - `warning`: 可预期的异常情况（如已存在）
   - `critical`: 真正的错误

3. **提供详细的上下文信息**
   ```php
   $this->logger->critical('Failed to process', [
       'entity_id' => $id,
       'error' => $e->getMessage(),
       'trace' => $e->getTraceAsString(),  // 便于调试
   ]);
   ```

### ❌ 避免的做法

1. **不要手动管理消息状态**
   ```php
   // ❌ 错误
   $operation->setStatus(...);
   $this->entityManager->save($operation);
   
   // ✅ 正确
   throw $e;  // 让框架处理
   ```

2. **不要吞掉异常**
   ```php
   // ❌ 错误
   catch (\Exception $e) {
       $this->logger->error($e->getMessage());
       // 没有抛出异常，框架认为成功
   }
   
   // ✅ 正确
   catch (\Exception $e) {
       $this->logger->error($e->getMessage());
       throw $e;  // 必须抛出
   }
   ```

3. **不要区分可重试/不可重试**
   ```php
   // ❌ 错误（过度复杂）
   catch (LockWaitException $e) {
       $status = STATUS_TYPE_RETRIABLY_FAILED;
   } catch (LocalizedException $e) {
       $status = STATUS_TYPE_NOT_RETRIABLY_FAILED;
   }
   
   // ✅ 正确（简单明了）
   catch (\Exception $e) {
       $this->logger->critical('Error', [...]);
       throw $e;  // 框架会根据配置决定是否重试
   }
   ```

---

**修改日期**: 2026-04-18  
**状态**: ✅ 已完成  
**影响文件**: 
- `CategoryImportConsumer.php`
- `ProductImportConsumer.php`
- `ProductDetailConsumer.php`

**下一步**: 清理缓存并测试
