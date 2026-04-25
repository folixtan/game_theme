<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Api;

/**
 * Third Party Order Management Interface
 * 
 * REST API接口定义,用于第三方订单管理
 * @api
 */
interface ThirdPartyOrderManagementInterface
{
    /**
     * 接收第三方订单状态通知(异步回调)
     * 
     * POST /rest/V1/thirdpartyorders/notification
     *
     * @param array $notificationData 通知数据
     * @return bool true表示处理成功
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleNotification(array $notificationData): bool;

    /**
     * 根据Magento订单ID获取第三方订单信息
     * 
     * GET /rest/V1/thirdpartyorders/:magentoOrderId
     *
     * @param int $magentoOrderId Magento订单ID
     * @return array|null 第三方订单信息
     */
    public function getByMagentoOrderId(int $magentoOrderId): ?array;

    /**
     * 根据第三方订单ID获取订单信息
     * 
     * GET /rest/V1/thirdpartyorders/third-party/:thirdPartyOrderId
     *
     * @param string $thirdPartyOrderId 第三方订单ID
     * @return array|null 订单信息
     */
    public function getByThirdPartyOrderId(string $thirdPartyOrderId): ?array;
}
