<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Test\Integration\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * CategoryImportConsumer 完整流程集成测试
 * 
 * ⚠️ 注意：这是集成测试，会执行完整的业务流程
 * - 会真正调用 CategoryImporter::import()
 * - 会真正访问数据库（创建/更新分类）
 * - 需要完整的 Magento 环境
 * 
 * 测试覆盖：
 * 1. ID 和 Name 必填验证
 * 2. URL Key 自动生成（name + 随机字符串）
 * 3. 完整的数据库写入流程
 * 4. 分类属性的正确保存
 */
class CategoryImportConsumerIntegrationTest extends TestCase
{
    /**
     * @var CategoryImportConsumer
     */
    private $consumer;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \FolixCode\ProductSync\Service\CategoryService
     */
    private $categoryService;

    /**
     * 存储创建的分类 ID，用于清理
     */
    private $createdCategoryIds = [];

    protected function setUp(): void
    {
        // 使用 Magento 的 ObjectManager 获取真实对象（不是 Mock）
        $objectManager = Bootstrap::getObjectManager();
        
        $this->consumer = $objectManager->create(CategoryImportConsumer::class);
        $this->serializer = $objectManager->create(Json::class);
        $this->operationFactory = $objectManager->create(OperationInterfaceFactory::class);
        $this->categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
        $this->categoryService = $objectManager->create(\FolixCode\ProductSync\Service\CategoryService::class);
        
        $this->createdCategoryIds = [];
    }

    /**
     * 测试 1：完整流程 - 导入新分类（带所有字段）
     * 
     * ✅ 验证点：
     * 1. ID 和 Name 验证通过
     * 2. URL Key 自动生成（如果未提供）
     * 3. 分类真的被创建到数据库
     * 4. 所有属性正确保存
     */
    public function testFullImportFlowWithAllFields(): void
    {
        $timestamp = time();
        $categoryData = [
            'id' => "test_full_{$timestamp}",
            'name' => 'Test Full Category',
            'description' => 'This is a complete test category',
            'is_active' => 1,
            'include_in_menu' => 1,
            'position' => 100,
            'url_key' => "test-full-category-{$timestamp}"  // 提供自定义 URL Key
        ];

        // 创建 Operation 对象
        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($categoryData));

        // 执行测试 - 这会走完整流程！
        $this->consumer->process($operation);

        // 验证：从数据库读取分类
        // 注意：这里需要通过路径或名称查找分类
        // 由于我们不知道自动生成的 category_id，需要通过其他方式查找
        $categoryList = $this->findCategoriesByName('Test Full Category');
        
        $this->assertNotEmpty($categoryList, 'Category should be created in database');
        
        $category = $categoryList[0];
        $this->createdCategoryIds[] = $category->getId();
        
        // 验证所有字段
        $this->assertEquals('Test Full Category', $category->getName());
        $this->assertEquals('This is a complete test category', $category->getDescription());
        $this->assertEquals(1, $category->getIsActive());
        $this->assertEquals(1, $category->getIncludeInMenu());
        $this->assertEquals(100, $category->getPosition());
        $this->assertEquals("test-full-category-{$timestamp}", $category->getUrlKey());
        
