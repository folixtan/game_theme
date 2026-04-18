# Magento 单元测试运行原理详解

## 🎯 核心概念

Magento 单元测试基于 **PHPUnit** 框架，通过 **Mock 对象**隔离被测代码与外部依赖，实现快速、可靠的测试。

---

## 🔄 完整执行流程

### 流程图

```
┌─────────────────────────────────────────┐
│  1. 用户运行测试命令                      │
│  vendor/bin/phpunit TestFile.php        │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  2. PHPUnit 启动                        │
│  - 加载 phpunit.xml 配置                │
│  - 初始化自动加载器                       │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  3. 加载 Bootstrap                      │
│  dev/tests/unit/framework/bootstrap.php │
│  - 设置错误处理                          │
│  - 配置 Magento 环境                     │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  4. 扫描测试文件                         │
│  - 发现 CategoryImportConsumerTest      │
│  - 解析测试类和方法                      │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  5. 对每个测试方法执行:                   │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │ A. 调用 setUp()                   │  │
│  │    - 创建 Mock 对象               │  │
│  │    - 初始化被测类实例              │  │
│  └──────────────┬────────────────────┘  │
│                 │                        │
│                 ▼                        │
│  ┌───────────────────────────────────┐  │
│  │ B. 执行测试方法                    │  │
│  │    - 准备测试数据                  │  │
│  │    - 配置 Mock 行为               │  │
│  │    - 调用被测方法                  │  │
│  └──────────────┬────────────────────┘  │
│                 │                        │
│                 ▼                        │
│  ┌───────────────────────────────────┐  │
│  │ C. 验证断言 (Assertions)          │  │
│  │    - 检查 Mock 调用次数           │  │
│  │    - 验证返回值                   │  │
│  │    - 检查异常抛出                  │  │
│  └──────────────┬────────────────────┘  │
│                 │                        │
│                 ▼                        │
│  ┌───────────────────────────────────┐  │
│  │ D. 调用 tearDown() (如有)         │  │
│  │    - 清理资源                     │  │
│  └───────────────────────────────────┘  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  6. 生成测试报告                         │
│  - 显示通过/失败数量                     │
│  - 显示断言统计                          │
│  - 显示执行时间和内存使用                │
└─────────────────────────────────────────┘
```

---

## 📝 实际案例详解

以 `testProcessValidCategoryData` 为例：

### 第一步：setUp() 初始化

```php
protected function setUp(): void
{
    // 1️⃣ 创建 Mock 对象（不执行真实逻辑）
    $this->categoryImporterMock = $this->getMockBuilder(CategoryImporter::class)
        ->disableOriginalConstructor()  // 不调用构造函数
        ->getMock();                     // 生成 Mock 对象

    $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
        ->getMock();

    $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
        ->getMock();

    $this->operationMock = $this->getMockBuilder(OperationInterface::class)
        ->getMock();

    // 2️⃣ 创建被测类实例，注入 Mock 依赖
    $this->consumer = new CategoryImportConsumer(
        $this->categoryImporterMock,  // 假的 CategoryImporter
        $this->serializerMock,        // 假的 Serializer
        $this->loggerMock             // 假的 Logger
    );
}
```

**关键点**：
- ✅ Mock 对象不会执行真实代码
- ✅ 可以精确控制 Mock 的返回值和行为
- ✅ 测试速度快（无数据库、无网络请求）

---

### 第二步：配置 Mock 行为

```php
public function testProcessValidCategoryData(): void
{
    // 1️⃣ 准备测试数据
    $categoryData = [
        'id' => '123',
        'name' => 'Test Category',
        'description' => 'Test Description',
        'is_active' => 1,
        'position' => 10
    ];
    $serializedData = serialize($categoryData);

    // 2️⃣ 配置 Operation Mock
    $this->operationMock->expects($this->once())  // 期望被调用 1 次
        ->method('getSerializedData')              // 当调用此方法时
        ->willReturn($serializedData);             // 返回序列化数据

    // 3️⃣ 配置 Serializer Mock
    $this->serializerMock->expects($this->once())
        ->method('unserialize')
        ->with($serializedData)                    // 期望传入此参数
        ->willReturn($categoryData);               // 返回反序列化后的数组

    // 4️⃣ 配置 CategoryImporter Mock
    $this->categoryImporterMock->expects($this->once())
        ->method('import')
        ->with($categoryData);                     // 期望被调用且参数正确

    // 5️⃣ 配置 Logger Mock
    $this->loggerMock->expects($this->exactly(2)) // 期望被调用 2 次
        ->method('info');                          // info() 方法
}
```

**Mock 配置说明**：

