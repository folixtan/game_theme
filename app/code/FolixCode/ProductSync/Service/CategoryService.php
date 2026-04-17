<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Psr\Log\LoggerInterface;

/**
 * 分类管理服务 - 继承 Magento 官方 CategoryProcessor
 * 
 * 优势：
 * 1. 复用官方的分类处理逻辑（经过充分测试）
 * 2. 减少代码量，降低维护成本
 * 3. 自动跟随 Magento 升级
 */
class CategoryService extends CategoryProcessor
{
    private LoggerInterface $logger;
    private bool $isInitialized = false;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($categoryColFactory, $categoryFactory);
        $this->logger = $logger;
    }

    /**
     * 重写 initCategories，改为懒加载
     * 避免在构造函数中就加载所有分类
     *
     * @return $this
     */
    protected function initCategories()
    {
        if ($this->isInitialized) {
            return $this;
        }

        try {
            parent::initCategories();
            $this->isInitialized = true;
            
            $this->logger->info('Category cache initialized', [
                'total_categories' => count($this->categoriesCache),
                'path_mappings' => count($this->categories)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize category cache', [
                'error' => $e->getMessage()
            ]);
        }

        return $this;
    }

    /**
     * 根据名称路径查找或创建分类（公开方法）
     * 支持多级路径，如 "Games/Coins/Premium"
     *
     * @param string $categoryPath 分类路径，如 "Games/Coins"
     * @return int 分类ID
     */
    public function upsertCategoryByPath(string $categoryPath): int
    {
        $this->initCategories();
        
        try {
            // 调用父类的 protected 方法
            return $this->upsertCategory($categoryPath);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upsert category', [
                'path' => $categoryPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据 ID 获取分类（公开方法）
     *
     * @param int|string $categoryId
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCategoryById($categoryId): ?\Magento\Catalog\Model\Category
    {
        $this->initCategories();
        return parent::getCategoryById($categoryId);
    }

    /**
     * 批量创建或更新分类（便捷方法，与父类区分）
     *
     * @param array $categoryPaths 分类路径数组，如 ['Games/Coins', 'Games/Gems']
     * @return array 分类ID数组
     */
    public function upsertCategoriesBatch(array $categoryPaths): array
    {
        $this->initCategories();
        
        $categoryIds = [];
        foreach ($categoryPaths as $path) {
            try {
                $categoryIds[] = $this->upsertCategoryByPath($path);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to upsert category in batch', [
                    'path' => $path,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $categoryIds;
    }

    /**
     * 清除缓存（用于测试或强制刷新）
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->categories = [];
        $this->categoriesCache = [];
        $this->failedCategories = [];
        $this->isInitialized = false;
    }
}