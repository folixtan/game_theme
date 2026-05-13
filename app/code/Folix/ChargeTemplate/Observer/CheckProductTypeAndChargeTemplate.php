<?php
declare(strict_types=1);
namespace Folix\ChargeTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Observer: 在添加到购物车前验证产品类型和充值模板数据
 * 
 * 触发时机：checkout_cart_product_add_before
 * 
 * 验证逻辑：
 * 1. Virtual Product（标准产品）：
 *    - 检查 game_charge_type 是否为直充类型
 *    - 验证是否提交了 charge_template 数据
 * 
 * 2. Configurable Product（配置产品）：
 *    - 通过用户选择的属性查找对应的子产品
 *    - 检查子产品的 game_charge_type
 *    - 验证子产品是否提交了 charge_template 数据
 * 
 * 注意：只负责验证，不负责保存数据（保存在 after 事件中完成）
 */
class CheckProductTypeAndChargeTemplate implements ObserverInterface
{ 
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        Json $jsonSerializer,
        ProductRepositoryInterface $productRepository
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->productRepository = $productRepository;
    }

    public function execute(Observer $observer): void
    {
        $product = $observer->getProduct();  // 对于配置产品是父产品
        $info = $observer->getInfo();         // 请求数据（包含 super_attribute 和 charge_template）

        // 判断产品类型
        if ($product->getTypeId() === 'configurable') {
            // 配置产品：验证子产品
            $this->validateConfigurableProduct($product, $info);
            return;
        }

        // 虚拟产品（标准产品）
        if ($product->getTypeId() === 'virtual') {
            $this->validateVirtualProduct($product, $info);
            return;
        }

        // 其他产品类型不处理
        return;
    }

    /**
     * 验证虚拟产品（标准产品）
     */
    protected function validateVirtualProduct($product, $info): void
    {
        $chargeType = (int)$product->getData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE);

        // 只有直充产品需要验证充值模板
        if ($chargeType !== AddProductAttributes::CHARGE_TYPE_DIRECT) {
            return;
        }

        // 验证是否提交了充值模板数据
        if (empty($info['charge_template'])) {
            throw new LocalizedException(__('Please select charge template'));
        }
    }

    /**
     * 验证配置产品
     */
    protected function validateConfigurableProduct($product, $info): void
    {
        // 获取用户选择的属性组合
        $superAttribute = $info['super_attribute'] ?? [];

        if (empty($superAttribute)) {
            // 用户还没有选择属性，不验证
            return;
        }

        // 通过 super_attribute 属性组合查找子产品 ID
        $childProductId = $this->getChildProductIdByAttributes($product, $superAttribute);

        if (!$childProductId) {
            // 无法找到对应的子产品，不验证
            return;
        }

        // 通过 ProductRepository 加载子产品
        try {
            $childProduct = $this->productRepository->getById($childProductId);
        } catch (\Exception $e) {
            // 无法加载子产品，不验证
            return;
        }

        // 检查子产品是否为直充类型
        $chargeType = (int)$childProduct->getData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE);

        if ($chargeType !== AddProductAttributes::CHARGE_TYPE_DIRECT) {
            return;
        }

        // 验证子产品是否提交了充值模板数据
        if (empty($info['charge_template'])) {
            throw new LocalizedException(__('Please select charge template'));
        }
    }

    /**
     * 根据属性组合查找子产品 ID
     * 
     * @param \Magento\Catalog\Model\Product $parentProduct
     * @param array $superAttribute 属性组合 ['attribute_id' => 'option_value']
     * @return int|null
     */
    protected function getChildProductIdByAttributes($parentProduct, array $superAttribute): ?int
    {
        try {
            // 获取所有子产品
            $childProducts = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct, null);

            // 遍历子产品，查找匹配的属性组合
            foreach ($childProducts as $childProduct) {
                if ($this->isProductMatchAttributes($childProduct, $superAttribute)) {
                    return (int)$childProduct->getId();
                }
            }
        } catch (\Exception $e) {
            // 查找失败，返回 null
            return null;
        }

        return null;
    }

    /**
     * 检查产品是否匹配指定的属性组合
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @param array $superAttribute
     * @return bool
     */
    protected function isProductMatchAttributes($product, array $superAttribute): bool
    {
        foreach ($superAttribute as $attributeId => $optionValue) {
            // 获取产品的属性值
            $productAttributeValue = $product->getData($attributeId);
            
            // 如果属性值不匹配，返回 false
            if ((string)$productAttributeValue !== (string)$optionValue) {
                return false;
            }
        }

        return true;
    }
}