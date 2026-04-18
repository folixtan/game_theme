# CategoryImportConsumer 单元测试 - 使用说明

## 📋 概述

本测试套件用于测试 `FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer` 类的消息队列消费功能。

## 🎯 测试目标

验证类别导入消费者能够：
1. ✅ 正确处理有效的分类数据
2. ✅ 正确拒绝无效的分类数据
3. ✅ 正确处理各种异常情况（已存在、数据库锁、死锁等）
4. ✅ 正确记录日志信息
5. ✅ 按照设计策略处理异常（可重试 vs 不可重试）

## 🚀 快速开始

### 方法 1: 使用测试脚本（推荐）

```bash
cd /var/www/html/game/game/app/code/FolixCode/ProductSync/Test/Unit
./run_tests.sh
```

### 方法 2: 直接使用 PHPUnit

```bash
cd /var/www/html/game/game
vendor/bin/phpunit app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php --testdox
```

### 方法 3: 查看详细输出

```bash
cd /var/www/html/game/game
vendor/bin/phpunit app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php --colors=always --verbose
```

## 📊 测试结果示例

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Category Import Consumer (FolixCode\ProductSync\Test\Unit\Model\MessageQueue\Consumer\CategoryImportConsumer)
 ✔ Process valid category data
 ✔ Process invalid category data without id
 ✔ Process empty category data
 ✔ Process with successful import and logging
 ✔ Process when category already exists
 ✔ Process with database lock wait exception
 ✔ Process with deadlock exception
 ✔ Process with generic exception
 ✔ Process with complete category data

OK (9 tests, 45 assertions)
```

## 🧪 测试用例说明

| 测试方法 | 描述 | 预期结果 |
|---------|------|---------|
| `testProcessValidCategoryData` | 处理有效的分类数据 | 成功导入，记录日志 |
| `testProcessInvalidCategoryDataWithoutId` | 处理缺少 ID 的数据 | 抛出 InvalidArgumentException |
| `testProcessEmptyCategoryData` | 处理空数组数据 | 抛出 InvalidArgumentException |
| `testProcessWithSuccessfulImportAndLogging` | 验证成功导入和日志 | 正确调用服务并记录日志 |
| `testProcessWhenCategoryAlreadyExists` | 分类已存在的情况 | 不抛出异常，记录 info 日志 |
| `testProcessWithDatabaseLockWaitException` | 数据库锁等待 | 记录 warning，重新抛出异常（可重试） |
| `testProcessWithDeadlockException` | 数据库死锁 | 记录 warning，重新抛出异常（可重试） |
| `testProcessWithGenericException` | 通用异常 | 记录 error，重新抛出异常 |
| `testProcessWithCompleteCategoryData` | 处理完整字段数据 | 成功处理所有字段 |

## 🔍 测试覆盖的场景

### 正常流程
- ✅ 基本分类数据处理
- ✅ 包含所有字段的完整数据处理
- ✅ 带父级路径的分类数据处理

### 异常处理
- ✅ 数据验证失败（缺少 ID）
- ✅ 空数据处理
- ✅ 分类已存在（AlreadyExistsException - 视为成功）
- ✅ 数据库锁等待（LockWaitException - 可重试）
- ✅ 数据库死锁（DeadlockException - 可重试）
- ✅ 通用异常（Exception - 不可重试）

### 日志验证
- ✅ Info 日志：处理开始、完成、已存在
- ✅ Warning 日志：无效数据、数据库锁
- ✅ Error 日志：处理失败

## 📁 文件结构

```
Test/Unit/
├── Model/
│   └── MessageQueue/
│       └── Consumer/
│           └── CategoryImportConsumerTest.php    # 测试文件
├── phpunit.xml                                    # PHPUnit 配置
├── run_tests.sh                                   # 测试运行脚本
├── README.md                                      # 测试说明
└── TEST_REPORT.md                                 # 测试报告
```

## 🛠️ Mock 对象

测试使用了以下 Mock 对象来隔离被测类：

- **CategoryImporter**: 模拟分类导入服务
- **SerializerInterface**: 模拟数据序列化器
- **LoggerInterface**: 模拟日志记录器
- **OperationInterface**: 模拟消息队列操作对象

## ⚠️ 注意事项

1. **依赖关系**: 确保 Magento 框架已正确安装，PHPUnit 可用
2. **自动加载**: 测试依赖于 Magento 的自动加载机制
3. **命名空间**: 测试类命名空间必须与目录结构匹配
4. **Mock 行为**: 所有外部依赖都被 Mock，测试不会真正访问数据库或文件系统

## 🐛 已知问题修复

在编写测试过程中发现并修复了一个 bug：

**问题**: `$startTime` 变量在数据验证失败时未初始化就被使用

**修复**: 将变量初始化移到 try 块之前

详见 [TEST_REPORT.md](./TEST_REPORT.md)

## 📈 测试统计

- **测试总数**: 9
- **断言总数**: 45
- **通过率**: 100%
- **平均执行时间**: ~14-32ms
- **内存使用**: ~10MB

## 🔗 相关文档

- [README.md](./README.md) - 测试详细说明
- [TEST_REPORT.md](./TEST_REPORT.md) - 详细测试报告
- [CategoryImportConsumer.php](../../Model/MessageQueue/Consumer/CategoryImportConsumer.php) - 被测源代码

## 💡 提示

- 使用 `--testdox` 参数可以获得更易读的输出
- 使用 `--colors=always` 可以保持彩色输出
- 使用 `--verbose` 可以查看更详细的测试信息
- 可以单独运行某个测试方法：`--filter testProcessValidCategoryData`

---

**最后更新**: 2026-04-18  
**测试状态**: ✅ 全部通过
