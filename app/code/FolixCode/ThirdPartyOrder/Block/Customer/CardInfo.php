<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Block\Customer;

use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbCollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Card Info Block - 用于在订单详情页显示卡密信息
 */
class CardInfo extends Template
{
    /**
     * @var string
     */
    protected $_template = 'FolixCode_ThirdPartyOrder::card_info.phtml';

    /**
     * @var ThirdPartyOrderDbCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ThirdPartyOrderDbCollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ThirdPartyOrderDbCollectionFactory $collectionFactory,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->_coreRegistry = $registry;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return OrderInterface|null
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Get all third party orders for current order (only card type)
     *
     * @return \FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderDb[]
     */
    public function getThirdPartyOrders(): array
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }

        try {
            // 使用 Collection 查询指定 Magento Order ID 的订单
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('magento_order_id', (int)$order->getEntityId());
            
            // 只返回卡密类型的订单（goods_type = 3 表示卡密）
            $cardOrders = [];
            foreach ($collection as $thirdPartyOrder) {
                if ($thirdPartyOrder->getGoodsType() === 3 && 
                    $thirdPartyOrder->getSyncStatus() === 'synced' &&
                    $thirdPartyOrder->getCardNo()) {
                    $cardOrders[] = $thirdPartyOrder;
                }
            }
            
            return $cardOrders;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get third party orders', [
                'order_id' => $order->getEntityId(),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if should display card info
     *
     * @return bool
     */
    public function shouldDisplay(): bool
    {
        $orders = $this->getThirdPartyOrders();
        
        if (empty($orders)) {
            return false;
        }

        // Check if any order has synced status and valid data
        foreach ($orders as $thirdPartyOrder) {
            $syncStatus = $thirdPartyOrder->getSyncStatus();
            if ($syncStatus !== 'synced') {
                continue;
            }

            // Check if it's a card order with card info
            if ($thirdPartyOrder->getOrderType() === 'card' && $thirdPartyOrder->getCardNo()) {
                return true;
            }

            // Check if it's a direct charge order with charge account
            if ($thirdPartyOrder->getOrderType() === 'direct' && $thirdPartyOrder->getChargeAccount()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format deadline date
     *
     * @param string|null $deadline
     * @return string
     */
    public function formatDeadline(?string $deadline): string
    {
        if (!$deadline) {
            return __('N/A');
        }

        try {
            $date = new \DateTime($deadline);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $deadline;
        }
    }

    /**
     * Check if card is expired
     *
     * @param string|null $deadline
     * @return bool
     */
    public function isExpired(?string $deadline): bool
    {
        if (!$deadline) {
            return false;
        }

        try {
            $expiryDate = new \DateTime($deadline);
            $now = new \DateTime();
            return $expiryDate < $now;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get sync status label
     *
     * @param string $status
     * @return string
     */
    public function getSyncStatusLabel(string $status): string
    {
        $labels = [
            'pending' => __('Pending'),
            'synced' => __('Synced'),
            'failed' => __('Failed')
        ];

        return $labels[$status] ?? $status;
    }
}
