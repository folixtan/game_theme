# Magento 消息队列系统 - 核心要点总结

## 🎯 一句话回答你的问题

**Magento 通过以下流程调用 Consumer 的 `process()` 方法：**

```
Cron/CLI → ConsumerRunner::__call() 
         → ConsumerFactory::get() 
         → Framework Consumer::process() 
         → dispatchMessage() 
         → call_user_func([YourConsumer, 'process'], $operation)
```

---

## 🔑 关键发现

### 1. 两层 Consumer 架构

```
┌─────────────────────────────────────┐
│  Framework Consumer                  │
│  (Magento\Framework\MessageQueue\   │
│   Consumer)                          │
│                                      │
│  职责：                               │
│  - 从队列取消息                       │
│  - 解码消息                           │
│  - 调用配置的 Handler                 │
│  - 确认/拒绝消息                      │
└──────────┬──────────────────────────┘
           │ call_user_func()
           ↓
┌─────────────────────────────────────┐
│  Your Consumer                       │
│  (CategoryImportConsumer)            │
│                                      │
│  职责：                               │
│  - 业务逻辑处理                       │
│  - 更新 Operation 状态                │
│  - 保存到数据库                       │
└─────────────────────────────────────┘
```

**重要**：
- `ConsumerFactory::get()` 返回的是 **Framework Consumer**
- Framework Consumer 通过 `call_user_func()` 调用**你的 Consumer**
- 你的 Consumer 只是一个普通的 PHP 类，不是框架的一部分

---

### 2. Handler 配置来源

Framework Consumer 如何知道要调用哪个类和方法？

**答案**：从两个配置文件读取

#### A. queue_consumer.xml（必需）

```xml
<consumer name="folixcode.category.import.consumer" 
          handler="FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer::process" />
```

#### B. communication.xml（必需！我们之前缺失）

```xml
<topic name="folixcode.category.import">
    <handler type="FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer" 
             method="process" />
</topic>
```

**优先级**：
1. 如果 `queue_consumer.xml` 配置了 `handler`，优先使用
2. 否则从 `communication.xml` 中查找对应 topic 的 handler

---

### 3. 数据流转路径

```
Publisher 发布
    ↓
queue_message 表 (status='new')
    ↓
Framework Consumer dequeue
    ↓
解码消息体 → OperationInterface 对象
    ↓
call_user_func([YourConsumer, 'process'], $operation)
    ↓
YourConsumer::process($operation)
    ↓
更新 Operation 状态
    ↓
entityManager->save($operation)
    ↓
queue_message_status 表更新
    ↓
Framework Consumer acknowledge
    ↓
queue_message 表 (status='complete')
```

---

## 📋 完整配置清单

### ✅ 已完成的配置

| 文件 | 路径 | 状态 |
|------|------|------|
| module.xml | `etc/module.xml` | ✅ |
| di.xml | `etc/di.xml` | ✅ |
| queue_consumer.xml | `etc/queue_consumer.xml` | ✅ |
| queue_publisher.xml | `etc/queue_publisher.xml` | ✅ |
| crontab.xml | `etc/crontab.xml` | ✅ |
| **communication.xml** | **`etc/communication.xml`** | **✅ 刚刚创建** |

### 📝 Consumer 实现检查清单

- [x] 实现 `process(OperationInterface $operation)` 方法
- [x] 注入 `EntityManager` 依赖
- [x] 捕获异常并设置状态
- [x] 调用 `$operation->setStatus()`
- [x] 调用 `$operation->setErrorCode()`
- [x] 调用 `$operation->setResultMessage()`
- [x] 调用 `$this->entityManager->save($operation)`

---

## 🚀 验证步骤

### 1. 清理缓存并编译

```bash
cd /var/www/html/game/game

# 清理缓存
bin/magento cache:clean

# 重新编译 DI
bin/magento setup:di:compile

# 部署静态资源（可选）
bin/magento setup:static-content:deploy -f
```

### 2. 测试单个 Consumer

```bash
# 单线程模式（实时观察日志）
bin/magento queue:consumers:start folixcode.category.import.consumer --single-thread

# 另一个终端发送测试消息
# （通过你的 Publisher 或手动插入数据库）
```

### 3. 查看日志

```bash
# 实时查看日志
tail -f var/log/system.log | grep -i "category.*import"

# 查看错误日志
tail -f var/log/exception.log
```

### 4. 检查数据库状态

