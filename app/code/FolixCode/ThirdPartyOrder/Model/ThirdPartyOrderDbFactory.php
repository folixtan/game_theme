<?php
/**
 * ThirdPartyOrderDb Factory
 * 用于创建ThirdPartyOrderDb对象的工厂类
 */
namespace FolixCode\ThirdPartyOrder\Model;

use Magento\Framework\ObjectManagerInterface;
use FolixCode\ThirdPartyOrder\Api\Data\ThirdPartyOrderDbInterface;

class ThirdPartyOrderDbFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var string
     */
    protected $_instanceName;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = '\\FolixCode\\ThirdPartyOrder\\Model\\ThirdPartyOrderDb'
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * 创建ThirdPartyOrderDb实例
     *
     * @param array $data
     * @return ThirdPartyOrderDbInterface
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, ['data' => $data]);
    }
}