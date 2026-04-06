<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 分类导入服务
 */
class CategoryImporter
{
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryInterfaceFactory $categoryFactory;
    private LoggerInterface $logger;
    private LoggerInterface $categoryLogger;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryInterfaceFactory $categoryFactory,
        LoggerInterface $logger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->logger = $logger;

        // 创建独立的分类导入日志记录器
        $this->categoryLogger = new Logger('category_importer');
        $logPath = BP . '/var/log/category_importer.log';
        $this->categoryLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * 导入分类
     *
     * @param array $categoryData
     * @return void
     * @throws LocalizedException
     */
    public function import(array $categoryData): void
    {
        try {
            $externalCategoryId = $categoryData['id'] ?? '';

            if (empty($externalCategoryId)) {
                throw new \InvalidArgumentException('Category ID is required');
            }

            // 检查分类是否已存在
            $categoryName = $categoryData['name'] ?? 'Unnamed Category';

            try {
                // 尝试通过名称查找分类
                $category = $this->findCategoryByName($categoryName);

                if ($category) {
                    $this->categoryLogger->info('Updating existing category', ['name' => $categoryName]);
                    $this->logger->info('Updating existing category', ['name' => $categoryName]);
                } else {
                    // 分类不存在，创建新分类
                    $category = $this->categoryFactory->create();
                    $category->setParentId(2); // 默认父分类ID (根分类)
                    $category->setPath('1/2'); // 默认路径
                    $category->setIsActive(1);
                    $category->setIncludeInMenu(1);
                    $this->categoryLogger->info('Creating new category', ['name' => $categoryName]);
                    $this->logger->info('Creating new category', ['name' => $categoryName]);
                }
            } catch (\Exception $e) {
                // 分类不存在，创建新分类
                $category = $this->categoryFactory->create();
                $category->setParentId(2);
                $category->setPath('1/2');
                $category->setIsActive(1);
                $category->setIncludeInMenu(1);
                $this->categoryLogger->info('Creating new category', ['name' => $categoryName]);
                $this->logger->info('Creating new category', ['name' => $categoryName]);
            }

            // 设置分类数据
            $category->setName($categoryName);
            $category->setDescription($categoryData['description'] ?? '');
            $category->setUrlKey($this->generateUrlKey($categoryName));

            // 保存分类
            $this->categoryRepository->save($category);

            $this->categoryLogger->info('Category imported successfully', [
                'name' => $categoryName,
                'id' => $category->getId()
            ]);
            $this->logger->info('Category imported successfully', [
                'name' => $categoryName,
                'id' => $category->getId()
            ]);

        } catch (\Exception $e) {
            $this->categoryLogger->error('Failed to import category', [
                'category_data' => $categoryData,
                'error' => $e->getMessage()
            ]);
            $this->logger->error('Failed to import category', [
                'category_data' => $categoryData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 通过名称查找分类
     *
     * @param string $name
     * @return \Magento\Catalog\Api\Data\CategoryInterface|null
     */
    private function findCategoryByName(string $name): ?\Magento\Catalog\Api\Data\CategoryInterface
    {
        try {
            $categories = $this->categoryRepository->getList(
                $this->categoryFactory->create()->getSearchCriteriaBuilder()
                    ->addFilter('name', $name)
                    ->create()
            );

            $items = $categories->getItems();
            return $items ? array_shift($items) : null;

        } catch (\Exception $e) {
            $this->categoryLogger->error('Failed to find category by name', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            $this->logger->error('Failed to find category by name', [
                'name' => $name,
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
}