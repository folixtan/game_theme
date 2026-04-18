# ProductSync 单元测试

## 目录结构

```
Test/Unit/
├── Model/
│   └── MessageQueue/
│       └── Consumer/
│           └── CategoryImportConsumerTest.php
└── phpunit.xml
```

## 运行测试

### 方法 1: 使用 PHPUnit 直接运行

```bash
cd /var/www/html/game/game/app/code/FolixCode/ProductSync/Test/Unit
../../../../../../../vendor/bin/phpunit --configuration phpunit.xml
```

### 方法 2: 运行单个测试类

```bash
cd /var/www/html/game/game
vendor/bin/phpunit app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php
```

### 方法 3: 使用 Magento 标准测试命令

```bash
cd /var/www/html/game/game
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php
```

## 测试覆盖

### CategoryImportConsumerTest

测试 `CategoryImportConsumer` 类的消息队列消费功能：

1. **testProcessValidCategoryData** - 测试处理有效的分类数据
2. **testProcessInvalidCategoryDataWithoutId** - 测试缺少 ID 的无效数据
3. **testProcessEmptyCategoryData** - 测试空数据处理
4. **testProcessWithSuccessfulImportAndLogging** - 测试成功导入并记录日志
5. **testProcessWhenCategoryAlreadyExists** - 测试分类已存在时的处理（AlreadyExistsException）
6. **testProcessWithDatabaseLockWaitException** - 测试数据库锁等待异常
7. **testProcessWithDeadlockException** - 测试死锁异常
8. **testProcessWithGenericException** - 测试通用异常处理
9. **testProcessWithCompleteCategoryData** - 测试完整字段数据的处理

## Mock 对象说明

- `CategoryImporter` - 模拟分类导入服务
- `SerializerInterface` - 模拟序列化器
- `LoggerInterface` - 模拟日志记录器
- `OperationInterface` - 模拟消息队列操作对象

## 预期结果

所有测试应该通过，验证：
- ✅ 有效数据能正常处理
- ✅ 无效数据抛出适当异常
- ✅ 异常情况正确处理和记录日志
- ✅ AlreadyExistsException 被视为成功（不抛出）
- ✅ 数据库锁异常会重新抛出（支持重试）
- ✅ 其他异常会记录错误并重新抛出
