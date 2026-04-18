# 单元测试 vs 集成测试 - 完整对比

## 🎯 核心区别

| 特性 | 单元测试 (Unit Test) | 集成测试 (Integration Test) |
|------|---------------------|---------------------------|
| **测试范围** | 单个类/方法 | 多个类协作的完整流程 |
| **依赖处理** | Mock 所有依赖 | 使用真实依赖 |
| **数据库访问** | ❌ 不访问 | ✅ 真实访问 |
| **执行速度** | ⚡ 非常快 (ms 级) | 🐢 较慢 (秒级) |
| **隔离性** | 🔒 完全隔离 | 🔗 相互影响 |
| **测试目的** | 验证逻辑正确性 | 验证系统集成 |
| **文件位置** | `Test/Unit/` | `Test/Integration/` |

---

## 📊 你的测试属于哪种？

### 当前测试：单元测试 ❌ 不调用完整流程

**文件**: [`CategoryImportConsumerTest.php`](../Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php)

```php
// setUp() 中创建 Mock
$this->categoryImporterMock = $this->getMockBuilder(CategoryImporter::class)
    ->disableOriginalConstructor()
    ->getMock();  // ← 假的！

// 测试中只验证调用，不执行真实逻辑
$this->categoryImporterMock->expects($this->once())
    ->method('import')
    ->with($categoryData);  // ← 不会真正执行 import() 内部代码
```

**执行流程**：
```
CategoryImportConsumer::process()
    ↓
Serializer::unserialize()        → Mock（直接返回值）
    ↓
CategoryImporter::import()       → Mock（什么都不做，只验证被调用）
    ↓
Logger::info()                   → Mock（只记录调用次数）
```

**特点**：
- ✅ **速度快**：14ms 跑完 9 个测试
- ✅ **无副作用**：不写数据库
- ✅ **隔离性好**：只测试 Consumer 的逻辑
- ❌ **不测试依赖**：`CategoryImporter` 的内部逻辑没被测试
- ❌ **不验证结果**：不知道分类是否真的被创建

---

### 新增测试：集成测试 ✅ 调用完整流程

**文件**: [`CategoryImportConsumerIntegrationTest.php`](../Integration/Model/MessageQueue/Consumer/CategoryImportConsumerIntegrationTest.php)

```php
// setUp() 中使用 ObjectManager 获取真实对象
$objectManager = Bootstrap::getObjectManager();
$this->consumer = $objectManager->create(CategoryImportConsumer::class);
// ← 真实的 CategoryImportConsumer，包含真实的依赖

// 没有 Mock！所有依赖都是真实的
```

**执行流程**：
```
CategoryImportConsumer::process()
    ↓
Serializer::unserialize()        → 真实执行反序列化
    ↓
CategoryImporter::import()       → 真实执行！
    ↓
    ├─ CategoryService::upsertCategoryByPath()
    │   └─ 查询数据库，检查分类是否存在
    │   └─ 创建或更新分类
    │   └─ 保存到数据库 💾
    ├─ CategoryService::getCategoryById()
    │   └─ 从数据库读取分类
    ├─ updateCategoryAttributes()
    │   └─ 更新分类属性
    │   └─ $category->save()  💾 写入数据库
    ↓
Logger::info()                   → 真实记录日志到 var/log/
```

**特点**：
- ✅ **测试完整流程**：包括 `CategoryImporter` 的所有逻辑
- ✅ **验证真实结果**：分类真的被创建了
- ✅ **发现集成问题**：依赖之间的配合问题
- ❌ **速度慢**：每个测试可能需要几秒
- ❌ **有副作用**：会修改数据库
- ❌ **需要清理**：测试后要删除测试数据

---

## 🔍 详细对比示例

### 场景：测试分类导入

#### 单元测试（当前）

