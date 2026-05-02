<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Api\Data;

/**
 * API Response Transformer Interface
 * 
 * 负责将第三方 API 响应转换为标准化的内部数据结构
 */
interface ApiResponseTransformerInterface
{
    /**
     * 转换创建订单的 API 响应
     *
     * @param array $apiResponse 原始 API 响应
     * @return array 标准化数据
     *   - goods_type: int (3=卡密, 4=直充)
     *   - order_type: string ('卡密' 或 '直充')
     *   - card_info: array|null (卡密信息)
     *     - card_no: string
     *     - card_pwd: string
     *     - card_deadline: string
     *   - charge_info: array|null (直充信息)
     *     - charge_account: string
     *     - charge_region: string
     */
    public function transformCreateOrderResponse(array $apiResponse): array;

    /**
     * 转换异步通知的 API 响应
     *
     * @param array $apiResponse 原始 API 响应
     * @return array 标准化数据（结构同上）
     */
    public function transformNotificationResponse(array $apiResponse): array;
}
