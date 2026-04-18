# 单元测试快速参考卡片

## 🚀 运行测试

```bash
# 运行所有测试
vendor/bin/phpunit app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 运行单个测试方法
vendor/bin/phpunit --filter testProcessValidCategoryData app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php

# 友好格式输出
vendor/bin/phpunit --testdox --colors=always app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php
```

---

## 📋 测试执行步骤

```
1. setUp()          → 创建 Mock 对象
2. 准备测试数据      → 定义输入数据
3. 配置 Mock        → 设置期望行为和返回值
4. 执行被测方法      → 调用 consumer->process()
5. 自动验证断言      → PHPUnit 检查所有 expects()
6. tearDown()       → 清理资源（如有）
```

---

## 🎯 Mock 配置速查

### 基本用法

```php
// 期望方法被调用 1 次，返回指定值
$mock->expects($this->once())
    ->method('methodName')
    ->willReturn($value);

// 期望方法被调用 N 次
$mock->expects($this->exactly(3))
    ->method('methodName');

// 期望方法不被调用
$mock->expects($this->never())
    ->method('methodName');

// 验证传入参数
$mock->expects($this->once())
    ->method('methodName')
    ->with($expectedParam1, $expectedParam2);

// 模拟抛出异常
$mock->expects($this->once())
    ->method('methodName')
    ->willThrowException(new \Exception('Error'));
```

### 常用次数断言

| 方法 | 含义 |
|------|------|
| `$this->once()` | 恰好 1 次 |
| `$this->never()` | 0 次 |
| `$this->any()` | 任意次数 |
| `$this->exactly(3)` | 恰好 3 次 |
| `$this->atLeastOnce()` | 至少 1 次 |

---

## ✅ 断言速查

### 异常断言

```php
// 期望抛出特定异常
$this->expectException(\InvalidArgumentException::class);
$this->expectExceptionMessage('Category ID is required');

// 执行会抛出异常的代码
$this->consumer->process($operationMock);
```

### 值断言（较少用，因为 Mock 已经做了大部分验证）

```php
$this->assertEquals($expected, $actual);
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertNotEmpty($array);
```

---

## 🔧 Mock 创建

```php
// Mock 类
$mock = $this->getMockBuilder(ClassName::class)
    ->disableOriginalConstructor()  // 不调用构造函数
    ->getMock();

// Mock 接口
$mock = $this->getMockBuilder(InterfaceName::class)
    ->getMock();
```

---

## 📊 测试结果解读

### 成功

```
OK (9 tests, 45 assertions)
```
- ✅ 9 个测试全部通过
- ✅ 验证了 45 个断言

### 失败

```
There was 1 failure:
Method was expected to be called 1 times, actually called 0 times.
```
- ❌ 期望方法被调用 1 次，实际 0 次
- 💡 检查代码逻辑是否正确调用了该方法

### 有风险

```
This test did not perform any assertions
```
- ⚠️ 测试没有验证任何东西
- 💡 添加 expects() 或 expectException()

---

## 💡 常见场景模板

### 场景 1：正常流程

```php
public function testSuccess(): void
{
    // 1. 准备数据
    $data = ['id' => '123'];
    
    // 2. 配置 Mock
    $this->mock->expects($this->once())
        ->method('process')
        ->with($data);
    
    // 3. 执行
    $this->service->doSomething($data);
    
    // 4. 自动验证（无需额外断言）
}
```

### 场景 2：异常处理

```php
public function testException(): void
{
    // 1. 配置 Mock 抛出异常
    $this->mock->expects($this->once())
        ->method('process')
        ->willThrowException(new \Exception('Error'));
    
    // 2. 期望异常
    $this->expectException(\Exception::class);
    
    // 3. 执行
    $this->service->doSomething();
}
```

### 场景 3：返回值验证

```php
public function testReturnValue(): void
{
    // 1. 配置 Mock 返回值
    $this->mock->expects($this->once())
        ->method('getData')
        ->willReturn(['id' => '123']);
    
    // 2. 执行
    $result = $this->service->fetchData();
    
    // 3. 验证返回值
    $this->assertEquals(['id' => '123'], $result);
}
```

### 场景 4：日志验证

```php
public function testLogging(): void
{
    // 1. 配置 Logger Mock
    $this->loggerMock->expects($this->once())
        ->method('info')
        ->with('Processing started');
    
    // 2. 执行
    $this->service->process();
    
    // 3. 自动验证日志被记录
}
```

---

## 🐛 调试技巧

### 1. 查看哪些 Mock 被调用

```php
// 在测试中添加临时调试
var_dump($this->mock->methodInvocations());
```

### 2. 只运行失败的测试

```bash
vendor/bin/phpunit --order-by=defects TestFile.php
```

### 3. 停止在第一个失败

```bash
vendor/bin/phpunit --stop-on-failure TestFile.php
```

### 4. 查看详细堆栈

```bash
vendor/bin/phpunit --verbose TestFile.php
```

---

## ⚠️ 常见错误

### 错误 1：忘记配置 Mock

```php
// ❌ 错误：没有配置 Mock 行为
$mock = $this->getMockBuilder(ClassName::class)->getMock();
$this->service->doSomething();  // Mock 返回 null，可能导致错误

// ✅ 正确：配置返回值
$mock->expects($this->once())
    ->method('getData')
    ->willReturn($data);
```

### 错误 2：期望次数不匹配

```php
// ❌ 错误：期望 1 次，实际调用了 2 次
$mock->expects($this->once())
    ->method('log');

// ✅ 正确：根据实际调用次数设置
$mock->expects($this->exactly(2))
    ->method('log');
```

### 错误 3：参数不匹配

```php
// ❌ 错误：期望的参数与实际不符
$mock->expects($this->once())
    ->method('process')
    ->with('wrong_param');

// ✅ 正确：使用正确的参数
$mock->expects($this->once())
    ->method('process')
    ->with('correct_param');
```

### 错误 4：变量未初始化

```php
// ❌ 错误：在 catch 块中使用 try 内初始化的变量
try {
    $startTime = microtime(true);
    throw new \Exception();
} catch (\Exception $e) {
    $duration = microtime(true) - $startTime;  // ⚠️ 可能未定义
}

// ✅ 正确：在 try 之前初始化
$startTime = microtime(true);
try {
    throw new \Exception();
} catch (\Exception $e) {
    $duration = microtime(true) - $startTime;  // ✓ 安全
}
```

---

## 📚 学习路径

1. **阅读**: [HOW_TESTS_WORK.md](./HOW_TESTS_WORK.md) - 详细原理说明
2. **查看**: [CategoryImportConsumerTest.php](./Model/MessageQueue/Consumer/CategoryImportConsumerTest.php) - 实际代码示例
3. **运行**: `./run_tests.sh` - 亲自执行测试
4. **修改**: 尝试修改测试，观察结果变化
5. **实践**: 为其他类编写单元测试

---

## 🎓 核心要点

> **单元测试的本质**：通过 Mock 隔离依赖，验证单个类的行为是否符合预期。

- ✅ **Mock** 让你控制依赖的行为
- ✅ **expects()** 既是配置也是断言
- ✅ **setUp()** 确保每个测试独立
- ✅ **一个测试一个场景**，保持简单清晰
- ✅ **测试命名**要表达意图

---

**最后更新**: 2026-04-18  
**状态**: ✅ 9/9 测试通过
