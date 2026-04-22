<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Folix\Customer\Block;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Helper\View;
use Magento\Framework\View\Element\Template\Context;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Custom Dashboard Block
 * 
 * 继承原生Info，保留所有原生功能
 * 职责：主容器Block，负责渲染整体布局
 * 
 * 统计数据由子Block Stats负责（Folix\Customer\Block\Account\Dashboard\Stats）
 * 订单数据由子Block RecentOrders负责（Folix\Customer\Block\Account\Dashboard\RecentOrders）
 * 卡密数据由子Block ActiveKeys负责（Folix\Customer\Block\Account\Dashboard\ActiveKeys）
 * 愿望清单由子Block Wishlist负责（Folix\Customer\Block\Account\Dashboard\Wishlist）
 * 
 * @api
 */
class Dashboard extends \Magento\Customer\Block\Account\Dashboard\Info
{
    /**
     * Constructor
     *
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
}
