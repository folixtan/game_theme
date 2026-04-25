<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Api;

/**
 * 外部API客户端接口 - 基础HTTP请求接口
 * 提供通用的HTTP请求能力，不包含具体业务逻辑
 */
interface ExternalApiClientInterface
{
    /**
     * 发送GET请求
     *
     * @param string $url 请求URL
     * @param array $params 查询参数
     * @param array $headers 请求头
     * @return array
     */
    public function get(string $url, array $params = [], array $headers = []): array;

    /**
     * 发送POST请求
     *
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array
     */
    public function post(string $url, array $data = [], array $headers = []): array;

    /**
     * 发送PUT请求
     *
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array
     */
    public function put(string $url, array $data = [], array $headers = []): array;

    /**
     * 发送DELETE请求
     *
     * @param string $url 请求URL
     * @param array $headers 请求头
     * @return array
     */
    public function delete(string $url, array $headers = []): array;

    /**
     * 获取API基础URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string;
}