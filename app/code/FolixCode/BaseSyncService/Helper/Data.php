<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * BaseSyncService Helper - 处理加密解密、配置等通用功能
 */
class Data extends AbstractHelper
{
    /**
     * 配置路径常量
     */
    public const XML_PATH_API_BASE_URL = 'folixcode_basesyncservice/settings/api_base_url';
    public const XML_PATH_SECRET_ID = 'folixcode_basesyncservice/settings/secret_id';
    public const XML_PATH_SECRET_KEY = 'folixcode_basesyncservice/settings/secret_key';
    public const XML_PATH_API_KEY = 'folixcode_basesyncservice/settings/api_key';
    public const XML_PATH_IS_ENABLED = 'folixcode_basesyncservice/settings/is_enabled';
    public const XML_PATH_SYNC_INTERVAL = 'folixcode_basesyncservice/settings/sync_interval';

    public const SECRET_ID = '81097469c53704b748e';

    public const SECRET_KEY = 'eO6OSXX1kfVcoQhYacc4u9t6FJnulT5f';

    public const ENCRYPT_METHOD = 'AES-256-CBC';


    private EncryptorInterface $encryptor;
    private Json $json;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        Json $json
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->json = $json;
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
        ) ?: self::SECRET_ID;
    }

    /**
     * 获取Secret Key
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

        return self::SECRET_KEY;//test KEY
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
     * 获取同步间隔（分钟）
     *
     * @return int
     */
    public function getSyncInterval(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_SYNC_INTERVAL,
            ScopeInterface::SCOPE_STORE
        ) ?: 60;
    }

    /**
     * 加密请求数据（按照API文档的规范）
     *
     * 加密步骤：
     * 1. 随机生成长16字节的初始向量iv
     * 2. 使用AES-256-CBC和PKCS#7填充进行加密
     * 3. 将iv和加密后的字符串分别base64编码
     * 4. 拼装成JSON: {"iv":"base64编码后的iv","value":"base64后的encryptedString"}
     * 5. 将此JSON再进行base64编码，得到最终的data密文
     *
     * @param array $data 请求数据
     * @return string 加密后的字符串
     */
    public function encryptRequestData(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        try {
            // 将数据转换为JSON
            $jsonData = $this->json->serialize($data);

            // 使用Secret Key进行AES加密
            $key = $this->getSecretKey();
            if (empty($key)) {
                $this->_logger->warning('Secret Key is not configured, returning unencrypted data');
                return base64_encode($jsonData);
            }

           // 2. 随机生成 16 字节的初始向量 (IV)
            // openssl_cipher_iv_length($method) 会自动返回 16，写死也可以，但动态获取更严谨
            $ivLength = openssl_cipher_iv_length(static::ENCRYPT_METHOD);
            $iv = openssl_random_pseudo_bytes($ivLength);

            // 使用AES-256-CBC加密，OPENSSL_RAW_DATA表示不自动base64编码
          //  var_dump(static::ENCRYPT_METHOD, $normalizedKey, $iv);exit;
            $encrypted = openssl_encrypt(
                $jsonData,
                static::ENCRYPT_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            // 检查加密是否成功
            if ($encrypted === false) {
                throw new \RuntimeException('AES-256-CBC encryption failed: ' . openssl_error_string());
            }

            // 将iv和encrypted分别base64编码
            $ivBase64 = base64_encode($iv);
            $encryptedBase64 = base64_encode($encrypted);

            // 拼装成JSON
            $jsonPayload = $this->json->serialize([
                'iv' => $ivBase64,
                'value' => $encryptedBase64
            ]);

            // 将JSON再进行base64编码，得到最终的data密文
            $result = base64_encode($jsonPayload);

            $this->_logger->info('Request data encrypted successfully', [
                'data_length' => strlen($jsonData),
                'encrypted_length' => strlen($result),
                'method' => static::ENCRYPT_METHOD
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->_logger->error('Failed to encrypt request data: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * 解密响应数据（按照API文档的规范）
     *
     * 解密步骤：
     * 1. 将data密文进行base64解码，得到JSON
     * 2. 将JSON中的iv进行base64解码，得到初始向量iv
     * 3. 将JSON中的value进行base64解码，得到密文encryptedString
     * 4. 使用key和iv，对encryptedString进行AES-256-CBC解密
     *
     * @param string $encryptedData 加密的数据
     * @return array 解密后的数组
     */
    public function decryptResponseData(string $encryptedData): array
    {
        if (empty($encryptedData)) {
            return [];
        }

        try {
            $key = $this->getSecretKey();
            if (empty($key)) {
                $this->_logger->warning('Secret Key is not configured, attempting base64 decode');
                return $this->json->unserialize(base64_decode($encryptedData));
            }
 
            // 第一步：将data密文进行base64解码，得到JSON
            $jsonPayload = base64_decode($encryptedData);

            if ($jsonPayload === false) {
                throw new \RuntimeException('Failed to base64 decode encrypted data');
            }

            // 第二步：解析JSON，获取iv和value
            $payloadData = $this->json->unserialize($jsonPayload);

            if (!isset($payloadData['iv']) || !isset($payloadData['value'])) {
                throw new \RuntimeException('Invalid encrypted data format: missing iv or value');
            }

            // 第三步：将iv进行base64解码，得到初始向量iv
            $iv = base64_decode($payloadData['iv']);
            if ($iv === false || strlen($iv) !== 16) {
                throw new \RuntimeException('Invalid IV: must be 16 bytes after base64 decode');
            }

            // 第四步：将value进行base64解码，得到密文encryptedString
            $encryptedString = base64_decode($payloadData['value']);
            if ($encryptedString === false) {
                throw new \RuntimeException('Failed to base64 decode encrypted value');
            }

            // 第五步：使用key和iv，对encryptedString进行AES-256-CBC解密
            $decrypted = openssl_decrypt(
                $encryptedString,
                static::ENCRYPT_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Failed to decrypt response data: ' . openssl_error_string());
            }

            $data = $this->json->unserialize($decrypted);

            $this->_logger->info('Response data decrypted successfully', ['method' => static::ENCRYPT_METHOD]);

            return is_array($data) ? $data : [];

        } catch (\Exception $e) {
            $this->_logger->error('Failed to decrypt response data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 生成签名（按照语雀文档的签名认证方式）
     *
     * @param array $data 请求数据
     * @param string $timestamp 时间戳
     * @return string 签名
     */
    public function generateSignature(array $data, string $timestamp): string
    {
        $secretKey = $this->getSecretKey();

        // 按文档的签名算法生成
        $params = array_merge($data, ['timestamp' => $timestamp]);
        ksort($params);

        $stringToSign = http_build_query($params);
        $stringToSign .= '&key=' . $secretKey;

        return md5($stringToSign);
    }

    /**
     * 获取最后一次同步时间戳
     *
     * @return int
     */
    public function getLastSyncTimestamp(): int
    {
        return (int)$this->scopeConfig->getValue(
            'folixcode_basesyncservice/settings/last_sync_timestamp',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * 设置最后一次同步时间戳
     *
     * @param int $timestamp
     * @return $this
     */
    public function setLastSyncTimestamp(int $timestamp): self
    {
        // 这里需要通过配置模型来保存
        return $this;
    }
}
