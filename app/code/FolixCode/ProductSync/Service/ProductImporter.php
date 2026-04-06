<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 产品导入服务 - 游戏充值项目
 */
class ProductImporter
{
    private ProductRepositoryInterface $productRepository;
    private ProductInterfaceFactory $productFactory;
    private LoggerInterface $logger;
    private LoggerInterface $importLogger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->logger = $logger;

        // 创建独立的产品导入日志记录器
        $this->importLogger = new Logger('product_importer');
        $logPath = BP . '/var/log/product_importer.log';
        $this->importLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * 导入产品
     *
     * @param array $productData
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

            // 检查产品是否已存在（通过SKU）
            $sku = 'vg_' . $externalProductId;

            try {
                $product = $this->productRepository->get($sku);
                $this->importLogger->info('Updating existing product', ['sku' => $sku]);
                $this->logger->info('Updating existing product', ['sku' => $sku]);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // 产品不存在，创建新产品
                $product = $this->productFactory->create();
                $product->setSku($sku);
                $product->setAttributeSetId(4); // 默认属性集ID
                $product->setTypeId('virtual'); // 设置为虚拟产品
                $this->importLogger->info('Creating new product', ['sku' => $sku]);
                $this->logger->info('Creating new product', ['sku' => $sku]);
            }

            // 设置基本产品数据
            $product->setName($productData['name'] ?? 'Unnamed Product');
            $product->setPrice((float)($productData['price'] ?? 0));
            $product->setStatus($productData['status'] ?? Status::STATUS_ENABLED);
            $product->setDescription($productData['description'] ?? '');
            $product->setShortDescription($productData['short_description'] ?? '');

            // 设置为虚拟产品（不发货）
            $product->setIsVirtual(true);
            $product->setWeight(0);

            // 设置充值类型（直充或卡密）
            $chargeType = $productData['charge_type'] ?? AddProductAttributes::CHARGE_TYPE_DIRECT;
            $product->setData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE, $chargeType);

            // 设置库存（虚拟产品不需要库存）
            $product->setStockData([
                'use_config_manage_stock' => 0,
                'manage_stock' => 0,
                'is_in_stock' => 1
            ]);

            // 设置网站（如果支持多网站）
            $product->setWebsiteIds([1]);

            // 设置可见性
            $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);

            // 设置分类（如果提供了分类ID）
            if (!empty($productData['category_ids'])) {
                $product->setCategoryIds($productData['category_ids']);
            }

            // 保存产品
            $this->productRepository->save($product);

            $this->importLogger->info('Product imported successfully', [
                'sku' => $sku,
                'id' => $product->getId(),
                'charge_type' => $chargeType,
                'is_virtual' => true
            ]);
            $this->logger->info('Product imported successfully', [
                'sku' => $sku,
                'id' => $product->getId(),
                'charge_type' => $chargeType,
                'is_virtual' => true
            ]);

        } catch (\Exception $e) {
            $this->importLogger->error('Failed to import product', [
                'product_data' => $productData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
}