<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Model;

use FolixCode\BaseSyncService\Api\VendorConfigInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * 默认供应商配置（从BaseSyncService Helper读取）
 */
class DefaultVendorConfig implements VendorConfigInterface
{
    private BaseHelper $helper;
    private EncryptorInterface $encryptor;

    public function __construct(
        BaseHelper $helper,
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
        return $this->helper->getSecretKey();
         

    }

    public function getEncryptionMethod(): string
    {
        return 'AES-256-CBC';
    }
}
