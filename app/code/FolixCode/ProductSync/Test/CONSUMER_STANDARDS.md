# Consumer 统一规范文档

## 📋 目录

1. [架构设计原则](#架构设计原则)
2. [标准实现模板](#标准实现模板)
3. [状态管理规范](#状态管理规范)
4. [异常处理策略](#异常处理策略)
5. [日志记录规范](#日志记录规范)
6. [依赖注入规范](#依赖注入规范)
7. [验证清单](#验证清单)

---

## 🎯 架构设计原则

### 核心原则

所有 Consumer 必须遵循以下原则，确保与 Magento MQ 框架完全兼容：

1. **状态管理**：必须更新 Operation 状态并保存到数据库
2. **异常分类**：区分可重试和不可重试异常
3. **早期验证**：在业务逻辑执行前验证必填字段
4. **完整日志**：记录关键节点和错误详情
5. **资源清理**：确保数据库事务正确提交或回滚

---

## 📝 标准实现模板

### 完整的 Consumer 结构

```php
<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Service\{YourService};
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * {功能描述}消费者
 */
class {Feature}Consumer
{
    private {YourService} ${yourService};
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private EntityManager $entityManager;

    public function __construct(
        {YourService} ${yourService},
        SerializerInterface $serializer,
        LoggerInterface $logger,
        EntityManager $entityManager  // ← 必需
    ) {
        $this->{yourService} = ${yourService};
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * 处理{功能}消息
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function process(OperationInterface $operation): void
    {
        $startTime = microtime(true);
        $entityId = 'unknown';
        $status = OperationInterface::STATUS_TYPE_COMPLETE;  // ← 默认成功
        $errorCode = null;
        $message = null;
        
        try {
            // 1. 反序列化数据
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);

            // 2. 验证必填字段（早期验证）
            if (empty($data['id'])) {
                $this->logger->warning('Invalid data received: missing ID', [
                    'data' => $data
                ]);
                throw new \InvalidArgumentException('{Entity} ID is required');
            }

            $entityId = $data['id'];

            // 3. 记录开始日志
            $this->logger->info('Processing {entity} import', [
                '{entity}_id' => $entityId
            ]);

            // 4. 执行业务逻辑
            $this->{yourService}->import($data);

            // 5. 记录成功日志
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('{Entity} import completed successfully', [
                '{entity}_id' => $entityId,
                'duration_ms' => $duration
            ]);

        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            // ✅ 已存在，视为成功
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('{Entity} already exists, skipped', [
                '{entity}_id' => $entityId ?? 'unknown',
                'duration_ms' => $duration
            ]);
            
            // 状态保持为 COMPLETE
            
        } catch (\Magento\Framework\DB\Adapter\LockWaitException | 
                 \Magento\Framework\DB\Adapter\DeadlockException |
                 \Magento\Framework\DB\Adapter\ConnectionException $e) {
            // ✅ 数据库锁，可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->warning('Database lock detected, will retry', [
                '{entity}_id' => $entityId ?? 'unknown',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // ❌ 业务异常，不可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process {entity} import (business error)', [
                '{entity}_id' => $entityId ?? 'unknown',
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            
        } catch (\Exception $e) {
            // ❌ 其他异常，不可重试
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->critical('Failed to process {entity} import', [
                '{entity}_id' => $entityId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration
            ]);
            
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during {entity} import. Please see log for details.');
        }

        // ✅ 关键步骤：更新 Operation 状态并保存
        $operation->setStatus($status)
            ->setErrorCode($errorCode)
            ->setResultMessage($message);

        $this->entityManager->save($operation);
    }
}
```

---

## 📊 状态管理规范

### Operation 状态常量

| 常量 | 值 | 含义 | 使用场景 |
|------|-----|------|---------|
| `STATUS_TYPE_COMPLETE` | 2 | 完成 | 成功处理、已存在跳过 |
| `STATUS_TYPE_RETRIABLY_FAILED` | 5 | 可重试失败 | 数据库锁、连接超时 |
| `STATUS_TYPE_NOT_RETRIABLY_FAILED` | 6 | 不可重试失败 | 验证错误、业务逻辑错误 |

### 状态设置规则

```php
// 初始化默认状态（假设成功）
$status = OperationInterface::STATUS_TYPE_COMPLETE;

try {
    // 业务逻辑
    
} catch (AlreadyExistsException $e) {
    // ✅ 已存在视为成功，不修改状态
    
} catch (LockWaitException | DeadlockException | ConnectionException $e) {
    // ✅ 可重试的临时错误
    $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
    
} catch (LocalizedException $e) {
    // ❌ 业务逻辑错误，不可重试
    $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
    
} catch (\Exception $e) {
    // ❌ 未知错误，不可重试
    $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
}

// ✅ 必须执行：更新并保存
$operation->setStatus($status)
    ->setErrorCode($errorCode)
    ->setResultMessage($message);

$this->entityManager->save($operation);
```

---

## ⚠️ 异常处理策略

### 异常分类矩阵

| 异常类型 | 状态 | 是否重试 | 日志级别 | 错误消息 |
|---------|------|---------|---------|---------|
| `AlreadyExistsException` | COMPLETE | ❌ 否 | INFO | 记录跳过原因 |
| `LockWaitException` | RETRIABLY_FAILED | ✅ 是 | WARNING | 原始错误消息 |
| `DeadlockException` | RETRIABLY_FAILED | ✅ 是 | WARNING | 原始错误消息 |
| `ConnectionException` | RETRIABLY_FAILED | ✅ 是 | WARNING | 原始错误消息 |
| `LocalizedException` | NOT_RETRIABLY_FAILED | ❌ 否 | CRITICAL | 原始错误消息 |
| `\InvalidArgumentException` | NOT_RETRIABLY_FAILED | ❌ 否 | CRITICAL | 原始错误消息 |
| 其他 `\Exception` | NOT_RETRIABLY_FAILED | ❌ 否 | CRITICAL | 通用友好提示 |

### 异常捕获顺序

```php
try {
    // 业务逻辑
    
} catch (AlreadyExistsException $e) {
    // 1️⃣ 最先捕获：已存在视为成功
    
} catch (LockWaitException | DeadlockException | ConnectionException $e) {
    // 2️⃣ 其次捕获：数据库临时错误（可重试）
    
} catch (LocalizedException $e) {
    // 3️⃣ 然后捕获：业务逻辑错误（不可重试）
    
} catch (\Exception $e) {
    // 4️⃣ 最后捕获：所有其他异常（兜底）
}
```

**重要**：
- ✅ 具体异常在前，通用异常在后
- ✅ 不要直接 `throw $e`，而是设置状态并保存
- ✅ 每个 catch 块都要记录日志

---

## 📝 日志记录规范

### 日志级别使用

| 级别 | 方法 | 使用场景 | 示例 |
|------|------|---------|------|
| INFO | `$logger->info()` | 正常流程、成功完成 | "Processing category import" |
| WARNING | `$logger->warning()` | 可恢复的警告、需要关注 | "Database lock detected" |
| CRITICAL | `$logger->critical()` | 严重错误、需要立即处理 | "Failed to process import" |
| ERROR | `$logger->error()` | ❌ 避免使用，用 CRITICAL 替代 | - |

### 日志内容规范

#### 1. 开始日志

```php
$this->logger->info('Processing {entity} import', [
    '{entity}_id' => $entityId
]);
```

**要求**：
- ✅ 包含实体 ID
- ✅ 简洁明了
- ✅ 便于追踪

---

#### 2. 成功日志

```php
$duration = round((microtime(true) - $startTime) * 1000, 2);
$this->logger->info('{Entity} import completed successfully', [
    '{entity}_id' => $entityId,
    'duration_ms' => $duration
]);
```

**要求**：
- ✅ 包含执行时长
- ✅ 明确标识成功
- ✅ 便于性能分析

---

#### 3. 警告日志（可重试）

```php
$duration = round((microtime(true) - $startTime) * 1000, 2);
$this->logger->warning('Database lock detected, will retry', [
    '{entity}_id' => $entityId ?? 'unknown',
    'error' => $e->getMessage(),
    'duration_ms' => $duration
]);
```

**要求**：
- ✅ 说明会重试
- ✅ 包含错误详情
- ✅ 包含执行时长

---

#### 4. 错误日志（不可重试）

```php
$duration = round((microtime(true) - $startTime) * 1000, 2);
$this->logger->critical('Failed to process {entity} import', [
    '{entity}_id' => $entityId ?? 'unknown',
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'duration_ms' => $duration
]);
```

**要求**：
- ✅ 包含完整堆栈跟踪
- ✅ 包含错误详情
- ✅ 使用 CRITICAL 级别

---

#### 5. 验证失败日志

```php
$this->logger->warning('Invalid data received: missing ID', [
    'data' => $data
]);
```

**要求**：
- ✅ 说明验证失败原因
- ✅ 包含原始数据（便于调试）
- ✅ 使用 WARNING 级别

---

## 🔧 依赖注入规范

### 必需的依赖

```php
public function __construct(
    {YourService} ${yourService},      // 业务服务
    SerializerInterface $serializer,   // 数据反序列化
    LoggerInterface $logger,           // 日志记录
    EntityManager $entityManager       // ← 必需：保存 Operation 状态
) {
    $this->{yourService} = ${yourService};
    $this->serializer = $serializer;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
}
```

### 依赖说明

| 依赖 | 类型 | 作用 | 必需性 |
|------|------|------|--------|
| `{YourService}` | 业务服务 | 执行具体的导入逻辑 | ✅ 必需 |
| `SerializerInterface` | 框架服务 | 反序列化消息数据 | ✅ 必需 |
| `LoggerInterface` | PSR-3 | 记录日志 | ✅ 必需 |
| `EntityManager` | 框架服务 | 保存 Operation 状态 | ✅ 必需 |

---

## ✅ 验证清单

### 代码审查清单

在提交 Consumer 代码前，必须检查以下项目：

#### 1. 依赖注入

- [ ] 注入了 `EntityManager` 依赖
- [ ] 构造函数参数顺序合理
- [ ] 所有依赖都有类型声明

#### 2. 状态管理

- [ ] 初始化了 `$status`、`$errorCode`、`$message` 变量
- [ ] 默认状态设置为 `STATUS_TYPE_COMPLETE`
- [ ] 在 catch 块中正确设置状态
- [ ] 调用了 `$operation->setStatus()`
- [ ] 调用了 `$operation->setErrorCode()`
- [ ] 调用了 `$operation->setResultMessage()`
- [ ] 调用了 `$this->entityManager->save($operation)`

#### 3. 异常处理

- [ ] 捕获了 `AlreadyExistsException`（视为成功）
- [ ] 捕获了数据库锁异常（可重试）
- [ ] 捕获了 `LocalizedException`（不可重试）
- [ ] 捕获了通用 `\Exception`（兜底）
- [ ] 异常捕获顺序正确（具体 → 通用）
- [ ] 没有直接 `throw $e`

#### 4. 日志记录

- [ ] 记录了开始日志（INFO）
- [ ] 记录了成功日志（INFO + 时长）
- [ ] 记录了警告日志（WARNING + 会重试）
- [ ] 记录了错误日志（CRITICAL + 堆栈）
- [ ] 记录了验证失败日志（WARNING + 数据）
- [ ] 所有日志都包含实体 ID
- [ ] 所有日志都包含执行时长（除验证外）

#### 5. 数据验证

- [ ] 在业务逻辑前验证必填字段
- [ ] 验证失败时抛出 `\InvalidArgumentException`
- [ ] 验证失败时记录日志
- [ ] 使用了早期返回/抛出模式

#### 6. 代码质量

- [ ] 使用了 `declare(strict_types=1)`
- [ ] 类和方法有 PHPDoc 注释
- [ ] 变量命名清晰
- [ ] 没有硬编码的字符串（使用 `__()` 翻译）
- [ ] 没有调试代码（var_dump、exit 等）

---

## 📋 已实现的 Consumer

### 1. CategoryImportConsumer

**文件**: [`CategoryImportConsumer.php`](../Model/MessageQueue/Consumer/CategoryImportConsumer.php)

**功能**: 分类导入

**特点**:
- ✅ 验证 `id` 和 `name` 必填字段
- ✅ 自动生成 URL Key（如果缺失）
- ✅ 完整的状态管理
- ✅ 详细的日志记录

---

### 2. ProductImportConsumer

**文件**: [`ProductImportConsumer.php`](../Model/MessageQueue/Consumer/ProductImportConsumer.php)

**功能**: 产品导入

**特点**:
- ✅ 验证 `id` 必填字段
- ✅ 完整的状态管理
- ✅ 详细的日志记录
- ✅ 与 CategoryImportConsumer 保持一致

---

### 3. ProductDetailConsumer

**文件**: [`ProductDetailConsumer.php`](../Model/MessageQueue/Consumer/ProductDetailConsumer.php)

**功能**: 产品详情导入

**特点**:
- ✅ 验证 `product_id` 必填字段
- ✅ 完整的状态管理
- ✅ 详细的日志记录
- ✅ 与 CategoryImportConsumer 保持一致

---

## 🚀 测试建议

### 单元测试

为每个 Consumer 创建单元测试，覆盖以下场景：

1. **成功场景**
   - 正常数据处理
   - 已存在数据（AlreadyExistsException）

2. **验证失败**
   - 缺少必填字段
   - 数据格式错误

3. **可重试异常**
   - LockWaitException
   - DeadlockException
   - ConnectionException

4. **不可重试异常**
   - LocalizedException
   - 通用 Exception

5. **状态验证**
   - 验证 Operation 状态被正确设置
   - 验证 EntityManager save 被调用

---

### 集成测试

创建集成测试验证完整流程：

1. **端到端测试**
   - 发布消息 → Consumer 处理 → 数据库验证

2. **状态持久化**
   - 验证 `queue_message_status` 表状态正确

3. **URL Key 生成**（分类）
   - 验证自动生成的 URL Key 格式
   - 验证唯一性

---

## 📚 参考文档

- [Magento MQ Framework](https://devdocs.magento.com/guides/v2.4/message-queues/message-queue-framework.html)
- [Asynchronous Operations](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/message-queues/asynchronous-operations.html)
- [OperationInterface](../../vendor/magento/module-asynchronous-operations/Api/Data/OperationInterface.php)
- [CONSUMER_FIX_REPORT.md](./CONSUMER_FIX_REPORT.md)
- [MQ_CALL_FLOW_ANALYSIS.md](./MQ_CALL_FLOW_ANALYSIS.md)

---

**文档版本**: 1.0  
**最后更新**: 2026-04-18  
**维护者**: FolixCode Team  
**状态**: ✅ 已完成并实施
