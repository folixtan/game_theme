<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Api\Message;

/**
 * 消息队列发布者接口
 */
interface PublisherInterface
{
    /**
     * 发布产品导入消息
     *
     * @param array $productData
     * @return void
     */
    public function publishProductImport(array $productData): void;

    /**
     * 发布分类导入消息
     *
     * @param array $categoryData
     * @return void
     */
    public function publishCategoryImport(array $categoryData): void;

}