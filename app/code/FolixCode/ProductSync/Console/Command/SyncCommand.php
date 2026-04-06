<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Console\Command;

use FolixCode\ProductSync\Service\SyncManager;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 同步命令 - 用于手动触发产品同步
 * 业务层Console命令
 */
class SyncCommand extends Command
{
    public const COMMAND_NAME = 'folixcode:sync:products';

    private SyncManager $syncManager;
    private BaseHelper $baseHelper;

    public function __construct(
        SyncManager $syncManager,
        BaseHelper $baseHelper
    ) {
        $this->syncManager = $syncManager;
        $this->baseHelper = $baseHelper;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Sync products from virtual goods external API')
            ->setDefinition([
                new InputOption(
                    'type',
                    't',
                    InputOption::VALUE_OPTIONAL,
                    'Sync type: products, categories, all (default: all)',
                    'all'
                ),
                new InputOption(
                    'limit',
                    'l',
                    InputOption::VALUE_OPTIONAL,
                    'Limit number of items per page (default: 100)',
                    100
                ),
                new InputOption(
                    'page',
                    'p',
                    InputOption::VALUE_OPTIONAL,
                    'Page number (default: 1)',
                    1
                ),
                new InputOption(
                    'timestamp',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Timestamp for incremental sync (default: 0 for full sync)',
                    0
                )
            ]);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $limit = (int)$input->getOption('limit');
        $page = (int)$input->getOption('page');
        $timestamp = (int)$input->getOption('timestamp');

        $output->writeln('<info>========================================</info>');
        $output->writeln('<info>FolixCode Product Sync</info>');
        $output->writeln('<info>========================================</info>');
        $output->writeln('<comment>Type:</comment> ' . $type);
        $output->writeln('<comment>Limit:</comment> ' . $limit);
        $output->writeln('<comment>Page:</comment> ' . $page);
        $output->writeln('<comment>Timestamp:</comment> ' . ($timestamp ?: 'Full sync'));
        $output->writeln('<info>========================================</info>');

        if (!$this->baseHelper->isEnabled()) {
            $output->writeln('<error>Synchronization is disabled in configuration.</error>');
            return Cli::RETURN_FAILURE;
        }

        $startTime = microtime(true);

        try {
            $results = $this->syncManager->sync($type, [
                'limit' => $limit,
                'page' => $page,
                'timestamp' => $timestamp
            ]);

            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);
            $totalItems = $results['total'] ?? 0;

            $output->writeln('<info>========================================</info>');
            $output->writeln(sprintf('<info>Success:</info> %d items | <info>Time:</info> %.2f seconds', $totalItems, $totalTime));

            // 显示详细结果
            if (!empty($results['success'])) {
                foreach ($results['success'] as $type => $count) {
                    $output->writeln(sprintf('  <comment>✓ %s:</comment> %d items', ucfirst($type), $count));
                }
            }

            if (!empty($results['failed'])) {
                foreach ($results['failed'] as $type => $error) {
                    $output->writeln(sprintf('  <error>✗ %s:</error> %s', ucfirst($type), $error));
                }
            }

            $output->writeln('<info>========================================</info>');

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            $output->writeln('<error>Stack trace: ' . $e->getTraceAsString() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}