| 方法 | 作用 | 示例 |
|------|------|------|
| `expects()` | 设定期望调用次数 | `$this->once()`, `$this->exactly(2)` |
| `method()` | 指定要 Mock 的方法名 | `'getSerializedData'` |
| `willReturn()` | 设置返回值 | `willReturn($data)` |
| `with()` | 验证传入参数 | `with($expectedParam)` |
| `willThrowException()` | 模拟抛出异常 | `willThrowException(new \Exception())` |

---

### 第三步：执行被测方法

```php
// 调用被测方法
$this->consumer->process($this->operationMock);
```

**内部执行流程**：

```
CategoryImportConsumer::process($operationMock)
    ↓
$operationMock->getSerializedData()  
    → 返回: "a:5:{s:2:"id";s:3:"123";...}"  (Mock 返回值)
    ↓
$this->serializerMock->unserialize($serializedData)
    → 返回: ['id' => '123', 'name' => 'Test Category', ...]  (Mock 返回值)
    ↓
验证 ID 是否存在 ✓
    ↓
记录日志: "Processing category import"
    → $loggerMock->info() 被调用 (第 1 次) ✓
    ↓
$this->categoryImporterMock->import($categoryData)
    → 什么都不做（只是验证被调用）✓
    ↓
记录日志: "Category import completed successfully"
    → $loggerMock->info() 被调用 (第 2 次) ✓
    ↓
方法执行完成 ✓
```

---

### 第四步：自动验证断言

PHPUnit 在测试方法结束后自动验证：

```php
✅ $operationMock->getSerializedData() 被调用了 1 次
✅ $serializerMock->unserialize() 被调用了 1 次，且参数正确
✅ $categoryImporterMock->import() 被调用了 1 次，且参数正确
✅ $loggerMock->info() 被调用了 2 次
✅ 没有抛出异常
```

**如果任何一项不符合预期，测试失败！**

---

## 🧪 不同类型的测试场景

### 场景 1：正常流程测试

```php
public function testProcessValidCategoryData(): void
{
    // 配置 Mock 返回正常值
    $this->serializerMock->expects($this->once())
        ->method('unserialize')
        ->willReturn(['id' => '123', 'name' => 'Test']);

    $this->categoryImporterMock->expects($this->once())
        ->method('import');

    // 执行测试
    $this->consumer->process($this->operationMock);
    
    // PHPUnit 自动验证所有 expects() 断言
}
```

---

### 场景 2：异常测试

```php
public function testProcessInvalidCategoryDataWithoutId(): void
{
    // 配置 Mock 返回无效数据
    $this->serializerMock->expects($this->once())
        ->method('unserialize')
        ->willReturn(['name' => 'No ID']);  // 缺少 id

    // 期望抛出异常
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Category ID is required');

    // 执行测试（应该抛出异常）
    $this->consumer->process($this->operationMock);
}
```

---

### 场景 3：模拟异常抛出

```php
public function testProcessWithDatabaseLockWaitException(): void
{
    // 准备数据
    $categoryData = ['id' => '999', 'name' => 'Locked'];
    
    // 配置 Mock 抛出异常
    $lockException = new \Magento\Framework\DB\Adapter\LockWaitException(
        __('Lock wait timeout exceeded')
    );

    $this->categoryImporterMock->expects($this->once())
        ->method('import')
        ->willThrowException($lockException);  // ⚠️ 模拟抛出异常

    // 验证日志
    $this->loggerMock->expects($this->once())
        ->method('warning');

    // 期望异常被重新抛出
    $this->expectException(\Magento\Framework\DB\Adapter\LockWaitException::class);

    // 执行测试
    $this->consumer->process($this->operationMock);
}
```

---

## 🔍 Mock 对象工作原理

### Mock vs Real Object

```php
// ❌ 真实对象（会执行真实逻辑，慢且不可控）
$realSerializer = new \Magento\Framework\Serialize\Serializer\Json();
$result = $realSerializer->unserialize($data);  // 真正执行反序列化

// ✅ Mock 对象（只返回预设值，快且可控）
$mockSerializer = $this->getMockBuilder(SerializerInterface::class)
    ->getMock();
$mockSerializer->expects($this->once())
    ->method('unserialize')
    ->willReturn(['id' => '123']);  // 直接返回，不执行任何逻辑
```

### Mock 的优势

1. **速度快**：不访问数据库、文件系统、网络
2. **可预测**：每次返回相同的结果
3. **隔离性好**：只测试当前类，不测试依赖
4. **易于测试异常**：可以轻松模拟各种异常情况

---

## 📊 测试输出解读

### 成功输出

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

.........                                                           9 / 9 (100%)

Time: 00:00.014, Memory: 10.00 MB

OK (9 tests, 45 assertions)
```

**解读**：
- `.........` - 9 个点表示 9 个测试全部通过
- `9 / 9 (100%)` - 9 个测试，100% 通过率
- `9 tests` - 执行了 9 个测试方法
- `45 assertions` - 验证了 45 个断言（expects + expectException 等）

---

### 失败输出

```
There was 1 failure:

