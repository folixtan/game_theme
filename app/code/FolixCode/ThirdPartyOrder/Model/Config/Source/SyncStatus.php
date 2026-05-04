<?php
/**
 * Sync Status Options
 */
namespace FolixCode\ThirdPartyOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class SyncStatus
 */
class SyncStatus implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'pending', 'label' => __('Pending')],
            ['value' => 'synced', 'label' => __('Synced')],
            ['value' => 'failed', 'label' => __('Failed')],
        ];
    }
}
