<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Test\Unit\Helper;

use FolixCode\ProductSync\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * ProductSync Helper 单元测试
 */
class DataTest extends TestCase
{
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private Data $helper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);
        
        $this->helper = new Data($context, $this->logger);
    }

    /**
     * 测试获取同步间隔
     */
    public function testGetSyncInterval(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(120);
        
        $this->assertEquals(120, $this->helper->getSyncInterval());
    }

    /**
     * 测试同步间隔默认值
     */
    public function testGetSyncIntervalWithDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);
        
        $this->assertEquals(60, $this->helper->getSyncInterval());
    }

    /**
     * 测试获取最后同步时间戳
     */
    public function testGetLastSyncTimestamp(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(1234567890);
        
        $this->assertEquals(1234567890, $this->helper->getLastSyncTimestamp());
    }

    /**
     * 测试设置最后同步时间戳
     */
    public function testSetLastSyncTimestamp(): void
    {
        $timestamp = time();
        
        // 验证不抛出异常
        $this->helper->setLastSyncTimestamp($timestamp);
        
        $this->assertTrue(true);
    }

    /**
     * 测试获取批量大小
     */
    public function testGetBatchSize(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(50);
        
        $this->assertEquals(50, $this->helper->getBatchSize());
    }

    /**
     * 测试批量大小默认值
     */
    public function testGetBatchSizeWithDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);
        
        $this->assertEquals(100, $this->helper->getBatchSize());
    }
}
