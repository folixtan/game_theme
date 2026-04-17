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
    public const XML_PATH_SYNC_INTERVAL = 'folixcode_productsync/settings/sync_interval';
    public const XML_PATH_LAST_SYNC_TIMESTAMP = 'folixcode_productsync/settings/last_sync_timestamp';
    public const XML_PATH_BATCH_SIZE = 'folixcode_productsync/settings/batch_size';

    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    /**
     * 获取同步间隔（分钟）
     *
     * @return int
     */
    public function getSyncInterval(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_SYNC_INTERVAL,
            ScopeInterface::SCOPE_STORE
        ) ?: 60; // 默认 60 分钟
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
     * 设置最后一次同步时间戳
     *
     * @param int $timestamp
     * @return void
     */
    public function setLastSyncTimestamp(int $timestamp): void
    {
        // TODO: 实现配置保存逻辑
        // 需要通过 Magento 的配置资源模型来持久化配置
        // 示例实现（需要注入 ConfigResource）：
        // $this->configResource->saveConfig(
        //     self::XML_PATH_LAST_SYNC_TIMESTAMP,
        //     $timestamp,
        //     ScopeInterface::SCOPE_STORE,
        //     0
        // );
        
        $this->logger->info('ProductSync last sync timestamp updated (not persisted)', [
            'timestamp' => $timestamp,
            'note' => 'Configuration persistence not implemented yet'
        ]);
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
}