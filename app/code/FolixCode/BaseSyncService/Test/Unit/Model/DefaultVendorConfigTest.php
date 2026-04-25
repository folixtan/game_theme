<?php
declare(strict_types=1);

namespace FolixCode\Test\Unit\BaseSyncService\Model;

use FolixCode\BaseSyncService\Helper\Data as BaseSyncHelper;
use FolixCode\BaseSyncService\Model\DefaultVendorConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;

/**
 * DefaultVendorConfig 单元测试
 */
class DefaultVendorConfigTest extends TestCase
{
    private BaseSyncHelper $helper;
    private EncryptorInterface $encryptor;
    private DefaultVendorConfig $vendorConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Helper
        $this->helper = $this->createMock(BaseSyncHelper::class);
        $this->helper->method('getApiBaseUrl')->willReturn('https://default-api.com');
        $this->helper->method('getSecretId')->willReturn('default-secret-id');
        $this->helper->method('getSecretKey')->willReturn('encrypted-default-key');
        
        // Mock Encryptor
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->encryptor->method('decrypt')->willReturn('decrypted-default-key');
        
        // 创建DefaultVendorConfig实例
        $this->vendorConfig = new DefaultVendorConfig($this->helper, $this->encryptor);
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
        $this->assertEquals('https://default-api.com', $baseUrl);
    }

    /**
     * 测试获取Secret ID
     */
    public function testGetSecretId(): void
    {
        $secretId = $this->vendorConfig->getSecretId();
        $this->assertEquals('default-secret-id', $secretId);
    }

    /**
     * 测试获取解密的Secret Key
     */
    public function testGetDecryptedSecretKey(): void
    {
        $secretKey = $this->vendorConfig->getSecretKey();
        $this->assertEquals('decrypted-default-key', $secretKey);
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
     * 测试作为默认配置的作用
     */
    public function testServesAsDefaultConfiguration(): void
    {
        // 验证所有必需方法都返回有效值
        $this->assertNotEmpty($this->vendorConfig->getApiBaseUrl());
        $this->assertNotEmpty($this->vendorConfig->getSecretId());
        $this->assertIsString($this->vendorConfig->getSecretKey());
        $this->assertNotEmpty($this->vendorConfig->getEncryptionMethod());
    }
}
