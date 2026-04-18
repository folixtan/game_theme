<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Test\Unit\Model\MessageQueue\Consumer;

use FolixCode\ProductSync\Model\MessageQueue\Consumer\CategoryImportConsumer;
use FolixCode\ProductSync\Service\CategoryImporter;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * CategoryImportConsumer 单元测试
 */
class CategoryImportConsumerTest extends TestCase
{
    /**
     * @var CategoryImporter|MockObject
     */
    private $categoryImporterMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManagerMock;

    /**
     * @var CategoryImportConsumer
     */
    private $consumer;

    /**
     * @var OperationInterface|MockObject
     */
    private $operationMock;

    protected function setUp(): void
    {
        // 创建 Mock 对象
        $this->categoryImporterMock = $this->getMockBuilder(CategoryImporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // 配置 Operation Mock 支持链式调用
        $this->operationMock = $this->getMockBuilder(OperationInterface::class)
            ->getMock();
        
        $this->operationMock->method('setStatus')
            ->willReturnSelf();
        $this->operationMock->method('setErrorCode')
            ->willReturnSelf();
        $this->operationMock->method('setResultMessage')
            ->willReturnSelf();

        // 初始化被测类
        $this->consumer = new CategoryImportConsumer(
            $this->categoryImporterMock,
            $this->serializerMock,
            $this->loggerMock,
            $this->entityManagerMock
        );
    }

    /**
     * 测试：处理有效的分类数据
     */
    public function testProcessValidCategoryData(): void
    {
        // 准备测试数据
        $categoryData = [
            'id' => '123',
            'name' => 'Test Category',
            'description' => 'Test Description',
            'is_active' => 1,
            'position' => 10
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info');

        // 验证 Operation 状态被设置为 COMPLETE
        $this->operationMock->expects($this->once())
            ->method('setStatus')
            ->with(\Magento\AsynchronousOperations\Api\Data\OperationInterface::STATUS_TYPE_COMPLETE);

        // 验证 EntityManager save 被调用
        $this->entityManagerMock->expects($this->once())
            ->method('save')
            ->with($this->operationMock);

        // 执行测试（不应该抛出异常）
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：处理缺少 ID 的分类数据
     */
    public function testProcessInvalidCategoryDataWithoutId(): void
    {
        // 准备测试数据（缺少 id）
        $categoryData = [
            'name' => 'Test Category'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Invalid category data received: missing ID',
                $this->callback(function ($context) {
                    return isset($context['data']['name']) && $context['data']['name'] === 'Test Category';
                })
            );

        // 验证 Operation 状态被设置为 NOT_RETRIABLY_FAILED
        $this->operationMock->expects($this->once())
            ->method('setStatus')
            ->with(\Magento\AsynchronousOperations\Api\Data\OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);

        $this->operationMock->expects($this->once())
            ->method('setResultMessage')
            ->with('Category ID is required');

        // 验证 EntityManager save 被调用
        $this->entityManagerMock->expects($this->once())
            ->method('save')
            ->with($this->operationMock);

        // 执行测试（不应该抛出异常，而是设置状态）
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：处理空数据
     */
    public function testProcessEmptyCategoryData(): void
    {
        // 准备测试数据（空数组）
        $categoryData = [];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Invalid category data received: missing ID',
                $this->callback(function ($context) {
                    return empty($context['data']);
                })
            );

        // 期望抛出 InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category ID is required');

        // 执行测试
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：分类导入成功并记录日志
     */
    public function testProcessWithSuccessfulImportAndLogging(): void
    {
        // 准备测试数据
        $categoryData = [
            'id' => '456',
            'name' => 'Games',
            'parent_path' => 'Electronics'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData);

        // 验证日志被调用（至少 2 次：开始和完成）
        $this->loggerMock->expects($this->atLeast(2))
            ->method('info');

        // 执行测试
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：分类已存在时跳过（AlreadyExistsException）
     */
    public function testProcessWhenCategoryAlreadyExists(): void
    {
        // 准备测试数据
        $categoryData = [
            'id' => '789',
            'name' => 'Existing Category'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        // 模拟 AlreadyExistsException
        $alreadyExistsException = new \Magento\Framework\Exception\AlreadyExistsException(
            __('Category already exists')
        );

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData)
            ->willThrowException($alreadyExistsException);

        // 验证日志记录（应该记录 2 次 info：开始处理和已存在）
        $logCalls = [];
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message, $context = []) use (&$logCalls) {
                $logCalls[] = ['message' => $message, 'context' => $context];
            });

        // 执行测试（不应该抛出异常，因为 AlreadyExistsException 被视为成功）
        $this->consumer->process($this->operationMock);

        // 验证日志内容
        $this->assertEquals('Processing category import', $logCalls[0]['message']);
        $this->assertEquals('Category already exists, skipped', $logCalls[1]['message']);
        $this->assertEquals('789', $logCalls[1]['context']['category_id']);
    }

    /**
     * 测试：数据库锁等待异常会重新抛出
     */
    public function testProcessWithDatabaseLockWaitException(): void
    {
        // 准备测试数据
        $categoryData = [
            'id' => '999',
            'name' => 'Locked Category'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        // 模拟 LockWaitException
        $lockException = new \Magento\Framework\DB\Adapter\LockWaitException(
            __('Lock wait timeout exceeded')
        );

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData)
            ->willThrowException($lockException);

        // 验证 warning 日志
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Database lock detected, will retry',
                $this->callback(function ($context) {
                    return isset($context['category_id']) && $context['category_id'] === '999';
                })
            );

        // 期望异常被重新抛出
        $this->expectException(\Magento\Framework\DB\Adapter\LockWaitException::class);

        // 执行测试
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：死锁异常会重新抛出
     */
    public function testProcessWithDeadlockException(): void
    {
        // 准备测试数据
        $categoryData = [
            'id' => '888',
            'name' => 'Deadlock Category'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        // 模拟 DeadlockException
        $deadlockException = new \Magento\Framework\DB\Adapter\DeadlockException(
            __('Deadlock found')
        );

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData)
            ->willThrowException($deadlockException);

        // 验证 warning 日志
        $this->loggerMock->expects($this->once())
            ->method('warning');

        // 期望异常被重新抛出
        $this->expectException(\Magento\Framework\DB\Adapter\DeadlockException::class);

        // 执行测试
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：其他异常会记录错误并重新抛出
     */
    public function testProcessWithGenericException(): void
    {
        // 准备测试数据
        $categoryData = [
            'id' => '777',
            'name' => 'Error Category'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        // 模拟通用异常
        $genericException = new \Exception('Something went wrong');

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData)
            ->willThrowException($genericException);

        // 验证 error 日志
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Failed to process category import',
                $this->callback(function ($context) {
                    return isset($context['category_id']) && 
                           $context['category_id'] === '777' &&
                           isset($context['error']) &&
                           $context['error'] === 'Something went wrong';
                })
            );

        // 期望异常被重新抛出
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong');

        // 执行测试
        $this->consumer->process($this->operationMock);
    }

    /**
     * 测试：处理包含完整字段的分类数据
     */
    public function testProcessWithCompleteCategoryData(): void
    {
        // 准备完整的测试数据
        $categoryData = [
            'id' => '100',
            'name' => 'Complete Category',
            'description' => 'Full description',
            'is_active' => 1,
            'include_in_menu' => 1,
            'position' => 5,
            'url_key' => 'complete-category',
            'parent_path' => 'Root/SubCategory'
        ];

        $serializedData = serialize($categoryData);

        // 设置 Mock 行为
        $this->operationMock->expects($this->once())
            ->method('getSerializedData')
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($categoryData);

        $this->categoryImporterMock->expects($this->once())
            ->method('import')
            ->with($categoryData);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info');

        // 执行测试
        $this->consumer->process($this->operationMock);
    }
}
