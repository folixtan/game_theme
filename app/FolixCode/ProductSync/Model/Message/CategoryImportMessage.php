<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\Message;

use FolixCode\ProductSync\Api\Message\CategoryImportMessageInterface;

/**
 * 分类导入消息实现
 */
class CategoryImportMessage implements CategoryImportMessageInterface
{
    private array $data;

    /**
     * @inheritdoc
     */
    public function create(array $data): CategoryImportMessageInterface
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