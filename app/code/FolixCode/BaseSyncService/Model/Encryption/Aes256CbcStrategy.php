<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Model\Encryption;

use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use FolixCode\BaseSyncService\Api\VendorConfigInterface;

/**
 * AES-256-CBC 加密策略
 * 
 * 按照API文档规范实现：
 * 1. 随机生成长16字节的初始向量iv
 * 2. 使用AES-256-CBC和PKCS#7填充进行加密
 * 3. 将iv和加密后的字符串分别base64编码
 * 4. 拼装成JSON: {"iv":"base64编码后的iv","value":"base64后的encryptedString"}
 * 5. 将此JSON再进行base64编码，得到最终的data密文
 */
class Aes256CbcStrategy implements EncryptionStrategyInterface
{
 

    private Json $json;
    private LoggerInterface $logger;
    private VendorConfigInterface $config;

    public function __construct(
        Json $json,
        LoggerInterface $logger,
        VendorConfigInterface $config
    ) {
        $this->json = $json;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    private function getSecertKey():string {

        return $this->config->getSecretKey();
    }

    /**
     * @inheritdoc
     */
    public function encrypt(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        try {
            // 将数据转换为JSON
            $jsonData = $this->json->serialize($data);

            if (empty($this->getSecertKey())) {
                throw new \RuntimeException('Secret Key is not configured');
            }

            // 随机生成长16字节的初始向量iv（每次加密都不同）
            $ivLength = openssl_cipher_iv_length($this->config->getEncryptionMethod());
            $iv = openssl_random_pseudo_bytes($ivLength);

            // 使用AES-256-CBC加密，OPENSSL_RAW_DATA表示不自动base64编码
            $encrypted = openssl_encrypt(
                $jsonData,
                $this->config->getEncryptionMethod(),
                $this->getSecertKey(),
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

            $this->logger->debug('Data encrypted successfully', [
                'method' => $this->config->getEncryptionMethod(),
                'data_length' => strlen($jsonData),
                'encrypted_length' => strlen($result)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to encrypt data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function decrypt(string $encryptedData): array
    {
        if (empty($encryptedData)) {
            return [];
        }

        try {
            if (empty($this->getSecertKey())) {
                $this->logger->warning('Secret Key is not configured, attempting base64 decode');
                return $this->json->unserialize(base64_decode($encryptedData));
            }

            // 第一步：将data密文进行base64解码，得到JSON
            $jsonPayload = base64_decode($encryptedData, true);

            if ($jsonPayload === false) {
                throw new \RuntimeException('Failed to base64 decode encrypted data');
            }

            // 第二步：解析JSON，获取iv和value
            $payloadData = $this->json->unserialize($jsonPayload);

            if (!isset($payloadData['iv']) || !isset($payloadData['value'])) {
                throw new \RuntimeException('Invalid encrypted data format: missing iv or value');
            }

            // 第三步：将iv进行base64解码，得到初始向量iv
            $iv = base64_decode($payloadData['iv'], true);
            if ($iv === false || strlen($iv) !== 16) {
                throw new \RuntimeException('Invalid IV: must be 16 bytes after base64 decode');
            }

            // 第四步：将value进行base64解码，得到密文encryptedString
            $encryptedString = base64_decode($payloadData['value'], true);
            if ($encryptedString === false) {
                throw new \RuntimeException('Failed to base64 decode encrypted value');
            }

            // 第五步：使用key和iv，对encryptedString进行AES-256-CBC解密
            $decrypted = openssl_decrypt(
                $encryptedString,
                $this->config->getEncryptionMethod(),
                $this->getSecertKey(),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Failed to decrypt data: ' . openssl_error_string());
            }

            $data = $this->json->unserialize($decrypted);

            $this->logger->debug('Data decrypted successfully', [
                'method' => $this->config->getEncryptionMethod()
            ]);

            return is_array($data) ? $data : [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function generateSignature(array $data, string $timestamp): string
    {
        $params = array_merge($data, ['timestamp' => $timestamp]);
        ksort($params);

        $stringToSign = http_build_query($params);
        $stringToSign .= '&key=' . $this->getSecertKey();

        return md5($stringToSign);
    }

    /**
     * @inheritdoc
     */
    public function getMethodName(): string
    {
        return $this->config->getEncryptionMethod();
    }
}
