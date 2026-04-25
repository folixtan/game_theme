<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * ProductSync 业务 Helper - 处理产品同步相关的配置和状态管理
 */
class Data extends AbstractHelper
{
    /**
     * 配置路径常量
     */
    public const XML_PATH_IS_ENABLED = 'folixcode_productsync/settings/is_enabled';
    public const XML_PATH_LAST_SYNC_TIMESTAMP = 'folixcode_productsync/settings/last_sync_timestamp';
    public const XML_PATH_BATCH_SIZE = 'folixcode_productsync/settings/batch_size';
    public const XML_PATH_LAST_SYNC_PAGE = 'folixcode_productsync/settings/last_sync_page';

    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    
    /**
     * Undocumented function
     *
     * @return boolean
     */
    public function isEnabled():bool {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_IS_ENABLED,ScopeInterface::SCOPE_STORE);
    }

    /**
     * 获取最后一次同步时间戳
     *
     * @return int
     */
    public function getLastSyncTimestamp(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LAST_SYNC_TIMESTAMP,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * 获取批量处理大小
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE
        ) ?: 100; // 默认 100
    }

    /**
     * 获取最后一次同步的页码（用于断点续传）
     *
     * @return int
     */
    public function getLastSyncPage(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LAST_SYNC_PAGE,
            ScopeInterface::SCOPE_STORE
        ) ?: 1; // 默认从第1页开始
    }
}