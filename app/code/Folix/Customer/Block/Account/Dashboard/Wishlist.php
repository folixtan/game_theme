<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Folix\Customer\Block\Account\Dashboard;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Data\Helper\PostHelper;

/**
 * Dashboard Wishlist Block
 * 
 * 负责渲染愿望清单商品列表
 * TODO: 后续需要集成Magento_Wishlist模块
 */
class Wishlist extends Template
{
    /**
     * @var PostHelper
     */
    protected $postHelper;

    /**
     * @param Template\Context $context
     * @param PostHelper $postHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        PostHelper $postHelper,
        array $data = []
    ) {
        $this->postHelper = $postHelper;
        parent::__construct($context, $data);
    }

    /**
     * 获取愿望清单商品（限制2条）
     * TODO: 需要从Wishlist模块获取真实数据
     *
     * @return array
     */
    public function getWishlistItems(): array
    {
        // TODO: 后续集成Magento_Wishlist模块
        // 暂时返回示例数据，后续将包含add_to_cart_url和remove_url
        return [
          new \Magento\Framework\DataObject( [
                'product_id' => 123,
                'product_name' => 'Legendary Skin Bundle',
                'price' => 1288.00,
                'image_placeholder' => '🎮',
                'add_to_cart_url' => $this->getUrl('wishlist/index/cart', ['item' => 123]),
                'remove_url' => $this->getUrl('wishlist/index/remove', ['item' => 123])
            ]),
           new \Magento\Framework\DataObject(  [
                'product_id' => 456,
                'product_name' => 'Starter Pack - Beginner',
                'price' => 168.00,
                'image_placeholder' => '🎯',
                'add_to_cart_url' => $this->getUrl('wishlist/index/cart', ['item' => 456]),
                'remove_url' => $this->getUrl('wishlist/index/remove', ['item' => 456])
            ])
        ];
    }

    /**
     * 格式化价格
     *
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return '¥' . number_format($price, 2);
    }

    /**
     * 获取愿望清单URL
     *
     * @return string
     */
    public function getWishlistUrl(): string
    {
        return $this->getUrl('wishlist');
    }
}
