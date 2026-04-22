<?php
/**
 * Folix Game Theme - Account Dashboard Active Keys Block
 *
 * 活跃卡密区块
 * TODO: 后续需要集成CardKeys模块
 */

namespace Folix\Customer\Block\Account\Dashboard;

use Magento\Customer\Block\Account\Dashboard;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;

/**
 * Dashboard Active Keys Block
 */
class ActiveKeys extends Dashboard
{
    /**
     * 获取活跃卡密列表（限制2条）
     * TODO: 需要从CardKeys模块获取真实数据
     *
     * @return array
     */
    public function getActiveKeys(): array
    {
        // TODO: 后续集成CardKeys模块
        // 暂时返回示例数据
        return [
            [
                'key_code' => 'XXXX-YYYY-ZZZZ-1234',
                'product_name' => 'Game Currency Pack - 1000 Coins',
                'status' => 'active',
                'expires_at' => '2025-01-15'
            ],
            [
                'key_code' => 'AAAA-BBBB-CCCC-5678',
                'product_name' => 'Premium Battle Pass S5',
                'status' => 'active',
                'expires_at' => '2024-08-20'
            ]
        ];
    }

    /**
     * 获取卡密状态标签
     *
     * @param array $key
     * @return string
     */
    public function getKeyStatusLabel(array $key): string
    {
        return ucfirst($key['status']);
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
