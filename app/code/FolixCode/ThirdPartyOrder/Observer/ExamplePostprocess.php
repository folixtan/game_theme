<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Example Postprocess Observer
 * 
 * 这是一个示例 Observer，演示如何监听 thirdparty_order_after_sync_success 事件
 * 
 * 供应商可以创建自己的 Observer 来执行后续操作（如发送邮件、更新库存等）
 */
class ExamplePostprocess implements ObserverInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        // 获取传递的数据
        $data = $observer->getData('data');
        $orderItem = $observer->getData('order_item');
        $entityId = $observer->getData('entity_id');
        
        if (!$data) {
            return;
        }

        $this->logger->info('Example postprocess observer triggered', [
            'entity_id' => $entityId,
            'goods_type' => $data['goods_type'] ?? null,
            'third_party_order_id' => $data['third_party_order_id'] ?? null
        ]);

        // 示例：可以在这里执行额外逻辑
        // - 发送通知邮件
        // - 更新库存
        // - 触发积分奖励
    }
}
