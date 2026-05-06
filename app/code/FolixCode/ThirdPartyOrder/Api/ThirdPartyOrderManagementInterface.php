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
     * @param mixed $data 通知数据
     * @return int 200e表示处理成功
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleNotification(string $data): int;

    
}
