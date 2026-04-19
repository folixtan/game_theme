<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * API 同步异常
 * 
 * 用于处理游戏充值 API 同步过程中的业务异常
 * 包括产品导入、分类同步、库存更新等操作的错误场景
 */
class ApiSyncException extends LocalizedException
{
    /**
     * @var array 额外的上下文数据，用于日志记录和调试
     */
    protected $contextData = [];

    /**
     * 构造函数
     *
     * @param Phrase $phrase 本地化的错误消息
     * @param \Exception|null $cause 原始异常（可选）
     * @param int $code 错误代码
     * @param array $contextData 额外的上下文数据（如 product_id, api_response 等）
     */
    public function __construct(
        Phrase $phrase,
        ?\Exception $cause = null,
        $code = 0,
        array $contextData = []
    ) {
        $this->contextData = $contextData;
        parent::__construct($phrase, $cause, $code);
    }

    /**
     * 获取额外的上下文数据
     *
     * @return array
     */
    public function getContextData(): array
    {
        return $this->contextData;
    }

    /**
     * 设置额外的上下文数据
     *
     * @param array $contextData
     * @return self
     */
    public function setContextData(array $contextData): self
    {
        $this->contextData = $contextData;
        return $this;
    }
}
