<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * 添加游戏充值相关产品属性
 */
class AddProductAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * 充值类型属性代码
     */
    public const ATTRIBUTE_CODE_CHARGE_TYPE = 'game_charge_type';

    /**
     * 充值类型选项值
     */
    public const CHARGE_TYPE_DIRECT = 'direct'; // 直充
    public const CHARGE_TYPE_CARD = 'card';     // 卡密

    /**
     * 产品类型属性代码
     */
    public const ATTRIBUTE_CODE_IS_VIRTUAL = 'is_virtual';

    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // 添加充值类型属性
        $this->addChargeTypeAttribute($eavSetup);

        // 产品类型已经是Magento默认属性，只需确保虚拟产品属性集包含该属性
        $this->ensureVirtualProductAttributes($eavSetup);
    }

    /**
     * 添加充值类型属性
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addChargeTypeAttribute(EavSetup $eavSetup): void
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::ATTRIBUTE_CODE_CHARGE_TYPE,
            [
                'type' => 'int',
                'label' => '充值类型',
                'input' => 'select',
                'source' => 'FolixCode\ProductSync\Model\Product\Attribute\Source\ChargeType',
                'required' => true,
                'user_defined' => true,
                'default' => self::CHARGE_TYPE_DIRECT,
                'searchable' => true,
                'filterable' => true,
                'filterable_in_search' => true,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'Game Settings',
                'sort_order' => 10,
                'apply_to' => 'simple,virtual,downloadable',
                'note' => '选择充值类型：直充或卡密'
            ]
        );
    }

    /**
     * 确保虚拟产品相关属性
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function ensureVirtualProductAttributes(EavSetup $eavSetup): void
    {
        // Magento默认已经有is_virtual属性
        // 如果需要添加其他虚拟产品特定属性，可以在这里添加
    }

    /**
     * @inheritdoc
     */
    public function revert(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // 删除充值类型属性
        $eavSetup->removeAttribute(Product::ENTITY, self::ATTRIBUTE_CODE_CHARGE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}