```php
public function testProcessValidCategoryData(): void
{
    // 1. Mock CategoryImporter
    $this->categoryImporterMock->expects($this->once())
        ->method('import')
        ->with($categoryData);
    
    // 2. 执行
    $this->consumer->process($operationMock);
    
    // 3. 验证
    // ✅ import() 被调用了 1 次
    // ✅ 参数正确
    // ❌ 但不知道 import() 内部做了什么
    // ❌ 不知道分类是否真的被创建
}
```

**测试了什么**：
- ✅ `CategoryImportConsumer` 是否正确调用了 `CategoryImporter`
- ✅ 是否正确处理了序列化和日志
- ❌ **没有测试** `CategoryImporter::import()` 的内部逻辑

---

#### 集成测试（新增）

```php
public function testFullImportFlowCreatesCategory(): void
{
    // 1. 准备真实数据
    $categoryData = [
        'id' => 'test_123',
        'name' => 'Test Category',
        'description' => 'Test Description'
    ];
    
    // 2. 创建真实 Operation
    $operation = $this->operationFactory->create();
    $operation->setSerializedData($this->serializer->serialize($categoryData));
    
    // 3. 执行 - 这会走完整流程！
    $this->consumer->process($operation);
    //   ↓
    //   CategoryImporter::import() 真的被执行
    //   ↓
    //   CategoryService::upsertCategoryByPath() 真的被调用
    //   ↓
    //   数据库中的 category 表真的被插入/更新
    
    // 4. 验证真实结果
    $categoryRepository = Bootstrap::getObjectManager()
        ->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
    $category = $categoryRepository->get($categoryId);
    
    $this->assertEquals('Test Category', $category->getName());
    $this->assertEquals('Test Description', $category->getDescription());
}
```

**测试了什么**：
- ✅ 完整的导入流程
- ✅ `CategoryImporter::import()` 的内部逻辑
- ✅ `CategoryService` 的分类创建/更新逻辑
- ✅ 数据库操作是否正确
- ✅ 分类真的被创建了

---

## 📋 何时使用哪种测试？

### 使用单元测试（Unit Test）当...

✅ 你想快速验证单个类的逻辑  
✅ 你想隔离测试，不受外部因素影响  
✅ 你想测试异常处理逻辑  
✅ 你想快速运行大量测试  
✅ 你在开发阶段频繁运行测试  

**你的当前测试适合**：
- 验证 `CategoryImportConsumer` 的消息处理逻辑
- 验证各种异常情况（锁等待、死锁等）
- 验证日志记录是否正确
- CI/CD 中快速反馈

---

### 使用集成测试（Integration Test）当...

✅ 你想测试多个类的协作  
✅ 你想验证数据库操作  
✅ 你想测试真实的业务流程  
✅ 你想发现集成问题  
✅ 你在发布前进行最终验证  

**集成测试适合**：
- 验证完整的分类导入流程
- 验证分类真的被创建到数据库
- 验证 `CategoryImporter` 和 `CategoryService` 的配合
- 验证事务和回滚逻辑
- 性能测试

---

## 🎓 实际建议

### 推荐的测试策略

```
项目测试金字塔：

        /\
       /  \      少量 E2E 测试（浏览器自动化）
      /----\
     /      \    适量集成测试（验证模块协作）
    /--------\
   /          \   大量单元测试（验证单个类）
  /------------\
```

**对于你的项目**：

1. **单元测试**（已有，9 个测试）✅
   - 测试 `CategoryImportConsumer` 的逻辑
   - 快速、隔离、可靠
   - 每次提交都运行

2. **集成测试**（新增，4 个测试）⚠️
   - 测试完整导入流程
   - 验证数据库操作
   - 发布前运行或定期运行

3. **功能测试**（可选）
   - 通过 API 或 UI 测试
   - 模拟真实用户操作
   - 关键业务流程验证

---

## 🚀 如何运行集成测试

### 运行集成测试

```bash
# 运行集成测试（需要完整的 Magento 环境）
cd /var/www/html/game/game
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist \
  app/code/FolixCode/ProductSync/Test/Integration/Model/MessageQueue/Consumer/CategoryImportConsumerIntegrationTest.php
```

### ⚠️ 注意事项

