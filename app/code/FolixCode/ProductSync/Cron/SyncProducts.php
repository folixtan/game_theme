<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Cron;

use FolixCode\ProductSync\Service\SyncManager;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Cron任务 - 定时同步虚拟商品数据
 * 业务层Cron任务
 */
class SyncProducts
{
    private SyncManager $syncManager;
    private BaseHelper $baseHelper;
    private LoggerInterface $logger;
    private LoggerInterface $cronLogger;
    private int $syncInterval;

    public function __construct(
        SyncManager $syncManager,
        BaseHelper $baseHelper,
        LoggerInterface $logger,
        int $syncInterval = 60
    ) {
        $this->syncManager = $syncManager;
        $this->baseHelper = $baseHelper;
        $this->logger = $logger;
        $this->syncInterval = $syncInterval;

        // 创建独立的Cron日志记录器
        $this->cronLogger = new Logger('sync_cron');
        $logPath = BP . '/var/log/sync_cron.log';
        $this->cronLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * 执行定时同步任务
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            // 检查是否启用
            if (!$this->baseHelper->isEnabled()) {
                $this->cronLogger->info('Synchronization is disabled, skipping.');
                $this->logger->info('ProductSync Cron: Synchronization is disabled, skipping.');
                return;
            }

            // 检查同步间隔
            $configuredInterval = $this->baseHelper->getSyncInterval();
            if ($configuredInterval > 0) {
                $this->syncInterval = $configuredInterval;
            }

            $this->cronLogger->info('Starting scheduled synchronization...');
            $this->logger->info('ProductSync Cron: Starting scheduled synchronization...');

            // 获取最后一次同步时间戳（增量同步）
            $lastSyncTimestamp = $this->baseHelper->getLastSyncTimestamp();
            $currentTimestamp = time();

            // 如果距离上次同步时间小于间隔，则跳过
            if ($lastSyncTimestamp > 0 && ($currentTimestamp - $lastSyncTimestamp) < ($this->syncInterval * 60)) {
                $this->cronLogger->info(sprintf(
                    'Skipping sync, last sync was %d minutes ago (interval: %d minutes)',
                    round(($currentTimestamp - $lastSyncTimestamp) / 60),
                    $this->syncInterval
                ));
                $this->logger->info(sprintf(
                    'ProductSync Cron: Skipping sync, last sync was %d minutes ago (interval: %d minutes)',
                    round(($currentTimestamp - $lastSyncTimestamp) / 60),
                    $this->syncInterval
                ));
                return;
            }

            // 调用同步管理器执行同步
            $results = $this->syncManager->sync(
                'all',
                ['timestamp' => $lastSyncTimestamp, 'limit' => 100]
            );

            $this->cronLogger->info('Synchronization completed.', ['results' => $results]);
            $this->logger->info('ProductSync Cron: Synchronization completed.', [
                'results' => $results
            ]);

        } catch (\Exception $e) {
            $this->cronLogger->error('Synchronization failed', ['error' => $e->getMessage()]);
            $this->logger->error('ProductSync Cron: Synchronization failed - ' . $e->getMessage());
            // 这里可以添加错误通知逻辑
        }
    }
}