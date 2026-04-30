<?php
/**
 * ThirdPartyOrderDb Model
 * 与数据库表对应的第三方订单模型
 */
namespace FolixCode\ThirdPartyOrder\Model;

use Magento\Framework\Model\AbstractModel;
use FolixCode\ThirdPartyOrder\Api\Data\ThirdPartyOrderDbInterface;

class ThirdPartyOrderDb extends AbstractModel implements ThirdPartyOrderDbInterface
{
    protected function _construct()
    {
        $this->_init(ResourceModel\ThirdPartyOrderDbResource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getMagentoOrderId(): ?int
    {
        return $this->getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoOrderId(int $magentoOrderId): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magentoOrderId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId(int $customerId): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getThirdPartyOrderId(): ?string
    {
        return $this->getData(self::THIRD_PARTY_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setThirdPartyOrderId(string $thirdPartyOrderId): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::THIRD_PARTY_ORDER_ID, $thirdPartyOrderId);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderType(): ?string
    {
        return $this->getData(self::ORDER_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderType(string $orderType): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::ORDER_TYPE, $orderType);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): ?int
    {
        return $this->getData(self::STATUS_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusCode(int $statusCode): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::STATUS_CODE, $statusCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeAccount(): ?string
    {
        return $this->getData(self::CHARGE_ACCOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeAccount(string $chargeAccount): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CHARGE_ACCOUNT, $chargeAccount);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeRegion(): ?string
    {
        return $this->getData(self::CHARGE_REGION);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeRegion(string $chargeRegion): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CHARGE_REGION, $chargeRegion);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardNo(): ?string
    {
        return $this->getData(self::CARD_NO);
    }

    /**
     * {@inheritdoc}
     */
    public function setCardNo(string $cardNo): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CARD_NO, $cardNo);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardPwd(): ?string
    {
        return $this->getData(self::CARD_PWD);
    }

    /**
     * {@inheritdoc}
     */
    public function setCardPwd(string $cardPwd): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CARD_PWD, $cardPwd);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardDeadline(): ?string
    {
        return $this->getData(self::CARD_DEADLINE);
    }

    /**
     * {@inheritdoc}
     */
    public function setCardDeadline(string $cardDeadline): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CARD_DEADLINE, $cardDeadline);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardsCount(): ?int
    {
        return $this->getData(self::CARDS_COUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCardsCount(int $cardsCount): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CARDS_COUNT, $cardsCount);
    }

    /**
     * {@inheritdoc}
     */
    public function getSyncStatus(): ?string
    {
        return $this->getData(self::SYNC_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setSyncStatus(string $syncStatus): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::SYNC_STATUS, $syncStatus);
    }

    /**
     * {@inheritdoc}
     */
    public function getSyncedAt(): ?string
    {
        return $this->getData(self::SYNCED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setSyncedAt(string $syncedAt): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::SYNCED_AT, $syncedAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(string $createdAt): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt(string $updatedAt): ThirdPartyOrderDbInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}