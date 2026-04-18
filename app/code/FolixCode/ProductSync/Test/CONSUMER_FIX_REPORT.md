# Consumer 状态管理修复报告

## 🔴 问题发现

通过对比 Magento 官方的 [`Consumer.php`](../../vendor/magento/module-catalog/Model/Attribute/Backend/Consumer.php) 实现，发现我们的 `CategoryImportConsumer` 存在**严重的架构缺陷**：

### 官方实现（正确）

```php
public function process(OperationInterface $operation)
{
    try {
        // 执行业务逻辑
        $this->execute($data);
        
        // ✅ 成功时设置状态
        $status = OperationInterface::STATUS_TYPE_COMPLETE;
        $message = null;
        
    } catch (\Exception $e) {
        // ❌ 失败时设置状态和错误消息
        $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
        $errorCode = $e->getCode();
        $message = $e->getMessage();
    }
    
    // ✅ 关键步骤：更新 Operation 并保存到数据库
    $operation->setStatus($status)
        ->setErrorCode($errorCode ?? null)
        ->setResultMessage($message ?? null);
    
    $this->entityManager->save($operation);  // ← 写入 queue_message_status 表
}
```

### 我们的实现（错误）

```php
public function process(OperationInterface $operation): void
{
    try {
        // 执行逻辑
        $this->categoryImporter->import($categoryData);
        
    } catch (\Exception $e) {
        // ❌ 只记录日志
        $this->logger->error('Failed...');
        
        // ❌ 直接抛出异常，没有更新 Operation 状态
        throw $e;
    }
    
    // ❌ 缺少：没有设置 status、errorCode、resultMessage
    // ❌ 缺少：没有调用 entityManager->save($operation)
}
```

---

## 💥 导致的问题

### 1. 消息状态不正确

当 Consumer 处理失败时：
- ❌ **预期**：`queue_message_status.status = 6 (REJECTED)` + `result_message` 包含错误详情
- ✅ **实际**：状态可能保持为 `2 (IN_PROGRESS)` 或其他中间状态，`result_message` 为空

### 2. 无法追踪错误原因

```sql
-- 查询失败的消息
SELECT * FROM queue_message_status WHERE status = 6;

-- 官方实现会填充 result_message 字段
-- 我们的实现：result_message = NULL ❌
```

### 3. 重试机制失效

Magento MQ 框架根据 `status` 字段决定是否重试：
- `STATUS_TYPE_RETRIABLY_FAILED` (5) → 可重试
- `STATUS_TYPE_NOT_RETRIABLY_FAILED` (6) → 不重试，标记为 REJECTED

我们的实现直接抛出异常，框架无法正确判断是否应该重试。

---

## ✅ 修复方案

### 核心改进

1. **注入 EntityManager**
   ```php
   private EntityManager $entityManager;
   
   public function __construct(
       CategoryImporter $categoryImporter,
       SerializerInterface $serializer,
       LoggerInterface $logger,
       EntityManager $entityManager  // ← 新增
   ) {
       // ...
       $this->entityManager = $entityManager;
   }
   ```

2. **捕获所有异常并设置状态**
   ```php
   public function process(OperationInterface $operation): void
   {
       $status = OperationInterface::STATUS_TYPE_COMPLETE;
       $errorCode = null;
       $message = null;
       
       try {
           // 业务逻辑
           
       } catch (\Magento\Framework\DB\Adapter\LockWaitException | 
                \Magento\Framework\DB\Adapter\DeadlockException |
                \Magento\Framework\DB\Adapter\ConnectionException $e) {
           // ✅ 可重试的数据库异常
           $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
           $errorCode = $e->getCode();
           $message = $e->getMessage();
           
       } catch (\Magento\Framework\Exception\LocalizedException $e) {
           // ✅ 业务异常，不可重试
           $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
           $errorCode = $e->getCode();
           $message = $e->getMessage();
           
       } catch (\Exception $e) {
           // ✅ 其他异常，不可重试
           $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
           $errorCode = $e->getCode();
           $message = __('Sorry, something went wrong...');
       }
       
       // ✅ 关键：更新 Operation 并保存
       $operation->setStatus($status)
           ->setErrorCode($errorCode)
           ->setResultMessage($message);
       
       $this->entityManager->save($operation);
   }
   ```

3. **AlreadyExistsException 特殊处理**
   ```php
   catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
       // ✅ 分类已存在视为成功，不抛出异常
       $this->logger->info('Category already exists, skipped');
       // 状态保持为 COMPLETE
   }
   ```

---

## 📊 Operation 状态常量

| 常量 | 值 | 含义 | 使用场景 |
|------|-----|------|---------|
| `STATUS_TYPE_COMPLETE` | 2 | 完成 | 成功处理 |
| `STATUS_TYPE_RETRIABLY_FAILED` | 5 | 可重试失败 | 数据库锁、超时等临时错误 |
| `STATUS_TYPE_NOT_RETRIABLY_FAILED` | 6 | 不可重试失败 | 数据验证错误、业务逻辑错误 |

---

## 🔍 数据库影响

### 修复前

```sql
SELECT id, status, error_code, result_message 
FROM queue_message_status 
WHERE topic_name = 'folix.category.import';

-- 结果：
-- id: 123
-- status: 2 (可能卡在 IN_PROGRESS)
-- error_code: NULL ❌
-- result_message: NULL ❌
```

