<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
use FolixCode\ProductSync\Helper\Data as ProductSyncHelper;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Cron 任务基类
 * 
 * 提供所有 Cron 任务的公共依赖和工具方法
 */
abstract class AbstractCron
{
    protected VirtualGoodsApiService $apiService;
    protected PublisherInterface $publisher;
    protected ProductSyncHelper $productSyncHelper;
    protected BaseHelper $baseHelper;
    protected TimezoneInterface $timezone;
    protected LoggerInterface $logger;

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        ProductSyncHelper $productSyncHelper,
        BaseHelper $baseHelper,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->productSyncHelper = $productSyncHelper;
        $this->baseHelper = $baseHelper;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }

    /**
     * 检查模块是否启用
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->baseHelper->isEnabled();
    }

    /**
     * 获取最后一次同步时间戳
     *
     * @return int
     */
    protected function getLastSyncTimestamp(): int
    {
        return $this->productSyncHelper->getLastSyncTimestamp();
    }

    /**
     * 更新最后同步时间戳为当前时间
     *
     * @return void
     */
    protected function updateLastSyncTimestamp(): void
    {
        $currentTimestamp = $this->timezone->date()->getTimestamp();
        $this->productSyncHelper->setLastSyncTimestamp($currentTimestamp);
        
        $this->logger->info('Last sync timestamp updated', [
            'timestamp' => $currentTimestamp,
            'datetime' => $this->timezone->date()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * 记录 Cron 开始日志
     *
     * @param string $taskName 任务名称
     * @return void
     */
    protected function logStart(string $taskName): void
    {
        $this->logger->info(sprintf('%s: Starting synchronization...', $taskName), [
            'timestamp' => $this->timezone->date()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * 记录 Cron 完成日志
     *
     * @param string $taskName 任务名称
     * @param int $publishedCount 发布到 MQ 的数量
     * @param float $duration 执行时长（秒）
     * @return void
     */
    protected function logComplete(string $taskName, int $publishedCount, float $duration): void
    {
        $this->logger->info(sprintf('%s: Synchronization completed.', $taskName), [
            'published_to_mq' => $publishedCount,
            'duration_seconds' => round($duration, 2),
            'timestamp' => $this->timezone->date()->format('Y-m-d H:i:s'),
            'note' => 'Actual import will be handled by Consumer asynchronously'
        ]);
    }

    /**
     * 记录 Cron 错误日志
     *
     * @param string $taskName 任务名称
     * @param \Exception $e 异常对象
     * @return void
     */
    protected function logError(string $taskName, \Exception $e): void
    {
        $this->logger->error(sprintf('%s: Synchronization failed.', $taskName), [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => $this->timezone->date()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * 记录跳过日志（模块未启用）
     *
     * @param string $taskName 任务名称
     * @return void
     */
    protected function logSkipped(string $taskName): void
    {
        $this->logger->info(sprintf('%s: Synchronization is disabled, skipping.', $taskName));
    }

    /**
     * 记录无数据日志
     *
     * @param string $taskName 任务名称
     * @return void
     */
    protected function logNoData(string $taskName): void
    {
        $this->logger->info(sprintf('%s: No data to sync.', $taskName));
    }

    /**
     * 记录 API 数据获取日志
     *
     * @param string $taskName 任务名称
     * @param int $count 数据数量
     * @return void
     */
    protected function logApiDataFetched(string $taskName, int $count): void
    {
        $this->logger->info(sprintf('%s: Fetched data from API.', $taskName), [
            'count' => $count
        ]);
    }

    /**
     * 记录消息发布失败日志
     *
     * @param string $taskName 任务名称
     * @param mixed $itemId 项目 ID
     * @param \Exception $e 异常对象
     * @return void
     */
    protected function logPublishFailed(string $taskName, $itemId, \Exception $e): void
    {
        $this->logger->warning(sprintf('%s: Failed to publish item to MQ.', $taskName), [
            'item_id' => $itemId,
            'error' => $e->getMessage()
        ]);
    }
}
