<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\Product\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;

/**
 * 充值类型属性后端模型
 */
class ChargeType extends AbstractBackend
{
    /**
     * 验证充值类型
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate($object): self
    {
        parent::validate($object);

        $value = $object->getData($this->getAttribute()->getAttributeCode());
        $validValues = [
            \FolixCode\ProductSync\Model\Product\Attribute\Source\ChargeType::DIRECT,
            \FolixCode\ProductSync\Model\Product\Attribute\Source\ChargeType::CARD
        ];

        if ($value !== null && $value !== '' && !in_array($value, $validValues)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid charge type value. Please select valid charge type.')
            );
        }

        return $this;
    }
}