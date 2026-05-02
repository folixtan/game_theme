<?php
/**
 * ThirdPartyOrder Repository Interface
 */
namespace FolixCode\ThirdPartyOrder\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use FolixCode\ThirdPartyOrder\Api\Data\ThirdPartyOrderDbInterface;

interface ThirdPartyOrderDbRepositoryInterface
{
    /**
     * Save third party order
     *
     * @param ThirdPartyOrderDbInterface $order
     * @return ThirdPartyOrderDbInterface
     * @throws CouldNotSaveException
     */
    public function save(ThirdPartyOrderDbInterface $order);

    /**
     * Retrieve third party order
     *
     * @param int $orderId
     * @return ThirdPartyOrderDbInterface
     * @throws NoSuchEntityException
     */
    public function getById($orderId);

    /**
     * Delete third party order
     *
     * @param ThirdPartyOrderDbInterface $order
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ThirdPartyOrderDbInterface $order);

    /**
     * Delete third party order by ID
     *
     * @param int $orderId
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function deleteById($orderId);
}
