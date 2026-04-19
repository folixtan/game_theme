<?php
declare(strict_types=1);
namespace FolixCode\ProductSync\Setup\Patch\Data;

/**
 * use Magento\Framework\Setup\{
   * ModuleContextInterface,
   * ModuleDataSetupInterface,
   * InstallDataInterface
  *};
 * 
 * 
 */



use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;


class AddCategoryVendorAttribute implements DataPatchInterface, PatchRevertableInterface
{


    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    const VENDOR_ID = 'vendor_id';

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

        // 添加属性
        $this->addAttribute($eavSetup);

    }

        /**
     * 添加充值类型属性
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addAttribute(EavSetup $eavSetup): void
    {
       
        $eavSetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, self::VENDOR_ID, [
            'type'     => 'int',
            'label'    => 'Vendor ID',
            'input'    => 'text',
            'visible'  => true,
            'default'  => '0',
            'required' => false,
            'global'   => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
           // 'group'    => 'Vendor Info',
        ]);
    }
     
    /**
     * @inheritdoc
     */
    public function revert(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // 删除充值类型属性
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Category::ENTITY, self::VENDOR_ID);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Category::ENTITY, 'self::VENDOR_ID');
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
