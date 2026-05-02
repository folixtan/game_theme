<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;
use FolixCode\BaseSyncService\Api\VendorConfigInterface;
use FolixCode\BaseSyncService\Model\ApiClient;
use Psr\Log\LoggerInterface;

/**
 * Mock API Client - 用于本地测试
 * 
 * 在开发环境下返回模拟的第三方 API 响应数据，无需真实调用第三方接口
 */
class MockApiClient extends ApiClient
{
    private LoggerInterface $logger;

    public function __construct(
        VendorConfigInterface $vendorConfig,
        EncryptionStrategyInterface $encryptionStrategy,
        LoggerInterface $logger,
        array $guzzleConfig = []
    ) {
        parent::__construct($vendorConfig, $encryptionStrategy, $logger, $guzzleConfig);
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        $this->logger->info('Using Mock API Client', [
            'url' => $url,
            'request_data' => $data
        ]);

        // 根据不同的 URL 返回不同的 Mock 数据
        if (strpos($url, '/api/user-order/create') !== false) {
            return $this->getMockCreateOrderResponse($data);
        }

        // 默认返回成功响应
        return ['status' => 1, 'message' => 'success', 'data' => []];
    }

    /**
     * 生成创建订单的 Mock 响应
     *
     * @param array $requestData
     * @return array
     */
    private function getMockCreateOrderResponse(array $requestData): array
    {
        // 模拟加密后的响应数据（实际使用时需要根据加密策略调整）
        $mockResponse = $this->generateMockOrderData($requestData);

        $this->logger->info('Mock Create Order Response Generated', [
            'user_order_id' => $requestData['user_order_id'] ?? 'unknown',
            'product_id' => $requestData['product_id'] ?? 'unknown'
        ]);

        return $mockResponse;
    }

    /**
     * 生成模拟订单数据
     *
     * @param array $requestData
     * @return array
     */
    private function generateMockOrderData(array $requestData): array
    {
        $userOrderId = $requestData['user_order_id'] ?? 'test_' . time();
        $productId = $requestData['product_id'] ?? 'TEST_PRODUCT';
        $chargeType = $requestData['charge_type'] ?? '3'; // 默认为卡密类型

        // 根据 charge_type 生成不同类型的数据
        if ($chargeType == '3') {
            // 卡密类型
            return $this->generateCardMockData($userOrderId, $productId);
        } elseif ($chargeType == '4') {
            // 直充类型
            return $this->generateDirectChargeMockData($userOrderId, $productId, $requestData);
        }

        // 默认返回卡密数据
        return $this->generateCardMockData($userOrderId, $productId);
    }

    /**
     * 生成卡密类型的 Mock 数据
     *
     * @param string $userOrderId
     * @param string $productId
     * @return array
     */
    private function generateCardMockData(string $userOrderId, string $productId): array
    {
        return [
            'order_id' => 'ps' . bin2hex(random_bytes(16)),
            'user_order_id' => $userOrderId,
            'product_id' => $productId,
            'product_name' => '卡密测试商品-' . $productId,
            'buy_num' => 1,
            'goods_currency' => 'USD',
            'goods_price' => '1.1000',
            'goods_amount' => '1.1000',
            'goods_type' => 3,  // 3 = 卡密
            'cny_price' => '7.9480',
            'cny_amount' => '7.9480',
            'usd_rate' => '0.138400',
            'usd_price' => '1.1000',
            'usd_amount' => '1.1000',
            'currency' => 'USD',
            'currency_rate' => '1.000000',
            'currency_price' => '1.1000',
            'currency_amount' => '1.1000',
            'order_type' => '卡密',
            'status' => '处理中',
            'status_code' => 2,
            'desc_code' => 0,
            'description' => 'Success',
            'order_max_amount' => '0.0000',
            'order_max_currency' => '',
            'split_type' => 0,
            'split_num_total' => 1,
            'is_part_succ' => 0,
            'currency_consume_amount' => '1.1000',
            'currency_unfreeze_amount' => '0.0000',
            'split_orders' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            // 使用一维数组结构（更常见）
            'cards' => [
                [
                    'card_no' => 'CARD-' . strtoupper(bin2hex(random_bytes(8))),
                    'card_pwd' => 'PWD-' . strtoupper(bin2hex(random_bytes(6))),
                    'card_deadline' => date('Y-m-d', strtotime('+1 year'))
                ]
            ]
            // 如果第三方返回二维数组，取消下面注释并注释上面：
            /*
            'cards' => [
                [
                    [
                        'card_no' => 'CARD-' . strtoupper(bin2hex(random_bytes(8))),
                        'card_pwd' => 'PWD-' . strtoupper(bin2hex(random_bytes(6))),
                        'card_deadline' => date('Y-m-d', strtotime('+1 year'))
                    ]
                ]
            ]
            */
        ];
    }

    /**
     * 生成直充类型的 Mock 数据
     *
     * @param string $userOrderId
     * @param string $productId
     * @param array $requestData
     * @return array
     */
    private function generateDirectChargeMockData(string $userOrderId, string $productId, array $requestData): array
    {
        $chargeAccount = $requestData['charge_account'] ?? 'test_account_' . rand(100000, 999999);
        $chargeRegion = $requestData['charge_region'] ?? 'CN';

        return [
            'order_id' => 'ps' . bin2hex(random_bytes(16)),
            'user_order_id' => $userOrderId,
            'product_id' => $productId,
            'product_name' => '直充测试商品-' . $productId,
            'buy_num' => 1,
            'goods_currency' => 'USD',
            'goods_price' => '13.2000',
            'goods_amount' => '13.2000',
            'goods_type' => 4,  // 4 = 直充
            'cny_price' => '95.8861',
            'cny_amount' => '95.8861',
            'usd_rate' => '0.137663',
            'usd_price' => '13.2000',
            'usd_amount' => '13.2000',
            'currency' => 'CNY',
            'currency_rate' => '7.264100',
            'currency_price' => '95.8861',
            'currency_amount' => '95.8861',
            'order_type' => '直充',
            'status' => '处理中',
            'status_code' => 2,
            'desc_code' => 0,
            'description' => 'Partial success',
            'order_max_amount' => '0.0000',
            'order_max_currency' => '',
            'split_type' => 2,
            'split_num_total' => 2,
            'is_part_succ' => 1,
            'currency_consume_amount' => '43.5846',
            'currency_unfreeze_amount' => '52.3015',
            'split_orders' => [
                [
                    'split_order_no' => 'ps' . bin2hex(random_bytes(16)) . '-1',
                    'product_id' => '4',
                    'goods_name' => '线下直充测试-1',
                    'split_currency_amount' => '43.5846',
                    'deliver_status' => 0
                ],
                [
                    'split_order_no' => 'ps' . bin2hex(random_bytes(16)) . '-2',
                    'product_id' => '19',
                    'goods_name' => '线下直充测试-2',
                    'split_currency_amount' => '52.3015',
                    'deliver_status' => 0
                ]
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'account' => [
                'charge_account' => $chargeAccount,
                'charge_password' => '',
                'charge_game' => null,
                'charge_region' => $chargeRegion,
                'charge_server' => null,
                'charge_type' => null,
                'buyer_ip' => null,
                'role_name' => null,
                'contact_phone' => null,
                'contact_qq' => null
            ]
        ];
    }
}
