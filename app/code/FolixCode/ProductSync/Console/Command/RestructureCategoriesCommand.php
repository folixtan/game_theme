<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * 移动分类到对应层级命令 - 基于产品属性（game_charge_type）移动分类到 Level 5
 * 
 * 核心逻辑：
 * 1. 读取 CreateCategoryStructureCommand 生成的 CSV 映射文件
 * 2. 查询每个分类下产品的 game_charge_type
 * 3. 根据 charge_type + 分类名称映射到对应的 Level 5
 * 4. 使用 CategoryManagement::move() 移动分类
 */
class RestructureCategoriesCommand extends Command
{
    public const COMMAND_NAME = 'folixcode:restructure:categories';
    private const CSV_MAPPING_FILE = BP.'/var/category_mapping.csv';

    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryManagementInterface $categoryManagement;
    private ProductCollectionFactory $productCollectionFactory;
    private CategoryFactory $categoryFactory;
    private LoggerInterface $logger;

    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryManagementInterface $categoryManagement,
        ProductCollectionFactory $productCollectionFactory,
        CategoryFactory $categoryFactory,
        LoggerInterface $logger
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryManagement = $categoryManagement;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Move categories to Level 5 based on product attributes (game_charge_type)')
            ->setDefinition([
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only'),
                new InputOption('execute', 'e', InputOption::VALUE_NONE, 'Execute restructuring'),
            ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = !$input->getOption('execute');
        
        $output->writeln('========================================');
        $output->writeln('Category Restructuring Tool (Move to Level 5)');
        $output->writeln('Mode: ' . ($isDryRun ? 'DRY RUN' : 'EXECUTE'));
        $output->writeln('========================================');
        $output->writeln('');

        // Step 1: 读取 CSV 映射文件
        $output->writeln('<info>Step 1: Reading category mapping...</info>');
        $categoryMapping = $this->readCategoryMapping($output);

        // Step 2: 分析并移动分类
        $output->writeln('');
        $output->writeln('<info>Step 2: Moving categories...</info>');
        $moveResult = $this->moveCategories($output, $categoryMapping, $isDryRun);

        // 总结
        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('✓ Completed! Moved ' . $moveResult['total_moved'] . ' categories, skipped ' . $moveResult['total_skipped']);
        $output->writeln('========================================');

        return 0;
    }

    /**
     * 读取 CSV 映射文件
     */
    private function readCategoryMapping(OutputInterface $output): array
    {
        $output->writeln("  Checking file: " . self::CSV_MAPPING_FILE);
        
        if (!file_exists(self::CSV_MAPPING_FILE)) {
            $output->writeln('<error>CSV mapping file not found: ' . self::CSV_MAPPING_FILE);
            $output->writeln('Please run: php bin/magento folixcode:create:category-structure first');
            return [];
        }

        $output->writeln("  File exists, size: " . filesize(self::CSV_MAPPING_FILE) . " bytes");

        $mapping = [];
        $fp = fopen(self::CSV_MAPPING_FILE, 'r');
        $header = fgetcsv($fp, 0, ',', '"', '\\'); // 跳过表头

        while (($row = fgetcsv($fp, 0, ',', '"', '\\')) !== false) {
            [$level3Name, $level4Name, $level5Name, $level5Id] = $row;
            
            $mapping[$level5Name] = [
                'level3' => $level3Name,
                'level4' => $level4Name,
                'level5' => $level5Name,
                'id' => (int)$level5Id,
            ];
        }

        fclose($fp);
        $output->writeln("  Loaded " . count($mapping) . " Level 5 categories from CSV");
        return $mapping;
    }

    /**
     * 分析并移动分类
     */
    private function moveCategories(OutputInterface $output, array $categoryMapping, bool $isDryRun): array
    {
        // 获取所有需要移动的分类（parent_id = 2, entity_id > 6, <= 811）
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name'])
            ->addAttributeToFilter('parent_id', 2)
            ->addAttributeToFilter('entity_id', ['gt' => 6])
            ->addAttributeToFilter('entity_id', ['lteq' => 811])
            ->setOrder('entity_id', 'ASC');

        $totalMoved = 0;
        $totalSkipped = 0;

        $output->writeln("Found {$collection->count()} categories to move");

        foreach ($collection as $category) {
            $categoryId = (int)$category->getId();
            $categoryName = $category->getName();

            // 获取该分类下产品的 game_charge_type
            $chargeType = $this->getCategoryChargeType($categoryId);

            // 映射到 Level 5
            $targetLevel5Name = $this->mapCategoryToLevel5($categoryName, $chargeType);

            if (!isset($categoryMapping[$targetLevel5Name])) {
                $output->writeln("  [SKIP] {$categoryName} (ID: {$categoryId}) - Target '{$targetLevel5Name}' not found in mapping");
                $totalSkipped++;
                continue;
            }

            $targetInfo = $categoryMapping[$targetLevel5Name];
            $targetLevel5Id = $targetInfo['id'];

            if ($isDryRun) {
                $output->writeln("  [DRY RUN] {$categoryName} (ID: {$categoryId}) → {$targetInfo['level3']} > {$targetInfo['level4']} > {$targetInfo['level5']}");
                $totalMoved++;
            } else {
                try {
                    $this->categoryManagement->move($categoryId, $targetLevel5Id, null);
                    $output->writeln("  ✓ Moved: {$categoryName} → {$targetInfo['level3']} > {$targetInfo['level4']} > {$targetInfo['level5']}");
                    $totalMoved++;
                } catch (\Exception $e) {
                    $output->writeln("  ✗ Failed: {$categoryName} - " . $e->getMessage());
                    $this->logger->error("Failed to move category '{$categoryName}' (ID: {$categoryId}): " . $e->getMessage());
                    $totalSkipped++;
                }
            }
        }

        return ['total_moved' => $totalMoved, 'total_skipped' => $totalSkipped];
    }

    /**
     * 获取分类下产品的 game_charge_type
     */
    private function getCategoryChargeType(int $categoryId): ?int
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addCategoryFilter($this->categoryFactory->create()->load($categoryId))
                ->addAttributeToSelect('game_charge_type')
                ->setPageSize(10);

            $typeCounts = [3 => 0, 4 => 0];
            foreach ($collection as $product) {
                $chargeType = (int)$product->getData('game_charge_type');
                if (isset($typeCounts[$chargeType])) {
                    $typeCounts[$chargeType]++;
                }
            }

            if ($typeCounts[3] > $typeCounts[4]) {
                return 3;
            } elseif ($typeCounts[4] > $typeCounts[3]) {
                return 4;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 将分类映射到 Level 5 名称
     */
    private function mapCategoryToLevel5(string $categoryName, ?int $chargeType): string
    {
        if ($chargeType == 3) {
            // 卡密类
            if (stripos($categoryName, 'Steam') !== false) return 'Steam';
            if (stripos($categoryName, 'PlayStation') !== false || stripos($categoryName, 'PSN') !== false) return 'PlayStation';
            if (stripos($categoryName, 'Xbox') !== false) return 'Xbox';
            if (stripos($categoryName, 'Nintendo') !== false || stripos($categoryName, 'Switch') !== false) return 'Nintendo';
            if (stripos($categoryName, 'Razer') !== false) return 'Razer';
            if (stripos($categoryName, 'Riot') !== false || stripos($categoryName, 'Valorant') !== false) return 'Riot Games';
            if (stripos($categoryName, 'Roblox') !== false) return 'Roblox';
            if (stripos($categoryName, 'EA') !== false && stripos($categoryName, 'Sports') === false) return 'EA Games';
            if (stripos($categoryName, 'Fortnite') !== false) return 'Fortnite';
            if (stripos($categoryName, 'Blizzard') !== false) return 'Blizzard';
            if (stripos($categoryName, 'PUBG') !== false) return 'PUBG';
            if (stripos($categoryName, 'Garena') !== false) return 'Garena';
            if (stripos($categoryName, 'Nexon') !== false) return 'Nexon';
            if (stripos($categoryName, 'Apple') !== false || stripos($categoryName, 'iTunes') !== false) return 'Apple & iTunes';
            if (stripos($categoryName, 'Amazon') !== false) return 'Amazon';
            if (stripos($categoryName, 'Google Play') !== false || stripos($categoryName, 'Google') !== false) return 'Google Play';
            if (stripos($categoryName, 'Netflix') !== false) return 'Netflix';
            if (stripos($categoryName, 'Spotify') !== false) return 'Spotify';
            if (stripos($categoryName, 'Bilibili') !== false) return 'Bilibili';
            if (stripos($categoryName, 'Disney') !== false) return 'Disney+';
            if (stripos($categoryName, 'Twitch') !== false) return 'Twitch';
            if (stripos($categoryName, 'Airbnb') !== false) return 'Airbnb';
            if (stripos($categoryName, 'Uber') !== false) return 'Uber';
            if (stripos($categoryName, 'Grab') !== false) return 'Grab';
            if (stripos($categoryName, '7-Eleven') !== false) return '7-Eleven';
            if (stripos($categoryName, 'Flexepin') !== false) return 'Flexepin';
            if (stripos($categoryName, 'UniPin') !== false) return 'UniPin';
            
            return 'Prepaid'; // 兜底
        }

        if ($chargeType == 4) {
            // 直充类
            if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $categoryName)) return 'CN Services';
            if (stripos($categoryName, 'Live') !== false || stripos($categoryName, 'Streaming') !== false || stripos($categoryName, 'Entertainment') !== false) return 'Entertainment';
            if (stripos($categoryName, 'League of Legends') !== false || stripos($categoryName, 'LOL') !== false) return 'League of Legends';
            
            return 'Popular Games'; // 默认手游
        }

        return 'Prepaid'; // 默认兜底
    }
}