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
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;

/**
 * 创建分类层级结构命令 - 创建 Level 4 和 Level 5 分组
 * 
 * 结构：
 * Level 3: Card & Key (ID: 5), Game Top-Up (ID: 6)
 * Level 4: 业务类型分组
 * Level 5: 品牌/游戏细分
 */
class CreateCategoryStructureCommand extends Command
{
    public const COMMAND_NAME = 'folixcode:create:category-structure';

    private CategoryFactory $categoryFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private LoggerInterface $logger;

    // Level 3 固定分类
    private const LEVEL3_CATEGORIES = [
        'Card & Key' => 5,
        'Game Top-Up' => 6,
    ];

    // Level 4 和 Level 5 结构定义
    private const CATEGORY_STRUCTURE = [
        'Card & Key' => [
            'PC Gaming Cards' => ['Steam', 'PlayStation', 'Xbox', 'Nintendo'],
            'Game Brand Cards' => ['Razer', 'Riot Games', 'Roblox', 'EA Games', 'Fortnite', 'Blizzard', 'PUBG', 'Garena', 'Nexon'],
            'Digital Brands' => ['Apple & iTunes', 'Amazon', 'Google Play'],
            'Membership & Services' => ['Netflix', 'Spotify', 'Bilibili', 'Disney+', 'Twitch', 'Airbnb', 'Uber', 'Grab', '7-Eleven'],
            'Prepaid Cards' => ['Flexepin', 'UniPin', 'Prepaid'],
        ],
        'Game Top-Up' => [
            'Mobile Games' => ['Popular Games', 'Other Games'],
            'PC Games' => ['League of Legends', 'Other PC Games'],
            'China Services' => ['CN Services'],
            'Entertainment' => ['Entertainment'],
        ],
    ];

    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Create category hierarchy structure (Level 4 and Level 5)')
            ->setDefinition([
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only, do not create categories'),
            ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');
        
        $output->writeln('========================================');
        $output->writeln('Create Category Structure');
        $output->writeln('Mode: ' . ($isDryRun ? 'DRY RUN' : 'EXECUTE'));
        $output->writeln('========================================');
        $output->writeln('');

        $level4Created = [];
        $level5Created = [];

        foreach (self::CATEGORY_STRUCTURE as $level3Name => $level4Groups) {
            $level3Id = self::LEVEL3_CATEGORIES[$level3Name];
            $output->writeln("<info>Level 3: {$level3Name} (ID: {$level3Id})</info>");

            $level4Created[$level3Id] = [];

            foreach ($level4Groups as $level4Name => $level5Names) {
                // 创建或查找 Level 4
                $level4Id = $this->findOrCreateCategory(
                    $level4Name,
                    $level3Id,
                    $isDryRun,
                    $output,
                    'Level 4'
                );

                if (!$level4Id && !$isDryRun) {
                    continue;
                }

                // Dry-run 模式下使用模拟 ID
                if ($isDryRun) {
                    $level4Id = 9000 + array_sum(array_map('ord', str_split($level4Name))) % 1000;
                }

                $level4Created[$level3Id][$level4Name] = $level4Id;
                $level5Created[$level3Name][$level4Name] = [];

                // 创建或查找 Level 5
                foreach ($level5Names as $level5Name) {
                    $level5Id = $this->findOrCreateCategory(
                        $level5Name,
                        $level4Id,
                        $isDryRun,
                        $output,
                        'Level 5'
                    );

                    // Dry-run 模式下使用模拟 ID
                    if ($isDryRun && !$level5Id) {
                        $level5Id = 9500 + array_sum(array_map('ord', str_split($level5Name))) % 1000;
                    }

                    if ($level5Id) {
                        $level5Created[$level3Name][$level4Name][$level5Name] = $level5Id;
                    }
                }
            }
        }

        // 输出结果
        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('Category Structure Created');
        $output->writeln('========================================');
        $output->writeln('');

        // 生成 CSV 映射文件
        $csvFile = BP.'/var/category_mapping.csv';
        $this->generateMappingCSV($level5Created, $csvFile, $output);

        return 0;
    }

    /**
     * 查找或创建分类
     */
    private function findOrCreateCategory(
        string $name,
        int $parentId,
        bool $isDryRun,
        OutputInterface $output,
        string $level
    ): ?int {
        // 查找现有分类
        $existing = $this->findCategoryByName($name, $parentId);
        
        if ($existing && $existing->getId()) {
            $output->writeln("  ✓ Found: {$level} - {$name} (ID: {$existing->getId()})");
            return (int)$existing->getId();
        }

        if ($isDryRun) {
            $output->writeln("  [DRY RUN] Would create: {$level} - {$name}");
            return null;
        }

        // 创建新分类
        try {
            $newCat = $this->categoryFactory->create();
            $newCat->setName($name)
                ->setParentId($parentId)
                ->setIsActive(1)
                ->setIncludeInMenu(1)
                ->setUrlKey($this->generateUrlKey($name));

            $savedCategory = $this->categoryRepository->save($newCat);
            $categoryId = (int)$savedCategory->getId();
            
            $output->writeln("  ✓ Created: {$level} - {$name} (ID: {$categoryId})");
            return $categoryId;
        } catch (\Exception $e) {
            $output->writeln("  ✗ Failed: {$name} - " . $e->getMessage());
            $this->logger->error("Failed to create {$level} '{$name}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * 查找分类
     */
    private function findCategoryByName(string $name, int $parentId)
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', $name)
            ->addAttributeToFilter('parent_id', $parentId)
            ->setPageSize(1);
        return $collection->getFirstItem();
    }

    /**
     * 生成 URL Key
     */
    private function generateUrlKey(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    }

    /**
     * 生成 CSV 映射文件
     */
    private function generateMappingCSV(array $level5Created, string $csvFile, OutputInterface $output): void
    {
        // 始终重新生成文件（覆盖旧文件）
        $fp = fopen($csvFile, 'w');
        
        if (!$fp) {
            $output->writeln('<error>Failed to open file for writing: ' . $csvFile);
            return;
        }
        
        // 写入表头
        fputcsv($fp, ['level3_name', 'level4_name', 'level5_name', 'level5_id'], ',', '"', '\\');
        
        // 写入数据
        $count = 0;
        foreach ($level5Created as $level3Name => $level4Groups) {
            foreach ($level4Groups as $level4Name => $level5Groups) {
                foreach ($level5Groups as $level5Name => $level5Id) {
                    fputcsv($fp, [$level3Name, $level4Name, $level5Name, $level5Id], ',', '"', '\\');
                    $count++;
                }
            }
        }
        
        fclose($fp);
        
        $output->writeln('');
        $output->writeln("✓ Mapping file saved: {$csvFile}");
        $output->writeln("  Total entries: {$count}");
        $output->writeln("  This file can be used by the move command to find target category IDs.");
    }
}