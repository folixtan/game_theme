<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource as ThirdPartyOrderResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Psr\Log\LoggerInterface;

/**
 * Order State Updater
 * 
 * 负责更新 Magento 订单状态
 */
class OrderStateUpdater
{
    private ThirdPartyOrderResource $resource;
    private OrderRepositoryInterface $orderRepository;
    private OrderConfig $orderConfig;
    private LoggerInterface $logger;

    public function __construct(
        ThirdPartyOrderResource $resource,
        OrderRepositoryInterface $orderRepository,
        OrderConfig $orderConfig,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->orderRepository = $orderRepository;
        $this->orderConfig = $orderConfig;
        $this->logger = $logger;
    }

    /**
     * 检查并标记订单为完成状态
     *
     * @param int $orderId Magento 订单 ID
     * @return void
     */
    public function checkAndMarkOrderComplete(int $orderId): void
    {
        try {
            // 1. 轻量级查询：只获取 entity_id 和 sync_status
            $syncStatuses = $this->resource->getSyncStatusesByOrderId($orderId);

            if (empty($syncStatuses)) {
                $this->logger->warning('No third party orders found for order', [
                    'order_id' => $orderId
                ]);
                return;
            }

            // 2. 在内存中判断是否全部同步成功
            $allSynced = true;
            foreach ($syncStatuses as $status) {
                if ($status['sync_status'] !== 'synced') {
                    $allSynced = false;
                    break;
                }
            }

            // 3. 如果全部同步成功，更新订单状态
            if ($allSynced) {
                $this->markOrderComplete($orderId);
            } else {
                $this->logger->info('Order not fully synced yet', [
                    'order_id' => $orderId,
                    'total_items' => count($syncStatuses),
                    'synced_items' => count(array_filter($syncStatuses, fn($s) => $s['sync_status'] === 'synced'))
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to check and mark order complete', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 标记订单为完成状态
     *
     * @param int $orderId
     * @return void
     */
    private function markOrderComplete(int $orderId): void
    {
        try {
            $order = $this->orderRepository->get($orderId);

            // 只有当订单状态不是 complete 时才更新
            if ($order->getState() !== Order::STATE_COMPLETE) {
                $order->setState(Order::STATE_COMPLETE);
                $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_COMPLETE));
                $this->orderRepository->save($order);

                $this->logger->info('Order marked as complete', [
                    'order_id' => $orderId,
                    'increment_id' => $order->getIncrementId()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark order as complete', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
