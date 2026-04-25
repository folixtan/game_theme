<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Test\Unit\Api;

use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;
use PHPUnit\Framework\TestCase;

/**
 * EncryptionStrategyInterface 接口测试
 */
class EncryptionStrategyInterfaceTest extends TestCase
{
    /**
     * 测试接口是否存在
     */
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(EncryptionStrategyInterface::class));
    }

    /**
     * 测试接口定义了必需的方法
     */
    public function testInterfaceHasRequiredMethods(): void
    {
        $methods = get_class_methods(EncryptionStrategyInterface::class);
        
        $this->assertContains('encrypt', $methods, '缺少 encrypt 方法');
        $this->assertContains('decrypt', $methods, '缺少 decrypt 方法');
        $this->assertContains('generateSignature', $methods, '缺少 generateSignature 方法');
        $this->assertContains('getMethodName', $methods, '缺少 getMethodName 方法');
    }

    /**
     * 测试Mock实现可以正常工作
     */
    public function testMockImplementation(): void
    {
        $mock = $this->createMock(EncryptionStrategyInterface::class);
        
        $testData = ['key' => 'value'];
        $encrypted = 'encrypted_data';
        
        $mock->method('encrypt')->willReturn($encrypted);
        $mock->method('decrypt')->willReturn($testData);
        $mock->method('generateSignature')->willReturn('signature');
        $mock->method('getMethodName')->willReturn('TEST-METHOD');
        
        $this->assertEquals($encrypted, $mock->encrypt($testData));
        $this->assertEquals($testData, $mock->decrypt($encrypted));
        $this->assertEquals('signature', $mock->generateSignature($testData, '123456'));
        $this->assertEquals('TEST-METHOD', $mock->getMethodName());
    }
}
