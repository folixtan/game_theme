<?php
namespace Folix\ChargeTemplate\Block\Product\View;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Charge Template Block - 用于在 PDP 页面渲染充值表单
 * 
 * 继承基础的 Template，通过 Registry 获取当前产品
 */
class ChargeTemplate extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Json $jsonSerializer
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Json $jsonSerializer,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->jsonSerializer = $jsonSerializer;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * 获取当前产品
     * 
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * 获取产品的充值模板配置
     * 
     * @return array|null
     */
    public function getChargeTemplate()
    {
        $product = $this->getProduct();
        
        if (!$product) {
            return null;
        }
        
        // 检查是否为直充产品（game_charge_type = 4）
        // 只有直充产品才需要显示充值模板
        $chargeType = (int)$product->getData('game_charge_type');
        if ($chargeType !== 4) {
            // 不是直充产品（卡密等），不显示充值模板
            return null;
        }
        
        $chargeTemplateJson = $product->getData('charge_template');
        
        if (!$chargeTemplateJson) {
            return null;
        }
        
        try {
            $template = $this->jsonSerializer->unserialize($chargeTemplateJson);
            return is_array($template) ? $template : null;
        } catch (\Exception $e) {
            $this->_logger->error('Failed to parse charge template: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 根据类型编号获取字段类型名称
     * 
     * @param int $type
     * @return string
     */
    public function getFieldTypeName($type)
    {
        $typeMap = [
            1 => 'text',
            2 => 'select',
            3 => 'textarea'
        ];
        
        return $typeMap[$type] ?? 'text';
    }

    /**
     * 判断当前产品是否为配置产品
     * 
     * @return bool
     */
    public function isConfigurableProduct()
    {
        $product = $this->getProduct();
        return $product && $product->getTypeId() === 'configurable';
    }

    /**
     * 获取配置产品的子产品充值模板映射
     * 用于前端 JS 动态加载
     * 
     * @return array
     */
    public function getChildProductChargeTemplates()
    {
        $product = $this->getProduct();
        
        if (!$product || $product->getTypeId() !== 'configurable') {
            return [];
        }
        
        $templates = [];
        
        try {
            // 获取所有子产品ID
            $simpleProducts = $product->getTypeInstance()->getUsedProducts($product, null);
            
            foreach ($simpleProducts as $simpleProduct) {
                // 重新加载产品以获取完整的自定义属性
                $fullProduct = $this->productRepository->getById(
                    $simpleProduct->getId(),
                    false,
                    $this->_storeManager->getStore()->getId()
                );
                
                // 检查子产品是否为直充类型
                $chargeType = (int)$fullProduct->getData('game_charge_type');
                
                if ($chargeType === 4) {
                    // 获取子产品的充值模板配置
                    $chargeTemplateJson = $fullProduct->getData('charge_template');
                    
                    if ($chargeTemplateJson) {
                        $template = $this->jsonSerializer->unserialize($chargeTemplateJson);
                        
                        if (is_array($template)) {
                            $templates[$simpleProduct->getId()] = [
                                'template_id' => $simpleProduct->getId(),
                                'fields' => $this->formatFieldsForJs($template)
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error('Failed to get child product charge templates: ' . $e->getMessage());
        }
        
        return $templates;
    }

    /**
     * 格式化充值模板字段为 JS 可用格式
     * 
     * @param array $template
     * @return array
     */
    protected function formatFieldsForJs(array $template)
    {
        $fields = [];
        
        foreach ($template as $field) {
            $fields[] = [
                'charge_field_name' => $field['charge_field_name'] ?? '',
                'alias' => $field['alias'] ?? '',
                'placeholder' => $field['placeholder'] ?? '',
                'type' => (int)($field['type'] ?? 1),
                'field_type' => $this->getFieldTypeName((int)($field['type'] ?? 1)),
                'is_required' => true,
                'options' => $field['options'] ?? []
            ];
        }
        
        return $fields;
    }
}
