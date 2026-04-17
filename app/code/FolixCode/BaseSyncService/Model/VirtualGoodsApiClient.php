<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Model;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\BaseSyncService\Api\HttpClientInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use Psr\Log\LoggerInterface;

/**
 * 通用外部API客户端 - 基础HTTP请求实现
 * 提供通用的HTTP请求能力，自动处理加密和签名
 * 不包含具体业务逻辑，可被多个模块复用
 */
class VirtualGoodsApiClient implements ExternalApiClientInterface
{
    private HttpClientInterface $httpClient;
    private BaseHelper $baseHelper;
    private LoggerInterface $logger;
    private string $apiBaseUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        BaseHelper $baseHelper,
        LoggerInterface $logger,
        string $apiBaseUrl = ''
    ) {
        $this->httpClient = $httpClient;
        $this->baseHelper = $baseHelper;
        $this->logger = $logger;
        $this->apiBaseUrl = $apiBaseUrl ?: $this->baseHelper->getApiBaseUrl();
    }

    /**
     * @inheritdoc
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        try {
            // 自动添加加密数据到查询参数
            $encryptedData = $this->baseHelper->encryptRequestData($params);

            // 将加密数据添加到查询参数中
            $finalParams = array_merge($params, [
                'secret_id' => $this->baseHelper->getSecretId(),
                'data' => $encryptedData
            ]);

            // 添加自定义headers
            $finalHeaders = array_merge(['Content-Type' => 'application/json'], $headers);

            $this->logger->debug('Sending GET request', [
                'url' => $url,
                'params' => $params,
                'encrypted' => true
            ]);

            $response = $this->httpClient->get($url, $finalParams, $finalHeaders);

           // $this->logger->debug('GET response received', ['response' => $response]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send GET request: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        try {
          //  var_dump($data);
            // 自动添加加密数据
            $encryptedData = $this->baseHelper->encryptRequestData($data);

            // 将加密数据添加到请求体中
            $finalData = [
                'secret_id' => $this->baseHelper->getSecretId(),
                'data' => $encryptedData
            ];

            // 添加自定义headers
            $finalHeaders = array_merge(['Content-Type' => 'application/json'], $headers);

            $this->logger->debug('Sending POST request', [
                'url' => $url,
                'data' => $data,
                'encrypted' => true
            ]);

            $response = $this->httpClient->post($url, $finalData, $finalHeaders);
            //$this->logger->debug('POST response received', ['response' => $response]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send POST request: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        try {
            // 自动添加加密数据
            $encryptedData = $this->baseHelper->encryptRequestData($data);

            // 将加密数据添加到请求体中
            $finalData = array_merge($data, [
                'secret_id' => $this->baseHelper->getSecretId(),
                'data' => $encryptedData
            ]);

            // 添加自定义headers
            $finalHeaders = array_merge(['Content-Type' => 'application/json'], $headers);

            $this->logger->debug('Sending PUT request', [
                'url' => $url,
                'data' => $data,
                'encrypted' => true
            ]);

            $response = $this->httpClient->put($url, $finalData, $finalHeaders);
          //  $this->logger->debug('PUT response received', ['response' => $response]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send PUT request: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(string $url, array $headers = []): array
    {
        try {
            // 自动添加认证头
            $finalHeaders = array_merge([
                'secret_id' => $this->baseHelper->getSecretId(),
                'Content-Type' => 'application/json'
            ], $headers);

          //  $this->logger->debug('Sending DELETE request', ['url' => $url]);

            $response = $this->httpClient->delete($url, $finalHeaders);
           // $this->logger->debug('DELETE response received', ['response' => $response]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send DELETE request: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取API基础URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    private function validateResponse(array $response): array {
      
        $this->baseHelper->encryptRequestData();
    }
}