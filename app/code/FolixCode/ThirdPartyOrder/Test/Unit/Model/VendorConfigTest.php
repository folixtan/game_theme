<?php
declare(strict_types=1);

namespace FolixCode\Test\Unit\ThirdPartyOrder\Model;

use FolixCode\ThirdPartyOrder\Helper\Data as ThirdPartyHelper;
use FolixCode\ThirdPartyOrder\Model\VendorConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;

/**
 * ThirdPartyOrder VendorConfig 单元测试
 */
class VendorConfigTest extends TestCase
{
    private ThirdPartyHelper $helper;
    private EncryptorInterface $encryptor;
    private VendorConfig $vendorConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock ThirdPartyOrder Helper
        $this->helper = $this->createMock(ThirdPartyHelper::class);
        $this->helper->method('getApiBaseUrl')->willReturn('https://test-api.com');
        $this->helper->method('getAppKey')->willReturn('test-app-key');
        $this->helper->method('getSecretKey')->willReturn('encrypted-secret-key');
        
        // Mock Encryptor
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->encryptor->method('decrypt')->willReturn('decrypted-secret-key');
        
        // 创建VendorConfig实例
        $this->vendorConfig = new VendorConfig($this->helper, $this->encryptor);
    }

    /**
     * 测试实现了VendorConfigInterface接口
     */
    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(
            \FolixCode\BaseSyncService\Api\VendorConfigInterface::class,
            $this->vendorConfig
        );
    }

    /**
     * 测试获取API Base URL
     */
    public function testGetApiBaseUrl(): void
    {
        $baseUrl = $this->vendorConfig->getApiBaseUrl();
        $this->assertEquals('https://test-api.com', $baseUrl);
    }

    /**
     * 测试获取Secret ID (从AppKey)
     */
    public function testGetSecretId(): void
    {
        $secretId = $this->vendorConfig->getSecretId();
        $this->assertEquals('test-app-key', $secretId);
    }

    /**
     * 测试获取Secret ID时使用默认值
     */
    public function testGetSecretIdWithDefaultValue(): void
    {
        $helperWithoutAppKey = $this->createMock(ThirdPartyHelper::class);
        $helperWithoutAppKey->method('getAppKey')->willReturn('');
        
        $vendorConfig = new VendorConfig($helperWithoutAppKey, $this->encryptor);
        $this->assertEquals('81097469c53704b748e', $vendorConfig->getSecretId());
    }

    /**
     * 测试获取解密的Secret Key
     */
    public function testGetDecryptedSecretKey(): void
    {
        $secretKey = $this->vendorConfig->getSecretKey();
        $this->assertEquals('decrypted-secret-key', $secretKey);
    }

    /**
     * 测试Secret Key解密失败时使用默认值
     */
    public function testGetSecretKeyWithDecryptionFailure(): void
    {
        $encryptorThatFails = $this->createMock(EncryptorInterface::class);
        $encryptorThatFails->method('decrypt')
            ->willThrowException(new \Exception('Decryption failed'));
        
        $vendorConfig = new VendorConfig($this->helper, $encryptorThatFails);
        $this->assertEquals('eO6OSXX1kfVcoQhYacc4u9t6FJnulT5f', $vendorConfig->getSecretKey());
    }

    /**
     * 测试加密方法名称
     */
    public function testGetEncryptionMethod(): void
    {
        $method = $this->vendorConfig->getEncryptionMethod();
        $this->assertEquals('AES-256-CBC', $method);
    }

    /**
     * 测试构造函数依赖注入
     */
    public function testConstructorDependencies(): void
    {
        $reflection = new \ReflectionClass(VendorConfig::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertEquals(2, count($parameters));
        $this->assertEquals('helper', $parameters[0]->getName());
        $this->assertEquals('encryptor', $parameters[1]->getName());
    }
}