        echo "\n✅ Test 1 Passed: Category created with all fields\n";
        echo "   Category ID: {$category->getId()}\n";
        echo "   Name: {$category->getName()}\n";
        echo "   URL Key: {$category->getUrlKey()}\n";
    }

    /**
     * 测试 2：URL Key 自动生成（不提供 url_key）
     * 
     * ✅ 验证点：
     * 1. 当 url_key 为空时，自动生成
     * 2. 格式为：name-randomString（3-5位随机字符）
     * 3. 多次生成不会重复（概率极低）
     */
    public function testAutoGenerateUrlKey(): void
    {
        $timestamp = time();
        $categoryName = 'Test Auto URL Key';
        
        $categoryData = [
            'id' => "test_auto_url_{$timestamp}",
            'name' => $categoryName,
            'description' => 'Testing auto URL key generation',
            'is_active' => 1
            // 注意：不提供 url_key，应该自动生成
        ];

        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($categoryData));

        // 执行测试
        $this->consumer->process($operation);

        // 验证分类被创建
        $categoryList = $this->findCategoriesByName($categoryName);
        $this->assertNotEmpty($categoryList, 'Category should be created');
        
        $category = $categoryList[0];
        $this->createdCategoryIds[] = $category->getId();
        
        // 验证 URL Key 被自动生成
        $urlKey = $category->getUrlKey();
        $this->assertNotEmpty($urlKey, 'URL key should be auto-generated');
        
        // 验证格式：应该包含原始名称（转换为小写和连字符）
        $expectedPrefix = strtolower(str_replace(' ', '-', trim($categoryName)));
        $this->assertStringStartsWith($expectedPrefix, $urlKey, 
            "URL key should start with normalized name. Got: {$urlKey}");
        
        // 验证有随机后缀（格式：name-xxx）
        $parts = explode('-', $urlKey);
        $randomPart = end($parts);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{3,5}$/', $randomPart,
            "Random part should be 3-5 alphanumeric characters. Got: {$randomPart}");
        
        echo "\n✅ Test 2 Passed: URL key auto-generated\n";
        echo "   Category Name: {$categoryName}\n";
        echo "   Generated URL Key: {$urlKey}\n";
        echo "   Random Part: {$randomPart}\n";
    }

    /**
     * 测试 3：多次生成 URL Key 不重复
     * 
     * ✅ 验证点：
     * 1. 相同名称的分类，每次生成的 URL Key 不同
     * 2. 随机性足够（3-5位字符，重复概率极低）
     */
    public function testUrlKeyUniqueness(): void
    {
        $categoryName = 'Test Unique URL';
        $generatedUrlKeys = [];
        
        // 创建 5 个相同名称的分类
        for ($i = 1; $i <= 5; $i++) {
            $timestamp = time() + $i;  // 确保 ID 不同
            $categoryData = [
                'id' => "test_unique_{$timestamp}_{$i}",
                'name' => $categoryName,
                'is_active' => 1
            ];

            $operation = $this->operationFactory->create();
            $operation->setSerializedData($this->serializer->serialize($categoryData));

            $this->consumer->process($operation);
            
            // 获取分类
            $categoryList = $this->findCategoriesByName($categoryName);
            if (!empty($categoryList)) {
                $category = $categoryList[0];
                $this->createdCategoryIds[] = $category->getId();
                $generatedUrlKeys[] = $category->getUrlKey();
            }
        }
        
        // 验证生成了 5 个不同的 URL Key
        $this->assertCount(5, $generatedUrlKeys, 'Should have 5 generated URL keys');
        
        // 验证所有 URL Key 都是唯一的
        $uniqueUrlKeys = array_unique($generatedUrlKeys);
        $this->assertCount(5, $uniqueUrlKeys, 
            'All URL keys should be unique. Generated: ' . implode(', ', $generatedUrlKeys));
        
        echo "\n✅ Test 3 Passed: All URL keys are unique\n";
        echo "   Generated URL Keys:\n";
        foreach ($generatedUrlKeys as $index => $urlKey) {
            echo "     " . ($index + 1) . ". {$urlKey}\n";
        }
    }

    /**
     * 测试 4：缺少 ID 时抛出异常
     * 
     * ✅ 验证点：
     * 1. 消费者端验证 ID 必填
     * 2. 抛出 InvalidArgumentException
     * 3. 不执行后续的导入逻辑
     */
    public function testMissingIdThrowsException(): void
    {
        $categoryData = [
            'name' => 'Test No ID Category'
            // 缺少 id
        ];

        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($categoryData));

        // 期望抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category ID is required');

        // 执行测试
        $this->consumer->process($operation);
        
        echo "\n✅ Test 4 Passed: Missing ID validation works\n";
    }

    /**
     * 测试 5：缺少 Name 时抛出异常
     * 
     * ✅ 验证点：
     * 1. 消费者端验证 Name 必填
     * 2. 抛出 InvalidArgumentException
     * 3. 不执行后续的导入逻辑
     */
    public function testMissingNameThrowsException(): void
    {
        $categoryData = [
            'id' => 'test_no_name_' . time()
            // 缺少 name
        ];

        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($categoryData));

        // 期望抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name is required');

        // 执行测试
        $this->consumer->process($operation);
        
        echo "\n✅ Test 5 Passed: Missing name validation works\n";
    }

    /**
     * 测试 6：完整流程 - 带父级路径的分类
     * 
     * ✅ 验证点：
     * 1. 父级分类自动创建
     * 2. 子级分类正确关联
     * 3. 路径结构正确
     */
    public function testImportWithParentPath(): void
    {
        $timestamp = time();
        $parentPath = "Test Parent {$timestamp}";
        $childName = "Test Child {$timestamp}";
        
        $categoryData = [
            'id' => "test_child_{$timestamp}",
            'name' => $childName,
            'parent_path' => $parentPath,
            'description' => 'Child category with parent path',
            'is_active' => 1
        ];

        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($categoryData));

        // 执行测试
        $this->consumer->process($operation);

        // 验证子分类被创建
        $childList = $this->findCategoriesByName($childName);
        $this->assertNotEmpty($childList, 'Child category should be created');
        
        $childCategory = $childList[0];
        $this->createdCategoryIds[] = $childCategory->getId();
        
        // 验证父分类也被创建
        $parentList = $this->findCategoriesByName($parentPath);
        $this->assertNotEmpty($parentList, 'Parent category should be auto-created');
        
        $parentCategory = $parentList[0];
        $this->createdCategoryIds[] = $parentCategory->getId();
        
        // 验证父子关系
        $this->assertEquals($parentCategory->getId(), $childCategory->getParentId(),
            'Child category should have correct parent ID');
        
        echo "\n✅ Test 6 Passed: Parent-child relationship established\n";
        echo "   Parent: {$parentCategory->getName()} (ID: {$parentCategory->getId()})\n";
        echo "   Child: {$childCategory->getName()} (ID: {$childCategory->getId()})\n";
    }

    /**
     * 测试 7：更新已存在的分类
     * 
     * ✅ 验证点：
     * 1. 第二次导入同一分类会更新而非创建
     * 2. 修改的属性被正确更新
     */
    public function testUpdateExistingCategory(): void
    {
        $timestamp = time();
        $categoryName = "Test Update Category {$timestamp}";
        $categoryId = "test_update_{$timestamp}";
        
        // 第一次导入 - 创建分类
        $categoryData1 = [
            'id' => $categoryId,
            'name' => $categoryName,
            'description' => 'Original description',
            'is_active' => 1,
            'position' => 50
        ];

        $operation1 = $this->operationFactory->create();
        $operation1->setSerializedData($this->serializer->serialize($categoryData1));
        $this->consumer->process($operation1);

        // 获取初始分类
        $categoryList = $this->findCategoriesByName($categoryName);
        $this->assertNotEmpty($categoryList, 'Category should be created');
        
        $category = $categoryList[0];
        $this->createdCategoryIds[] = $category->getId();
        $originalId = $category->getId();
        $this->assertEquals('Original description', $category->getDescription());
        $this->assertEquals(50, $category->getPosition());
        
        // 第二次导入 - 更新分类
        $categoryData2 = [
            'id' => $categoryId,
            'name' => $categoryName,
            'description' => 'Updated description',  // 修改描述
            'is_active' => 1,
            'position' => 100  // 修改位置
        ];

        $operation2 = $this->operationFactory->create();
        $operation2->setSerializedData($this->serializer->serialize($categoryData2));
        $this->consumer->process($operation2);

        // 验证分类被更新（不是创建新的）
        $categoryList = $this->findCategoriesByName($categoryName);
        $this->assertCount(1, $categoryList, 'Should still have only one category');
        
        $updatedCategory = $categoryList[0];
        $this->assertEquals($originalId, $updatedCategory->getId(), 
            'Category ID should remain the same (updated, not created new)');
        $this->assertEquals('Updated description', $updatedCategory->getDescription(),
            'Description should be updated');
        $this->assertEquals(100, $updatedCategory->getPosition(),
            'Position should be updated');
        
        echo "\n✅ Test 7 Passed: Category updated successfully\n";
        echo "   Category ID: {$updatedCategory->getId()} (unchanged)\n";
        echo "   Description: {$updatedCategory->getDescription()}\n";
        echo "   Position: {$updatedCategory->getPosition()}\n";
    }

    /**
     * 测试 8：验证 CategoryService 正确初始化（回归测试）
     * 
     * ✅ 验证点：
     * 1. CategoryService 可以正常实例化
     * 2. initCategories() 不会因为 logger 未初始化而报错
     * 3. upsertCategoryByPath() 可以正常工作
     * 
     * 这是针对 "Typed property must not be accessed before initialization" 错误的回归测试
     */
    public function testCategoryServiceInitialization(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        
        // 获取 CategoryService 实例
        $categoryService = $objectManager->create(\FolixCode\ProductSync\Service\CategoryService::class);
        
        $this->assertNotNull($categoryService, 'CategoryService should be instantiated');
        
        // 测试 upsertCategoryByPath 不会抛出 "must not be accessed before initialization" 错误
        try {
            $categoryId = $categoryService->upsertCategoryByPath('Test/Initialization');
            
            // 如果成功，记录分类 ID 用于清理
            if ($categoryId) {
                $this->createdCategoryIds[] = $categoryId;
            }
            
            echo "\n✅ Test 8 Passed: CategoryService initialized correctly\n";
            echo "   Category ID: {$categoryId}\n";
            
        } catch (\Error $e) {
            // 捕获 Typed property 访问错误
            if (strpos($e->getMessage(), 'must not be accessed before initialization') !== false) {
                $this->fail("CategoryService has initialization error: " . $e->getMessage());
            }
            // 其他错误可能是业务逻辑问题，重新抛出
            throw $e;
        }
    }

    /**
     * 测试 9：批量导入分类时 CategoryService 正常工作
     * 
     * ✅ 验证点：
     * 1. 批量操作不会触发 logger 初始化错误
     * 2. 多个分类可以连续创建
     */
    public function testBatchCategoryImportWithService(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $categoryService = $objectManager->create(\FolixCode\ProductSync\Service\CategoryService::class);
        
        $paths = [
            'Test/Batch/Category1',
            'Test/Batch/Category2',
            'Test/Batch/Category3'
        ];
        
        try {
            $categoryIds = $categoryService->upsertCategoriesBatch($paths);
            
            // 清理创建的分类
            foreach ($categoryIds as $id) {
                if ($id) {
                    $this->createdCategoryIds[] = $id;
                }
            }
            
            $this->assertNotEmpty($categoryIds, 'Should create at least some categories');
            
            echo "\n✅ Test 9 Passed: Batch category import works correctly\n";
            echo "   Created " . count($categoryIds) . " categories\n";
            
        } catch (\Error $e) {
            if (strpos($e->getMessage(), 'must not be accessed before initialization') !== false) {
                $this->fail("CategoryService batch operation has initialization error: " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * 辅助方法：根据名称查找分类
     * 
     * @param string $name
     * @return array
     */
    private function findCategoriesByName(string $name): array
    {
        try {
            // 使用集合查找分类
            $categoryCollection = Bootstrap::getObjectManager()
                ->create(\Magento\Catalog\Model\ResourceModel\Category\Collection::class);
            
            $categories = $categoryCollection
                ->addAttributeToFilter('name', ['eq' => $name])
                ->addAttributeToSelect('*')
                ->getItems();
            
            return array_values($categories);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function tearDown(): void
    {
        // 清理测试数据：删除创建的分类
        foreach ($this->createdCategoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
                $this->categoryRepository->delete($category);
                echo "   🗑️  Cleaned up category ID: {$categoryId}\n";
            } catch (\Exception $e) {
                // 忽略清理错误
            }
        }
        
        parent::tearDown();
    }
}
