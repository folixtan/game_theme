<?php
declare(strict_types=1);

namespace FolixCode\Test\Unit\BaseSyncService\Model;

use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;
use FolixCode\BaseSyncService\Api\VendorConfigInterface;
use FolixCode\BaseSyncService\Model\ApiClient;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * ApiClient 单元测试
 */
class ApiClientTest extends TestCase
{
    private VendorConfigInterface $vendorConfig;
    private EncryptionStrategyInterface $encryptionStrategy;
    private LoggerInterface $logger;
    private ApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock VendorConfig
        $this->vendorConfig = $this->createMock(VendorConfigInterface::class);
        $this->vendorConfig->method('getApiBaseUrl')->willReturn('https://api.test.com');
        $this->vendorConfig->method('getSecretId')->willReturn('test-secret-id');
        
        // Mock EncryptionStrategy
        $this->encryptionStrategy = $this->createMock(EncryptionStrategyInterface::class);
        $this->encryptionStrategy->method('encrypt')->willReturn('encrypted_data');
        $this->encryptionStrategy->method('decrypt')->willReturn(['status' => 1, 'data' => ['result' => 'success']]);
        $this->encryptionStrategy->method('generateSignature')->willReturn('test_signature');
        $this->encryptionStrategy->method('getMethodName')->willReturn('AES-256-CBC');
        
        // Mock Logger
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // 创建ApiClient（不传入Guzzle Client，让它自己创建）
        $this->apiClient = new ApiClient(
            $this->vendorConfig,
            $this->encryptionStrategy,
            $this->logger,
            ['timeout' => 30]
        );
    }

    /**
     * 测试ApiClient实例创建
     */
    public function testApiClientCreation(): void
    {
        $this->assertInstanceOf(ApiClient::class, $this->apiClient);
    }

    /**
     * 测试实现了ExternalApiClientInterface接口
     */
    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(
            \FolixCode\BaseSyncService\Api\ExternalApiClientInterface::class,
            $this->apiClient
        );
    }

    /**
     * 测试GET方法存在
     */
    public function testGetMethodExists(): void
    {
        $this->assertTrue(method_exists($this->apiClient, 'get'));
    }

    /**
     * 测试POST方法存在
     */
    public function testPostMethodExists(): void
    {
        $this->assertTrue(method_exists($this->apiClient, 'post'));
    }

    /**
     * 测试PUT方法存在
     */
    public function testPutMethodExists(): void
    {
        $this->assertTrue(method_exists($this->apiClient, 'put'));
    }

    /**
     * 测试DELETE方法存在
     */
    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->apiClient, 'delete'));
    }

    /**
     * 测试构造函数参数验证 - vendorConfig必需
     */
    public function testConstructorRequiresVendorConfig(): void
    {
        $reflection = new \ReflectionClass(ApiClient::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertEquals('vendorConfig', $parameters[0]->getName());
        $type = $parameters[0]->getType();
        $this->assertNotNull($type);
        $this->assertEquals(VendorConfigInterface::class, $type->getName());
    }

    /**
     * 测试构造函数参数验证 - encryptionStrategy必需
     */
    public function testConstructorRequiresEncryptionStrategy(): void
    {
        $reflection = new \ReflectionClass(ApiClient::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertEquals('encryptionStrategy', $parameters[1]->getName());
        $type = $parameters[1]->getType();
        $this->assertNotNull($type);
        $this->assertEquals(EncryptionStrategyInterface::class, $type->getName());
    }

    /**
     * 测试构造函数参数验证 - logger必需
     */
    public function testConstructorRequiresLogger(): void
    {
        $reflection = new \ReflectionClass(ApiClient::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertEquals('logger', $parameters[2]->getName());
        $type = $parameters[2]->getType();
        $this->assertNotNull($type);
        $this->assertEquals(LoggerInterface::class, $type->getName());
    }

    /**
     * 测试guzzleConfig可选参数
     */
    public function testGuzzleConfigIsOptional(): void
    {
        $reflection = new \ReflectionClass(ApiClient::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertEquals('guzzleConfig', $parameters[3]->getName());
        $this->assertTrue($parameters[3]->isOptional());
        $this->assertEquals([], $parameters[3]->getDefaultValue());
    }
}
