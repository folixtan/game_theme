<?php
/**
 * Copyright © PortGame. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PortGame\Email\Model;

use Magento\Framework\Mail\Template\TransportBuilder;
use PortGame\Email\Model\Container\Template;
use Psr\Log\LoggerInterface;

/**
 * Email Sender Builder
 * 
 * Builds and sends emails using Template Container and TransportBuilder.
 * Follows Magento native pattern from \Magento\Sales\Model\Order\Email\SenderBuilder
 */
class SenderBuilder
{
    /**
     * @var Template
     */
    protected $templateContainer;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var EmailConfig
     */
    protected $emailConfig;

    private $logger;

    /**
     * Constructor
     *
     * @param Template $templateContainer
     * @param TransportBuilder $transportBuilder
     * @param EmailConfig $emailConfig
     */
    public function __construct(
        Template $templateContainer,
        TransportBuilder $transportBuilder,
        LoggerInterface $logger,
        EmailConfig $emailConfig
    ) {
        $this->templateContainer = $templateContainer;
        $this->transportBuilder = $transportBuilder;
        $this->emailConfig = $emailConfig;
        $this->logger = $logger;
    }

    /**
     * Prepare and send email message
     *
     * @param string $customerEmail Customer email address
     * @param string $customerName Customer name
     * @return void
     */
    public function send(
        string $customerEmail,
        string $customerName
    ): void {
        try {
            // Check if email is enabled
            if (!$this->emailConfig->isEnabled()) {
                return;
            }

            $this->configureEmailTemplate();

            $this->transportBuilder->addTo($customerEmail, $customerName);

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
           $this->logger->error('Failed to send email: ' . $e->getMessage());
        }
        $this->templateContainer->_resetState();
    }

    /**
     * Configure email template
     *
     * @return void
     */
    protected function configureEmailTemplate(): void
    {
        // Get template ID and identity from config
        $templateId = $this->emailConfig->getTemplateId();
        $identity = $this->emailConfig->getIdentity();
        $store = $this->emailConfig->getStore();

        $this->transportBuilder->setTemplateIdentifier($templateId);
        $this->transportBuilder->setTemplateOptions($this->templateContainer->getTemplateOptions());
        $this->transportBuilder->setTemplateVars($this->templateContainer->getTemplateVars());
        $this->transportBuilder->setFromByScope($identity, $store->getId());
    }
}
