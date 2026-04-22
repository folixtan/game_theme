<?php
/**
 * Folix Game Theme - Account Dashboard Info Block
 *
 * 账户信息区块
 * 继承原生 Magento\Customer\Block\Account\Dashboard\Info
 */

namespace Folix\Customer\Block\Account\Dashboard;

use Magento\Customer\Block\Account\Dashboard\Info as NativeInfo;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Helper\View;
use Magento\Framework\View\Element\Template\Context;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Dashboard Info Block
 */
class Info extends NativeInfo
{
    /**
     * @param Context $context
     * @param CurrentCustomer $currentCustomer
     * @param SubscriberFactory $subscriberFactory
     * @param View $helperView
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        SubscriberFactory $subscriberFactory,
        View $helperView,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $currentCustomer,
            $subscriberFactory,
            $helperView,
            $data
        );
    }

    /**
     * 获取联系信息
     *
     * @return array
     */
    public function getContactInfo(): array
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return [];
        }
        
        return [
            'name' => $this->getName(),
            'email' => $customer->getEmail(),
            'phone' => $customer->getCustomAttribute('telephone') ? $customer->getCustomAttribute('telephone')->getValue() : ''
        ];
    }

    /**
     * 获取账户信息
     *
     * @return array
     */
    public function getAccountInfo(): array
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return [];
        }
        
        return [
            'customer_group' => $this->getCustomerGroupName($customer->getGroupId()),
            'created_at' => $customer->getCreatedAt() ? $this->getFormattedDate($customer->getCreatedAt()) : '',
        ];
    }

    /**
     * 获取客户组名称
     *
     * @param int $groupId
     * @return string
     */
    private function getCustomerGroupName(int $groupId): string
    {
        switch ($groupId) {
            case 0:
                return __('NOT LOGGED IN');
            case 1:
                return __('General');
            case 2:
                return __('Wholesale');
            case 3:
                return __('Retailer');
            default:
                return __('General');
        }
    }

    /**
     * 格式化日期
     *
     * @param string $date
     * @return string
     */
    private function getFormattedDate(string $date): string
    {
        return $this->_localeDate->formatDate(
            $this->_localeDate->scopeDate(
                $this->_storeManager->getStore(),
                $date
            ),
            \IntlDateFormatter::MEDIUM,
            false
        );
    }

    /**
     * 获取安全设置
     *
     * @return array
     */
    public function getSecuritySettings(): array
    {
        return [
            'two_factor_enabled' => false, // TODO: 集成2FA模块
            'email_verified' => true // TODO: 检查邮件验证状态
        ];
    }
}
