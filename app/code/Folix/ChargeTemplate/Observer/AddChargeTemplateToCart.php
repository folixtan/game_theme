<?php
namespace Folix\ChargeTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes;

/**
 * Observer: 在添加到购物车时保存充值模板数据到子 Quote Item
 * 
 * 数据流转机制：
 * 1. 用户在前端填写充值表单（UID、Server 等）
 * 2. 表单数据随 add to cart POST 请求提交（charge_template[charge_account]=xxx）
 * 3. 此 Observer 在 checkout_cart_product_add_after 事件中触发
 * 4. 判断产品类型：
 *    - Virtual Product：直接保存
 *    - Configurable Product：只处理子 Item（有 parent_item_id）
 * 5. 保存到 Quote Item 的 additional_data（JSON 格式）
 * 6. Magento 通过 fieldset.xml 自动将 additional_data 从 Quote Item 流转到 Order Item
 *    参考：vendor/magento/module-quote/etc/fieldset.xml
 *    <field name="additional_data"><aspect name="to_order_item" /></field>
 * 
 * 注意：验证逻辑在 CheckProductTypeAndChargeTemplate (before 事件) 中完成
 * 本 Observer 只负责保存数据，不重复验证
 */
class AddChargeTemplateToCart implements ObserverInterface
{
    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterfaceFactory
     */
    protected $chargeTemplateDataFactory;

    /**
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterfaceFactory $chargeTemplateDataFactory
     */
    public function __construct(
        Json $jsonSerializer,
        LoggerInterface $logger,
        \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterfaceFactory $chargeTemplateDataFactory
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->chargeTemplateDataFactory = $chargeTemplateDataFactory;
    }

    /**
     * 执行 Observer
     * 
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getQuoteItem();
        
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
       // var_dump($product->getTypeId());exit;
        // 判断产品类型和处理逻辑
        if ($product->getTypeId() === 'configurable') {
            // 配置产品：只处理子 Item
            $this->processConfigurableProduct($quoteItem, $product);
            return;
        }
        
        if ($product->getTypeId() === 'virtual') {
            // 虚拟产品（标准产品）：直接处理
            $this->processVirtualProduct($quoteItem, $product);
            return;
        }
        
        // 其他产品类型不处理
        return;
    }

    /**
     * 处理配置产品 - 只处理子 Item
     */
    protected function processConfigurableProduct($quoteItem, $parentProduct)
    {
        // 通过 product_type 判断是否为子产品
        // 子产品的 product_type 通常是 'virtual' 或 'simple'，而不是 'configurable'
        $productType = $quoteItem->getProductType();
        
        if ($productType === 'configurable') {
            // 这是父 Item，跳过
            return;
        }
        
        // 获取子产品（从 Quote Item 获取）
        $childProduct = $quoteItem->getProduct();
         $this->processVirtualProduct($quoteItem, $childProduct);
    }

    /**
     * 处理虚拟产品（标准产品）
     */
    protected function processVirtualProduct($quoteItem, $product)
    {
        // 检查是否为直充类型
        $chargeType = (int)$product->getData(AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE);
        
        if ($chargeType !== AddProductAttributes::CHARGE_TYPE_DIRECT) {
            return;
        }
        
        // 保存充值数据
        $this->saveChargeTemplateData($quoteItem, $product);
    }

    /**
     * 保存充值模板数据到 Quote Item
     * 注意：此方法不执行验证，验证已在 before 事件中完成
     */
    protected function saveChargeTemplateData($quoteItem, $product)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = \Magento\Framework\App\ObjectManager::getInstance()->get(RequestInterface::class);
        
        // 从 POST 请求中获取充值模板数据
        $chargeTemplateData = $request->getParam('charge_template');
        
        // 如果没有充值数据，直接返回
        if (!$chargeTemplateData || !is_array($chargeTemplateData)) {
            return;
        }
        
        // 创建 ChargeTemplateData 对象
        /** @var \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterface $chargeTemplateObj */
        $chargeTemplateObj = $this->chargeTemplateDataFactory->create();
        
        // 设置充值数据到接口对象
        $this->setChargeDataToInterface($chargeTemplateObj, $chargeTemplateData);
        
        // 保存到 extension_attributes
        $extensionAttributes = $quoteItem->getExtensionAttributes();
        if ($extensionAttributes) {
            $extensionAttributes->setChargeTemplateData($chargeTemplateObj);
            $quoteItem->setExtensionAttributes($extensionAttributes);
        }
        
        // 保存到 additional_data（用于 Order Item 自动流转）
        $chargeTemplateJson = json_encode($chargeTemplateData);
        $quoteItem->setAdditionalData($chargeTemplateJson);
    }

    /**
     * 将数组数据设置到 ChargeTemplateDataInterface 对象
     * 
     * @param \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterface $chargeTemplateObj
     * @param array $data
     * @return void
     */
    protected function setChargeDataToInterface($chargeTemplateObj, array $data)
    {
        $fieldMapping = [
            'charge_account' => 'setChargeAccount',
            'charge_password' => 'setChargePassword',
            'charge_game' => 'setChargeGame',
            'charge_region' => 'setChargeRegion',
            'charge_server' => 'setChargeServer',
            'charge_type' => 'setChargeType',
            'role_name' => 'setRoleName',
            'contact_phone' => 'setContactPhone',
            'contact_qq' => 'setContactQq'
        ];
        
        foreach ($fieldMapping as $field => $setter) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $chargeTemplateObj->{$setter}($data[$field]);
            }
        }
    }
}
