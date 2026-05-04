<?php
/**
 * Status Code Options
 */
namespace FolixCode\ThirdPartyOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class StatusCode
 */
class StatusCode implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Processing')],
            ['value' => 2, 'label' => __('Success')],
            ['value' => 3, 'label' => __('Failed')],
        ];
    }
}
