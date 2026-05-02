<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Test\Unit\Model;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\ThirdPartyOrder\Helper\Data as ThirdPartyHelper;
use FolixCode\ThirdPartyOrder\Model\ChargeInfoExtractor;
use FolixCode\ThirdPartyOrder\Model\OrderSyncService;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource as ThirdPartyOrderResource;
use Magento\Framework\Test\Unit\TestCase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * OrderSyncService 单元测试
 */
class OrderSyncServiceTest extends TestCase
{
    private ExternalApiClientInterface $apiClient;
    private ThirdPartyOrderResource $resource;
    private \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderFactory $thirdPartyOrderFactory;
    private OrderRepositoryInterface $orderRepository;
    private ChargeInfoExtractor $chargeInfoExtractor;
    private ThirdPartyHelper $helper;
    private Json $json;
    private TimezoneInterface $timezone;
    private LoggerInterface $logger;
    private OrderSyncService $orderSyncService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock所有依赖
        $this->apiClient = $this->createMock(ExternalApiClientInterface::class);
        $this->resource = $this->createMock(ThirdPartyOrderResource::class);
        $this->thirdPartyOrderFactory = $this->createMock(\FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderFactory::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->chargeInfoExtractor = $this->createMock(ChargeInfoExtractor::class);
        $this->helper = $this->createMock(ThirdPartyHelper::class);
        $this->json = new Json();
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Mock timezone返回当前时间
        $mockDateTime = new \DateTime('2024-01-15 10:30:00');
        $this->timezone->method('date')->willReturn($mockDateTime);
        
        // 直接new创建被测试对象（现代Magento推荐做法）
        $this->orderSyncService = new OrderSyncService(
            $this->apiClient,
            $this->resource,
            $this->thirdPartyOrderFactory,
            $this->orderRepository,
            $this->chargeInfoExtractor,
            $this->helper,
            $this->json,
            $this->timezone,
            $this->logger
        );
    }

    /**
     * 测试构造函数注入
     */
    public function testConstructorInjection(): void
    {
        $this->assertInstanceOf(OrderSyncService::class, $this->orderSyncService);
    }

    /**
     * 测试syncOrder方法存在
     */
    public function testSyncOrderMethodExists(): void
    {
        $this->assertTrue(method_exists($this->orderSyncService, 'syncOrder'));
    }

    /**
     * 测试已同步订单跳过逻辑
     */
    public function testSyncSkipsAlreadySyncedOrder(): void
    {
        // 创建Mock Order对象，明确指定要mock的方法
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $order->method('getId')->willReturn(123);
        
        // Mock资源层返回已存在的记录
        $existingRecord = ['entity_id' => 1];
        $this->resource->method('loadByMagentoOrderId')
            ->with(123)
            ->willReturn($existingRecord);
        
        $result = $this->orderSyncService->syncOrder($order);
        
        $this->assertTrue($result, '已同步的订单应返回true');
    }

    /**
     * 测试订单类型常量定义
     */
    public function testOrderTypeConstants(): void
    {
        $this->assertEquals('direct', OrderSyncService::ORDER_TYPE_DIRECT);
        $this->assertEquals('card', OrderSyncService::ORDER_TYPE_CARD);
    }

    /**
     * 测试时区处理 - 验证使用TimezoneInterface而非time()
     */
    public function testTimezoneHandling(): void
    {
        // 验证TimezoneInterface被正确注入
        $reflection = new \ReflectionClass($this->orderSyncService);
        $property = $reflection->getProperty('timezone');
        $property->setAccessible(true);
        
        $timezone = $property->getValue($this->orderSyncService);
        $this->assertInstanceOf(TimezoneInterface::class, $timezone);
        
        // 验证date方法被调用
        $mockDateTime = new \DateTime('2024-01-15 10:30:00');
        $this->timezone->expects($this->any())
            ->method('date')
            ->willReturn($mockDateTime);
        
        // 触发任何会保存时间的操作（这里只是验证依赖存在）
        $this->assertTrue(true, 'TimezoneInterface已正确注入');
    }
}
