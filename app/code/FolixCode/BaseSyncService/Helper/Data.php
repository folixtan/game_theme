<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * BaseSyncService Helper - 处理供应商配置管理
 * 
 * 职责：
 * - 读取供应商配置（API URL, Secret ID/Key等）
 * - 提供加密密钥的安全访问（自动解密）
 * - 不包含任何业务逻辑（如同步间隔、时间戳管理等）
 */
class Data extends AbstractHelper
{
    /**
     * 配置路径常量
     */
    public const XML_PATH_API_BASE_URL = 'folixcode_basesyncservice/settings/api_base_url';
    public const XML_PATH_SECRET_ID = 'folixcode_basesyncservice/settings/secret_id';
    public const XML_PATH_SECRET_KEY = 'folixcode_basesyncservice/settings/secret_key';
    public const XML_PATH_IS_ENABLED = 'folixcode_basesyncservice/settings/is_enabled';

    // 默认测试配置（仅用于开发环境）
    public const DEFAULT_SECRET_ID = '81097469c53704b748e';
    public const DEFAULT_SECRET_KEY = 'eO6OSXX1kfVcoQhYacc4u9t6FJnulT5f';

    private EncryptorInterface $encryptor;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * 获取API基础URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        $url = $this->scopeConfig->getValue(
            self::XML_PATH_API_BASE_URL,
            ScopeInterface::SCOPE_STORE
        );

        return rtrim($url ?: 'https://playsentral.qr67.com', '/');
    }

    /**
     * 获取Secret ID
     *
     * @return string
     */
    public function getSecretId(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SECRET_ID,
            ScopeInterface::SCOPE_STORE
        ) ?: self::DEFAULT_SECRET_ID;
    }

    /**
     * 获取Secret Key（自动解密）
     *
     * @return string
     */
    public function getSecretKey(): string
    {
        $encryptedKey = $this->scopeConfig->getValue(
            self::XML_PATH_SECRET_KEY,
            ScopeInterface::SCOPE_STORE
        );

        if ($encryptedKey) {
            return $this->encryptor->decrypt($encryptedKey);
        }

        return self::DEFAULT_SECRET_KEY;
    }

    /**
     * 检查模块是否启用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * 解密第三方响应数据
     * 
     * 使用AES-256-CBC算法解密第三方API返回的加密数据
     *
     * @param string $encryptedData Base64编码的加密数据
     * @return array|null 解密后的数组数据，失败返回null
     */
    public function decryptResponseData(string $encryptedData): ?array
    {
        try {
            // Base64解码
            $binaryData = base64_decode($encryptedData, true);
            if ($binaryData === false) {
                $this->_logger->error('Failed to base64 decode encrypted data');
                return null;
            }

            // 获取密钥
            $secretKey = $this->getSecretKey();
            
            // AES-256-CBC解密（假设IV在前16字节）
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            if ($ivLength === false || strlen($binaryData) < $ivLength) {
                $this->_logger->error('Invalid encrypted data length');
                return null;
            }

            $iv = substr($binaryData, 0, $ivLength);
            $ciphertext = substr($binaryData, $ivLength);

            $decrypted = openssl_decrypt(
                $ciphertext,
                'aes-256-cbc',
                $secretKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                $this->_logger->error('Failed to decrypt data');
                return null;
            }

            // JSON解码
            $data = json_decode($decrypted, true);
            if (!is_array($data)) {
                $this->_logger->error('Decrypted data is not valid JSON array');
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            $this->_logger->error('Exception during decryption', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
