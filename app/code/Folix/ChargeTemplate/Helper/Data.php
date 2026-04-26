<?php
namespace Folix\ChargeTemplate\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Charge Template Helper
 * 
 * 提供读取和处理充值模板数据的工具方法
 */
class Data extends AbstractHelper
{
    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @param Context $context
     * @param Json $jsonSerializer
     */
    public function __construct(
        Context $context,
        Json $jsonSerializer
    ) {
        parent::__construct($context);
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * 从 Quote Item 获取充值模板数据
     * 
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array|null
     */
    public function getChargeTemplateDataFromQuoteItem($quoteItem)
    {
        $additionalData = $quoteItem->getAdditionalData();
        
        if (!$additionalData) {
            return null;
        }
        
        try {
            $data = $this->jsonSerializer->unserialize($additionalData);
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            $this->_logger->error('Failed to parse charge template data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 从 Order Item 获取充值模板数据
     * 
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array|null
     */
    public function getChargeTemplateDataFromOrderItem($orderItem)
    {
        $additionalData = $orderItem->getAdditionalData();
        
        if (!$additionalData) {
            return null;
        }
        
        try {
            $data = $this->jsonSerializer->unserialize($additionalData);
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            $this->_logger->error('Failed to parse charge template data from order: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 格式化充值数据为可读文本（用于显示）
     * 
     * @param array $chargeData
     * @return string
     */
    public function formatChargeDataForDisplay($chargeData)
    {
        if (empty($chargeData) || !is_array($chargeData)) {
            return '';
        }
        
        $parts = [];
        foreach ($chargeData as $key => $value) {
            if ($value) {
                // 将 charge_account 转换为 User ID 等可读格式
                $label = ucwords(str_replace('_', ' ', str_replace('charge_', '', $key)));
                $parts[] = sprintf('%s: %s', $label, $this->escapeHtml($value));
            }
        }
        
        return implode('<br/>', $parts);
    }

    /**
     * HTML 转义
     * 
     * @param string $value
     * @return string
     */
    protected function escapeHtml($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 检查产品是否需要充值模板
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function productHasChargeTemplate($product)
    {
        $chargeTemplate = $product->getData('charge_template');
        return !empty($chargeTemplate);
    }
}
