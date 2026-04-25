<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Api;

/**
 * 加密策略接口
 */
interface EncryptionStrategyInterface
{
    /**
     * 加密数据
     *
     * @param array $data
     * @return string
     */
    public function encrypt(array $data): string;

    /**
     * 解密数据
     *
     * @param string $encryptedData
     * @return array
     */
    public function decrypt(string $encryptedData): array;

    /**
     * 生成签名
     *
     * @param array $data
     * @param string $timestamp
     * @return string
     */
    public function generateSignature(array $data, string $timestamp): string;

    /**
     * 获取加密方法名称
     *
     * @return string
     */
    public function getMethodName(): string;
}
