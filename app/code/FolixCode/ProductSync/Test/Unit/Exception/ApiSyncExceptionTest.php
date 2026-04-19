<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Test\Unit\Exception;

use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

/**
 * ApiSyncException 单元测试
 */
class ApiSyncExceptionTest extends TestCase
{
    /**
     * 测试基本异常创建
     */
    public function testBasicException(): void
    {
        $exception = new ApiSyncException(
            new Phrase('Test error message')
        );

        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertEmpty($exception->getContextData());
    }

    /**
     * 测试带上下文数据的异常
     */
    public function testExceptionWithContextData(): void
    {
        $contextData = [
            'product_id' => 123,
            'sku' => 'TEST-SKU',
            'api_response' => ['status' => 'error']
        ];

        $exception = new ApiSyncException(
            new Phrase('Product import failed'),
            null,
            400,
            $contextData
        );

        $this->assertEquals('Product import failed', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals($contextData, $exception->getContextData());
    }

    /**
     * 测试包装原始异常
     */
    public function testExceptionWithCause(): void
    {
        $cause = new \RuntimeException('Original error');
        
        $exception = new ApiSyncException(
            new Phrase('Wrapped error: %1', [$cause->getMessage()]),
            $cause,
            500
        );

        $this->assertStringContainsString('Original error', $exception->getMessage());
        $this->assertEquals($cause, $exception->getPrevious());
        $this->assertEquals(500, $exception->getCode());
    }

    /**
     * 测试动态设置上下文数据
     */
    public function testSetContextData(): void
    {
        $exception = new ApiSyncException(new Phrase('Error'));
        
        $initialData = ['step' => 'validation'];
        $exception->setContextData($initialData);
        $this->assertEquals($initialData, $exception->getContextData());

        $updatedData = ['step' => 'import', 'product_id' => 456];
        $exception->setContextData($updatedData);
        $this->assertEquals($updatedData, $exception->getContextData());
    }

    /**
     * 测试带参数的本地化消息
     */
    public function testLocalizedMessageWithParameters(): void
    {
        $productId = 789;
        $exception = new ApiSyncException(
            new Phrase('Failed to import product %1', [$productId])
        );

        $this->assertEquals('Failed to import product 789', $exception->getMessage());
        $this->assertEquals('Failed to import product %1', $exception->getRawMessage());
        $this->assertEquals([$productId], $exception->getParameters());
    }

    /**
     * 测试完整的异常场景
     */
    public function testCompleteExceptionScenario(): void
    {
        $originalException = new \GuzzleHttp\Exception\ClientException(
            'API Error',
            new \GuzzleHttp\Psr7\Request('POST', '/api/products'),
            new \GuzzleHttp\Psr7\Response(422, [], '{"error": "Invalid data"}')
        );

        $contextData = [
            'endpoint' => '/api/products',
            'method' => 'POST',
            'product_id' => 999,
            'request_body' => ['name' => 'Test Product'],
            'response_body' => '{"error": "Invalid data"}'
        ];

        $exception = new ApiSyncException(
            new Phrase('API request failed with status 422: Invalid data'),
            $originalException,
            422,
            $contextData
        );

        // 验证所有属性
        $this->assertStringContainsString('422', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertEquals($originalException, $exception->getPrevious());
        $this->assertEquals($contextData, $exception->getContextData());
        
        // 验证继承的方法
        $this->assertInstanceOf(\Magento\Framework\Exception\LocalizedException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
