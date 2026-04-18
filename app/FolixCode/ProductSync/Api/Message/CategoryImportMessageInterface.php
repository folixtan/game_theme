<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Api\Message;

/**
 * 分类导入消息接口
 */
interface CategoryImportMessageInterface
{
    /**
     * 创建消息实例
     *
     * @param array $data
     * @return self
     */
    public function create(array $data): self;

    /**
     * 获取分类数据
     *
     * @return array
     */
    public function getData(): array;

    /**
     * 设置分类数据
     *
     * @param array $data
     * @return void
     */
    public function setData(array $data): void;
}