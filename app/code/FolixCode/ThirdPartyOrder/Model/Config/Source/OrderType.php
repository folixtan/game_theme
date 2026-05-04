<?php
/**
 * Order Type Options
 */
namespace FolixCode\ThirdPartyOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class OrderType
 */
class OrderType implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '直充', 'label' => __('Direct Charge')],
            ['value' => '卡密', 'label' => __('Card Key')],
        ];
    }
}
