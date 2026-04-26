<?php
namespace Folix\ChargeTemplate\Block\Product\View;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

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
     * @param Context $context
     * @param Registry $registry
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->jsonSerializer = $jsonSerializer;
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
}
