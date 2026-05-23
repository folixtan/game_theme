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
    public const XML_PATH_LAST_SYNC_NAME_INDEX = 'folixcode_productsync/settings/last_sync_name_index';
    public const XML_PATH_LAST_SYNC_PRODUCT_TYPE_INDEX = 'folixcode_productsync/settings/last_sync_product_type_index';
    public const XML_PATH_SYNC_NAME = 'folixcode_productsync/settings/name';

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

    /**
     * 获取产品类型筛选配置
     * 3=卡密, 4=直充
     *
     * @return array 产品类型数组，如 [3, 4]
     */
    public function getProductTypes(): array
    {
        $value = $this->scopeConfig->getValue(
            'folixcode_productsync/settings/product_types',
            ScopeInterface::SCOPE_STORE
        );

        // 如果配置为空或未设置，默认返回卡密和直充
        if (empty($value)) {
            return [3, 4]; // 默认：卡密 + 直充
        }

        // 解析逗号分隔的值，如 "3,4"
        $types = array_map('intval', explode(',', $value));
        return array_filter($types); // 移除空值
    }

    /**
     * 获取商品分类ID筛选配置
     *
     * @return int|null 分类ID，null表示不限制
     */
    public function getGoodsCategoryId(): ?int
    {
        $value = $this->scopeConfig->getValue(
            'folixcode_productsync/settings/goods_category_id',
            ScopeInterface::SCOPE_STORE
        );

        if (empty($value)) {
            return null; // 不限制分类
        }

        return (int)$value;
    }

    /**
     * 获取产品名称关键词筛选配置（逗号分隔）
     *
     * @return array 关键词数组
     */
    public function getSyncNames(): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_SYNC_NAME,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($value)) {
            return [];
        }

        // 逗号分隔，trim 去除空格，过滤空值
        $names = array_map('trim', explode(',', $value));
        return array_values(array_filter($names));
    }

    /**
     * 获取最后一次同步的关键词索引（用于断点续传）
     *
     * @return int 0-based 索引，默认0
     */
    public function getLastSyncNameIndex(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LAST_SYNC_NAME_INDEX,
            ScopeInterface::SCOPE_STORE
        ) ?: 0;
    }

    /**
     * 获取最后一次同步的产品类型索引（用于断点续传）
     *
     * @return int 0-based 索引，默认0
     */
    public function getLastSyncProductTypeIndex(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LAST_SYNC_PRODUCT_TYPE_INDEX,
            ScopeInterface::SCOPE_STORE
        ) ?: 0;
    }

}