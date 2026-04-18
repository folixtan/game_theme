# 消费者未运行问题排查指南

## 🔴 问题现象

执行 `bin/magento queue:consumers:start` 后，消费者没有处理消息。

---

## ✅ 根本原因

**`queue_consumer.xml` 中缺少 `handler` 属性**

### ❌ 错误配置
```xml
<consumer name="folixcode.category.import.consumer" 
          queue="folixcode.category.import.queue"
          maxMessages="100" />
<!-- ❌ 缺少 handler 属性！ -->
```

### ✅ 正确配置
```xml
<consumer name="folixcode.category.import.consumer" 
          queue="folixcode.category.import.queue"
          handler="FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer::process"
          maxMessages="100"
          maxIdleTime="60"
          sleep="1" />
```

---

## 📋 完整的配置检查清单

### 1️⃣ queue_consumer.xml - Consumer 注册

**文件**: `app/code/FolixCode/ProductSync/etc/queue_consumer.xml`

**必需属性**：
- ✅ `name`: Consumer 名称（用于 CLI 启动）
- ✅ `queue`: 队列名称
- ✅ `handler`: **类名::方法名**（最关键！）

**可选属性**：
- `maxMessages`: 每次处理的最大消息数（默认 10000）
- `maxIdleTime`: 最大空闲时间（秒）
- `sleep`: 无消息时的休眠时间（秒）

---

### 2️⃣ communication.xml - Topic 定义

**文件**: `app/code/FolixCode/ProductSync/etc/communication.xml`

**必需属性**：
- ✅ `name`: Topic 名称
- ✅ `request`: 消息类型（`OperationInterface`）
- ✅ `<handler>`: 指向 Consumer 类和方法

```xml
<topic name="folixcode.category.import" 
       is_synchronous="false" 
       request="Magento\AsynchronousOperations\Api\Data\OperationInterface">
    <handler name="folixcode.category.import.handler" 
             type="FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer" 
             method="process" />
</topic>
```

---

### 3️⃣ di.xml - 依赖注入配置

**文件**: `app/code/FolixCode/ProductSync/etc/di.xml`

确保所有 Service 的依赖都正确配置：

```xml
<!-- CategoryService 配置 -->
<type name="FolixCode\ProductSync\Service\CategoryService">
    <arguments>
        <argument name="categoryColFactory" xsi:type="object">Magento\Catalog\Model\ResourceModel\Category\CollectionFactory</argument>
        <argument name="categoryFactory" xsi:type="object">Magento\Catalog\Model\CategoryFactory</argument>
    </arguments>
</type>

<!-- CategoryImporter 配置 -->
<type name="FolixCode\ProductSync\Service\CategoryImporter">
    <arguments>
        <argument name="categoryService" xsi:type="object">FolixCode\ProductSync\Service\CategoryService</argument>
    </arguments>
</type>
```

---

## 🔍 排查步骤

### Step 1: 验证配置文件语法

```bash
cd /var/www/html/game/game

# 检查 XML 语法
xmllint --noout app/code/FolixCode/ProductSync/etc/queue_consumer.xml
xmllint --noout app/code/FolixCode/ProductSync/etc/communication.xml
xmllint --noout app/code/FolixCode/ProductSync/etc/di.xml
```

---

### Step 2: 清理缓存并重新编译

```bash
# 清理缓存
bin/magento cache:clean

# 删除生成的代码
rm -rf generated/code/FolixCode

# 重新编译 DI
bin/magento setup:di:compile
```

---

### Step 3: 列出已注册的消费者

```bash
bin/magento queue:consumers:list
```

**预期输出**：
```
folixcode.product.import.consumer
folixcode.category.import.consumer
folixcode.product.detail.consumer
```

**如果没有看到你的消费者**：
- ❌ 检查 `queue_consumer.xml` 是否在正确的模块目录
- ❌ 检查模块是否已启用：`bin/magento module:status FolixCode_ProductSync`
- ❌ 检查 `module.xml` 中的序列依赖

---

### Step 4: 测试单个消费者

```bash
# 单线程模式启动（便于调试）
bin/magento queue:consumers:start folixcode.category.import.consumer --single-thread

# 或者后台运行
bin/magento queue:consumers:start folixcode.category.import.consumer &
```

**观察输出**：
- ✅ 如果看到 "Waiting for messages..." → 配置正确，等待消息
- ❌ 如果报错 → 查看错误信息
- ❌ 如果立即退出 → 检查 handler 配置

---

### Step 5: 触发消息发布

```bash
# 通过 CLI 命令触发同步
bin/magento folixcode:sync --type=categories --limit=1
```

**检查日志**：
```bash
# Publisher 日志
tail -f var/log/mq_publisher.log

# Consumer 日志
tail -f var/log/productsync.log

# 系统日志
tail -f var/log/system.log
```

---

### Step 6: 检查数据库中的消息状态

```sql
-- 查看队列消息
SELECT 
    id,
    topic_name,
    status,
    created_at,
    updated_at
FROM queue_message
ORDER BY created_at DESC
LIMIT 10;

-- 查看消息状态详情
SELECT 
    operation_id,
    status,
    error_code,
    result_message,
    updated_at
FROM queue_message_status
ORDER BY updated_at DESC
LIMIT 10;
```

**状态说明**：
- `1` = OPEN（待处理）
- `2` = COMPLETE（已完成）
- `5` = RETRIABLY_FAILED（可重试失败）
- `6` = NOT_RETRIABLY_FAILED（不可重试失败）

