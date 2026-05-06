<?php
/**
 * Folix ChargeTemplate Module - CustomerData Charge Item
 * 
 * 处理充值模板商品的购物车数据，将 additional_data 中的充值字段提取到前端
 */

namespace Folix\ChargeTemplate\CustomerData;

use Magento\Checkout\CustomerData\ItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Serialize\Serializer\Json;
use  Magento\Checkout\CustomerData\DefaultItem;

/**
 * Charge Item CustomerData Processor
 * 只实现 ItemInterface 接口，不继承任何类
 */
class ChargeItem extends DefaultItem
{
   
 
    /**
     * 获取购物车项数据
     * 
    
     * @return array
     */
    protected function doGetItemData():array
    {

        $item = parent::doGetItemData();
        // 从 additional_data 中提取充值模板字段
        $chargeTemplateFields = $this->getChargeTemplateFields($this->item);
       //var_dump($item);exit;
       $item['item_id'] = $this->item->getId();
    
         $item['charge_template_fields'] =   $chargeTemplateFields;
        
         return $item;
    }

    /**
     * 从 Quote Item 的 additional_data 中提取充值模板字段
     * 
     * @param Item $item
     * @return array
     */
    private function getChargeTemplateFields(Item $item): array
    {
        try {
            // 获取 additional_data（JSON 字符串）
            $additionalData = $item->getAdditionalData();
            
            if (empty($additionalData)) {
                return [];
            }
             $jsonSerializer = $jsonSerializer ?? \Magento\Framework\App\ObjectManager::getInstance()->get(Json::class);
            // 尝试解析 JSON
            $decodedData = is_string($additionalData) 
                ? $jsonSerializer->unserialize($additionalData)
                : $additionalData;
            
            // 如果不是数组，返回空数组
            if (!is_array($decodedData)) {
                return [];
            }
            
            // 返回清洗后的数据（只保留需要的字段）
            return $this->sanitizeChargeData($decodedData);
            
        } catch (\Exception $e) {
            // 记录日志但不中断流程
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)
                ->warning('Failed to parse charge template data: ' . $e->getMessage());
            
            return [];
        }
    }

    /**
     * 清洗充值数据，只保留安全字段
     * 
     * @param array $data
     * @return array
     */
    private function sanitizeChargeData(array $data): array
    {
        // 定义允许的字段白名单
        $allowedFields = [
            'charge_account',
            'charge_password',
            'charge_game',
            'charge_region',
            'charge_server',
            'charge_type',
            'role_name',
            'contact_phone',
            'contact_qq'
        ];
        
        $sanitized = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                // 对敏感字段进行脱敏处理（如密码）
                if ($field === 'charge_password') {
                    $sanitized[$field] = '***'; // 不返回真实密码
                } else {
                    $sanitized[$field] = htmlspecialchars((string)$data[$field], ENT_QUOTES, 'UTF-8');
                }
            }
        }
        
        return $sanitized;
    }
}
