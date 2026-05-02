<?php
/**
 * ThirdPartyOrder Repository
 */
namespace FolixCode\ThirdPartyOrder\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use FolixCode\ThirdPartyOrder\Api\ThirdPartyOrderDbRepositoryInterface;
use FolixCode\ThirdPartyOrder\Api\Data\ThirdPartyOrderDbInterface;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbResource;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrderDbCollection;

class ThirdPartyOrderDbRepository implements ThirdPartyOrderDbRepositoryInterface
{
    /**
     * @var ThirdPartyOrderDbResource
     */
    protected $resource;

    /**
     * @var ThirdPartyOrderDbFactory
     */
    protected $orderFactory;

    /**
     * @var ThirdPartyOrderDbCollection
     */
    protected $collectionFactory;

    /**
     * @param ThirdPartyOrderDbResource $resource
     * @param ThirdPartyOrderDbFactory $orderFactory
     * @param ThirdPartyOrderDbCollection $collectionFactory
     */
    public function __construct(
        ThirdPartyOrderDbResource $resource,
        ThirdPartyOrderDbFactory $orderFactory,
        ThirdPartyOrderDbCollection $collectionFactory
    ) {
        $this->resource = $resource;
        $this->orderFactory = $orderFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ThirdPartyOrderDbInterface $order)
    {
        try {
            $this->resource->save($order);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the order: %1',
                $exception->getMessage()
            ));
        }
        return $this->getById($order->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getById($orderId)
    {
        $order = $this->orderFactory->create();
        $this->resource->load($order, $orderId);
        if (!$order->getId()) {
            throw new NoSuchEntityException(__('Order with id "%1" does not exist.', $orderId));
        }
        return $order;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ThirdPartyOrderDbInterface $order)
    {
        try {
            $this->resource->delete($order);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the order: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($orderId)
    {
        return $this->delete($this->getById($orderId));
    }
}
