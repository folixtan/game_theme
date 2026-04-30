<?php
/**
 * Interface ThirdPartyOrderDbInterface
 * 定义第三方订单数据库模型的数据结构
 */
namespace FolixCode\ThirdPartyOrder\Api\Data;

interface ThirdPartyOrderDbInterface
{
    const ENTITY_ID = 'entity_id';
    const MAGENTO_ORDER_ID = 'magento_order_id';
    const CUSTOMER_ID = 'customer_id';
    const THIRD_PARTY_ORDER_ID = 'third_party_order_id';
    const ORDER_TYPE = 'order_type';
    const STATUS_CODE = 'status_code';
    const CHARGE_ACCOUNT = 'charge_account';
    const CHARGE_REGION = 'charge_region';
    const CARD_NO = 'card_no';
    const CARD_PWD = 'card_pwd';
    const CARD_DEADLINE = 'card_deadline';
    const CARDS_COUNT = 'cards_count';
    const SYNC_STATUS = 'sync_status';
    const SYNCED_AT = 'synced_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
 
    /**
     * Get Magento Order ID
     *
     * @return int|null
     */
    public function getMagentoOrderId(): ?int;

    /**
     * Set Magento Order ID
     *
     * @param int $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId(int $magentoOrderId): self;

    /**
     * Get Customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set Customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Get Third Party Order ID
     *
     * @return string|null
     */
    public function getThirdPartyOrderId(): ?string;

    /**
     * Set Third Party Order ID
     *
     * @param string $thirdPartyOrderId
     * @return $this
     */
    public function setThirdPartyOrderId(string $thirdPartyOrderId): self;

    /**
     * Get Order Type
     *
     * @return string|null
     */
    public function getOrderType(): ?string;

    /**
     * Set Order Type
     *
     * @param string $orderType
     * @return $this
     */
    public function setOrderType(string $orderType): self;

    /**
     * Get Status Code
     *
     * @return int|null
     */
    public function getStatusCode(): ?int;

    /**
     * Set Status Code
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode): self;

    /**
     * Get Charge Account
     *
     * @return string|null
     */
    public function getChargeAccount(): ?string;

    /**
     * Set Charge Account
     *
     * @param string $chargeAccount
     * @return $this
     */
    public function setChargeAccount(string $chargeAccount): self;

    /**
     * Get Charge Region
     *
     * @return string|null
     */
    public function getChargeRegion(): ?string;

    /**
     * Set Charge Region
     *
     * @param string $chargeRegion
     * @return $this
     */
    public function setChargeRegion(string $chargeRegion): self;

    /**
     * Get Card Number
     *
     * @return string|null
     */
    public function getCardNo(): ?string;

    /**
     * Set Card Number
     *
     * @param string $cardNo
     * @return $this
     */
    public function setCardNo(string $cardNo): self;

    /**
     * Get Card Password
     *
     * @return string|null
     */
    public function getCardPwd(): ?string;

    /**
     * Set Card Password
     *
     * @param string $cardPwd
     * @return $this
     */
    public function setCardPwd(string $cardPwd): self;

    /**
     * Get Card Deadline
     *
     * @return string|null
     */
    public function getCardDeadline(): ?string;

    /**
     * Set Card Deadline
     *
     * @param string $cardDeadline
     * @return $this
     */
    public function setCardDeadline(string $cardDeadline): self;

    /**
     * Get Cards Count
     *
     * @return int|null
     */
    public function getCardsCount(): ?int;

    /**
     * Set Cards Count
     *
     * @param int $cardsCount
     * @return $this
     */
    public function setCardsCount(int $cardsCount): self;

    /**
     * Get Sync Status
     *
     * @return string|null
     */
    public function getSyncStatus(): ?string;

    /**
     * Set Sync Status
     *
     * @param string $syncStatus
     * @return $this
     */
    public function setSyncStatus(string $syncStatus): self;

    /**
     * Get Synced At
     *
     * @return string|null
     */
    public function getSyncedAt(): ?string;

    /**
     * Set Synced At
     *
     * @param string $syncedAt
     * @return $this
     */
    public function setSyncedAt(string $syncedAt): self;

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}