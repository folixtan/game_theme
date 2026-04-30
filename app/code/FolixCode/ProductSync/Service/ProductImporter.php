<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product\Attribute\Management as AttributeManagement;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use FolixCode\ProductSync\Service\VirtualGoodsApiService;
use Magento\Framework\Exception\NoSuchEntityException;
use FolixCode\ProductSync\Exception\ApiSyncException;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterfaceFactory;

/**
 * 产品导入服务 - 游戏充值项目
 * 复用 Magento Import 模块的工具类来处理分类、库存等复杂逻辑
 */
class ProductImporter
{


    const CUSTOM_ATTR = [
         'face_value' => [
               'type' => 'text',
                'label' => 'Face Value',
                'frontend_label' => 'Face Value',
                'fontend_input' => 'text',
                'required' => false,
                'user_defined' => true,
                'default' => 0,
                'is_searchable' => false,
                'is_filterable' => false,
                'is_filterable_in_search' => false,
                'is_visible_on_front' => true,
          
                'scope' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'Game Settings',
                'sort_order' => 10,
           //     'apply_to' => 'simple,virtual,downloadable',
                'note' => '商品实际面值'
         ],
         'charge_template' => [
            "type" => 'text',
            'label' => 'Charge Template',
            'frontend_label' => 'Charge Templates',
            'fontend_input' => 'textarea',
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'is_searchable' => false,
            'is_filterable' => false,
             'scope' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'is_filterable_in_search' => false,
            'is_visible_on_front' => true,
         ]
    ];

    private $categories = [];

    public const SKU_PREFIX = 'vg_';

    /**
     * 构造函数
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductFactory $productFactory,
        private CategoryProcessor $categoryProcessor,
        private ResourceConnection $resourceConnection,
        private AttributeRepository $attributeRepository,
        private AttributeManagement $attributeManagement,
        private LoggerInterface $logger,
        private ProductExtensionFactory $extensionFactory,
        private TimezoneInterface $timezone,
        private VirtualGoodsApiService $apiService,
        private AttributeSetCollectionFactory $attributeSetCollectionFactory,
        private ProductAttributeInterfaceFactory $productAttributeFactory
    ) {
      
    }

    /**
     * 导入产品
     *     array(7) {
      ["name"]=>
      string(18) "Sandbox card 1 USD"
      ["face_value"]=>
      int(1)
      ["product_id"]=>
      string(8) "78979978"
      ["product_type"]=>
      int(3)
      ["sales_price"]=>
      string(6) "0.9450"
      ["sales_currency"]=>
      string(3) "USD"
      ["goods_category_id"]=>
      int(50)
    }

     * @param array $productData API返回的产品数据
     * @return int|string|null
     * @throws LocalizedException
     */
    public function import(array $productData): int|string|null
    {
        try {
            $externalProductId = $productData['product_id'] ?? '';

            if (empty($externalProductId)) {
                throw new \InvalidArgumentException('Product ID is required');
            }

            // 生成 SKU
            $sku = self::SKU_PREFIX . $externalProductId;

           // var_dump($sku,$productData);exit;
            // 检查产品是否已存在
            $isNewProduct = false;
            try {
                $product = $this->productRepository->get($sku);
               
                $this->logger->info('Updating existing product', ['sku' => $sku]);
                return $product->getId();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // 产品不存在，创建新产品
                $product = $this->productFactory->create();
                $product->setSku($sku);
                $attributeSetId = $this->getDefaultAttributeSetId();
                $product->setAttributeSetId($attributeSetId);
                $product->setTypeId('virtual');
                $isNewProduct = true;
                $this->logger->info('Creating new product', ['sku' => $sku]);
              
                $this->createAttributes($attributeSetId);
                //get category id
                    
                // 通过扩展属性设置分类（参考 ProductRepository 第 548-553 行逻辑）
                if (!empty($productData['goods_category_id'])) {
                    $categoryId = $this->getCategoryId((int)$productData['goods_category_id']);
                    
                    $extensionAttributes = $product->getExtensionAttributes() 
                        ?: $this->extensionFactory->create();
                    $extensionAttributes->setWebsiteIds([1]);
                //  $extensionAttributes->setCategoryIds($productData['category_ids']);
                    $product->setExtensionAttributes($extensionAttributes);
                    $product->setCategoryIds([$categoryId ?? 2]);
                }
                

            }

          
            // 设置基本产品数据
            $product->setName($productData['name'] ?? 'Unnamed Product');
            $product->setPrice(($productData['sales_price'] ?? 0.00));
            $product->setStatus($productData['status'] ?? Status::STATUS_ENABLED);
            $product->setDescription($productData['name'] ?? '');
            $product->setShortDescription($productData['name'] ?? '');
            $product->setData('face_value', $productData['face_value'] ?? 0);

            // 设置为虚拟产品
            $product->setIsVirtual(true);
            $product->setWeight(0);

            // 设置自定义属性：充值类型
            $chargeType = $productData['product_type'] ?? AddProductAttributes::CHARGE_TYPE_DIRECT;
            $product->setData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE, $chargeType);

            // 使用 StockProcessor 处理库存数据（复用 Magento 逻辑）
            $stockData = $this->prepareStockData($productData);
            $product->setStockData($stockData);

            // 设置网站
            $product->setWebsiteIds([1]);

            // 设置可见性
            $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);


