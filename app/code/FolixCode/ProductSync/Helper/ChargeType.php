<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Helper;

/**
 * 充值类型助手类
 */
class ChargeType
{
    /**
     * 直充
     */
    public const DIRECT = 'direct';

    /**
     * 卡密
     */
    public const CARD = 'card';

    /**
     * 获取所有充值类型
     *
     * @return array
     */
    public static function getAllTypes(): array
    {
        return [
            self::DIRECT => __('直充'),
            self::CARD => __('卡密')
        ];
    }

    /**
     * 获取充值类型标签
     *
     * @param string $type
     * @return string
     */
    public static function getTypeLabel(string $type): string
    {
        $types = self::getAllTypes();
        return $types[$type] ?? '';
    }

    /**
     * 验证充值类型是否有效
     *
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, [self::DIRECT, self::CARD]);
    }

    /**
     * 是否为直充
     *
     * @param string $type
     * @return bool
     */
    public static function isDirect(string $type): bool
    {
        return $type === self::DIRECT;
    }

    /**
     * 是否为卡密
     *
     * @param string $type
     * @return bool
     */
    public static function isCard(string $type): bool
    {
        return $type === self::CARD;
    }
}