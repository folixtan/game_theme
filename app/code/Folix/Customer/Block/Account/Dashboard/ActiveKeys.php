<?php
/**
 * Folix Game Theme - Account Dashboard Active Keys Block
 *
 * 活跃卡密区块
 * 从 folix_third_party_orders 表查询真实的卡密数据
 */

namespace Folix\Customer\Block\Account\Dashboard;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Block\Account\Dashboard;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Newsletter\Model\SubscriberFactory;
use Psr\Log\LoggerInterface;

/**
 * Dashboard Active Keys Block
 */
class ActiveKeys extends Dashboard
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array|null
     */
    private $cachedKeys = null;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param SubscriberFactory $subscriberFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        SubscriberFactory $subscriberFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $customerSession,
            $subscriberFactory,
            $customerRepository,
            $customerAccountManagement,
            $data
        );
    }

    /**
     * 获取活跃卡密列表（限制2条）
     *
     * @return array
     */
    public function getActiveKeys(): array
    {
        if ($this->cachedKeys === null) {
            try {
                $customerId = $this->customerSession->getCustomerId();
                
                if (!$customerId) {
                    return [];
                }
                
                $connection = $this->resourceConnection->getConnection();
                $table = $this->resourceConnection->getTableName('folix_third_party_orders');
                
                // 查询当前客户最新的卡密（直接查询，不需要关联产品）
                $select = $connection->select()
                    ->from($table, [
                        'entity_id',
                        'card_no',
                        'card_pwd',
                        'magento_order_id',
                        'card_deadline',
                        'status_code',
                        'created_at'
                    ])
                    ->where('customer_id = ?', $customerId)
                    ->where('goods_type = ?', 3)  // 卡密类型
                    ->where('sync_status = ?', 'synced')  // 同步成功
                    ->where('status_code = ?', 2)  // 成功状态
                    ->order('created_at DESC')
                    ->limit(4);  // 只显示最新2条
               
                $keys = $connection->fetchAll($select);
                
                // 格式化数据
                $this->cachedKeys = [];
           
                foreach ($keys as $key) {
                    $this->cachedKeys[] = [
                        'magento_order_id' => $key['magento_order_id'],
                        'key_code' => $key['card_no'] ?? 'N/A',
                        'card_pwd' => $key['card_pwd'] ?? 'N/A',
                        'expires_at' => $key['card_deadline'] ?? 'N/A',
                        'status' => $this->getKeyStatus($key['status_code'], $key['card_deadline']),
                        'created_at' => $key['created_at']
                    ];
                }
             
            } catch (\Exception $e) {
                $this->logger->error('Failed to get active keys', [
                    'error' => $e->getMessage()
                ]);
                
                $this->cachedKeys = [];
            }
        }
        
        return $this->cachedKeys;
    }

    /**
     * 获取卡密状态
     *
     * @param int $statusCode
     * @param string|null $deadline
     * @return string
     */
    private function getKeyStatus(int $statusCode, ?string $deadline): string
    {
        // 检查是否过期
        if ($deadline && strtotime($deadline) < time()) {
            return 'expired';
        }
        
        // 根据状态码判断
        if ($statusCode === 2) {
            return 'active';
        }
        
        return 'default';
    }

    /**
     * 获取卡密状态标签
     *
     * @param array $key
     * @return string
     */
    public function getKeyStatusLabel(array $key): string
    {
        $statusMap = [
            'active' => 'Active',
            'expired' => 'Expired',
            'used' => 'Used',
            'default' => 'Pending'
        ];
        
        return $statusMap[$key['status']] ?? 'Unknown';
    }

    /**
     * 获取卡密状态CSS类
     *
     * @param array $key
     * @return string
     */
    public function getKeyStatusClass(array $key): string
    {
        switch ($key['status']) {
            case 'active':
                return 'status-active';
            case 'used':
                return 'status-used';
            case 'expired':
                return 'status-expired';
            default:
                return 'status-default';
        }
    }
}
