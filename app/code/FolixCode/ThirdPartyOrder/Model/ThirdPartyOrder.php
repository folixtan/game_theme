<?php
declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Model;

use Magento\Framework\Model\AbstractModel;
use FolixCode\ThirdPartyOrder\Model\ResourceModel\ThirdPartyOrder\ThirdPartyOrder as ResourceModel;

/**
 * Third Party Order Model
 */
class ThirdPartyOrder extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * 设置Magento订单ID
     *
     * @param int $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId(int $magentoOrderId): self
    {
        return $this->setData('magento_order_id', $magentoOrderId);
    }

    /**
     * 获取Magento订单ID
     *
     * @return int|null
     */
    public function getMagentoOrderId(): ?int
    {
        return $this->getData('magento_order_id') ? (int)$this->getData('magento_order_id') : null;
    }

    /**
     * 设置客户ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * 设置第三方订单ID
     *
     * @param string $thirdPartyOrderId
     * @return $this
     */
    public function setThirdPartyOrderId(string $thirdPartyOrderId): self
    {
        return $this->setData('third_party_order_id', $thirdPartyOrderId);
    }

    /**
     * 获取第三方订单ID
     *
     * @return string|null
     */
    public function getThirdPartyOrderId(): ?string
    {
        return $this->getData('third_party_order_id');
    }

    /**
     * 设置订单类型
     *
     * @param string $orderType
     * @return $this
     */
    public function setOrderType(string $orderType): self
    {
        return $this->setData('order_type', $orderType);
    }

    /**
     * 设置状态码
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode): self
    {
        return $this->setData('status_code', $statusCode);
    }

    /**
     * 获取状态码
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->getData('status_code') ? (int)$this->getData('status_code') : null;
    }

    /**
     * 设置充值账号
     *
     * @param string $chargeAccount
     * @return $this
     */
    public function setChargeAccount(string $chargeAccount): self
    {
        return $this->setData('charge_account', $chargeAccount);
    }

    /**
     * 设置区服
     *
     * @param string $chargeRegion
     * @return $this
     */
    public function setChargeRegion(string $chargeRegion): self
    {
        return $this->setData('charge_region', $chargeRegion);
    }

    /**
     * 设置卡密信息
     *
     * @param array $cardKeys
     * @return $this
     */
    public function setCardKeys(array $cardKeys): self
    {
        return $this->setData('card_keys', json_encode($cardKeys));
    }

    /**
     * 获取卡密信息
     *
     * @return array
     */
    public function getCardKeys(): array
    {
        $cardKeys = $this->getData('card_keys');
        if (empty($cardKeys)) {
            return [];
        }
        return is_array($cardKeys) ? $cardKeys : (json_decode($cardKeys, true) ?: []);
    }

    /**
     * 设置卡密数量
     *
     * @param int $cardsCount
     * @return $this
     */
    public function setCardsCount(int $cardsCount): self
    {
        return $this->setData('cards_count', $cardsCount);
    }

    /**
     * 获取卡密数量
     *
     * @return int
     */
    public function getCardsCount(): int
    {
        return (int)$this->getData('cards_count') ?: 0;
    }

    /**
     * 设置同步状态
     *
     * @param string $syncStatus
     * @return $this
     */
    public function setSyncStatus(string $syncStatus): self
    {
        return $this->setData('sync_status', $syncStatus);
    }

    /**
     * 检查是否同步成功
     *
     * @return bool
     */
    public function isSynced(): bool
    {
        return $this->getSyncStatus() === 'synced';
    }

    /**
     * 获取同步状态
     *
     * @return string
     */
    public function getSyncStatus(): string
    {
        return (string)$this->getData('sync_status') ?: 'pending';
    }
}
