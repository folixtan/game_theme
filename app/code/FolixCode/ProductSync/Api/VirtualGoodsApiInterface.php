<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Api;

/**
 * 虚拟商品API接口 - 业务层
 * 定义与第三方虚拟商品API交互的业务接口
 */
interface VirtualGoodsApiInterface
{
    /**
     * 获取商品列表
     * API端点: /api/user-goods/list
     *
     * @param int $limit 每页数量
     * @param int $page 页码
     * @param int $timestamp 时间戳（增量同步）
     * @return array 商品列表数据
     */
    public function getProductList(int $limit = 100, int $page = 1, int $timestamp = 0): array;

    /**
     * 获取商品分类列表
     * API端点: /api/user-goods/category
     *
     * @param int $timestamp 时间戳（增量同步）
     * @return array 分类列表数据
     */
    public function getCategoryList(int $timestamp = 0): array;

    /**
     * 获取商品详情
     * API端点: /api/user-goods/detail/{productId}
     *
     * @param string $productId 商品ID
     * @return array 商品详情数据
     */
    public function getProductDetail(string $productId): array;
}