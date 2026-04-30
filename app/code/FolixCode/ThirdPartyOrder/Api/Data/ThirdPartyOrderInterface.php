<?php
/**
 * Interface ThirdPartyOrderInterface
 * 定义第三方订单的数据结构
 */
namespace FolixCode\ThirdPartyOrder\Api\Data;

interface ThirdPartyOrderInterface
{
    /**
     * 常量定义
     */
    const PRODUCT_ID = 'product_id';
    const BUY_NUM = 'buy_num';
    const CHARGE_ACCOUNT = 'charge_account';
    const CHARGE_PASSWORD = 'charge_password';
    const CHARGE_GAME = 'charge_game';
    const CHARGE_REGION = 'charge_region';
    const CHARGE_SERVER = 'charge_server';
    const CHARGE_TYPE = 'charge_type';
    const ROLE_NAME = 'role_name';
    const CONTACT_PHONE = 'contact_phone';
    const CONTACT_QQ = 'contact_qq';
    const BUYER_IP = 'buyer_ip';
    const USER_ORDER_ID = 'user_order_id';
    const USER_NOTIFY_URL = 'user_notify_url';
    const ORDER_MAX_AMOUNT = 'order_max_amount';
    const ORDER_MAX_CURRENCY = 'order_max_currency';

    /**
     * 获取产品ID
     * 必填字段
     *
     * @return string|null
     */
    public function getProductId(): ?string;

    /**
     * 设置产品ID
     * 必填字段
     *
     * @param string $productId
     * @return $this
     */
    public function setProductId(string $productId): self;

    /**
     * 获取购买数量
     * 必填字段
     *
     * @return string|null
     */
    public function getBuyNum(): ?string;

    /**
     * 设置购买数量
     * 必填字段
     *
     * @param string $buyNum
     * @return $this
     */
    public function setBuyNum(string $buyNum): self;

    /**
     * 获取充值账号
     * 可选字段
     *
     * @return string|null
     */
    public function getChargeAccount(): ?string;

    /**
     * 设置充值账号
     * 可选字段
     *
     * @param string $chargeAccount
     * @return $this
     */
    public function setChargeAccount(string $chargeAccount): self;

    /**
     * 获取充值密码
     * 可选字段
     *
     * @return string|null
     */
    public function getChargePassword(): ?string;

    /**
     * 设置充值密码
     * 可选字段
     *
     * @param string $chargePassword
     * @return $this
     */
    public function setChargePassword(string $chargePassword): self;

    /**
     * 获取充值游戏
     * 可选字段
     *
     * @return string|null
     */
    public function getChargeGame(): ?string;

    /**
     * 设置充值游戏
     * 可选字段
     *
     * @param string $chargeGame
     * @return $this
     */
    public function setChargeGame(string $chargeGame): self;

    /**
     * 获取充值区
     * 可选字段
     *
     * @return string|null
     */
    public function getChargeRegion(): ?string;

    /**
     * 设置充值区
     * 可选字段
     *
     * @param string $chargeRegion
     * @return $this
     */
    public function setChargeRegion(string $chargeRegion): self;

    /**
     * 获取充值服
     * 可选字段
     *
     * @return string|null
     */
    public function getChargeServer(): ?string;

    /**
     * 设置充值服
     * 可选字段
     *
     * @param string $chargeServer
     * @return $this
     */
    public function setChargeServer(string $chargeServer): self;

    /**
     * 获取充值类型
     * 可选字段
     *
     * @return string|null
     */
    public function getChargeType(): ?string;

    /**
     * 设置充值类型
     * 可选字段
     *
     * @param string $chargeType
     * @return $this
     */
    public function setChargeType(string $chargeType): self;

    /**
     * 获取角色名
     * 可选字段
     *
     * @return string|null
     */
    public function getRoleName(): ?string;

    /**
     * 设置角色名
     * 可选字段
     *
     * @param string $roleName
     * @return $this
     */
    public function setRoleName(string $roleName): self;

    /**
     * 获取联系电话
     * 可选字段
     *
     * @return string|null
     */
    public function getContactPhone(): ?string;

    /**
     * 设置联系电话
     * 可选字段
     *
     * @param string $contactPhone
     * @return $this
     */
    public function setContactPhone(string $contactPhone): self;

    /**
     * 获取联系QQ
     * 可选字段
     *
     * @return string|null
     */
    public function getContactQq(): ?string;

    /**
     * 设置联系QQ
     * 可选字段
     *
     * @param string $contactQq
     * @return $this
     */
    public function setContactQq(string $contactQq): self;

    /**
     * 获取买家IP
     * 可选字段
     *
     * @return string|null
     */
    public function getBuyerIp(): ?string;

    /**
     * 设置买家IP
     * 可选字段
     *
     * @param string $buyerIp
     * @return $this
     */
    public function setBuyerIp(string $buyerIp): self;

    /**
     * 获取用户订单号
     * 必填字段
     *
     * @return string|null
     */
    public function getUserOrderId(): ?string;

    /**
     * 设置用户订单号
     * 必填字段
     *
     * @param string $userOrderId
     * @return $this
     */
    public function setUserOrderId(string $userOrderId): self;

    /**
     * 获取用户回调地址
     * 可选字段
     *
     * @return string|null
     */
    public function getUserNotifyUrl(): ?string;

    /**
     * 设置用户回调地址
     * 可选字段
     *
     * @param string $userNotifyUrl
     * @return $this
     */
    public function setUserNotifyUrl(string $userNotifyUrl): self;

    /**
     * 获取订单最大成本金额
     * 可选字段
     *
     * @return string|null
     */
    public function getOrderMaxAmount(): ?string;

    /**
     * 设置订单最大成本金额
     * 可选字段
     *
     * @param string $orderMaxAmount
     * @return $this
     */
    public function setOrderMaxAmount(string $orderMaxAmount): self;

    /**
     * 获取订单最大成本金额币种
     * 可选字段
     *
     * @return string|null
     */
    public function getOrderMaxCurrency(): ?string;

    /**
     * 设置订单最大成本金额币种
     * 可选字段
     *
     * @param string $orderMaxCurrency
     * @return $this
     */
    public function setOrderMaxCurrency(string $orderMaxCurrency): self;
}