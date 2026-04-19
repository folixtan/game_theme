<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * 充值类型属性源模型
 */
class ChargeType extends AbstractSource
{
    /**
     * 充值类型选项
     */
    public const DIRECT = 4;
    public const CARD = 3;

    /**
     * 获取所有选项
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('直充'), 'value' => self::DIRECT],
                ['label' => __('卡密'), 'value' => self::CARD]
            ];
        }

        return $this->_options;
    }

    /**
     * 获取选项文本
     *
     * @param string $value
     * @return string|null
     */
    public function getOptionText($value): ?string
    {
        switch ($value) {
            case self::DIRECT:
                return __('直充');
            case self::CARD:
                return __('卡密');
            default:
                return null;
        }
    }
}