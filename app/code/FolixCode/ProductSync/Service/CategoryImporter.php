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
     * @param array $categoryData 分类数据（数组参数，可扩展）
     * @return void
     * @throws LocalizedException
     */
    public function import(array $categoryData): void
    {
        try {
            if (empty($categoryData['id'])) {
                throw new \InvalidArgumentException('Category ID is required');
            }

            $categoryId = $categoryData['id'];
            $startTime = microtime(true);

            $this->logger->info('Starting category import', [
                'category_id' => $categoryId,
                'name' => $categoryData['name'] ?? 'Unknown'
            ]);

            // 使用 CategoryService 创建或更新分类
            $category = $this->categoryService->createOrUpdateCategory($categoryData);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Category imported successfully', [
                'category_id' => $category->getId(),
                'external_id' => $categoryId,
                'name' => $category->getName(),
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

    /**
     * 批量导入分类
     *
     * @param array $categoriesData
     * @return array
     */
    public function importBatch(array $categoriesData): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($categoriesData as $categoryData) {
            try {
                $this->import($categoryData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'category_id' => $categoryData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}