            // 保存产品（ProductRepository 会自动处理分类分配）
           $product =  $this->productRepository->save($product);

            $this->logger->info('Product imported successfully', [
                'sku' => $sku,
                'id' => $product->getId(),
                'charge_type' => $chargeType,
                'is_new' => $isNewProduct
            ]);
            //create Detail
            if($isNewProduct && (int)$chargeType === AddProductAttributes::CHARGE_TYPE_DIRECT)  $this->createDetail($product);

           return (int)$product->getId();
        } catch (\Exception $e) {
            $this->logger->error('Failed to import product', [
                'product_data' => $productData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
           throw  new  ApiSyncException(__(
                'Failed to import product: %1',
                $e->getMessage()
             ));
        }

        return null;
    }

    /** 
     * 创建自定义属性
     */
    private function createAttributes($attributeSetId = 4): void {
        /**
         * 获取属性组ID
         */
        $select = $this->resourceConnection->getConnection()->
            select()->from(
                $this->resourceConnection->getTableName('eav_attribute_group'),
                ['attribute_group_id']
            )
            ->where('attribute_set_id = ?', $attributeSetId)
            ->where('attribute_group_name = ?', 'Game Settings');

        $groupId = $this->resourceConnection->getConnection()->fetchOne($select);

        $select = $this->resourceConnection->getConnection()->
            select()->from(
                $this->resourceConnection->getTableName('eav_attribute'),
                ['attribute_code']
            )->where('attribute_code in (?)', array_keys(self::CUSTOM_ATTR));

        $result = $this->resourceConnection->getConnection()->fetchCol($select);

        $originAttributes = array_keys(self::CUSTOM_ATTR);
        $needCreateAttributes = array_diff($originAttributes, $result);

        if (empty($needCreateAttributes)) {
            return;
        }

        foreach ($needCreateAttributes as $code) {
            $attribute = self::CUSTOM_ATTR[$code];
            $attributeObj = $this->productAttributeFactory->create();

            $attributeObj->setAttributeCode($code);
            $attributeObj->setDefaultFrontendLabel($attribute['frontend_label']);
            $attributeObj->setFrontendInput($attribute['fontend_input']);
            $attributeObj->setIsUserDefined(true);
            $attributeObj->setIsVisible(true);
            $attributeObj->setIsVisibleOnFront(true);
            $attributeObj->setFrontendLabel($attribute['frontend_label']);
            $attributeObj->setScope($attribute['scope'] ?? \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL);

            $resultAttribute = $this->attributeRepository->save($attributeObj);

            if ($resultAttribute && $groupId) {
                $this->logger->info('Created custom attribute', ['attribute_code' => $code]);
                // ✅ 修复：使用$code而不是硬编码的'face_value'
                $this->attributeManagement->assign(
                    $attributeSetId,
                    $groupId,
                    $code,
                    $attribute['sort_order'] ?? 10
                );
            }
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
            'ids' => [],
            'errors' => []
        ];

        foreach ($productsData as $productData) {
            try {
               $id = $this->import($productData);
               $results['ids'][$id] = $id;
                $results['success']++;
            } catch (ApiSyncException $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'sku' => $productData['product_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        if($results['failed'] > 0){
             $this->logger->error('Failed to import products', $results['errors']);
        }
        
        return $results;
    }

   /*
     * 创建产品详情
     */
    private function createDetail( \Magento\Catalog\Model\Product $product):void {
        $api_productId = str_replace(self::SKU_PREFIX,'',$product->getSku());
         $params = [
             'product_id' => $api_productId,
             'timestamp'  => $this->timezone->date()->getTimestamp()
         ];
         $detail = $this->apiService->getProductDetail($params);
         if(empty($detail['charge_template'])) return;

         $product->setData('charge_template',json_encode( $detail['charge_template']));

         $this->productRepository->save($product);
         
    }

    /** 创建产品详情 */
   public function importDetail(string $sku )
   { 

    try { 
          $product = $this->productRepository->get($sku);
     
         $this->createDetail($product);
    } catch (NoSuchEntityException $e) {
        $this->logger->error('Failed to import product detail', [
            'product_sku' => $sku,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
          
    }
     

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
            'qty' => 10000
        ];
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
                $attributeSetCollection = $this->attributeSetCollectionFactory->create();
                
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


    /**
     * 获取分类ID
     *
     * @param int $vendor_id
     * @return int|null
     */
    private function getCategoryId(int $vendor_id): ?int
    {

       if(isset($this->categories[$vendor_id])) return (int)$this->categories[$vendor_id];

        $select =  $this->resourceConnection->getConnection()->select()
            ->from(
               [
                   'ev' => $this->resourceConnection->getTableName('eav_attribute')
               ],
               
                  [

                   'ev.attribute_code'
                  
                  ]
               
               
            )
            ->join(
                [
                    'cat' => $this->resourceConnection->getTableName('catalog_category_entity_int')
                ],
                'ev.attribute_id = cat.attribute_id',
                [
                   'cat.entity_id'
                ]
            )
            ->where('ev.attribute_code = "vendor_id"')
            ->where('cat.value = ?', $vendor_id);

           $category= $this->resourceConnection->getConnection()->fetchRow($select);
          
           if(isset($category['entity_id'])) {
               $this->categories[$vendor_id] = $category['entity_id'];
               return (int)$category['entity_id'];
           }
        
         return null;
    }
}