<?php
declare(strict_types=1);
namespace FolixCode\ProductSync\Setup;

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
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, 'vendor_id', [
            'type'     => 'varchar',
            'label'    => 'Vendor ID',
            'input'    => 'text',
            'visible'  => true,
            'default'  => '0',
            'required' => false,
            'global'   => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
           // 'group'    => 'Vendor Info',
        ]);
    }
}
