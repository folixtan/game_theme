<?php
declare(strict_types=1);

namespace Folix\ChargeTemplate\Model;

use Folix\ChargeTemplate\Api\Data\ChargeTemplateDataInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

/**
 * Class ChargeTemplateData
 * 
 * 充值模板数据实现类
 * 
 * @api
 */
class ChargeTemplateData extends AbstractExtensibleObject implements ChargeTemplateDataInterface
{
    /**
     * @inheritdoc
     */
    public function getChargeAccount()
    {
        return $this->_get(self::CHARGE_ACCOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setChargeAccount($chargeAccount)
    {
        return $this->setData(self::CHARGE_ACCOUNT, $chargeAccount);
    }

    /**
     * @inheritdoc
     */
    public function getChargePassword()
    {
        return $this->_get(self::CHARGE_PASSWORD);
    }

    /**
     * @inheritdoc
     */
    public function setChargePassword($chargePassword)
    {
        return $this->setData(self::CHARGE_PASSWORD, $chargePassword);
    }

    /**
     * @inheritdoc
     */
    public function getChargeGame()
    {
        return $this->_get(self::CHARGE_GAME);
    }

    /**
     * @inheritdoc
     */
    public function setChargeGame($chargeGame)
    {
        return $this->setData(self::CHARGE_GAME, $chargeGame);
    }

    /**
     * @inheritdoc
     */
    public function getChargeRegion()
    {
        return $this->_get(self::CHARGE_REGION);
    }

    /**
     * @inheritdoc
     */
    public function setChargeRegion($chargeRegion)
    {
        return $this->setData(self::CHARGE_REGION, $chargeRegion);
    }

    /**
     * @inheritdoc
     */
    public function getChargeServer()
    {
        return $this->_get(self::CHARGE_SERVER);
    }

    /**
     * @inheritdoc
     */
    public function setChargeServer($chargeServer)
    {
        return $this->setData(self::CHARGE_SERVER, $chargeServer);
    }

    /**
     * @inheritdoc
     */
    public function getChargeType()
    {
        return $this->_get(self::CHARGE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setChargeType($chargeType)
    {
        return $this->setData(self::CHARGE_TYPE, $chargeType);
    }

    /**
     * @inheritdoc
     */
    public function getRoleName()
    {
        return $this->_get(self::ROLE_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setRoleName($roleName)
    {
        return $this->setData(self::ROLE_NAME, $roleName);
    }

    /**
     * @inheritdoc
     */
    public function getContactPhone()
    {
        return $this->_get(self::CONTACT_PHONE);
    }

    /**
     * @inheritdoc
     */
    public function setContactPhone($contactPhone)
    {
        return $this->setData(self::CONTACT_PHONE, $contactPhone);
    }

    /**
     * @inheritdoc
     */
    public function getContactQq()
    {
        return $this->_get(self::CONTACT_QQ);
    }

    /**
     * @inheritdoc
     */
    public function setContactQq($contactQq)
    {
        return $this->setData(self::CONTACT_QQ, $contactQq);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(
        \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
