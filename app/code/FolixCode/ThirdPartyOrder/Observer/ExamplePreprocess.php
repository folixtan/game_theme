<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Example Preprocess Observer
 * 
 * 这是一个示例 Observer，演示如何监听 thirdparty_order_before_sync 事件
 * 
 * 供应商可以创建自己的 Observer 来预处理数据
 */
class ExamplePreprocess implements ObserverInterface
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
        
        if (!$data) {
            return;
        }

        $this->logger->info('Example preprocess observer triggered', [
            'entity_id' => $orderItem ? $orderItem->getItemId() : null,
            'goods_type' => $data['goods_type'] ?? null
        ]);

        // 示例：可以在这里修改数据
        // $data['custom_field'] = 'custom_value';
    }
}
