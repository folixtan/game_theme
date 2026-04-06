<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\Product\Attribute\Frontend;

use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;

/**
 * 充值类型属性前端模型
 */
class ChargeType extends AbstractFrontend
{
    /**
     * 获取属性值文本
     *
     * @param \Magento\Framework\DataObject $object
     * @return string
     */
    public function getValue(\Magento\Framework\DataObject $object): string
    {
        $value = $object->getData($this->getAttribute()->getAttributeCode());
        $options = $this->getAttribute()->getSource()->getAllOptions();

        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }

        return '';
    }
}