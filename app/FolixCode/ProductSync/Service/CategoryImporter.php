<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * 分类导入服务
 * 使用 CategoryService 处理分类业务逻辑
 */
class CategoryImporter
{
    private CategoryService $categoryService;
    private LoggerInterface $logger;

    

    public function __construct(
        CategoryService $categoryService,
        LoggerInterface $logger
    ) {
        $this->categoryService = $categoryService;
        $this->logger = $logger;
    }

    /**
     * 导入分类
     *
     * @param array $categoryData 分类数据
     * @return void
     * @throws LocalizedException
     */
    public function import(array $categoryData): void
    {
        try {
            // 验证必填字段
            if (empty($categoryData['id'])) {
                throw new \InvalidArgumentException('Category ID is required');
            }

            if (empty($categoryData['name'])) {
                throw new \InvalidArgumentException('Category name is required');
            }

            $categoryId = $categoryData['id'];
            $categoryName = $categoryData['name'];
            $startTime = microtime(true);

            $this->logger->info('Starting category import', [
                'category_id' => $categoryId,
                'name' => $categoryName
            ]);

         
            // 构建分类路径
            // 优先使用 parent_path（如 "Games/Coins"），否则使用 name
           $this->categoryService->buildCategory($categoryData);
           $this->categoryService->clearCache();
             
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Category imported successfully', [
                'category_id' => $newCategoryId,
                'external_id' => $categoryId,
                'name' => $categoryName,
                'path' => $categoryPath,
                'url_key' => $categoryData['url_key'],
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to import category', [
                'category_data' => $categoryData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

}