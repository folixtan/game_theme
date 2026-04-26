<?php
declare(strict_types=1);

namespace Folix\ChargeTemplate\Api\Data;

/**
 * Interface ChargeTemplateDataInterface
 * 
 * 充值模板数据接口
 * 用于存储和传递充值相关的信息（UID、服务器、角色等）
 * 
 * @api
 */
interface ChargeTemplateDataInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * 充值数据字段常量
     */
    const CHARGE_ACCOUNT = 'charge_account';       // 充值账号（UID）
    const CHARGE_PASSWORD = 'charge_password';     // 充值密码
    const CHARGE_GAME = 'charge_game';             // 充值游戏
    const CHARGE_REGION = 'charge_region';         // 充值区
    const CHARGE_SERVER = 'charge_server';         // 充值服
    const CHARGE_TYPE = 'charge_type';             // 充值类型
    const ROLE_NAME = 'role_name';                 // 角色名
    const CONTACT_PHONE = 'contact_phone';         // 联系电话
    const CONTACT_QQ = 'contact_qq';               // 联系QQ
    /**#@-*/

    /**
     * 获取充值账号（UID）
     *
     * @return string|null
     */
    public function getChargeAccount();

    /**
     * 设置充值账号（UID）
     *
     * @param string $chargeAccount
     * @return $this
     */
    public function setChargeAccount($chargeAccount);

    /**
     * 获取充值密码
     *
     * @return string|null
     */
    public function getChargePassword();

    /**
     * 设置充值密码
     *
     * @param string $chargePassword
     * @return $this
     */
    public function setChargePassword($chargePassword);

    /**
     * 获取充值游戏
     *
     * @return string|null
     */
    public function getChargeGame();

    /**
     * 设置充值游戏
     *
     * @param string $chargeGame
     * @return $this
     */
    public function setChargeGame($chargeGame);

    /**
     * 获取充值区
     *
     * @return string|null
     */
    public function getChargeRegion();

    /**
     * 设置充值区
     *
     * @param string $chargeRegion
     * @return $this
     */
    public function setChargeRegion($chargeRegion);

    /**
     * 获取充值服
     *
     * @return string|null
     */
    public function getChargeServer();

    /**
     * 设置充值服
     *
     * @param string $chargeServer
     * @return $this
     */
    public function setChargeServer($chargeServer);

    /**
     * 获取充值类型
     *
     * @return string|null
     */
    public function getChargeType();

    /**
     * 设置充值类型
     *
     * @param string $chargeType
     * @return $this
     */
    public function setChargeType($chargeType);

    /**
     * 获取角色名
     *
     * @return string|null
     */
    public function getRoleName();

    /**
     * 设置角色名
     *
     * @param string $roleName
     * @return $this
     */
    public function setRoleName($roleName);

    /**
     * 获取联系电话
     *
     * @return string|null
     */
    public function getContactPhone();

    /**
     * 设置联系电话
     *
     * @param string $contactPhone
     * @return $this
     */
    public function setContactPhone($contactPhone);

    /**
     * 获取联系QQ
     *
     * @return string|null
     */
    public function getContactQq();

    /**
     * 设置联系QQ
     *
     * @param string $contactQq
     * @return $this
     */
    public function setContactQq($contactQq);

    /**
     * 获取扩展属性
     *
     * @return \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * 设置扩展属性
     *
     * @param \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Folix\ChargeTemplate\Api\Data\ChargeTemplateDataExtensionInterface $extensionAttributes
    );
}
