<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Test\Unit\Helper;

use FolixCode\ThirdPartyOrder\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

/**
 * ThirdPartyOrder Helper 单元测试
 */
class DataTest extends TestCase
{
    private ScopeConfigInterface $scopeConfig;
    private UrlInterface $urlBuilder;
    private Data $helper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        
        // 使用ObjectManager Helper创建Context
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $context = $objectManagerHelper->getObject(
            \Magento\Framework\App\Helper\Context::class,
            ['scopeConfig' => $this->scopeConfig]
        );
        
        $this->helper = new Data($context);
    }

    /**
     * 测试模块启用状态
     */
    public function testIsEnabled(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('1');
        
        $this->assertTrue($this->helper->isEnabled());
    }

    /**
     * 测试模块禁用状态
     */
    public function testIsDisabled(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('0');
        
        $this->assertFalse($this->helper->isEnabled());
    }

    /**
     * 测试获取API Base URL
     */
    public function testGetApiBaseUrl(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('https://test-api.com');
        
        $this->assertEquals('https://test-api.com', $this->helper->getApiBaseUrl());
    }

    /**
     * 测试API Base URL默认值
     */
    public function testGetApiBaseUrlWithDefault(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn(null);
        
        $this->assertEquals('https://playsentral.qr67.com', $this->helper->getApiBaseUrl());
    }

    /**
     * 测试获取App Key
     */
    public function testGetAppKey(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('test-app-key');
        
        $this->assertEquals('test-app-key', $this->helper->getAppKey());
    }

    /**
     * 测试获取Secret Key
     */
    public function testGetSecretKey(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('encrypted-secret-key');
        
        $this->assertEquals('encrypted-secret-key', $this->helper->getSecretKey());
    }

    /**
     * 测试获取重试次数
     */
    public function testGetRetryTimes(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn(3);
        
        $this->assertEquals(3, $this->helper->getRetryTimes());
    }

    /**
     * 测试重试次数默认值
     */
    public function testGetRetryTimesWithDefault(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn(null);
        
        $this->assertEquals(3, $this->helper->getRetryTimes());
    }

    /**
     * 测试获取重试间隔
     */
    public function testGetRetryInterval(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn(60);
        
        $this->assertEquals(60, $this->helper->getRetryInterval());
    }

    /**
     * 测试最大查询年龄
     */
    public function testGetMaxQueryAgeHours(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn(24);
        
        $this->assertEquals(24, $this->helper->getMaxQueryAgeHours());
    }
}
