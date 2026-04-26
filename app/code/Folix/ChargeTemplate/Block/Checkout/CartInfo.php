<?php
namespace Folix\ChargeTemplate\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Folix\ChargeTemplate\Helper\Data as ChargeTemplateHelper;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * 购物车和 Checkout 页面显示充值信息的 Block
 */
class CartInfo extends Template
{
    /**
     * @var ChargeTemplateHelper
     */
    protected $chargeTemplateHelper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @param Context $context
     * @param ChargeTemplateHelper $chargeTemplateHelper
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        ChargeTemplateHelper $chargeTemplateHelper,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->chargeTemplateHelper = $chargeTemplateHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * 获取当前购物车的所有 Item
     * 
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getQuoteItems()
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote->getAllVisibleItems();
    }

    /**
     * 获取 Item 的充值模板数据
     * 
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array|null
     */
    public function getItemChargeData($item)
    {
        return $this->chargeTemplateHelper->getChargeTemplateDataFromQuoteItem($item);
    }

    /**
     * 格式化显示充值信息
     * 
     * @param array $chargeData
     * @return string
     */
    public function formatChargeInfo($chargeData)
    {
        return $this->chargeTemplateHelper->formatChargeDataForDisplay($chargeData);
    }

    /**
     * 检查 Item 是否有充值数据
     * 
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function hasChargeData($item)
    {
        $chargeData = $this->getItemChargeData($item);
        return !empty($chargeData);
    }
}
