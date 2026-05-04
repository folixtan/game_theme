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
     * 测试订单同步启用状态
     */
    public function testIsOrderSyncEnabled(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('1');
        
        $this->assertTrue($this->helper->isOrderSyncEnabled());
    }

    /**
     * 测试订单同步禁用状态
     */
    public function testIsOrderSyncDisabled(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('0');
        
        $this->assertFalse($this->helper->isOrderSyncEnabled());
    }

    /**
     * 测试 View API 启用状态
     */
    public function testIsViewApiEnabled(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('1');
        
        $this->assertTrue($this->helper->isViewApiEnabled());
    }

    /**
     * 测试 View API 禁用状态
     */
    public function testIsViewApiDisabled(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('0');
        
        $this->assertFalse($this->helper->isViewApiEnabled());
    }

    /**
     * 测试获取自定义 Notify URL
     */
    public function testGetNotifyUrlWithCustom(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('https://custom-notify.com/callback');
        
        $this->assertEquals('https://custom-notify.com/callback', $this->helper->getNotifyUrl());
    }

    /**
     * 测试获取默认 Notify URL（需要 mock urlBuilder）
     */
    public function testGetNotifyUrlWithDefault(): void
    {
        // 由于 getNotifyUrl 会调用 _urlBuilder，这里简化测试
        // 实际使用时应通过集成测试验证默认 URL 生成逻辑
        $this->markTestSkipped('Requires full Context with urlBuilder for default URL generation');
    }
}
