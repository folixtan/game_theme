<?php
/**
 * ThirdPartyOrder Factory
 * 用于创建ThirdPartyOrder对象的工厂类
 */
namespace FolixCode\ThirdPartyOrder\Model;

use Magento\Framework\ObjectManagerInterface;
use FolixCode\ThirdPartyOrder\Api\Data\ThirdPartyOrderInterface;

class ThirdPartyOrderPushFactory
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
        string $instanceName = '\\FolixCode\\ThirdPartyOrder\\Model\\ThirdPartyOrderPushManager',
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * 创建ThirdPartyOrder实例
     *
     * @param array $data
     * @return ThirdPartyOrderInterface
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, ['data' => $data]);
    }
}