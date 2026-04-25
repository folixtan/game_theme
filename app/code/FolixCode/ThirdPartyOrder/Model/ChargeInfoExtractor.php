<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Psr\Log\LoggerInterface;

/**
 * Charge Info Extractor
 * 
 * 从订单中提取充值信息(charge_account, charge_region等)
 * 这些信息来自产品详情页的charge_template表单
 */
class ChargeInfoExtractor
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * 从订单中提取充值信息
     *
     * @param OrderInterface $order
     * @return array|null
     */
    public function extractFromOrder(OrderInterface $order): ?array
    {
        // 遍历订单项,查找第一个有充值信息的项
        foreach ($order->getAllVisibleItems() as $item) {
            $chargeInfo = $this->extractFromOrderItem($item);
            if ($chargeInfo) {
                return $chargeInfo;
            }
        }

        return null;
    }

    /**
     * 从订单项中提取充值信息
     *
     * @param OrderItemInterface $item
     * @return array|null
     */
    public function extractFromOrderItem(OrderItemInterface $item): ?array
    {
        try {
            // 方式1: 从product_options中获取
            $productOptions = $item->getProductOptions();
            if (is_array($productOptions) && isset($productOptions['additional_data'])) {
                $additionalData = $productOptions['additional_data'];
                
                if (is_string($additionalData)) {
                    $additionalData = json_decode($additionalData, true);
                }

                if (is_array($additionalData)) {
                    $chargeInfo = $this->parseChargeInfo($additionalData);
                    if ($chargeInfo) {
                        $this->logger->info('Extracted charge info from product_options', [
                            'order_item_id' => $item->getId(),
                            'charge_info' => $chargeInfo
                        ]);
                        return $chargeInfo;
                    }
                }
            }

            // 方式2: 从buy_request中获取
            $buyRequest = $item->getBuyRequest();
            if ($buyRequest && is_object($buyRequest)) {
                $requestData = $buyRequest->getData();
                if (is_array($requestData)) {
                    $chargeInfo = $this->parseChargeInfo($requestData);
                    if ($chargeInfo) {
                        $this->logger->info('Extracted charge info from buy_request', [
                            'order_item_id' => $item->getId(),
                            'charge_info' => $chargeInfo
                        ]);
                        return $chargeInfo;
                    }
                }
            }

            // 方式3: 从自定义字段获取(TODO: 根据实际实现调整)
            // $chargeAccount = $item->getData('charge_account');
            // $chargeRegion = $item->getData('charge_region');

            $this->logger->debug('No charge info found in order item', [
                'order_item_id' => $item->getId()
            ]);

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to extract charge info', [
                'order_item_id' => $item->getId(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 解析充值信息
     *
     * @param array $data
     * @return array|null
     */
    private function parseChargeInfo(array $data): ?array
    {
        $chargeAccount = $data['charge_account'] ?? $data['chargeAccount'] ?? null;
        $chargeRegion = $data['charge_region'] ?? $data['chargeRegion'] ?? null;

        if (empty($chargeAccount)) {
            return null;
        }

        return [
            'charge_account' => (string)$chargeAccount,
            'charge_region' => (string)($chargeRegion ?: '')
        ];
    }
}
