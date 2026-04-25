<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Observer;

use FolixCode\ThirdPartyOrder\Model\MessageQueue\Publisher;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Order Payment Success Observer
 * 
 * 监听订单支付成功事件,发布MQ消息异步同步到第三方
 * 
 * 监听事件: sales_order_payment_place_end 或 checkout_submit_all_after
 */
class OrderPaymentSuccess implements ObserverInterface
{
    private Publisher $publisher;
    private LoggerInterface $logger;

    public function __construct(
        Publisher $publisher,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var Order\Payment|null $payment */
            $payment = $observer->getEvent()->getData('payment');
            
            if (!$payment || !$payment instanceof Order\Payment) {
                return;
            }

            /** @var Order $order */
            $order = $payment->getOrder();

            // 检查订单状态
            if (!$this->isOrderPayed($order)) {
                return;
            }

            $this->logger->info('Order payment success, publishing sync message', [
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId()
            ]);

            // 发布MQ消息,异步处理
            $this->publisher->publishOrderSync([
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId()
            ]);

        } catch (\Exception $e) {
            // 不阻塞主流程,只记录错误
            $this->logger->error('Failed to publish order sync message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 检查订单是否已支付
     *
     * @param Order $order
     * @return bool
     */
    private function isOrderPayed(Order $order): bool
    {
        // 检查订单状态是否为已支付
        if ($order->getState() === Order::STATE_PROCESSING) {
            return true;
        }
        
        // 安全地获取默认状态
        $config = $order->getConfig();
        if ($config) {
            $defaultStatus = $config->getStateDefaultStatus(Order::STATE_PROCESSING);
            if ($defaultStatus && $order->getStatus() === $defaultStatus) {
                return true;
            }
        }
        
        return false;
    }
}
