<?php
/**
 * ThirdPartyOrder Resource Model
 */
namespace FolixCode\ThirdPartyOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ThirdPartyOrderDbResource extends AbstractDb
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('folix_third_party_orders', 'entity_id');
    }

    /**
     * @param Context $context
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * 批量插入记录（预初始化阶段使用）
     *
     * @param array $data 数据数组，每个元素包含 entity_id, magento_order_id 等字段
     * @return void
     */
    public function batchInsert(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $connection = $this->getConnection();
        
        // 自动添加时间戳
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        foreach ($data as &$item) {
            if (!isset($item['created_at'])) {
                $item['created_at'] = $now;
            }
            if (!isset($item['updated_at'])) {
                $item['updated_at'] = $now;
            }
        }
        
        $connection->insertMultiple($this->getMainTable(), $data);
    }

    /**
     * 根据 entity_id 更新记录
     *
     * @param int $entityId
     * @param array $data 要更新的字段
     * @return bool
     */
    public function updateByEntityId(int $entityId, array $data): bool
    {
        $connection = $this->getConnection();
        
        // 自动添加 updated_at
        $data['updated_at'] = $this->timezone->date()->format('Y-m-d H:i:s');
        
        $where = ['entity_id = ?' => $entityId];
        return (bool)$connection->update($this->getMainTable(), $data, $where);
    }

    /**
     * 根据 Magento Order ID 加载记录
     *
     * @param int $magentoOrderId
     * @return array|null
     */
    public function loadByMagentoOrderId(int $magentoOrderId): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('magento_order_id = ?', $magentoOrderId);
        
        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * 根据第三方订单 ID 加载记录
     *
     * @param string $thirdPartyOrderId
     * @return array|null
     */
    public function loadByThirdPartyOrderId(string $thirdPartyOrderId): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('third_party_order_id = ?', $thirdPartyOrderId);
        
        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * 标记为同步成功
     *
     * @param int $magentoOrderId
     * @param string $thirdPartyOrderId
     * @return bool
     */
    public function markSynced(int $magentoOrderId, string $thirdPartyOrderId): bool
    {
        $connection = $this->getConnection();
        $updateData = [
            'third_party_order_id' => $thirdPartyOrderId,
            'sync_status' => 'synced',
            'synced_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
            'updated_at' => $this->timezone->date()->format('Y-m-d H:i:s')
        ];

        $where = ['entity_id = ?' => $magentoOrderId];
        return (bool)$connection->update($this->getMainTable(), $updateData, $where);
    }

    /**
     * 获取订单的所有同步状态（轻量级查询，只返回 entity_id 和 sync_status）
     *
     * 使用场景：
     * - 检查订单是否全部 Item 都同步成功
     * - MQ 消费者更新状态后触发订单完成检查
     *
     * @param int $orderId Magento 订单 ID
     * @return array 格式: [['entity_id' => 1, 'sync_status' => 'synced'], ...]
     */
    public function getSyncStatusesByOrderId(int $orderId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['entity_id', 'sync_status'])
            ->where('magento_order_id = ?', $orderId);
        
        return $connection->fetchAll($select) ?: [];
    }
}