---

## 🐛 常见错误及解决方案

### 错误 1: Handler 类找不到

**错误信息**：
```
Class "FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer" does not exist
```

**原因**：
- 类文件路径错误
- 命名空间错误
- 文件权限问题

**解决**：
```bash
# 检查文件是否存在
ls -la app/code/FolixCode/ProductSync/Model/MessageQueue/Consumer/CategoryImportConsumer.php

# 检查命名空间
head -5 app/code/FolixCode/ProductSync/Model/MessageQueue/Consumer/CategoryImportConsumer.php

# 修复权限
chmod 644 app/code/FolixCode/ProductSync/Model/MessageQueue/Consumer/*.php
```

---

### 错误 2: Handler 方法不存在

**错误信息**：
```
Method "process" does not exist in class "CategoryImportConsumer"
```

**原因**：
- 方法名拼写错误
- 方法可见性不是 `public`

**解决**：
```php
// ✅ 正确
public function process(OperationInterface $operation): void { ... }

// ❌ 错误
private function process(...) { ... }
protected function process(...) { ... }
```

---

### 错误 3: 依赖注入失败

**错误信息**：
```
Typed property ... must not be accessed before initialization
```

**原因**：
- `di.xml` 配置不完整
- 构造函数参数缺失

**解决**：
检查 `di.xml` 中是否为所有必需参数提供了配置。

---

### 错误 4: Topic 未注册

**错误信息**：
```
Topic "folixcode.category.import" is not configured
```

**原因**：
- `communication.xml` 配置错误
- 模块未启用

**解决**：
```bash
# 检查模块状态
bin/magento module:status FolixCode_ProductSync

# 如果未启用，启用模块
bin/magento module:enable FolixCode_ProductSync
bin/magento setup:upgrade
```

---

## 📊 配置对比表

| 配置文件 | 作用 | 关键属性 | 常见错误 |
|---------|------|---------|---------|
| **queue_consumer.xml** | 注册 Consumer | `name`, `queue`, **`handler`** | ❌ 缺少 handler |
| **communication.xml** | 定义 Topic | `name`, `request`, `<handler>` | ❌ request 类型错误 |
| **di.xml** | 依赖注入 | `<argument>` | ❌ 参数缺失 |
| **queue_publisher.xml** | 注册 Publisher | `topic`, `connection` | ⚠️ 可选 |
| **queue_topology.xml** | 拓扑绑定 | `exchange`, `binding` | ⚠️ 可选（DB 模式） |

---

## 🚀 快速修复命令

```bash
cd /var/www/html/game/game

# 1. 清理所有缓存和生成文件
bin/magento cache:clean
rm -rf generated/code/FolixCode
rm -rf var/cache/*
rm -rf var/page_cache/*

# 2. 重新编译
bin/magento setup:di:compile

# 3. 部署静态资源（如果需要）
bin/magento setup:static-content:deploy -f

# 4. 测试消费者
bin/magento queue:consumers:start folixcode.category.import.consumer --single-thread

# 5. 在另一个终端触发消息
bin/magento folixcode:sync --type=categories --limit=1
```

---

## 💡 最佳实践

### 1. 开发时启用详细日志

**文件**: `app/code/FolixCode/ProductSync/etc/di.xml`

```xml
<virtualType name="FolixCodeProductSyncPublisherLogger" type="Magento\Framework\Logger\Monolog">
    <arguments>
        <argument name="name" xsi:type="string">mq_publisher</argument>
        <argument name="handlers" xsi:type="array">
            <item name="system" xsi:type="object">FolixCodeProductSyncPublisherLogHandler</item>
        </argument>
        <argument name="level" xsi:type="number">100</argument> <!-- DEBUG -->
    </arguments>
</virtualType>
```

---

### 2. 使用 Cron 自动启动消费者

**文件**: `crontab -e`

```bash
# 每 5 分钟检查并重启消费者
*/5 * * * * /usr/bin/php /var/www/html/game/game/bin/magento queue:consumers:start folixcode.category.import.consumer --max-messages=1000 >> /var/log/mq_consumer.log 2>&1
```

---

### 3. 监控消费者状态

**脚本**: `check_consumers.sh`

```bash
#!/bin/bash

CONSUMERS=(
    "folixcode.product.import.consumer"
    "folixcode.category.import.consumer"
    "folixcode.product.detail.consumer"
)

for consumer in "${CONSUMERS[@]}"; do
    if pgrep -f "queue:consumers:start.*$consumer" > /dev/null; then
        echo "✅ $consumer is running"
    else
        echo "❌ $consumer is NOT running"
        # 自动重启
        nohup bin/magento queue:consumers:start $consumer &
        echo "   → Restarted $consumer"
    fi
done
```

---

## 📝 总结

**消费者未运行的最常见原因**：
1. ❌ **缺少 `handler` 属性**（本次问题）
2. ❌ Handler 类或方法不存在
3. ❌ 依赖注入配置不完整
4. ❌ Topic 未正确注册
5. ❌ 模块未启用

**排查顺序**：
1. 检查 `queue_consumer.xml` 的 `handler` 属性
2. 清理缓存并重新编译
3. 使用 `queue:consumers:list` 验证注册
4. 单线程模式测试
5. 检查日志和数据库

---

**修复日期**: 2026-04-18  
**状态**: ✅ 已修复  
**影响范围**: `queue_consumer.xml`  
**下一步**: 清理缓存并测试
