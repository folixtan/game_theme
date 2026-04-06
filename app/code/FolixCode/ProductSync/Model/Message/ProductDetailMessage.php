<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Model\Message;

use FolixCode\ProductSync\Api\Message\ProductDetailMessageInterface;

/**
 * 产品详情消息实现
 */
class ProductDetailMessage implements ProductDetailMessageInterface
{
    private string $productId;

    /**
     * @inheritdoc
     */
    public function create(array $data): ProductDetailMessageInterface
    {
        $message = new self();
        $message->setProductId($data['product_id'] ?? '');
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @inheritdoc
     */
    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }
}