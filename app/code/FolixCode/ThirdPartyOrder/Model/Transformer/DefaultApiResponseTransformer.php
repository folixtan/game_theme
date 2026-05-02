<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model\Transformer;

use FolixCode\ThirdPartyOrder\Api\Data\ApiResponseTransformerInterface;
use Psr\Log\LoggerInterface;

/**
 * Default API Response Transformer
 * 
 * 将第三方 API 响应转换为标准化的内部数据结构
 */
class DefaultApiResponseTransformer implements ApiResponseTransformerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * 转换创建订单的 API 响应
     *
     * @param array $apiResponse 原始 API 响应
     * @return array 标准化数据，包含所有需要保存到数据库的字段
     */
    public function transformCreateOrderResponse(array $apiResponse): array
    {
        try {
            $this->logger->debug('Transforming create order API response', [
                'order_id' => $apiResponse['order_id'] ?? 'unknown'
            ]);

            return $this->transformCommonData($apiResponse);
        } catch (\Exception $e) {
            $this->logger->error('Failed to transform create order response', [
                'error' => $e->getMessage(),
                'response' => $apiResponse
            ]);
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function transformNotificationResponse(array $apiResponse): array
    {
        try {
            $this->logger->debug('Transforming notification API response', [
                'order_id' => $apiResponse['order_id'] ?? 'unknown'
            ]);

            return $this->transformCommonData($apiResponse);
        } catch (\Exception $e) {
            $this->logger->error('Failed to transform notification response', [
                'error' => $e->getMessage(),
                'response' => $apiResponse
            ]);
            throw $e;
        }
    }

    /**
     * 转换通用数据（创建订单和通知共用）
     *
     * @param array $apiResponse
     * @return array 包含所有需要保存到数据库的字段
     */
    private function transformCommonData(array $apiResponse): array
    {
        // 基础字段
        $result = [
            'third_party_order_id' => $apiResponse['order_id'] ?? '',
            'order_type' => $apiResponse['order_type'] ?? '',
            'goods_type' => $this->extractGoodsType($apiResponse),
            'status_code' => (int)($apiResponse['status_code'] ?? 0)
        ];

        // 提取并合并卡密信息
        if (!empty($apiResponse['cards']) && is_array($apiResponse['cards'])) {
            $cardInfo = $this->extractCardInfo($apiResponse['cards']);
            if ($cardInfo) {
                $result['card_no'] = $cardInfo['card_no'];
                $result['card_pwd'] = $cardInfo['card_pwd'];
                $result['card_deadline'] = $cardInfo['card_deadline'];
            }
        }

        // 提取并合并直充信息
        if (!empty($apiResponse['account']) && is_array($apiResponse['account'])) {
            $chargeInfo = $this->extractChargeInfo($apiResponse['account']);
            if ($chargeInfo) {
                $result['charge_account'] = $chargeInfo['charge_account'];
                $result['charge_region'] = $chargeInfo['charge_region'];
            }
        }

        return $result;
    }

    /**
     * 提取商品类型
     *
     * @param array $apiResponse
     * @return int 3=卡密, 4=直充, 0=未知
     */
    private function extractGoodsType(array $apiResponse): int
    {
        // 优先使用 goods_type 字段（数字类型）
        if (isset($apiResponse['goods_type'])) {
            return (int)$apiResponse['goods_type'];
        }

        // 兼容旧版本：根据 order_type 字符串推断
        $orderType = $apiResponse['order_type'] ?? '';
        if ($orderType === '卡密') {
            return 3;
        } elseif ($orderType === '直充') {
            return 4;
        }

        return 0;
    }

    /**
     * 提取卡密信息（支持一维和二维数组）
     *
     * @param array $cards
     * @return array|null
     */
    private function extractCardInfo(array $cards): ?array
    {
        if (empty($cards)) {
            return null;
        }

        // 判断是一维还是二维数组
        $firstElement = $cards[0] ?? null;
        
        if (!$firstElement) {
            return null;
        }

        // 如果是二维数组（内层也是数组且包含 card_no）
        if (is_array($firstElement) && isset($firstElement[0]) && is_array($firstElement[0])) {
            // 二维结构：取第一个拆分订单的第一张卡
            $firstCard = $firstElement[0] ?? null;
        } else {
            // 一维结构：直接取第一张卡
            $firstCard = $firstElement;
        }

        if (!$firstCard || !is_array($firstCard)) {
            return null;
        }

        return [
            'card_no' => $firstCard['card_no'] ?? '',
            'card_pwd' => $firstCard['card_pwd'] ?? '',
            'card_deadline' => $firstCard['card_deadline'] ?? ''
        ];
    }

    /**
     * 提取直充信息
     *
     * @param array $account
     * @return array|null
     */
    private function extractChargeInfo(array $account): ?array
    {
        if (empty($account['charge_account'])) {
            return null;
        }

        return [
            'charge_account' => $account['charge_account'] ?? '',
            'charge_region' => $account['charge_region'] ?? ''
        ];
    }
}