```sql
-- 查看最近的消息
SELECT 
    id,
    topic_name,
    status,
    LEFT(body, 100) as body_preview,
    created_at,
    updated_at
FROM queue_message 
WHERE topic_name LIKE '%category%'
ORDER BY created_at DESC 
LIMIT 10;

-- 查看消息状态
SELECT 
    operation_id,
    status,
    error_code,
    LEFT(result_message, 100) as message,
    updated_at
FROM queue_message_status 
ORDER BY updated_at DESC 
LIMIT 10;
```

**预期结果**：
- 成功的消息：`status = 2 (COMPLETE)`, `result_message = NULL`
- 失败的消息：`status = 6 (REJECTED)`, `result_message` 包含错误详情

---

## 💡 常见问题排查

### Q1: Consumer 没有被调用

**检查点**：
1. ✅ `communication.xml` 是否存在且配置正确
2. ✅ `queue_consumer.xml` 中的 `handler` 是否正确
3. ✅ Consumer 类是否存在且可自动加载
4. ✅ 运行 `bin/magento setup:di:compile`

**调试命令**：
```bash
# 查看已注册的 Consumer
bin/magento queue:consumers:list

# 应该看到：
# folixcode.category.import.consumer
# folixcode.product.import.consumer
# folixcode.product.detail.consumer
```

---

### Q2: 消息卡在 "in_progress" 状态

**原因**：
- Consumer 抛出异常但没有正确处理
- 没有调用 `entityManager->save($operation)`

**解决**：
确保 Consumer 中有完整的异常处理和状态保存：

```php
catch (\Exception $e) {
    $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
    $errorCode = $e->getCode();
    $message = $e->getMessage();
}

// ✅ 必须执行
$operation->setStatus($status)
    ->setErrorCode($errorCode)
    ->setResultMessage($message);

$this->entityManager->save($operation);
```

---

### Q3: 找不到 Handler

**错误信息**：
```
Specified topic "folixcode.category.import" has no handlers
```

**原因**：
- `communication.xml` 缺失或配置错误
- Topic 名称不匹配

**解决**：
1. 检查 `communication.xml` 中的 `topic name` 是否与 Publisher 使用的 topic 一致
2. 检查 `handler type` 是否指向正确的类
3. 运行 `bin/magento setup:di:compile`

---

## 📊 性能优化建议

### 1. 批量处理

```xml
<!-- queue_consumer.xml -->
<consumer name="folixcode.category.import.consumer" 
          queue="folixcode.category.import.queue" 
          handler="..."
          maxMessages="100"      ← 每次处理 100 条消息
          maxIdleTime="60"       ← 最大空闲 60 秒
          sleep="1" />           ← 轮询间隔 1 秒
```

### 2. 多进程消费

```bash
# 启动多个 Consumer 实例
bin/magento queue:consumers:start folixcode.category.import.consumer &
bin/magento queue:consumers:start folixcode.category.import.consumer &
bin/magento queue:consumers:start folixcode.category.import.consumer &
```

### 3. 使用 RabbitMQ（生产环境推荐）

```xml
<!-- env.php -->
'queue' => [
    'amqp' => [
        'host' => 'rabbitmq.example.com',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'virtualhost' => '/'
    ]
]
```

---

## 🎓 学习要点总结

### 核心概念

1. **Framework Consumer** vs **Your Consumer**
   - Framework Consumer：框架提供的通用处理器
   - Your Consumer：你的业务逻辑实现

2. **Handler 配置**
   - `queue_consumer.xml`：注册 Consumer
   - `communication.xml`：定义 Topic 和 Handler 映射

3. **状态管理**
   - 必须更新 Operation 状态
   - 必须调用 `entityManager->save()`
   - 区分可重试和不可重试异常

4. **消息生命周期**
   ```
   NEW → IN_PROGRESS → COMPLETE/REJECTED
   ```

---

## 📚 参考资源

- [Magento MQ Framework 官方文档](https://devdocs.magento.com/guides/v2.4/message-queues/message-queue-framework.html)
- [Asynchronous Operations](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/message-queues/asynchronous-operations.html)
- [Consumer Configuration](https://devdocs.magento.com/guides/v2.4/config-guide/mq/manage-message-queues.html)

---

**最后更新**: 2026-04-18  
**状态**: ✅ 分析完成，communication.xml 已创建  
**下一步**: 清理缓存、编译、测试
