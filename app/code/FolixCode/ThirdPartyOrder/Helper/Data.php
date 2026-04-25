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
    public const XML_PATH_ENABLED = 'folixcode_thirdpartyorder/general/enabled';
    public const XML_PATH_API_BASE_URL = 'folixcode_thirdpartyorder/general/api_base_url';
    public const XML_PATH_NOTIFY_URL = 'folixcode_thirdpartyorder/general/notify_url';
    public const XML_PATH_APP_KEY = 'folixcode_thirdpartyorder/general/app_key';
    public const XML_PATH_SECRET_KEY = 'folixcode_thirdpartyorder/general/secret_key';
    public const XML_PATH_RETRY_TIMES = 'folixcode_thirdpartyorder/sync/retry_times';
    public const XML_PATH_RETRY_INTERVAL = 'folixcode_thirdpartyorder/sync/retry_interval';
    public const XML_PATH_MAX_QUERY_AGE_HOURS = 'folixcode_thirdpartyorder/sync/max_query_age_hours';

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * 检查模块是否启用
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * 获取API基础URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiBaseUrl(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_API_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'https://playsentral.qr67.com';
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

    /**
     * 获取App Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAppKey(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_APP_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * 获取Secret Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSecretKey(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * 获取重试次数
     *
     * @param int|null $storeId
     * @return int
     */
    public function getRetryTimes(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_RETRY_TIMES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3;
    }

    /**
     * 获取重试间隔(秒)
     *
     * @param int|null $storeId
     * @return int
     */
    public function getRetryInterval(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_RETRY_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 60;
    }

    /**
     * 获取最大查询年龄(小时)
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxQueryAgeHours(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_QUERY_AGE_HOURS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 24;
    }
}
