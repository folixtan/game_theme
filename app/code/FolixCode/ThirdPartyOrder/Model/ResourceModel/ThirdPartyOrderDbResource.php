<?php
/**
 * ThirdPartyOrder Resource Model
 */
namespace FolixCode\ThirdPartyOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ThirdPartyOrderDbResource extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('folix_third_party_orders', 'entity_id');
    }
}