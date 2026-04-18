<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\Message;

use FolixCode\ProductSync\Api\Message\ProductImportMessageInterface;

/**
 * 产品导入消息实现
 */
class ProductImportMessage implements ProductImportMessageInterface
{
    private array $data;

    /**
     * @inheritdoc
     */
    public function create(array $data): ProductImportMessageInterface
    {
        $message = new self();
        $message->setData($data);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}