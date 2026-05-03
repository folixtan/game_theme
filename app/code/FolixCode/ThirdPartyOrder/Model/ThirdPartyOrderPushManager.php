<?php
/**
 * ThirdPartyOrder Model
 * 实现第三方订单接口
 */
namespace FolixCode\ThirdPartyOrder\Model;

use FolixCode\ThirdPartyOrder\Api\Data\ThirdPartyOrderInterface;
use Magento\Framework\DataObject;

class ThirdPartyOrderPushManager extends DataObject implements ThirdPartyOrderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProductId(): ?string
    {
        return $this->_get(self::PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setProductId(string $productId): ThirdPartyOrderInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuyNum(): ?string
    {
        return $this->_get(self::BUY_NUM);
    }

    /**
     * {@inheritdoc}
     */
    public function setBuyNum(int $buyNum): ThirdPartyOrderInterface
    {
        return $this->setData(self::BUY_NUM, $buyNum);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeAccount(): ?string
    {
        return $this->_get(self::CHARGE_ACCOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeAccount(string $chargeAccount): ThirdPartyOrderInterface
    {
        return $this->setData(self::CHARGE_ACCOUNT, $chargeAccount);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargePassword(): ?string
    {
        return $this->_get(self::CHARGE_PASSWORD);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargePassword(string $chargePassword): ThirdPartyOrderInterface
    {
        return $this->setData(self::CHARGE_PASSWORD, $chargePassword);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeGame(): ?string
    {
        return $this->_get(self::CHARGE_GAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeGame(string $chargeGame): ThirdPartyOrderInterface
    {
        return $this->setData(self::CHARGE_GAME, $chargeGame);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeRegion(): ?string
    {
        return $this->_get(self::CHARGE_REGION);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeRegion(string $chargeRegion): ThirdPartyOrderInterface
    {
        return $this->setData(self::CHARGE_REGION, $chargeRegion);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeServer(): ?string
    {
        return $this->_get(self::CHARGE_SERVER);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeServer(string $chargeServer): ThirdPartyOrderInterface
    {
        return $this->setData(self::CHARGE_SERVER, $chargeServer);
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeType(): ?string
    {
        return $this->_get(self::CHARGE_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeType(string $chargeType): ThirdPartyOrderInterface
    {
        return $this->setData(self::CHARGE_TYPE, $chargeType);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleName(): ?string
    {
        return $this->_get(self::ROLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setRoleName(string $roleName): ThirdPartyOrderInterface
    {
        return $this->setData(self::ROLE_NAME, $roleName);
    }

    /**
     * {@inheritdoc}
     */
    public function getContactPhone(): ?string
    {
        return $this->_get(self::CONTACT_PHONE);
    }

    /**
     * {@inheritdoc}
     */
    public function setContactPhone(string $contactPhone): ThirdPartyOrderInterface
    {
        return $this->setData(self::CONTACT_PHONE, $contactPhone);
    }

    /**
     * {@inheritdoc}
     */
    public function getContactQq(): ?string
    {
        return $this->_get(self::CONTACT_QQ);
    }

    /**
     * {@inheritdoc}
     */
    public function setContactQq(string $contactQq): ThirdPartyOrderInterface
    {
        return $this->setData(self::CONTACT_QQ, $contactQq);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuyerIp(): ?string
    {
        return $this->_get(self::BUYER_IP);
    }

    /**
     * {@inheritdoc}
     */
    public function setBuyerIp(string $buyerIp): ThirdPartyOrderInterface
    {
        return $this->setData(self::BUYER_IP, $buyerIp);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserOrderId(): ?string
    {
        return $this->_get(self::USER_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserOrderId(string $userOrderId): ThirdPartyOrderInterface
    {
        return $this->setData(self::USER_ORDER_ID, $userOrderId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserNotifyUrl(): ?string
    {
        return $this->_get(self::USER_NOTIFY_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserNotifyUrl(string $userNotifyUrl): ThirdPartyOrderInterface
    {
        return $this->setData(self::USER_NOTIFY_URL, $userNotifyUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderMaxAmount(): ?string
    {
        return $this->_get(self::ORDER_MAX_AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderMaxAmount(string $orderMaxAmount): ThirdPartyOrderInterface
    {
        return $this->setData(self::ORDER_MAX_AMOUNT, $orderMaxAmount);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderMaxCurrency(): ?string
    {
        return $this->_get(self::ORDER_MAX_CURRENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderMaxCurrency(string $orderMaxCurrency): ThirdPartyOrderInterface
    {
        return $this->setData(self::ORDER_MAX_CURRENCY, $orderMaxCurrency);
    }
}