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
     * @param array $params 参数数组（可扩展）
     *   - limit: int 每页数量，默认100
     *   - page: int 页码，默认1
     *   - timestamp: int 时间戳（增量同步），默认0
     *   - 其他自定义参数...
     * @return array 商品列表数据
     */
    public function getProductList(array $params = []): array;

    /**
     * 获取商品分类列表
     * API端点: /api/user-goods/category
     *
     * @param array $params 参数数组（可扩展）
     *   - timestamp: int 时间戳（增量同步），默认0
     *   - 其他自定义参数...
     * @return array 分类列表数据
     */
    public function getCategoryList(array $params = []): array;

    /**
     * 获取商品详情
     * API端点: /api/user-goods/detail/{productId}
     *
     * @param string $productId 商品ID
     * @param array $params 额外参数数组（可扩展）
     *   - 可根据需要添加任何额外参数
     * @return array 商品详情数据
     */
    public function getProductDetail(string $productId, array $params = []): array;
}