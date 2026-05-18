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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\ObjectManager;

use function Ramsey\Uuid\v1;

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
    private TimezoneInterface $timezone;
    private \Magento\Framework\App\State $appState;
 
    public function __construct(
        VirtualGoodsApiService $apiService,
        PublisherInterface $publisher,
        BaseHelper $baseHelper,
        TimezoneInterface $timezone,
        \Magento\Framework\App\State $appState
    ) {
        $this->apiService = $apiService;
        $this->publisher = $publisher;
        $this->baseHelper = $baseHelper;
        $this->timezone = $timezone;
        $this->appState = $appState;
        
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
                ),
                new InputOption(
                    'product_id',
                    'pid',
                    InputOption::VALUE_OPTIONAL,
                    'Product ID for detail sync (default: null for list sync)'
                ),
                new InputOption(
                    'sku',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Product SKU for detail sync (default: null for list sync)'
                ),
                new InputOption(
                    'product_type',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Product type filter: 3=Card Key, 4=Direct Top-up. Can use comma-separated values like "3,4" (default: 3,4)'
                ),
                new InputOption(
                    'goods_category_id',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Goods category ID filter (default: null for all categories)'
                )
            ]);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
          $output->setDecorated(true);
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $type = $input->getOption('type');
        $limit = (int)$input->getOption('limit');
        $page = (int)$input->getOption('page');
        $productId = $input->getOption('product_id');
        $sku       = $input->getOption('sku');
        $timestamp = (int)$input->getOption('timestamp');
        $productType = $input->getOption('product_type');
        $goodsCategoryId = $input->getOption('goods_category_id');
        
        $timestamp = $timestamp > 0 ?: $this->timezone->date()->getTimestamp();

        $output->writeln('<info>========================================</info>');
        $output->writeln('<info>FolixCode Product Sync (MQ Publisher)</info>');
        $output->writeln('<info>========================================</info>');
        $output->writeln('<comment>Type:</comment> ' . $type);
        $output->writeln('<comment>Limit:</comment> ' . $limit);
        $output->writeln('<comment>Page:</comment> ' . $page);
        $output->writeln('<comment>Timestamp:</comment> ' . ($timestamp ?: 'Full sync'));
        
        // ✅ 显示产品类型和分类ID筛选信息
        if ($productType) {
            $output->writeln('<comment>Product Type:</comment> ' . $productType);
        } else {
            $output->writeln('<comment>Product Type:</comment> 3,4 (default: Card Key + Direct Top-up)');
        }
        
        if ($goodsCategoryId) {
            $output->writeln('<comment>Goods Category ID:</comment> ' . $goodsCategoryId);
        } else {
            $output->writeln('<comment>Goods Category ID:</comment> All categories');
        }
        
        $output->writeln('<info>Note: Data will be published to MQ, Consumer handles import</info>');
        $output->writeln('<info>========================================</info>');

        if (!$this->baseHelper->isEnabled()) {
            $output->writeln('<error>Synchronization is disabled in configuration.</error>');
            return Cli::RETURN_FAILURE;
        }

        $startTime = microtime(true);
        
       
        try {
            // 根据类型调用对应的 API 并发布到 MQ
            if ($type === 'detail') {
                $output->writeln('<comment>Fetching product detail from API...</comment>');
          
                
                  
                $importDatail = ObjectManager::getInstance()->get(\FolixCode\ProductSync\Service\ProductImporter::class);

                $importDatail->importDetail($sku);
                $output->writeln('<info>Product detail published to MQ.</info>');
                return Command::SUCCESS;
            }

            if ($type === 'products' || $type === 'all') {
                $output->writeln('<comment>Fetching products from API...</comment>');
                
                // 构建 API 请求参数
                $apiParams = [
                    'per_page' => $limit,
                    'page'    => $page,
                    'timestamp' => $timestamp
                ];
                
                // ✅ 添加产品类型筛选（支持逗号分隔，如 "3,4"）
                if ($productType) {
                    $apiParams['product_type'] = $productType;
                }

                if($productId) {
                     $apiParams['product_id'] = $productId;
                }
                
                // ✅ 添加分类ID筛选
                if ($goodsCategoryId) {
                    $apiParams['goods_category_id'] = $goodsCategoryId;
                }
                
                // 从 API 获取产品列表
                $productsData = $this->apiService->getProductList($apiParams);
                
                $output->writeln(sprintf('<comment>Found %d products, publishing to MQ...</comment>', count($productsData)));
                
                // 发布到消息队列
                $this->publisher->publishProductImport($productsData);
                
                $output->writeln(sprintf('<info>✓ Published %d products to MQ</info>', count($productsData)));
            }

            if ($type === 'categories' || $type === 'all') {
                $output->writeln('<comment>Fetching categories from API...</comment>');
                
                // 从 API 获取分类列表
                $categoriesData = $this->apiService->getCategoryList([
                    'timestamp' => $timestamp
                ]);
                
                $totalCategories = count($categoriesData);
                $output->writeln(sprintf('<comment>Found %d categories, starting import...</comment>', $totalCategories));
                
                // ✅ 修复：直接导入但采用分批处理策略避免锁竞争
                // 策略：
                // 1. 每批处理 5 个分类（减少并发压力）
                // 2. 每批之间添加短暂延迟，减轻数据库压力
                // 3. 单个分类失败不影响其他分类
                // 4. 实时显示进度
                
                $batchSize = 5; // ✅ 减小批次大小，降低锁竞争风险
                $successCount = 0;
                $failCount = 0;
                $currentBatch = 0;
                
                $import = ObjectManager::getInstance()->get(\FolixCode\ProductSync\Service\CategoryImporter::class);
                
                foreach (array_chunk($categoriesData, $batchSize, true) as $batch) {
                    $currentBatch++;
                    $batchStart = microtime(true);
                    
                    $output->writeln(sprintf(
                        '<comment>Processing batch %d/%d (%d categories)...</comment>',
                        $currentBatch,
                        ceil($totalCategories / $batchSize),
                        count($batch)
                    ));
                    
                    foreach ($batch as $id => $name) {
                        try {
                            $import->import([
                                'id'    => $id,
                                'name'  => $name
                            ]);
                            $successCount++;
                        } catch (\Exception $e) {
                            $failCount++;
                            $output->writeln(sprintf(
                                '<error>✗ Failed to import category %s: %s</error>',
                                $id,
                                $e->getMessage()
                            ));
                        }
                    }
                    
                    $batchTime = round(microtime(true) - $batchStart, 2);
                    $output->writeln(sprintf(
                        '<info>✓ Batch %d completed in %.2fs (Success: %d, Failed: %d)</info>',
                        $currentBatch,
                        $batchTime,
                        $successCount,
                        $failCount
                    ));
                    
                    // ✅ 在批次之间添加短暂延迟，减轻数据库锁竞争
                    // 如果不是最后一批，则等待 0.5 秒
                    if ($currentBatch < ceil($totalCategories / $batchSize)) {
                        usleep(500000); // 0.5 秒
                    }
                }
                
                $output->writeln('');
                $output->writeln(sprintf('<info>✓ Import completed: %d succeeded, %d failed</info>', $successCount, $failCount));
            }

            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            $output->writeln('<info>========================================</info>');
            $output->writeln(sprintf('<info>Sync completed in:</info> %.2f seconds', $totalTime));
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