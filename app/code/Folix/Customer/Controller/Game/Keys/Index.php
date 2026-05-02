<?php
/**
 * Copyright © Folix Game Theme. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Folix\Customer\Controller\Game\Keys;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Customer\Model\Session;

/**
 * Customer Game Keys Index Controller
 * 
 * URL: /folix/game_keys/index
 * Maps to: Folix\Customer\Controller\Game\Keys\Index
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Session $customerSession
     */
    public function __construct(
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory,
        Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Display customer's game keys
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 检查客户是否登录
        if (!$this->customerSession->isLoggedIn()) {
            return $this->resultRedirectFactory->create()->setPath('customer/account/login');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Game Keys'));
        return $resultPage;
    }
}
