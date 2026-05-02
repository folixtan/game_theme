<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Folix\Customer\Block\Account\Dashboard;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Dashboard Recent Orders Block
 * 
 * 负责渲染Dashboard上的最近订单列表
 * 参考: Magento\Sales\Block\Order\Recent
 */
class RecentOrders extends Template
{
    /**
     * 订单数量限制
     */
    const ORDER_LIMIT = 4;

    /**
     * @var CollectionFactoryInterface
     */
    protected $orderCollectionFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Template\Context $context
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactoryInterface $orderCollectionFactory,
        Session $customerSession,
        Config $orderConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerSession = $customerSession;
        $this->orderConfig = $orderConfig;
        $this->storeManager = $storeManager;
        $this->_isScopePrivate = true;
        parent::__construct($context, $data);
    }

    /**
     * 获取最近订单（限制3条）
     * 参考: Magento\Sales\Block\Order\Recent::getRecentOrders()
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        $customerId = $this->customerSession->getCustomerId();
        
        if (!$customerId) {
            return null;
        }

        $orders = $this->orderCollectionFactory->create($customerId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('customer_id', $customerId)
            ->addAttributeToFilter('store_id', $this->storeManager->getStore()->getId())
            ->addAttributeToFilter(
                'status',
                ['in' => $this->orderConfig->getVisibleOnFrontStatuses()]
            )
            ->addAttributeToSort('created_at', 'desc')
            ->setPageSize(self::ORDER_LIMIT)
            ->load();

        return $orders;
    }

    /**
     * 格式化价格
     * 参考: Magento\Sales\Block\Order\History
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function formatPrice($order)
    {
        return $order->formatPrice($order->getGrandTotal());
    }

    /**
     * 格式化日期
     * 参考: Magento\Sales\Block\Order\History
     *
     * @param string $date
     * @return string
     */
    public function formatOrderDate($date)
    {
        return $this->formatDate($date, \IntlDateFormatter::MEDIUM);
    }

    /**
     * 获取订单查看URL
     * 参考: Magento\Sales\Block\Order\History::getViewUrl()
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getViewUrl($order)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $order->getId()]);
    }

    /**
     * 获取重新购买URL
     * 参考: Magento\Sales\Block\Order\History::getReorderUrl()
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/order/reorder', ['order_id' => $order->getId()]);
    }

    /**
     * 判断是否可以重新购买
     * 参考: Magento\Sales\Helper\Reorder::canReorder()
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function canReorder($order)
    {
        return  \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Sales\Helper\Reorder::class)->canReorder($order->getEntityId());
    }

    /**
     * 获取所有订单URL
     *
     * @return string
     */
    public function getAllOrdersUrl()
    {
        return $this->getUrl('sales/order/history');
    }
}