1) FolixCode\ProductSync\Test\Unit\...\CategoryImportConsumerTest::testProcessInvalidData
Expectation failed for method name is "import" when invoked 1 time(s).
Method was expected to be called 1 times, actually called 0 times.
```

**解读**：
- 测试失败原因：期望 `import()` 被调用 1 次，但实际调用了 0 次
- 可能原因：被测代码在调用 `import()` 之前就抛出了异常

---

## 🛠️ 常用 PHPUnit 命令

### 基本命令

```bash
# 运行单个测试文件
vendor/bin/phpunit app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 运行单个测试方法
vendor/bin/phpunit --filter testProcessValidCategoryData app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 显示详细输出
vendor/bin/phpunit --testdox --colors=always app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 显示警告和通知
vendor/bin/phpunit --display-warnings --display-notices app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php
```

### 调试技巧

```bash
# 只运行失败的测试
vendor/bin/phpunit --order-by=defects app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 停止在第一个失败处
vendor/bin/phpunit --stop-on-failure app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 生成代码覆盖率报告
vendor/bin/phpunit --coverage-html coverage/ app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php
```

---

## 💡 最佳实践

### 1. 每个测试独立

```php
// ✅ 好：每个测试都有自己的 setUp 和数据准备
public function testA() { /* ... */ }
public function testB() { /* ... */ }

// ❌ 坏：测试之间相互依赖
public function testA() { /* 设置状态 */ }
public function testB() { /* 依赖 testA 的状态 */ }
```

### 2. 测试命名清晰

```php
// ✅ 好：清楚表达测试意图
public function testProcessValidCategoryData()
public function testProcessInvalidCategoryDataWithoutId()
public function testProcessWhenCategoryAlreadyExists()

// ❌ 坏：不清楚测试什么
public function test1()
public function testProcess()
```

### 3. Mock 精确

```php
// ✅ 好：明确指定期望
$this->mock->expects($this->once())
    ->method('import')
    ->with($expectedData);

// ❌ 坏：过于宽松
$this->mock->method('import');  // 不验证调用次数和参数
```

### 4. 一个测试一个场景

```php
// ✅ 好：每个测试只验证一个场景
public function testValidData() { /* ... */ }
public function testInvalidData() { /* ... */ }
public function testException() { /* ... */ }

// ❌ 坏：一个测试混合多个场景
public function testAllScenarios() {
    // 测试有效数据
    // 测试无效数据
    // 测试异常
}
```

---

## 🎓 总结

### 单元测试的核心思想

1. **隔离**：只测试当前类，Mock 所有依赖
2. **快速**：不访问外部资源（数据库、网络、文件）
3. **可重复**：每次运行结果一致
4. **自动化**：无需人工干预

### 你的测试做了什么

```
CategoryImportConsumerTest
    ├─ setUp()
    │   └─ 创建 4 个 Mock 对象 + 1 个被测实例
    │
    ├─ testProcessValidCategoryData
    │   └─ 验证正常数据处理流程
    │
    ├─ testProcessInvalidCategoryDataWithoutId
    │   └─ 验证无效数据被拒绝
    │
    ├─ testProcessEmptyCategoryData
    │   └─ 验证空数据被拒绝
    │
    ├─ testProcessWithSuccessfulImportAndLogging
    │   └─ 验证日志记录
    │
    ├─ testProcessWhenCategoryAlreadyExists
    │   └─ 验证 AlreadyExistsException 被视为成功
    │
    ├─ testProcessWithDatabaseLockWaitException
    │   └─ 验证锁等待异常可重试
    │
    ├─ testProcessWithDeadlockException
    │   └─ 验证死锁异常可重试
    │
    ├─ testProcessWithGenericException
    │   └─ 验证通用异常不可重试
    │
    └─ testProcessWithCompleteCategoryData
        └─ 验证完整字段处理
```

### 关键要点

- ✅ **Mock 对象**是单元测试的核心，让你可以控制依赖的行为
- ✅ **expects()** 既是配置也是断言，PHPUnit 会自动验证
- ✅ **setUp()** 在每个测试前执行，确保测试独立性
- ✅ **异常测试**使用 `expectException()` 而不是 try-catch
- ✅ **测试命名**要清晰表达测试意图

---

## 🔗 相关资源

- [PHPUnit 官方文档](https://phpunit.de/documentation.html)
- [Magento 单元测试指南](https://developer.adobe.com/commerce/php/development/components/unit-testing/)
- [你的测试文件](./Model/MessageQueue/Consumer/CategoryImportConsumerTest.php)
- [测试报告](./TEST_REPORT.md)
