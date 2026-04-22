<?php
/**
 * Folix Customer Module - Customer Stats Repository
 *
 * 用户统计数据Repository实现
 */

namespace Folix\Customer\Model;

use Folix\Customer\Api\CustomerStatsRepositoryInterface;
use Folix\Customer\Model\ResourceModel\CustomerStats as CustomerStatsResource;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Customer Stats Repository
 */
class CustomerStatsRepository implements CustomerStatsRepositoryInterface
{
    /**
     * @var CustomerStatsResource
     */
    private $resource;

    /**
     * @param CustomerStatsResource $resource
     */
    public function __construct(
        CustomerStatsResource $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId(int $customerId): array
    {
        $stats = $this->resource->getStatsByCustomerId($customerId);
        
        if (empty($stats)) {
            throw new NoSuchEntityException(
                __('Customer stats for customer ID "%1" does not exist.', $customerId)
            );
        }
        
        return $stats;
    }

    /**
     * @inheritDoc
     */
    public function save(int $customerId, array $data): void
    {
        $this->resource->saveStats($customerId, $data);
    }

    /**
     * @inheritDoc
     */
    public function recalculate(int $customerId): array
    {
        return $this->resource->recalculateStats($customerId);
    }
}
