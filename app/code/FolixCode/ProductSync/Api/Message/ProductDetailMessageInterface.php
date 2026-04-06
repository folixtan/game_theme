<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Api\Message;

/**
 * 产品详情消息接口
 */
interface ProductDetailMessageInterface
{
    /**
     * 创建消息实例
     *
     * @param array $data
     * @return self
     */
    public function create(array $data): self;

    /**
     * 获取产品ID
     *
     * @return string
     */
    public function getProductId(): string;

    /**
     * 设置产品ID
     *
     * @param string $productId
     * @return void
     */
    public function setProductId(string $productId): void;
}