1. **需要数据库**：集成测试会访问真实数据库
2. **需要配置**：确保 `dev/tests/integration/etc/install-config-mysql.php` 已配置
3. **速度慢**：每个测试可能需要几秒
4. **有副作用**：会修改数据库，需要清理
5. **需要样本数据**：某些测试可能需要 Magento 样本数据

---

## 📊 对比总结表

| 方面 | 单元测试 | 集成测试 |
|------|---------|---------|
| **文件位置** | `Test/Unit/` | `Test/Integration/` |
| **Bootstrap** | `dev/tests/unit/framework/bootstrap.php` | `dev/tests/integration/framework/bootstrap.php` |
| **ObjectManager** | 不使用（手动 new） | 使用 `Bootstrap::getObjectManager()` |
| **Mock** | 大量使用 | 很少或不用 |
| **数据库** | ❌ 不访问 | ✅ 真实访问 |
| **速度** | 毫秒级 | 秒级 |
| **可靠性** | 高（隔离） | 中（依赖环境） |
| **覆盖率** | 代码逻辑 | 业务流程 |
| **维护成本** | 低 | 高 |
| **适用场景** | 日常开发 | 发布前验证 |

---

## 💡 回答你的问题

> **问：测试的时候会调完整的流程吗？比如 `CategoryImporter::import()` 这个方法？**

**答**：

### 当前的单元测试：**不会** ❌

```php
// 这是 Mock，不会执行真实代码
$this->categoryImporterMock->expects($this->once())
    ->method('import');  // ← 只验证被调用，不执行内部逻辑
```

- `CategoryImporter::import()` 内部的代码**不会被执行**
- `CategoryService::upsertCategoryByPath()` **不会被调用**
- 数据库**不会被访问**
- 分类**不会被创建**

**只验证了**：
- ✅ `CategoryImportConsumer` 是否调用了 `import()`
- ✅ 传递的参数是否正确

---

### 新增的集成测试：**会** ✅

```php
// 这是真实对象，会执行完整流程
$this->consumer = $objectManager->create(CategoryImportConsumer::class);
// ↓
// 调用 process() 时，会真正执行：
// - CategoryImporter::import()
// - CategoryService::upsertCategoryByPath()
// - 数据库 INSERT/UPDATE
```

- `CategoryImporter::import()` 的所有代码**都会被执行**
- `CategoryService` 的方法**会被调用**
- 数据库**会被访问和修改**
- 分类**会被真正创建**

**验证了**：
- ✅ 完整的业务流程
- ✅ 数据库操作
- ✅ 分类是否真的被创建

---

## 🎯 建议

### 你应该保留两种测试

1. **单元测试**（已有）
   - 用于日常开发
   - 快速验证逻辑
   - CI/CD 中每次提交都运行

2. **集成测试**（新增）
   - 用于验证完整流程
   - 发布前运行
   - 定期运行确保集成正常

### 测试覆盖矩阵

| 测试内容 | 单元测试 | 集成测试 |
|---------|---------|---------|
| Consumer 消息处理逻辑 | ✅ | ✅ |
| 异常处理（锁、死锁） | ✅ | ❌ |
| 日志记录 | ✅ | ✅ |
| CategoryImporter 内部逻辑 | ❌ | ✅ |
| CategoryService 分类创建 | ❌ | ✅ |
| 数据库操作 | ❌ | ✅ |
| 分类是否真的被创建 | ❌ | ✅ |

---

## 📚 相关文档

- [单元测试文件](../Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php)
- [集成测试文件](../Integration/Model/MessageQueue/Consumer/CategoryImportConsumerIntegrationTest.php)
- [单元测试原理](../Unit/HOW_TESTS_WORK.md)
- [快速参考](../Unit/QUICK_REFERENCE.md)

---

**总结**：
- 当前的单元测试**不会**调用完整流程，只验证 Consumer 的逻辑
- 新增的集成测试**会**调用完整流程，验证整个系统
- 两种测试互补，建议都保留
