<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrder;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Third Party Order Resource Model
 */
class ThirdPartyOrder extends AbstractDb
{
    private TimezoneInterface $timezone;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        TimezoneInterface $timezone,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->timezone = $timezone;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('folix_third_party_orders', 'entity_id');
    }

    /**
     * 根据Magento订单ID加载记录
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
     * 根据第三方订单ID加载记录
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
     * 更新订单状态
     *
     * @param int $magentoOrderId
     * @param int $statusCode
     * @param array $data
     * @return bool
     */
    public function updateOrderStatus(int $magentoOrderId, int $statusCode, array $data = []): bool
    {
        $connection = $this->getConnection();
        $updateData = array_merge($data, [
            'status_code' => $statusCode,
            'updated_at' => $this->timezone->date()->format('Y-m-d H:i:s')
        ]);

        $where = ['magento_order_id = ?' => $magentoOrderId];
        return (bool)$connection->update($this->getMainTable(), $updateData, $where);
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

        $where = ['magento_order_id = ?' => $magentoOrderId];
        return (bool)$connection->update($this->getMainTable(), $updateData, $where);
    }
}
