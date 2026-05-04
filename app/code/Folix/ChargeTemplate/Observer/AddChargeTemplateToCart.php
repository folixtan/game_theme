<?php
namespace Folix\ChargeTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

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
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getQuoteItem();
     
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
        
        // 检查是否为直充产品（game_charge_type = 4）
        $chargeType = $product->getData(\FolixCode\ProductSync\Setup\Patch\Data\AddProductAttributes::ATTRIBUTE_CODE_CHARGE_TYPE);
        
        if ($chargeType != 4) {
            // 不是直充产品，不需要处理充值模板
            return;
        }
        
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = \Magento\Framework\App\ObjectManager::getInstance()->get(RequestInterface::class);
        
        // 从 POST 请求中获取充值模板数据
        $chargeTemplateData = $request->getParam('charge_template');
        
        // 如果没有充值数据，直接返回，不影响正常流程
        if (!$chargeTemplateData || !is_array($chargeTemplateData)) {
            return;
        }
        
        // 验证产品是否有 charge_template 配置
        $productChargeTemplate = $product->getData('charge_template');
        if (!$productChargeTemplate) {
            return;
        }
        
        // 验证必填字段和特殊字符
        $validationResult = $this->validateChargeTemplate($product, $chargeTemplateData);
        if ($validationResult !== true) {
            // 抛出异常，中断购物车流程并提示用户修正
            throw new LocalizedException(__($validationResult));
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
        
        $template = $this->jsonSerializer->unserialize($chargeTemplateJson);
        if (!is_array($template)) {
            return true;
        }
        
        // 验证必填字段和特殊字符
        foreach ($template as $field) {
            $fieldName = $field['charge_field_name'] ?? '';
            $alias = $field['alias'] ?? 'Field';
            
            if (!isset($chargeData[$fieldName]) || empty(trim($chargeData[$fieldName]))) {
                return __('%1 is required', $alias);
            }
            
            // 验证特殊字符（仅对字符串类型的字段）
            $fieldValue = trim($chargeData[$fieldName]);
            if (is_string($fieldValue) && !empty($fieldValue)) {
                $validationError = $this->validateGameAccountFormat($fieldValue, $alias);
                if ($validationError !== true) {
                    return $validationError;
                }
            }
        }
        
        return true;
    }

    /**
     * 验证游戏账号格式（检查特殊字符和SQL注入风险）
     * 
     * @param string $value 要验证的值
     * @param string $fieldName 字段名称（用于错误提示）
     * @return bool|string 返回 true 表示验证通过，返回字符串表示错误信息
     */
    protected function validateGameAccountFormat(string $value, string $fieldName)
    {
        // 定义不允许的特殊字符（常见SQL注入和危险字符）
        $dangerousChars = [
            "'",      // 单引号 - SQL注入
            '"',      // 双引号 - SQL注入
            ';',      // 分号 - SQL语句分隔
            '--',     // SQL注释
            '/*',     // SQL块注释开始
            '*/',     // SQL块注释结束
            'xp_',    // SQL扩展存储过程
            'exec(',  // SQL执行命令
            'union',  // SQL联合查询
            'select', // SQL查询
            'insert', // SQL插入
            'update', // SQL更新
            'delete', // SQL删除
            'drop',   // SQL删除表
            'alter',  // SQL修改表
            '<script>', // XSS攻击
            '</script>', // XSS攻击
            'javascript:', // XSS攻击
            'onerror=', // XSS事件
            'onclick=', // XSS事件
        ];
        
        // 检查是否包含危险字符或关键词（不区分大小写）
        $lowerValue = strtolower($value);
        foreach ($dangerousChars as $dangerousChar) {
            if (strpos($lowerValue, strtolower($dangerousChar)) !== false) {
                return __('%1 contains invalid characters or keywords. Please remove special symbols like quotes, semicolons, and SQL commands.', $fieldName);
            }
        }
        
        // 检查是否包含其他常见的危险特殊字符
        // 允许的字符：字母、数字、下划线(_)、连字符(-)、点号(.)、@符号(邮箱)、中文字符、空格
        // 禁止的字符：! # $ % ^ & * ( ) + = [ ] { } | \ : " < > , ? / ` ~ 等
        if (preg_match('/[!#$%^&*()+\=\[\]{}|\\:"<>?,\/`~]/', $value)) {
            return __('%1 contains special characters that are not allowed. Allowed: letters, numbers, underscores (_), hyphens (-), dots (.), @ symbol, and spaces.', $fieldName);
        }
        
        return true;
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
