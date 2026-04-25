<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Test\Unit\Helper;

use FolixCode\BaseSyncService\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;

/**
 * BaseSyncService Helper 单元测试
 */
class DataTest extends TestCase
{
    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;
    private Data $helper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        
        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);
        
        $this->helper = new Data($context, $this->encryptor);
    }

    /**
     * 测试获取API Base URL
     */
    public function testGetApiBaseUrl(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn('https://test-api.com');
        
        $this->assertEquals('https://test-api.com', $this->helper->getApiBaseUrl());
    }

    /**
     * 测试API Base URL默认值
     */
    public function testGetApiBaseUrlWithDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);
        
        $this->assertEquals('https://playsentral.qr67.com', $this->helper->getApiBaseUrl());
    }

    /**
     * 测试获取Secret ID
     */
    public function testGetSecretId(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn('test-secret-id');
        
        $this->assertEquals('test-secret-id', $this->helper->getSecretId());
    }

    /**
     * 测试Secret ID默认值
     */
    public function testGetSecretIdWithDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);
        
        $this->assertNotEmpty($this->helper->getSecretId());
    }

    /**
     * 测试获取解密的Secret Key
     */
    public function testGetDecryptedSecretKey(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn('encrypted-key');
        
        $this->encryptor->method('decrypt')
            ->with('encrypted-key')
            ->willReturn('decrypted-key');
        
        $this->assertEquals('decrypted-key', $this->helper->getSecretKey());
    }

    /**
     * 测试Secret Key默认值
     */
    public function testGetSecretKeyWithDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);
        
        $this->assertNotEmpty($this->helper->getSecretKey());
    }

    /**
     * 测试模块启用状态
     */
    public function testIsEnabled(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->willReturn(true);
        
        $this->assertTrue($this->helper->isEnabled());
    }

    /**
     * 测试模块禁用状态
     */
    public function testIsDisabled(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->willReturn(false);
        
        $this->assertFalse($this->helper->isEnabled());
    }
}
