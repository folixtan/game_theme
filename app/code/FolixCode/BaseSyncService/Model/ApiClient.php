<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Model;

use FolixCode\BaseSyncService\Api\EncryptionStrategyInterface;
use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\BaseSyncService\Api\VendorConfigInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * 统一 API 客户端
 */
class ApiClient implements ExternalApiClientInterface
{
    private Client $guzzleClient;
    private EncryptionStrategyInterface $encryptionStrategy;
    private LoggerInterface $logger;
    private VendorConfigInterface $vendorConfig;

    public function __construct(
        VendorConfigInterface $vendorConfig,
        EncryptionStrategyInterface $encryptionStrategy,
        LoggerInterface $logger,
        array $guzzleConfig = []
    ) {
        $defaultConfig = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'verify' => false,
        ];

        $this->guzzleClient = new Client(array_merge($defaultConfig, $guzzleConfig));
        $this->vendorConfig = $vendorConfig;
        $this->encryptionStrategy = $encryptionStrategy;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        return $this->request('GET', $url, $params, $headers);
    }

    /**
     * @inheritdoc
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * @inheritdoc
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, [], $headers);
    }

    /**
     * 统一的请求处理方法
     */
    private function request(string $method, string $url, array $data = [], array $headers = []): array
    {
        $fullUrl = rtrim($this->vendorConfig->getApiBaseUrl(), '/') . '/' . ltrim($url, '/');
     
        try {
            // 加密请求数据
            $encryptedData = $this->encryptionStrategy->encrypt($data);
            
            $requestData = [
                'secret_id' => $this->vendorConfig->getSecretId(),
                'data' => $encryptedData
            ];

            $options = $method === 'GET' 
                ? ['query' => $requestData, 'headers' => array_merge(['Content-Type' => 'application/json'], $headers)]
                : ['json' => $requestData, 'headers' => array_merge(['Content-Type' => 'application/json'], $headers)];

            $this->logger->debug(sprintf('%s Request', strtoupper($method)), [
                'url' => $fullUrl,
                'encryption_method' => $this->encryptionStrategy->getMethodName()
            ]);

            $response = $this->guzzleClient->request($method, $fullUrl, $options);

            return $this->handleResponse($response, $method, $fullUrl);

        } catch (GuzzleException $e) {
            throw new \RuntimeException(sprintf('HTTP %s request failed: %s', $method, $e->getMessage()), 0, $e);
        }
    }

    /**
     * 处理 HTTP 响应
     */
    private function handleResponse($response, string $method, string $url): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode !== 200) {
            throw new \RuntimeException(sprintf('HTTP %s failed with status %d', $method, $statusCode));
        }

        $data = json_decode($body, true);
        if ($data === null) {
            throw new \RuntimeException('Failed to parse JSON response');
        }
        
        if (empty($data) || $data['status'] === 0) {
            throw new \Exception($data['message'] ?: '未知错误');
        }

        // 解密响应数据
        if (isset($data['data']) && is_string($data['data'])) {
            return $this->encryptionStrategy->decrypt($data['data']);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @inheritdoc
     */
    public function getApiBaseUrl(): string
    {
        return $this->vendorConfig->getApiBaseUrl();
    }
}