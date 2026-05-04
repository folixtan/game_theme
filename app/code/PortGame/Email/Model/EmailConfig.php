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
    const XML_PATH_ENABLED = 'portgame/email/enabled';
    const XML_PATH_TEMPLATE = 'portgame/email/template';
    const XML_PATH_IDENTITY = 'portgame/email/identity';
    
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
     * Check if email is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get template ID
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getTemplateId($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * Get identity
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getIdentity($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_IDENTITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
}
