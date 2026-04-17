<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * 分类管理服务 - 独立于导入流程
 * 提供分类的查找、创建、更新等基础操作
 * 可被 ProductImporter 和 CategoryImporter 共用
 */
class CategoryService
{
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryFactory $categoryFactory;
    private LoggerInterface $logger;

    // 默认根分类 ID（Magento 默认根分类）
    private const DEFAULT_ROOT_CATEGORY_ID = 2;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        LoggerInterface $logger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->logger = $logger;
    }

    /**
     * 根据名称查找分类
     *
     * @param string $name 分类名称
     * @param int|null $parentId 父分类ID（可选，用于精确匹配）
     * @return \Magento\Catalog\Model\Category|null
     */
    public function findCategoryByName(string $name, ?int $parentId = null): ?\Magento\Catalog\Model\Category
    {
        try {
            $collection = $this->categoryFactory->create()->getCollection()
                ->addFieldToFilter('name', $name)
                ->setPageSize(1);

            if ($parentId !== null) {
                $collection->addFieldToFilter('parent_id', $parentId);
            }

            $category = $collection->getFirstItem();

            return $category->getId() ? $category : null;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to find category by name', [
                'name' => $name,
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 根据外部ID查找分类（通过SKU或自定义属性）
     *
     * @param string $externalId 外部系统分类ID
     * @return \Magento\Catalog\Model\Category|null
     */
    public function findCategoryByExternalId(string $externalId): ?\Magento\Catalog\Model\Category
    {
        try {
            // TODO: 如果添加了自定义属性存储 external_id，可以在这里查询
            // 暂时通过名称查找作为备选方案
            return $this->findCategoryByName('ext_' . $externalId);

        } catch (\Exception $e) {
            $this->logger->warning('Failed to find category by external ID', [
                'external_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 创建或更新分类
     *
     * @param array $categoryData 分类数据
     * @return \Magento\Catalog\Model\Category
     * @throws LocalizedException
     */
    public function createOrUpdateCategory(array $categoryData): \Magento\Catalog\Model\Category
    {
        $categoryId = $categoryData['id'] ?? '';
        $categoryName = $categoryData['name'] ?? 'Unnamed Category';

        if (empty($categoryId)) {
            throw new \InvalidArgumentException('Category ID is required');
        }

        // 尝试查找现有分类
        $category = $this->findCategoryByExternalId($categoryId);

        $isNewCategory = false;
        if (!$category) {
            // 创建新分类
            $category = $this->categoryFactory->create();
            $category->setIsActive(1);
            $category->setIncludeInMenu(1);
            $isNewCategory = true;

            $this->logger->info('Creating new category', [
                'external_id' => $categoryId,
                'name' => $categoryName
            ]);
        } else {
            $this->logger->info('Updating existing category', [
                'category_id' => $category->getId(),
                'external_id' => $categoryId
            ]);
        }

        // 设置基本属性
        $category->setName($categoryName);
        $category->setDescription($categoryData['description'] ?? '');
        $category->setUrlKey($categoryData['url_key'] ?? $this->generateUrlKey($categoryName));

        // 设置父分类
        $parentId = $this->resolveParentCategoryId($categoryData);
        if ($parentId) {
            $category->setParentId($parentId);
        } elseif ($isNewCategory) {
            // 新分类默认挂在根分类下
            $category->setParentId(self::DEFAULT_ROOT_CATEGORY_ID);
            $category->setPath('1/' . self::DEFAULT_ROOT_CATEGORY_ID);
        }

        // 设置其他属性
        if (isset($categoryData['is_active'])) {
            $category->setIsActive((int)$categoryData['is_active']);
        }

        if (isset($categoryData['include_in_menu'])) {
            $category->setIncludeInMenu((int)$categoryData['include_in_menu']);
        }

        if (isset($categoryData['position'])) {
            $category->setPosition((int)$categoryData['position']);
        }

        // 保存分类
        $this->categoryRepository->save($category);

        $this->logger->info('Category saved successfully', [
            'category_id' => $category->getId(),
            'external_id' => $categoryId,
            'is_new' => $isNewCategory
        ]);

        return $category;
    }

    /**
     * 解析父分类ID
     *
     * @param array $categoryData
     * @return int|null
     */
    private function resolveParentCategoryId(array $categoryData): ?int
    {
        // 优先使用 parent_id
        if (!empty($categoryData['parent_id'])) {
            return (int)$categoryData['parent_id'];
        }

        // 其次使用 parent_path（分类路径，如 "Games/Coins"）
        if (!empty($categoryData['parent_path'])) {
            return $this->resolveParentByPath($categoryData['parent_path']);
        }

        return null;
    }

    /**
     * 通过路径解析父分类ID
     *
     * @param string $path 分类路径，如 "Games/Coins/Premium"
     * @return int|null 最后一级父分类的ID
     */
    private function resolveParentByPath(string $path): ?int
    {
        try {
            $pathParts = explode('/', trim($path, '/'));
            $parentId = self::DEFAULT_ROOT_CATEGORY_ID;

            // 逐级查找或创建分类
            foreach ($pathParts as $part) {
                $part = trim($part);
                if (empty($part)) {
                    continue;
                }

                // 查找当前级别的分类
                $category = $this->findCategoryByName($part, $parentId);

                if (!$category) {
                    // 如果不存在，创建临时分类（仅用于获取ID）
                    // 注意：这里不保存，只是递归查找的最终父分类会被实际创建
                    $this->logger->debug('Parent category not found in path', [
                        'name' => $part,
                        'parent_id' => $parentId
                    ]);
                    // 对于路径中的中间分类，暂时返回 null，由调用方决定如何处理
                    return null;
                }

                $parentId = $category->getId();
            }

            return $parentId;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to resolve parent by path', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 生成URL Key
     *
     * @param string $name
     * @return string
     */
    private function generateUrlKey(string $name): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }

    /**
     * 获取或创建分类ID（便捷方法）
     *
     * @param string $categoryName 分类名称
     * @param int|null $parentId 父分类ID
     * @return int 分类ID
     */
    public function getOrCreateCategoryId(string $categoryName, ?int $parentId = null): int
    {
        $category = $this->findCategoryByName($categoryName, $parentId);

        if ($category) {
            return (int)$category->getId();
        }

        // 创建新分类
        $newCategory = $this->categoryFactory->create();
        $newCategory->setName($categoryName);
        $newCategory->setParentId($parentId ?? self::DEFAULT_ROOT_CATEGORY_ID);
        $newCategory->setIsActive(1);
        $newCategory->setIncludeInMenu(1);
        $newCategory->setUrlKey($this->generateUrlKey($categoryName));

        $this->categoryRepository->save($newCategory);

        return (int)$newCategory->getId();
    }
}