### 修复后

```sql
-- 成功的消息
-- status: 2 (COMPLETE)
-- error_code: NULL
-- result_message: NULL

-- 可重试失败
-- status: 5 (RETRIABLY_FAILED)
-- error_code: "40001" (死锁错误码)
-- result_message: "Deadlock found when trying to get lock..."

-- 不可重试失败
-- status: 6 (NOT_RETRIABLY_FAILED / REJECTED)
-- error_code: "0"
-- result_message: "Category ID is required"
```

---

## 🧪 测试更新

### 单元测试修改

1. **添加 EntityManager Mock**
   ```php
   $this->entityManagerMock = $this->getMockBuilder(EntityManager::class)
       ->disableOriginalConstructor()
       ->getMock();
   ```

2. **配置 Operation Mock 支持链式调用**
   ```php
   $this->operationMock->method('setStatus')
       ->willReturnSelf();
   $this->operationMock->method('setErrorCode')
       ->willReturnSelf();
   $this->operationMock->method('setResultMessage')
       ->willReturnSelf();
   ```

3. **验证状态更新**
   ```php
   // 成功场景
   $this->operationMock->expects($this->once())
       ->method('setStatus')
       ->with(OperationInterface::STATUS_TYPE_COMPLETE);
   
   $this->entityManagerMock->expects($this->once())
       ->method('save')
       ->with($this->operationMock);
   ```

---

## 📋 修复清单

- [x] 注入 `EntityManager` 依赖
- [x] 初始化状态变量（`$status`, `$errorCode`, `$message`）
- [x] 捕获 `LockWaitException/DeadlockException` → `STATUS_TYPE_RETRIABLY_FAILED`
- [x] 捕获 `LocalizedException` → `STATUS_TYPE_NOT_RETRIABLY_FAILED`
- [x] 捕获通用 `Exception` → `STATUS_TYPE_NOT_RETRIABLY_FAILED`
- [x] `AlreadyExistsException` 视为成功（不抛出）
- [x] 更新 Operation 状态：`setStatus()`, `setErrorCode()`, `setResultMessage()`
- [x] 保存 Operation：`$this->entityManager->save($operation)`
- [x] 更新单元测试适配新逻辑
- [x] 验证测试通过

---

## 🚀 验证步骤

### 1. 运行单元测试

```bash
cd /var/www/html/game/game
vendor/bin/phpunit app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php --testdox
```

**预期输出**：
```
OK (10 tests, 50+ assertions)
```

### 2. 检查数据库状态

```sql
-- 查看最近的消息状态
SELECT 
    id,
    topic_name,
    status,
    error_code,
    LEFT(result_message, 100) as message_preview,
    updated_at
FROM queue_message_status
WHERE topic_name LIKE '%category%'
ORDER BY updated_at DESC
LIMIT 10;
```

**预期结果**：
- 成功的消息：`status = 2`, `result_message = NULL`
- 失败的消息：`status = 6`, `result_message` 包含错误详情

### 3. 手动触发 Consumer

```bash
# 单线程模式，实时观察
bin/magento queue:consumers:start folix.category.import.consumer --single-thread
```

发送测试消息后，检查：
- ✅ 日志中是否有详细的错误信息
- ✅ 数据库中 `result_message` 是否被填充
- ✅ 状态是否正确设置为 2/5/6

---

## 💡 关键要点

### 为什么必须保存 Operation？

1. **MQ 框架依赖状态判断**
   - 框架读取 `queue_message_status.status` 决定下一步操作
   - 如果不更新，消息可能卡住或重复处理

2. **错误追踪**
   - `result_message` 是排查问题的第一手资料
   - 没有它，只能去翻日志，效率极低

3. **重试机制**
   - `STATUS_TYPE_RETRIABLY_FAILED` (5) → 框架会自动重试
   - `STATUS_TYPE_NOT_RETRIABLY_FAILED` (6) → 标记为 REJECTED，不再重试

### 对比总结

| 特性 | 修复前 | 修复后 |
|------|--------|--------|
| **Operation 状态更新** | ❌ 不更新 | ✅ 正确设置 |
| **错误消息记录** | ❌ 只在日志 | ✅ 日志 + 数据库 |
| **EntityManager 保存** | ❌ 不调用 | ✅ 调用 save() |
| **可重试异常识别** | ⚠️ 抛出异常 | ✅ 设置 RETRIABLY_FAILED |
| **不可重试异常识别** | ⚠️ 抛出异常 | ✅ 设置 NOT_RETRIABLY_FAILED |
| **AlreadyExists 处理** | ✅ 正确 | ✅ 保持正确 |
| **框架兼容性** | ❌ 不符合规范 | ✅ 完全兼容 |

---

## 📚 参考文档

- [Magento 官方 Consumer 实现](../../vendor/magento/module-catalog/Model/Attribute/Backend/Consumer.php)
- [OperationInterface 定义](../../generated/code/Magento/AsynchronousOperations/Api/Data/OperationInterface.php)
- [队列消息状态表结构](https://devdocs.magento.com/guides/v2.4/message-queues/message-queue-framework.html)

---

**修复日期**: 2026-04-18  
**状态**: ✅ 已完成并测试通过  
**影响范围**: `CategoryImportConsumer` 及其单元测试
