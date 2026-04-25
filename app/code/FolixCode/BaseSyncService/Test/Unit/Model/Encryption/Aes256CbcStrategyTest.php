<?php
declare(strict_types=1);

namespace FolixCode\Test\Unit\BaseSyncService\Model\Encryption;

use FolixCode\BaseSyncService\Model\Encryption\Aes256CbcStrategy;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Aes256CbcStrategy 加密策略单元测试
 */
class Aes256CbcStrategyTest extends TestCase
{
    private Aes256CbcStrategy $strategy;
    private Json $jsonSerializer;
    private LoggerInterface $logger;
    private string $secretKey = 'test-secret-key-32-bytes-long!!';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->jsonSerializer = new Json();
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->strategy = new Aes256CbcStrategy(
            $this->jsonSerializer,
            $this->logger,
            $this->secretKey
        );
    }

    /**
     * 测试加密方法名称
     */
    public function testGetMethodName(): void
    {
        $this->assertEquals('AES-256-CBC', $this->strategy->getMethodName());
    }

    /**
     * 测试空数据加密返回空字符串
     */
    public function testEncryptEmptyData(): void
    {
        $result = $this->strategy->encrypt([]);
        $this->assertEquals('', $result);
    }

    /**
     * 测试加密和解密的往返一致性
     */
    public function testEncryptDecryptRoundTrip(): void
    {
        $testData = [
            'order_id' => 'ORD001',
            'amount' => 99.99,
            'currency' => 'USD',
            'items' => [
                ['sku' => 'PROD1', 'qty' => 2],
                ['sku' => 'PROD2', 'qty' => 1]
            ]
        ];

        // 加密
        $encrypted = $this->strategy->encrypt($testData);
        $this->assertNotEmpty($encrypted, '加密结果不应为空');
        $this->assertIsString($encrypted, '加密结果应为字符串');

        // 解密
        $decrypted = $this->strategy->decrypt($encrypted);
        $this->assertEquals($testData, $decrypted, '解密后的数据应与原始数据一致');
    }

    /**
     * 测试不同数据生成不同的密文（IV随机性）
     */
    public function testDifferentCiphertextForSameData(): void
    {
        $testData = ['test' => 'value'];
        
        $encrypted1 = $this->strategy->encrypt($testData);
        $encrypted2 = $this->strategy->encrypt($testData);
        
        // 由于IV是随机的，每次加密结果应该不同
        $this->assertNotEquals($encrypted1, $encrypted2, '相同数据多次加密应产生不同密文');
        
        // 但解密后应该相同
        $this->assertEquals($testData, $this->strategy->decrypt($encrypted1));
        $this->assertEquals($testData, $this->strategy->decrypt($encrypted2));
    }

    /**
     * 测试复杂数据结构加密解密
     */
    public function testComplexDataStructure(): void
    {
        $complexData = [
            'string' => '中文测试',
            'integer' => 12345,
            'float' => 99.99,
            'boolean' => true,
            'null_value' => null,
            'array' => [1, 2, 3],
            'nested' => [
                'level1' => [
                    'level2' => 'deep value'
                ]
            ]
        ];

        $encrypted = $this->strategy->encrypt($complexData);
        $decrypted = $this->strategy->decrypt($encrypted);

        $this->assertEquals($complexData, $decrypted);
    }

    /**
     * 测试签名生成
     */
    public function testGenerateSignature(): void
    {
        $data = ['order_id' => 'ORD001'];
        $timestamp = '1234567890';
        
        $signature = $this->strategy->generateSignature($data, $timestamp);
        
        $this->assertNotEmpty($signature, '签名不应为空');
        $this->assertIsString($signature, '签名应为字符串');
        $this->assertEquals(32, strlen($signature), 'MD5签名长度应为32');
    }

    /**
     * 测试相同数据和时间戳生成相同签名
     */
    public function testConsistentSignature(): void
    {
        $data = ['order_id' => 'ORD001'];
        $timestamp = '1234567890';
        
        $signature1 = $this->strategy->generateSignature($data, $timestamp);
        $signature2 = $this->strategy->generateSignature($data, $timestamp);
        
        $this->assertEquals($signature1, $signature2, '相同输入应生成相同签名');
    }

    /**
     * 测试不同时间戳生成不同签名
     */
    public function testDifferentTimestampProducesDifferentSignature(): void
    {
        $data = ['order_id' => 'ORD001'];
        
        $signature1 = $this->strategy->generateSignature($data, '1234567890');
        $signature2 = $this->strategy->generateSignature($data, '1234567891');
        
        $this->assertNotEquals($signature1, $signature2, '不同时间戳应生成不同签名');
    }

    /**
     * 测试无效密文解密抛出异常
     */
    public function testDecryptInvalidDataThrowsException(): void
    {
        $this->expectException(\Throwable::class);
        
        $this->strategy->decrypt('invalid_encrypted_data');
    }

    /**
     * 测试缺少Secret Key时加密抛出异常
     */
    public function testEncryptWithoutSecretKeyThrowsException(): void
    {
        $strategyWithoutKey = new Aes256CbcStrategy(
            $this->jsonSerializer,
            $this->logger,
            ''
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Secret Key is not configured');
        
        $strategyWithoutKey->encrypt(['test' => 'data']);
    }
}
