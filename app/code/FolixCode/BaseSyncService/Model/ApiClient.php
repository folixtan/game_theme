<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Model;

use FolixCode\BaseSyncService\Api\ExternalApiClientInterface;
use FolixCode\BaseSyncService\Helper\Data as BaseHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * 统一 API 客户端
 * 
 * 整合功能：
 * - HTTP 请求（基于 Guzzle）
 * - 自动加密/解密（AES-256-CBC）
 * - 统一错误处理
 * - 统一日志记录
 */
class ApiClient implements ExternalApiClientInterface
{
    private Client $guzzleClient;
    private BaseHelper $baseHelper;
    private LoggerInterface $logger;
    private string $apiBaseUrl;

    public function __construct(
        BaseHelper $baseHelper,
        LoggerInterface $logger,
        array $guzzleConfig = [],
        string $apiBaseUrl = ''
    ) {
        // 默认 Guzzle 配置
        $defaultConfig = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'verify' => false,
        ];

        $this->guzzleClient = new Client(array_merge($defaultConfig, $guzzleConfig));
        $this->baseHelper = $baseHelper;
        $this->logger = $logger;
        $this->apiBaseUrl = $apiBaseUrl ?: $this->baseHelper->getApiBaseUrl();
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
     *
     * @param string $method HTTP 方法
     * @param string $url 请求 URL
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array 响应数据
     * @throws \RuntimeException
     */
    private function request(string $method, string $url, array $data = [], array $headers = []): array
    {
        $fullUrl = $this->buildFullUrl($url);
        
        try {
            // 1. 加密请求数据
            $encryptedData = $this->baseHelper->encryptRequestData($data);
            
            // 2. 构建最终请求参数
            $requestData = [
                'secret_id' => $this->baseHelper->getSecretId(),
                'data' => $encryptedData
            ];

            // 3. 准备 Guzzle 选项
            $options = $this->prepareOptions($method, $requestData, $headers);

            // 4. 记录请求日志
            $this->logRequest($method, $fullUrl, $data);

            // 5. 发送请求
            $response = $this->guzzleClient->request($method, $fullUrl, $options);

            // 6. 处理响应
            return $this->handleResponse($response, $method, $fullUrl);

        } catch (GuzzleException $e) {
            $errorMsg = sprintf('HTTP %s request failed: %s. URL: %s', $method, $e->getMessage(), $fullUrl);
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg, 0, $e);
        } catch (\Exception $e) {
            $errorMsg = sprintf('Failed to process %s request: %s. URL: %s', $method, $e->getMessage(), $fullUrl);
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * 构建完整 URL
     *
     * @param string $url
     * @return string
     */
    private function buildFullUrl(string $url): string
    {
        // 如果已经是完整 URL，直接返回
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        
        // 拼接基础 URL
        return rtrim($this->apiBaseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * 准备 Guzzle 请求选项
     *
     * @param string $method HTTP 方法
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array
     */
    private function prepareOptions(string $method, array $data, array $headers): array
    {
        $options = [];

        // GET 请求使用 query 参数
        if ($method === 'GET') {
            $options['query'] = $data;
        } else {
            // POST/PUT 使用 JSON body
            $options['json'] = $data;
        }

        // 合并自定义 headers
        if (!empty($headers)) {
            $options['headers'] = array_merge(['Content-Type' => 'application/json'], $headers);
        } else {
            $options['headers'] = ['Content-Type' => 'application/json'];
        }

        return $options;
    }

    /**
     * 记录请求日志
     *
     * @param string $method
     * @param string $url
     * @param array $data
     */
    private function logRequest(string $method, string $url, array $data): void
    {
        $this->logger->debug(sprintf('%s Request', strtoupper($method)), [
            'url' => $url,
            'data_size' => strlen(json_encode($data)),
            'encrypted' => true
        ]);
    }

    /**
     * 处理 HTTP 响应
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $method
     * @param string $url
     * @return array
     * @throws \RuntimeException
     */
    private function handleResponse($response, string $method, string $url): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // 1. 检查 HTTP 状态码
        if ($statusCode !== 200) {
            $errorMsg = sprintf(
                'HTTP %s request failed with status %d. URL: %s, Response: %s',
                $method,
                $statusCode,
                $url,
                substr($body, 0, 200)
            );
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        // 2. 解析 JSON
        $data = json_decode($body, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = sprintf(
                'Failed to parse JSON response from %s %s. Error: %s',
                $method,
                $url,
                json_last_error_msg()
            );
            $this->logger->error($errorMsg, ['body' => substr($body, 0, 500)]);
            throw new \RuntimeException($errorMsg);
        }
           if(empty($data) || $data['status']  === 0)  throw new \Exception($data['message'] ?: '未知错误');

        // 3. 解密响应数据
        if (isset($data['data']) && is_string($data['data'])) {
            try {
                $decryptedData = $this->baseHelper->decryptResponseData($data['data']);
                $this->logger->debug(sprintf('%s Response decrypted successfully', strtoupper($method)), [
                    'status' => $statusCode,
                    'url' => $url
                ]);
                return is_array($decryptedData) ? $decryptedData : [];
            } catch (\Exception $e) {
                $this->logger->error('Failed to decrypt response data: ' . $e->getMessage());
                throw $e;
            }
        }

        // 4. 如果响应未加密，直接返回
        $this->logger->debug(sprintf('%s Response received (unencrypted)', strtoupper($method)), [
            'status' => $statusCode,
            'url' => $url
        ]);

        return is_array($data) ? $data : [];
    }

    /**
     * 获取 API 基础 URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }
}