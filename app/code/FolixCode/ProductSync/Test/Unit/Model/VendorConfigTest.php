<?php
declare(strict_types=1);

namespace FolixCode\Test\Unit\ProductSync\Model;

use FolixCode\BaseSyncService\Helper\Data as BaseSyncHelper;
use FolixCode\ProductSync\Model\VendorConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;

/**
 * ProductSync VendorConfig 单元测试
 */
class VendorConfigTest extends TestCase
{
    private BaseSyncHelper $helper;
    private EncryptorInterface $encryptor;
    private VendorConfig $vendorConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Helper
        $this->helper = $this->createMock(BaseSyncHelper::class);
        $this->helper->method('getApiBaseUrl')->willReturn('https://product-api.com');
        $this->helper->method('getSecretId')->willReturn('product-secret-id');
        $this->helper->method('getSecretKey')->willReturn('encrypted-product-key');
        
        // Mock Encryptor
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->encryptor->method('decrypt')->willReturn('decrypted-product-key');
        
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
        $this->assertEquals('https://product-api.com', $baseUrl);
    }

    /**
     * 测试获取Secret ID
     */
    public function testGetSecretId(): void
    {
        $secretId = $this->vendorConfig->getSecretId();
        $this->assertEquals('product-secret-id', $secretId);
    }

    /**
     * 测试获取解密的Secret Key
     */
    public function testGetDecryptedSecretKey(): void
    {
        $secretKey = $this->vendorConfig->getSecretKey();
        $this->assertEquals('decrypted-product-key', $secretKey);
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
     * 测试两个模块的VendorConfig相互独立
     */
    public function testVendorConfigsAreIndependent(): void
    {
        // 创建另一个不同的配置
        $differentHelper = $this->createMock(BaseSyncHelper::class);
        $differentHelper->method('getApiBaseUrl')->willReturn('https://different-api.com');
        $differentHelper->method('getSecretId')->willReturn('different-id');
        $differentHelper->method('getSecretKey')->willReturn('different-key');
        
        $differentEncryptor = $this->createMock(EncryptorInterface::class);
        $differentEncryptor->method('decrypt')->willReturn('decrypted-different-key');
        
        $differentConfig = new VendorConfig($differentHelper, $differentEncryptor);
        
        // 验证两个配置互不影响
        $this->assertNotEquals(
            $this->vendorConfig->getApiBaseUrl(),
            $differentConfig->getApiBaseUrl()
        );
        
        $this->assertNotEquals(
            $this->vendorConfig->getSecretId(),
            $differentConfig->getSecretId()
        );
    }
}
