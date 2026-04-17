<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;

/**
 * 产品导入服务 - 游戏充值项目
 * 复用 Magento Import 模块的工具类来处理分类、库存等复杂逻辑
 */
class ProductImporter
{
    private ProductRepositoryInterface $productRepository;
    private ProductInterfaceFactory $productFactory;
    private CategoryService $categoryService;
    private LoggerInterface $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        CategoryService $categoryService,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->categoryService = $categoryService;
        $this->logger = $logger;
    }

    /**
     * 导入产品
     *
     * @param array $productData API返回的产品数据
     * @return void
     * @throws LocalizedException
     */
    public function import(array $productData): void
    {
        try {
            $externalProductId = $productData['id'] ?? '';

            if (empty($externalProductId)) {
                throw new \InvalidArgumentException('Product ID is required');
            }

            // 生成 SKU
            $sku = 'vg_' . $externalProductId;

            // 检查产品是否已存在
            $isNewProduct = false;
            try {
                $product = $this->productRepository->get($sku);
                $this->logger->info('Updating existing product', ['sku' => $sku]);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // 产品不存在，创建新产品
                $product = $this->productFactory->create();
                $product->setSku($sku);
                $product->setAttributeSetId($this->getDefaultAttributeSetId());
                $product->setTypeId('virtual');
                $isNewProduct = true;
                $this->logger->info('Creating new product', ['sku' => $sku]);
            }

            // 设置基本产品数据
            $product->setName($productData['name'] ?? 'Unnamed Product');
            $product->setPrice((float)($productData['price'] ?? 0));
            $product->setStatus($productData['status'] ?? Status::STATUS_ENABLED);
            $product->setDescription($productData['description'] ?? '');
            $product->setShortDescription($productData['short_description'] ?? '');

            // 设置为虚拟产品
            $product->setIsVirtual(true);
            $product->setWeight(0);

            // 设置自定义属性：充值类型
            $chargeType = $productData['charge_type'] ?? AddProductAttributes::CHARGE_TYPE_DIRECT;
            $product->setData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE, $chargeType);

            // 使用 StockProcessor 处理库存数据（复用 Magento 逻辑）
            $stockData = $this->prepareStockData($productData);
            $product->setStockData($stockData);

            // 设置网站
            $product->setWebsiteIds([1]);

            // 设置可见性
            $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);

            // 使用 CategoryProcessor 处理分类（复用 Magento 逻辑）
            if (!empty($productData['categories'])) {
                $categoryIds = $this->processCategories($productData['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            } elseif (!empty($productData['category_ids'])) {
                // 兼容直接提供 category_ids 的情况
                $product->setCategoryIds($productData['category_ids']);
            }

            // 保存产品
            $this->productRepository->save($product);

            $this->logger->info('Product imported successfully', [
                'sku' => $sku,
                'id' => $product->getId(),
                'charge_type' => $chargeType,
                'is_new' => $isNewProduct
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to import product', [
                'product_data' => $productData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 批量导入产品
     *
     * @param array $productsData
     * @return array
     */
    public function importBatch(array $productsData): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($productsData as $productData) {
            try {
                $this->import($productData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'sku' => $productData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * 准备库存数据
     *
     * @param array $productData
     * @return array
     */
    private function prepareStockData(array $productData): array
    {
        // 虚拟产品默认不需要库存管理
        return [
            'use_config_manage_stock' => 0,
            'manage_stock' => 0,
            'is_in_stock' => 1,
            'qty' => 0
        ];
    }

    /**
     * 处理分类数据（使用 CategoryService - 共用服务）
     *
     * @param string|array $categories 分类路径、名称或ID数组
     * @return array 分类ID数组
     */
    private function processCategories($categories): array
    {
        try {
            $categoryIds = [];

            // 如果已经是ID数组，直接返回
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    if (is_numeric($category)) {
                        $categoryIds[] = (int)$category;
                    } elseif (is_string($category)) {
                        // 字符串可能是分类名称或路径
                        // 如果包含 "/"，视为路径；否则视为单层分类名
                        if (strpos($category, '/') !== false) {
                            // 多级路径，如 "Games/Coins/Premium"
                            $categoryId = $this->categoryService->upsertCategoryByPath($category);
                        } else {
                            // 单层分类名，如 "Games"
                            $categoryId = $this->categoryService->upsertCategoryByPath($category);
                        }
                        
                        if ($categoryId) {
                            $categoryIds[] = $categoryId;
                        }
                    }
                }
                return $categoryIds;
            }

            // 如果是分类路径字符串（如 "Games/Coins/Premium"）
            if (is_string($categories) && !empty($categories)) {
                $categoryId = $this->categoryService->upsertCategoryByPath($categories);
                if ($categoryId) {
                    $categoryIds[] = $categoryId;
                }
            }

            return $categoryIds;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to process categories', [
                'categories' => $categories,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 获取默认属性集ID
     *
     * @return int
     */
    private function getDefaultAttributeSetId(): int
    {
        // 动态获取默认属性集ID，避免硬编码
        static $attributeSetId = null;
        
        if ($attributeSetId === null) {
            try {
                $attributeSetCollection = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class);
                
                $attributeSetCollection->setEntityTypeFilter(
                    \Magento\Catalog\Model\Product::ENTITY
                );
                
                $defaultSet = $attributeSetCollection->addFieldToFilter('attribute_set_name', 'Default')
                    ->getFirstItem();
                
                $attributeSetId = $defaultSet->getId() ?: 4; // 降级为硬编码值
            } catch (\Exception $e) {
                $this->logger->warning('Failed to get default attribute set ID, using fallback', [
                    'error' => $e->getMessage()
                ]);
                $attributeSetId = 4; // 降级值
            }
        }

        return (int)$attributeSetId;
    }
}