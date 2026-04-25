<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Test\Unit\Api;

use FolixCode\BaseSyncService\Api\VendorConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * VendorConfigInterface 接口测试
 */
class VendorConfigInterfaceTest extends TestCase
{
    /**
     * 测试接口是否存在且方法签名正确
     */
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(VendorConfigInterface::class));
    }

    /**
     * 测试接口定义了必需的方法
     */
    public function testInterfaceHasRequiredMethods(): void
    {
        $methods = get_class_methods(VendorConfigInterface::class);
        
        $this->assertContains('getApiBaseUrl', $methods, '缺少 getApiBaseUrl 方法');
        $this->assertContains('getSecretId', $methods, '缺少 getSecretId 方法');
        $this->assertContains('getSecretKey', $methods, '缺少 getSecretKey 方法');
        $this->assertContains('getEncryptionMethod', $methods, '缺少 getEncryptionMethod 方法');
    }

    /**
     * 测试Mock实现可以正常工作
     */
    public function testMockImplementation(): void
    {
        $mock = $this->createMock(VendorConfigInterface::class);
        
        $mock->method('getApiBaseUrl')->willReturn('https://test.com');
        $mock->method('getSecretId')->willReturn('test-id');
        $mock->method('getSecretKey')->willReturn('test-key');
        $mock->method('getEncryptionMethod')->willReturn('AES-256-CBC');
        
        $this->assertEquals('https://test.com', $mock->getApiBaseUrl());
        $this->assertEquals('test-id', $mock->getSecretId());
        $this->assertEquals('test-key', $mock->getSecretKey());
        $this->assertEquals('AES-256-CBC', $mock->getEncryptionMethod());
    }
}
