<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Test\Unit\Observer;

use FolixCode\ThirdPartyOrder\Model\MessageQueue\Publisher;
use FolixCode\ThirdPartyOrder\Observer\OrderPaymentSuccess;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * OrderPaymentSuccess Observer 单元测试
 */
class OrderPaymentSuccessTest extends TestCase
{
    private Publisher $publisher;
    private LoggerInterface $logger;
    private OrderPaymentSuccess $observer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->publisher = $this->createMock(Publisher::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->observer = new OrderPaymentSuccess(
            $this->publisher,
            $this->logger
        );
    }

    /**
     * 测试正常流程 - 订单支付成功发布MQ消息
     */
    public function testExecuteWithValidPaidOrder(): void
    {
        // 准备Mock对象
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(123);
        $order->method('getIncrementId')->willReturn('100000001');
        $order->method('getState')->willReturn(Order::STATE_PROCESSING);
        
        $payment = $this->createMock(Order\Payment::class);
        $payment->method('getOrder')->willReturn($order);
        
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('payment')->willReturn($payment);
        
        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getEvent')->willReturn($event);
        
        // 验证Publisher被调用
        $this->publisher->expects($this->once())
            ->method('publishOrderSync')
            ->with([
                'order_id' => 123,
                'increment_id' => '100000001'
            ]);
        
        // 执行Observer
        $this->observer->execute($observerEvent);
    }

    /**
     * 测试空payment数据 - 应直接返回不处理
     */
    public function testExecuteWithNullPayment(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('payment')->willReturn(null);
        
        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getEvent')->willReturn($event);
        
        // Publisher不应被调用
        $this->publisher->expects($this->never())
            ->method('publishOrderSync');
        
        $this->observer->execute($observerEvent);
    }

    /**
     * 测试未支付订单 - 不应发布消息
     */
    public function testExecuteWithUnpaidOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getState')->willReturn(Order::STATE_NEW); // 未支付状态
        
        $payment = $this->createMock(Order\Payment::class);
        $payment->method('getOrder')->willReturn($order);
        
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('payment')->willReturn($payment);
        
        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getEvent')->willReturn($event);
        
        // Publisher不应被调用
        $this->publisher->expects($this->never())
            ->method('publishOrderSync');
        
        $this->observer->execute($observerEvent);
    }

    /**
     * 测试getConfig返回null时的安全处理
     */
    public function testExecuteWithNullConfig(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getState')->willReturn(Order::STATE_PROCESSING);
        $order->method('getConfig')->willReturn(null); // 模拟getConfig返回null
        $order->method('getId')->willReturn(123);
        $order->method('getIncrementId')->willReturn('100000001');
        
        $payment = $this->createMock(Order\Payment::class);
        $payment->method('getOrder')->willReturn($order);
        
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('payment')->willReturn($payment);
        
        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getEvent')->willReturn($event);
        
        // 即使getConfig返回null，也不应抛出异常
        $this->publisher->expects($this->once())
            ->method('publishOrderSync');
        
        $this->observer->execute($observerEvent);
    }

    /**
     * 测试异常捕获 - Observer不应抛出异常
     */
    public function testExecuteCatchesExceptions(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getState')->willThrowException(new \Exception('Test exception'));
        
        $payment = $this->createMock(Order\Payment::class);
        $payment->method('getOrder')->willReturn($order);
        
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('payment')->willReturn($payment);
        
        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getEvent')->willReturn($event);
        
        // 验证错误日志被记录
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to publish order sync message');
        
        // 不应抛出异常
        $this->observer->execute($observerEvent);
    }
}
