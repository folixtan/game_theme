<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Folix\Customer\Block\Account;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Game Keys List Block
 */
class GameKeys extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Folix_Customer::account/game_keys.phtml';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbCollectionFactory
     */
    private $thirdPartyOrderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbCollectionFactory $thirdPartyOrderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbCollectionFactory $thirdPartyOrderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->thirdPartyOrderCollectionFactory = $thirdPartyOrderCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Game Keys'));
    }

    /**
     * Get customer's game keys collection with pagination
     *
     * @return \FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbCollection|false
     */
    public function getGameKeys()
    {
        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return false;
        }

        // 直接获取客户的卡密集合（goods_type = 3 表示卡密）
        $collection = $this->thirdPartyOrderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('goods_type', 3)
            ->setOrder('created_at', 'desc');

        return $collection;
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        $gameKeys = $this->getGameKeys();
        if ($gameKeys) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'folix.customer.gamekeys.pager'
            )->setCollection(
                $gameKeys
            );
            $this->setChild('pager', $pager);
            $gameKeys->load();
        }
        
        return $this;
    }

    /**
     * Get Pager child block output
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Get order view URL
     *
     * @param int $orderId
     * @return string
     */
    public function getOrderViewUrl($orderId)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * Get message for empty list
     *
     * @return \Magento\Framework\Phrase
     */
    public function getEmptyMessage()
    {
        return __('You have no game keys yet.');
    }
}
