<?php
declare(strict_types=1);

namespace Folix\SimpleCheckout\Api;

interface ValidateEmailInterface
{
    /**
     * Check if email is available (not registered)
     *
     * @param string $email
     * @param int|null $websiteId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEmailAvailable(string $email, ?int $websiteId = null): bool;
}