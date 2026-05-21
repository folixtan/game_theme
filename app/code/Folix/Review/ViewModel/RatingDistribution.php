<?php
namespace Folix\Review\ViewModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class RatingDistribution implements ArgumentInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Cached distribution per product ID
     *
     * @var array
     */
    private $cache = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get star distribution for a product.
     *
     * Returns an array keyed by star value (5..1), each containing 'count' and 'percent'.
     * Also includes a 'total' key at the top level.
     *
     * @param int $productId
     * @return array
     */
    public function getDistribution(int $productId): array
    {
        if (isset($this->cache[$productId])) {
            return $this->cache[$productId];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableVote  = $this->resourceConnection->getTableName('rating_option_vote');
        $tableReview = $this->resourceConnection->getTableName('review');

        $select = $connection->select()
            ->from(
                ['v' => $tableVote],
                ['value', 'count' => 'COUNT(*)']
            )
            ->join(
                ['r' => $tableReview],
                'v.review_id = r.review_id',
                []
            )
            ->where('v.entity_pk_value = ?', $productId)
            ->where('r.status_id = ?', \Magento\Review\Model\Review::STATUS_APPROVED)
            ->group('v.value')
            ->order('v.value DESC');

        $rows = $connection->fetchAll($select);

        // Build distribution with all 5 star levels
        $distribution = [
            5 => ['count' => 0, 'percent' => 0],
            4 => ['count' => 0, 'percent' => 0],
            3 => ['count' => 0, 'percent' => 0],
            2 => ['count' => 0, 'percent' => 0],
            1 => ['count' => 0, 'percent' => 0],
        ];

        $total = 0;
        foreach ($rows as $row) {
            $value = (int) $row['value'];
            if (isset($distribution[$value])) {
                $distribution[$value]['count'] = (int) $row['count'];
                $total += (int) $row['count'];
            }
        }

        // Calculate percentages
        if ($total > 0) {
            foreach ($distribution as $star => &$data) {
                $data['percent'] = round(($data['count'] / $total) * 100);
            }
            unset($data);
        }

        $result = ['total' => $total] + $distribution;
        $this->cache[$productId] = $result;

        return $result;
    }

    /**
     * Get average star rating for a product.
     *
     * @param int $productId
     * @return float
     */
    public function getAverage(int $productId): float
    {
        $dist = $this->getDistribution($productId);
        $total = $dist['total'];

        if ($total === 0) {
            return 0.0;
        }

        $sum = 0;
        for ($star = 1; $star <= 5; $star++) {
            $sum += $star * $dist[$star]['count'];
        }

        return round($sum / $total, 1);
    }

    /**
     * Get average star percentage (0-100).
     *
     * @param int $productId
     * @return float
     */
    public function getAveragePercent(int $productId): float
    {
        return round($this->getAverage($productId) / 5 * 100);
    }
}
