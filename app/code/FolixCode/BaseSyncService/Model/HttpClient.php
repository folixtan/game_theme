<?php
declare(strict_types=1);

namespace FolixCode\BaseSyncService\Model;

use FolixCode\BaseSyncService\Api\HttpClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * HTTP客户端实现 - 使用Guzzle
 * 封装Guzzle客户端，提供统一的请求接口
 */
class HttpClient implements HttpClientInterface
{
    private Client $guzzleClient;
    private LoggerInterface $logger;
    private LoggerInterface $httpLogger;

    public const DEBUG_LEVEL = Logger::DEBUG; // 日志级别

    public function __construct(
        LoggerInterface $logger,
        array $config = []
    ) {
        // 默认配置
        $defaultConfig = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false, // 不自动抛出异常，由我们手动处理
            'verify' => false, // 生产环境应该设置为true
        ];

        $this->guzzleClient = new Client(array_merge($defaultConfig, $config));
        $this->logger = $logger;

        // 创建独立的HTTP日志记录器
        $this->httpLogger = new Logger('http_requests');
        $logPath = BP . '/var/log/http_requests.log';
        $this->httpLogger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * 处理HTTP响应
     *
     * @param Response $response
     * @param string $method
     * @param string $url
     * @return array
     * @throws \RuntimeException
     */
    private function handleResponse(Response $response, string $method, string $url): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
       
        $data = json_decode($body, true) ?? [
            $response->getContent(),
        ];

        
        if(self::DEBUG_LEVEL === Logger::DEBUG) {
            $this->httpLogger->info(sprintf('%s Response', $method), [
            'status' => $statusCode,
            'response' => $data
           ]);
        }
       

        // 检查HTTP状态码
        if ($statusCode !== 200) {
            $errorMsg = sprintf(
                'HTTP %s request failed with status %d. URL: %s, Response: %s',
                $method,
                $statusCode,
                $url,
                $response->getReasonPhrase()
            );
            $this->httpLogger->error($errorMsg);
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        $this->logger->debug(sprintf('%s response received', $method), ['status' => $statusCode, 'response' => $data]);

        return is_array($data) ? $data : [];
    }

    /**
     * @inheritdoc
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        try {
            $options = [];
            if (!empty($params)) {
                $options['query'] = $params;
            }
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }

            $this->httpLogger->info('GET Request', [
                'url' => $url,
                'params' => $params,
                'headers' => $headers
            ]);

            $response = $this->guzzleClient->get($url, $options);

            return $this->handleResponse($response, 'GET', $url);

        } catch (GuzzleException $e) {
            $errorMsg = sprintf('HTTP GET request failed: %s. URL: %s, Params: %s', $e->getMessage(), $url, json_encode($params));
            $this->httpLogger->error($errorMsg);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }

            $this->httpLogger->info('POST Request', [
                'url' => $url,
                'data' => $data,
                'headers' => $headers
            ]);

            $response = $this->guzzleClient->post($url, $options);

            return $this->handleResponse($response, 'POST', $url);

        } catch (GuzzleException $e) {
            $errorMsg = sprintf('HTTP POST request failed: %s. URL: %s, Data: %s', $e->getMessage(), $url, json_encode($data));
            $this->httpLogger->error($errorMsg);
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }

            $this->httpLogger->info('PUT Request', [
                'url' => $url,
                'data' => $data,
                'headers' => $headers
            ]);

            $response = $this->guzzleClient->put($url, $options);

            return $this->handleResponse($response, 'PUT', $url);

        } catch (GuzzleException $e) {
            $errorMsg = sprintf('HTTP PUT request failed: %s. URL: %s, Data: %s', $e->getMessage(), $url, json_encode($data));
            $this->httpLogger->error($errorMsg);
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(string $url, array $headers = []): array
    {
        try {
            $options = [];
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }

            $this->httpLogger->info('DELETE Request', [
                'url' => $url,
                'headers' => $headers
            ]);

            $response = $this->guzzleClient->delete($url, $options);

            return $this->handleResponse($response, 'DELETE', $url);

        } catch (GuzzleException $e) {
            $errorMsg = sprintf('HTTP DELETE request failed: %s. URL: %s', $e->getMessage(), $url);
            $this->httpLogger->error($errorMsg);
            $this->logger->error($errorMsg);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }
}