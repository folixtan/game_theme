<?php
/**
 * Third Party Order View Block
 */
namespace FolixCode\ThirdPartyOrder\Block\Adminhtml\Orders;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use FolixCode\ThirdPartyOrder\Helper\Data as HelperData;
use Psr\Log\LoggerInterface;

/**
 * Class View
 */
class View extends Template
{
    /**
     * API URI for order details
     */
    private const API_URI = '/api/user-order/details';

    /**
     * Whitelist of allowed fields to display
     */
    private const ALLOWED_FIELDS = [
        // Basic information
        'order_id', 'user_order_id', 'product_id', 'product_name',
        'buy_num', 'goods_type', 'order_type',
        
        // Price information
        'goods_currency', 'goods_price', 'goods_amount',
        'cny_price', 'cny_amount', 'usd_rate', 'usd_price',
        'usd_amount', 'currency', 'currency_rate', 'currency_price',
        'currency_amount',
        
        // Status information
        'status', 'status_code', 'desc_code', 'description',
        
        // Time information
        'created_at', 'updated_at',
        
        // Nested objects (will be displayed as JSON)
        'account', 'cards',
    ];

    /**
     * @var ExternalApiClientInterface
     */
    private $apiClient;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var HelperData
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ExternalApiClientInterface $apiClient
     * @param TimezoneInterface $timezone
     * @param HelperData $helper
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        ExternalApiClientInterface $apiClient,
        TimezoneInterface $timezone,
        HelperData $helper,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiClient = $apiClient;
        $this->timezone = $timezone;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Get order details from third party API
     *
     * @return array|null
     */
    public function getOrderDetails(): ?array
    {
        // Check if View API is enabled
        if (!$this->helper->isViewApiEnabled()) {
            $this->logger->info('View API is disabled by configuration');
            return null;
        }

        $orderId = $this->getRequest()->getParam('third_order_id');
        
        if (!$orderId) {
            return null;
        }

        try {
            $params = [
                'timestamp' => $this->timezone->date()->getTimestamp(),
                'order_id' => $orderId
            ];

            // Call third party API using configured client
            $response = $this->apiClient->post(self::API_URI, $params);

            // Filter response by whitelist
            if (is_array($response)) {
                return $this->filterByWhitelist($response);
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch order details', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Filter data by whitelist
     *
     * @param array $data
     * @return array
     */
    private function filterByWhitelist(array $data): array
    {
        $filtered = [];
        
        foreach (self::ALLOWED_FIELDS as $field) {
            if (isset($data[$field])) {
                $filtered[$field] = $data[$field];
            }
        }

        return $filtered;
    }

    /**
     * Check if value is a nested array/object
     *
     * @param mixed $value
     * @return bool
     */
    public function isNestedValue($value): bool
    {
        return is_array($value);
    }

    /**
     * Format nested value as JSON
     *
     * @param mixed $value
     * @return string
     */
    public function formatNestedValue($value): string
    {
        if(empty($value)) return '';
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('thirdpartyorder/orders/index');
    }
}
