<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Third Party Order Helper
 */
class Data extends AbstractHelper
{
    /** 配置路径常量 */
    public const XML_PATH_ENABLE_ORDER_SYNC = 'folixcode_thirdpartyorder/general/enable_order_sync';
    public const XML_PATH_ENABLE_VIEW_API = 'folixcode_thirdpartyorder/general/enable_view_api';
    public const XML_PATH_NOTIFY_URL = 'folixcode_thirdpartyorder/general/notify_url';

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * 检查订单同步是否启用
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isOrderSyncEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_ORDER_SYNC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * 检查 View 页面 API 是否启用
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isViewApiEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_VIEW_API,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * 获取通知回调URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getNotifyUrl(?int $storeId = null): string
    {
        $customUrl = (string)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFY_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!empty($customUrl)) {
            return $customUrl;
        }

        // 自动生成默认URL
        $baseUrl = $this->_urlBuilder->getBaseUrl();
        return rtrim($baseUrl, '/') . '/rest/V1/thirdpartyorders/notification';
    }
}
