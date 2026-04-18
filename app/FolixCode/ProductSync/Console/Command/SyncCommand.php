<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Console\Command;

use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use FolixCode\ProductSync\Api\Message\PublisherInterface;
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

    private VirtualGoodsApiService $apiService;
    private PublisherInterface $publisher;
    private BaseHelper $baseHelper;

    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        BaseHelper $baseHelper
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->baseHelper = $baseHelper;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Sync products from virtual goods external API (publishes to MQ)')
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
        $output->writeln('<info>FolixCode Product Sync (MQ Publisher)</info>');
        $output->writeln('<info>========================================</info>');
        $output->writeln('<comment>Type:</comment> ' . $type);
        $output->writeln('<comment>Limit:</comment> ' . $limit);
        $output->writeln('<comment>Page:</comment> ' . $page);
        $output->writeln('<comment>Timestamp:</comment> ' . ($timestamp ?: 'Full sync'));
        $output->writeln('<info>Note: Data will be published to MQ, Consumer handles import</info>');
        $output->writeln('<info>========================================</info>');

        if (!$this->baseHelper->isEnabled()) {
            $output->writeln('<error>Synchronization is disabled in configuration.</error>');
            return Cli::RETURN_FAILURE;
        }

        $startTime = microtime(true);

        try {
            $publishedCount = 0;

            // 根据类型调用对应的 API 并发布到 MQ
            if ($type === 'products' || $type === 'all') {
                $output->writeln('<comment>Fetching products from API...</comment>');
                
                // 1. 从 API 获取产品列表
                $productsData = $this->apiService->getProductList([
                    'limit' => $limit,
                    'page' => $page,
                    'timestamp' => $timestamp
                ]);
                
                $output->writeln(sprintf('<comment>Found %d products, publishing to MQ...</comment>', count($productsData)));
                
                // 2. 发布到消息队列
                foreach ($productsData as $productData) {
                    $this->publisher->publishProductImport($productData);
                    $publishedCount++;
                }
                
                $output->writeln(sprintf('<info>✓ Published %d products to MQ</info>', count($productsData)));
            }

            if ($type === 'categories' || $type === 'all') {
                $output->writeln('<comment>Fetching categories from API...</comment>');
                
                // 1. 从 API 获取分类列表
                $categoriesData = $this->apiService->getCategoryList([
                    'limit' => $limit,
                    'page' => $page,
                    'timestamp' => $timestamp
                ]);
                
                $output->writeln(sprintf('<comment>Found %d categories, publishing to MQ...</comment>', count($categoriesData)));
                
                // 2. 发布到消息队列
                foreach ($categoriesData as $categoryData) {
                    $this->publisher->publishCategoryImport($categoryData);
                    $publishedCount++;
                }
                
                $output->writeln(sprintf('<info>✓ Published %d categories to MQ</info>', count($categoriesData)));
            }

            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            $output->writeln('<info>========================================</info>');
            $output->writeln(sprintf('<info>Total Published to MQ:</info> %d items | <info>Time:</info> %.2f seconds', $publishedCount, $totalTime));
            $output->writeln('<comment>Consumer will process messages asynchronously</comment>');
            $output->writeln('<info>========================================</info>');

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            $output->writeln('<error>Stack trace: ' . $e->getTraceAsString() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}