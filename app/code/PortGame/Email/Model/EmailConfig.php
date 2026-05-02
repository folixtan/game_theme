<?php
/**
 * Copyright © PortGame. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PortGame\Email\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Email Configuration Reader
 * 
 * Reads email configuration from backend settings.
 */
class EmailConfig
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * Configuration paths
     */
    const XML_PATH_GENERAL_ENABLED = 'portgame_email/general/enable_thirdparty_email';
    
    const XML_PATH_THIRDPARTY_CARD_TEMPLATE = 'portgame_email/thirdparty_card/template';
    const XML_PATH_THIRDPARTY_CARD_IDENTITY = 'portgame_email/thirdparty_card/identity';
    const XML_PATH_THIRDPARTY_CARD_ENABLED = 'portgame_email/thirdparty_card/enabled';
    
    const XML_PATH_THIRDPARTY_DIRECT_TEMPLATE = 'portgame_email/thirdparty_direct/template';
    const XML_PATH_THIRDPARTY_DIRECT_IDENTITY = 'portgame_email/thirdparty_direct/identity';
    const XML_PATH_THIRDPARTY_DIRECT_ENABLED = 'portgame_email/thirdparty_direct/enabled';
    
    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }
    
    /**
     * Get current store
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }
    
    /**
     * Check if third party email is globally enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isGloballyEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Check if specific email type is enabled
     *
     * @param string $type
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled(string $type, $storeId = null): bool
    {
        if (!$this->isGloballyEnabled($storeId)) {
            return false;
        }
        
        $path = $this->getEnabledPath($type);
        if (!$path) {
            return false;
        }
        
        return (bool)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get template ID for email type
     *
     * @param string $type
     * @param int|string|null $storeId
     * @return string
     */
    public function getTemplateId(string $type, $storeId = null): string
    {
        $path = $this->getTemplatePath($type);
        return (string)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get identity for email type
     *
     * @param string $type
     * @param int|string|null $storeId
     * @return string
     */
    public function getIdentity(string $type, $storeId = null): string
    {
        $path = $this->getIdentityPath($type);
        return (string)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get enabled config path
     *
     * @param string $type
     * @return string|null
     */
    protected function getEnabledPath(string $type): ?string
    {
        $paths = [
            'thirdparty_card' => self::XML_PATH_THIRDPARTY_CARD_ENABLED,
            'thirdparty_direct' => self::XML_PATH_THIRDPARTY_DIRECT_ENABLED,
        ];
        
        return $paths[$type] ?? null;
    }
    
    /**
     * Get template config path
     *
     * @param string $type
     * @return string|null
     */
    protected function getTemplatePath(string $type): ?string
    {
        $paths = [
            'thirdparty_card' => self::XML_PATH_THIRDPARTY_CARD_TEMPLATE,
            'thirdparty_direct' => self::XML_PATH_THIRDPARTY_DIRECT_TEMPLATE,
        ];
        
        return $paths[$type] ?? null;
    }
    
    /**
     * Get identity config path
     *
     * @param string $type
     * @return string|null
     */
    protected function getIdentityPath(string $type): ?string
    {
        $paths = [
            'thirdparty_card' => self::XML_PATH_THIRDPARTY_CARD_IDENTITY,
            'thirdparty_direct' => self::XML_PATH_THIRDPARTY_DIRECT_IDENTITY,
        ];
        
        return $paths[$type] ?? null;
    }
}
