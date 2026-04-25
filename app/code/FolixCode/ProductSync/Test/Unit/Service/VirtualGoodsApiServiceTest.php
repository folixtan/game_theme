<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Test\Unit\Service;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * VirtualGoodsApiService 单元测试
 */
class VirtualGoodsApiServiceTest extends TestCase
{
    private ExternalApiClientInterface $apiClient;
    private LoggerInterface $logger;
    private VirtualGoodsApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->apiClient = $this->createMock(ExternalApiClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new VirtualGoodsApiService($this->apiClient, $this->logger);
    }

    /**
     * 测试服务实例创建
     */
    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(VirtualGoodsApiService::class, $this->service);
    }

    /**
     * 测试实现VirtualGoodsApiInterface接口
     */
    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(
            \FolixCode\ProductSync\Api\VirtualGoodsApiInterface::class,
            $this->service
        );
    }

    /**
     * 测试getProductList方法存在
     */
    public function testGetProductListMethodExists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getProductList'));
    }

    /**
     * 测试getCategoryList方法存在
     */
    public function testGetCategoryListMethodExists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCategoryList'));
    }

    /**
     * 测试getProductDetail方法存在
     */
    public function testGetProductDetailMethodExists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getProductDetail'));
    }

    /**
     * 测试getProductList调用API
     */
    public function testGetProductListCallsApi(): void
    {
        $mockResponse = [
            'status' => 1,
            'data' => [
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2']
            ]
        ];

        $this->apiClient->method('post')->willReturn($mockResponse);

        $result = $this->service->getProductList(['page' => 1, 'limit' => 10]);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
    }

    /**
     * 测试getProductList默认参数
     */
    public function testGetProductListWithDefaultParams(): void
    {
        $mockResponse = ['status' => 1, 'data' => []];
        $this->apiClient->method('post')->willReturn($mockResponse);

        $this->service->getProductList();
        
        $this->assertTrue(true);
    }

    /**
     * 测试getProductList无效响应抛出异常
     */
    public function testGetProductListInvalidResponseThrowsException(): void
    {
        $invalidResponse = ['status' => 1]; // 缺少data字段
        $this->apiClient->method('post')->willReturn($invalidResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid API response format for product list');

        $this->service->getProductList();
    }

    /**
     * 测试getCategoryList调用API
     */
    public function testGetCategoryListCallsApi(): void
    {
        $mockResponse = [
            'status' => 1,
            'data' => [
                ['id' => 1, 'name' => 'Category 1'],
                ['id' => 2, 'name' => 'Category 2']
            ]
        ];

        $this->apiClient->method('post')->willReturn($mockResponse);

        $result = $this->service->getCategoryList();

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
    }

    /**
     * 测试getProductDetail调用API
     */
    public function testGetProductDetailCallsApi(): void
    {
        $mockResponse = [
            'status' => 1,
            'data' => ['id' => 123, 'name' => 'Detail Product']
        ];

        $this->apiClient->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $result = $this->service->getProductDetail(['goods_id' => 123]);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['status']);
        $this->assertEquals(123, $result['data']['id']);
    }
}
