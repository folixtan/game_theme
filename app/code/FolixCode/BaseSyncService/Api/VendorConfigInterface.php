<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Api;

/**
 * 供应商配置接口
 * 
 * 业务模块实现此接口提供API配置信息
 */
interface VendorConfigInterface
{
    /**
     * 获取API基础URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string;

    /**
     * 获取Secret ID
     *
     * @return string
     */
    public function getSecretId(): string;

    /**
     * 获取Secret Key（已解密）
     *
     * @return string
     */
    public function getSecretKey(): string;

    /**
     * 获取加密方法名称
     *
     * @return string
     */
    public function getEncryptionMethod(): string;
}
