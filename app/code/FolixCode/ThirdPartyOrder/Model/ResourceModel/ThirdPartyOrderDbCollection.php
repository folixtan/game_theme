<?php
/**
 * ThirdPartyOrder Collection
 */
namespace FolixCode\ThirdPartyOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use FolixCode\ThirdPartyOrder\Model\ThirdPartyOrderDb;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource;

class ThirdPartyOrderDbCollection extends AbstractCollection
{
    /**
     * Define model and resource model
     */
    protected function _construct()
    {
        $this->_init(
            ThirdPartyOrderDb::class,
            ThirdPartyOrderDbResource::class
        );
    }
}