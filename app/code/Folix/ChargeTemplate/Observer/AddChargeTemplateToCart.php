<?php
namespace Folix\ChargeTemplate\Observer;

use Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterfaceFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use  Magento\Framework\App\RequestInterface;

/**
 * Observer: 在添加到购物车时收集充值模板数据
 * 
 * 数据流转机制：
 * 1. 用户在前端填写充值表单（UID、Server 等）
 * 2. 表单数据随 add to cart POST 请求提交（charge_template[charge_account]=xxx）
 * 3. 此 Observer 在 checkout_cart_product_add_after 事件中触发
 * 4. 从请求中提取 charge_template 数据
 * 5. 保存到 Quote Item 的 additional_data（JSON 格式）
 * 6. Magento 通过 fieldset.xml 自动将 additional_data 从 Quote Item 流转到 Order Item
 *    参考：vendor/magento/module-quote/etc/fieldset.xml
 *    <field name="additional_data"><aspect name="to_order_item" /></field>
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
        try {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
         
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getProduct();
            
            // 检查是否为直充产品（game_charge_type = 4）
            $chargeType = $product->getData('game_charge_type');
            
            if ($chargeType != 4) {
                // 不是直充产品，不需要处理充值模板
                return;
            }
            
            /** @var \Magento\Framework\App\RequestInterface $request */
            $request = \Magento\Framework\App\ObjectManager::getInstance()->get(RequestInterface::class);
            
            // 从 POST 请求中获取充值模板数据
            // 注意：直接从请求参数获取，不是从 $buyRequest
            $chargeTemplateData = $request->getParam('charge_template');
            
            // 调试日志：记录所有 POST 数据
            $this->logger->info('=== Charge Template Debug ===', [
                'all_post_params' => $request->getParams(),
                'charge_template_raw' => $chargeTemplateData,
                'charge_template_type' => gettype($chargeTemplateData),
                'product_id' => $product->getId(),
                'game_charge_type' => $product->getData('game_charge_type')
            ]);
            
            // 如果没有充值数据，直接返回，不影响正常流程
            if (!$chargeTemplateData || !is_array($chargeTemplateData)) {
                return;
            }
            
            // 验证产品是否有 charge_template 配置
            $productChargeTemplate = $product->getData('charge_template');
            if (!$productChargeTemplate) {
                return;
            }
            
            // 验证必填字段
            $validationResult = $this->validateChargeTemplate($product, $chargeTemplateData);
            if ($validationResult !== true) {
                // 不要抛出异常，改为记录日志并继续
                // 这样不会中断购物车流程
                $this->logger->warning('Charge template validation failed: ' . $validationResult, [
                    'product_id' => $product->getId()
                ]);
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
            
            // 同时保存到 additional_data（用于 Order Item 自动流转）
            // 将接口对象序列化为 JSON 存储
            $chargeTemplateJson = json_encode($chargeTemplateData);
            $quoteItem->setAdditionalData($chargeTemplateJson);
            
            $this->logger->info('Charge template data saved to quote item', [
                'quote_item_id' => $quoteItem->getId() ?: 'new',
                'product_id' => $product->getId(),
                'charge_type' => $chargeType,
                'charge_data' => $chargeTemplateData
            ]);
            
        } catch (\Exception $e) {
            // 记录错误但不要让 Observer 失败影响购物车流程
            $this->logger->error('Failed to process charge template: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // 不重新抛出异常，允许购物车继续正常流程
        }
    }

    /**
     * 验证充值模板数据
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @param array $chargeData
     * @return bool|string 返回 true 表示验证通过，返回字符串表示错误信息
     */
    protected function validateChargeTemplate($product, $chargeData)
    {
        $chargeTemplateJson = $product->getData('charge_template');
        if (!$chargeTemplateJson) {
            return true; // 产品没有充值模板配置，跳过验证
        }
        
        try {
            $template = $this->jsonSerializer->unserialize($chargeTemplateJson);
            if (!is_array($template)) {
                return true;
            }
            
            // 验证必填字段
            foreach ($template as $field) {
                $fieldName = $field['charge_field_name'] ?? '';
                $alias = $field['alias'] ?? 'Field';
                
                if (!isset($chargeData[$fieldName]) || empty(trim($chargeData[$fieldName]))) {
                    return __('%1 is required', $alias);
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate charge template: ' . $e->getMessage());
            return __('Invalid charge template configuration');
        }
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
