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
            if (empty($categoryData['id'])) {
                throw new \InvalidArgumentException('Category ID is required');
            }

            $categoryId = $categoryData['id'];
            $categoryName = $categoryData['name'] ?? 'Unknown Category';
            $startTime = microtime(true);

            $this->logger->info('Starting category import', [
                'category_id' => $categoryId,
                'name' => $categoryName
            ]);

            // 构建分类路径
            // 优先使用 parent_path（如 "Games/Coins"），否则使用 name
            $categoryPath = $this->buildCategoryPath($categoryData);

            // 使用 CategoryService 创建或更新分类（基于路径）
            $newCategoryId = $this->categoryService->upsertCategoryByPath($categoryPath);

            // 获取分类对象并更新其他属性
            $category = $this->categoryService->getCategoryById($newCategoryId);
            
            if ($category) {
                // 更新额外属性（description, is_active, position 等）
                $this->updateCategoryAttributes($category, $categoryData);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Category imported successfully', [
                'category_id' => $newCategoryId,
                'external_id' => $categoryId,
                'name' => $categoryName,
                'path' => $categoryPath,
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
     * 构建分类路径
     *
     * @param array $categoryData
     * @return string
     */
    private function buildCategoryPath(array $categoryData): string
    {
        // 如果有 parent_path，使用它 + 当前分类名
        if (!empty($categoryData['parent_path'])) {
            $parentPath = rtrim($categoryData['parent_path'], '/');
            $categoryName = $categoryData['name'] ?? 'Unknown';
            return $parentPath . '/' . $categoryName;
        }

        // 否则直接使用分类名作为单层路径
        return $categoryData['name'] ?? 'Unknown';
    }

    /**
     * 更新分类的额外属性
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param array $categoryData
     * @return void
     */
    private function updateCategoryAttributes(\Magento\Catalog\Model\Category $category, array $categoryData): void
    {
        $needsUpdate = false;

        // 更新描述
        if (isset($categoryData['description']) && $categoryData['description'] !== $category->getDescription()) {
            $category->setDescription($categoryData['description']);
            $needsUpdate = true;
        }

        // 更新激活状态
        if (isset($categoryData['is_active'])) {
            $isActive = (int)$categoryData['is_active'];
            if ($isActive !== (int)$category->getIsActive()) {
                $category->setIsActive($isActive);
                $needsUpdate = true;
            }
        }

        // 更新菜单位置
        if (isset($categoryData['include_in_menu'])) {
            $includeInMenu = (int)$categoryData['include_in_menu'];
            if ($includeInMenu !== (int)$category->getIncludeInMenu()) {
                $category->setIncludeInMenu($includeInMenu);
                $needsUpdate = true;
            }
        }

        // 更新排序位置
        if (isset($categoryData['position'])) {
            $position = (int)$categoryData['position'];
            if ($position !== (int)$category->getPosition()) {
                $category->setPosition($position);
                $needsUpdate = true;
            }
        }

        // 更新 URL Key
        if (isset($categoryData['url_key'])) {
            $category->setUrlKey($categoryData['url_key']);
            $needsUpdate = true;
        }

        // 如果有需要更新的属性，保存分类
        if ($needsUpdate) {
            $category->save();
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