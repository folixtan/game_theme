<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model;

use FolixCode\BaseSyncService\Api\VendorConfigInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseSyncHelper;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * ProductSync 供应商配置
 */
class VendorConfig implements VendorConfigInterface
{
    private BaseSyncHelper $helper;
    private EncryptorInterface $encryptor;

    public function __construct(
        BaseSyncHelper $helper,
        EncryptorInterface $encryptor
    ) {
        $this->helper = $helper;
        $this->encryptor = $encryptor;
    }

    public function getApiBaseUrl(): string
    {
        return $this->helper->getApiBaseUrl();
    }

    public function getSecretId(): string
    {
        return $this->helper->getSecretId();
    }

    public function getSecretKey(): string
    {
        $encryptedKey = $this->helper->getSecretKey();
        if (!empty($encryptedKey)) {
            try {
                return $this->encryptor->decrypt($encryptedKey);
            } catch (\Exception $e) {
                return '';
            }
        }
        return '';
    }

    public function getEncryptionMethod(): string
    {
        return 'AES-256-CBC';
    }
}
