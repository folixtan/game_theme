<?php
declare(strict_types=1);

namespace Folix\SimpleCheckout\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Folix\SimpleCheckout\Api\ValidateEmailInterface;

class ValidateEmail implements ValidateEmailInterface
{
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager,
        private CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function isEmailAvailable(string $email, ?int $websiteId = null): bool
    {
        $guestLoginConfig = $this->scopeConfig->getValue(
            AccountManagement::GUEST_CHECKOUT_LOGIN_OPTION_SYS_CONFIG,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        $result = false;

        if (!$guestLoginConfig) {
            $result = true;
        }

        try {
            if ($websiteId === null) {
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
            }

            $this->customerRepository->get($email, $websiteId);
            $result = false;
        } catch (NoSuchEntityException $e) {
            $result = true;
        }

        return $result;
    }
}