<?php

declare(strict_types=1);

/**
 * +----------------------------------------------------------------------
 * | HlwAdmin Plugin for HlwAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 HlwAdmin [ hlwadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://hlwadmin.top
 * +----------------------------------------------------------------------
 */

namespace think\admin\service;

use think\admin\base\Service;
use think\admin\facade\Crypt;
use think\exception\HttpException;

/**
 * API 信封混合解密与验签服务
 * @class ApiCryptService
 */
class ApiCryptService extends Service
{
    protected int $tolerance;
    protected int $ttl;
    protected string $secret;
    protected string $priv;
    protected string $pub;
    protected string $cipher;

    /**
     * 服务初始化
     */
    protected function initialize(): void
    {
        $this->tolerance = (int) $this->app->config->get('api.time_tolerance', 60000);
        $this->ttl = (int) $this->app->config->get('api.nonce_expire', 120);
        $this->secret = (string) $this->app->config->get('api.sign_secret', 'hlw_api_secret_key_2026');
        $this->priv = (string) $this->app->config->get('api.rsa_private_key', '');
        $this->pub = (string) $this->app->config->get('api.rsa_public_key', '');
        $this->cipher = (string) $this->app->config->get('api.aes_cipher', 'aes-256-gcm');
    }

    /**
     * 获取或自动生成 RSA 密钥对
     * @return array{public_key: string, private_key: string}
     */
    public function getKeys(): array
    {
        if ($this->priv !== '' && $this->pub !== '') {
            return [
                'public_key'  => $this->pub,
                'private_key' => $this->priv,
            ];
        }

        $dir = $this->app->getRootPath() . 'safefile' . DIRECTORY_SEPARATOR . 'certs';
        $priv = $dir . DIRECTORY_SEPARATOR . 'api_rsa_private.key';
        $pub = $dir . DIRECTORY_SEPARATOR . 'api_rsa_public.key';

        if (is_file($priv) && is_file($pub)) {
            $this->priv = (string) file_get_contents($priv);
            $this->pub = (string) file_get_contents($pub);
            return [
                'public_key'  => $this->pub,
                'private_key' => $this->priv,
            ];
        }

        // 首次使用自动生成 2048 位 RSA 密钥对
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $cnf = $this->app->config->get('api.openssl_cnf');
        $keys = Crypt::rsa()->generate(2048, is_string($cnf) ? $cnf : null);

        file_put_contents($priv, $keys['private_key']);
        file_put_contents($pub, $keys['public_key']);

        $this->priv = $keys['private_key'];
        $this->pub = $keys['public_key'];

        return $keys;
    }

    /**
     * 获取服务端 RSA 公钥
     */
    public function getPublicKey(): string
    {
        $keys = $this->getKeys();
        return $keys['public_key'];
    }

    /**
     * 获取服务端 RSA 私钥
     */
    public function getPrivateKey(): string
    {
        $keys = $this->getKeys();
        return $keys['private_key'];
    }

    /**
     * 校验并解密请求信封
     * @param string $context 信封密文 (x-client-context)
     * @param string $nonce 随机数 (x-client-nonce)
     * @param string $sign 签名串 (x-client-sign)
     * @param int $time 客户端时间戳毫秒 (x-client-timestamp)
     * @return array 解密出的业务参数
     */
    public function decrypt(string $context, string $nonce, string $sign, int $time): array
    {
        // 1. 校验时间戳防过期 (默认 60 秒漂移容差)
        $now = (int) (microtime(true) * 1000);
        if (abs($now - $time) > $this->tolerance) {
            throw new HttpException(400, '请求已超时或时间偏差过大');
        }

        // 2. 校验 Nonce 防重放攻击
        $cache = "api_nonce:{$nonce}";
        if ($this->app->cache->has($cache)) {
            throw new HttpException(400, '检测到重放请求 (Nonce Duplicate)');
        }
        $this->app->cache->set($cache, 1, $this->ttl);

        // 3. 校验数字签名
        $expect = $this->sign($context, $nonce, $time);
        if (!hash_equals($expect, strtolower($sign))) {
            throw new HttpException(400, '签名校验失败 (Signature Invalid)');
        }

        // 4. 拆解信封结构 (两段式 Base64，用 . 分割)
        if (!str_contains($context, '.')) {
            throw new HttpException(400, 'x-client-context 信封格式错误');
        }
        [$head, $body] = explode('.', $context, 2);

        // 5. 使用服务端 RSA 私钥解密出动态对称密钥 (AES Key)
        $priv = $this->getPrivateKey();
        $key = Crypt::rsa()->decrypt($head, $priv);
        if (empty($key)) {
            throw new HttpException(400, '信封密钥解密失败');
        }

        // 若动态密钥传输为 JSON 对象，自动提取 key 字段
        $meta = json_decode($key, true);
        if (is_array($meta) && isset($meta['key'])) {
            $key = (string) $meta['key'];
        }

        // 6. 使用对称密钥解密 Payload 业务数据
        $text = Crypt::aes()->decrypt($body, $key, ['cipher' => $this->cipher]);
        if ($text === null || $text === '') {
            // 尝试备用算法 aes-256-cbc
            $text = Crypt::aes()->decrypt($body, $key, ['cipher' => 'aes-256-cbc']);
        }

        if ($text === null || $text === '') {
            throw new HttpException(400, '业务数据解密失败');
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new HttpException(400, '明文数据非有效 JSON 格式');
        }

        return $data;
    }

    /**
     * 计算接口签名
     * 规则: context={context}&nonce={nonce}&timestamp={timestamp} 进行 HMAC-SHA256
     */
    public function sign(string $context, string $nonce, int $time): string
    {
        $raw = "context={$context}&nonce={$nonce}&timestamp={$time}";
        return (string) Crypt::hmac()->sign($raw, $this->secret);
    }

    /**
     * 模拟客户端加密生成请求信封 (用于开发测试与前端联调对照)
     * @param array $body 待发送的业务明文数据
     * @param ?string $key 自定义动态 AES 密钥 (留空自动生成)
     * @return array{headers: array<string, mixed>, key: string, body: array}
     */
    public function encrypt(array $body, ?string $key = null): array
    {
        if (empty($key)) {
            $key = bin2hex(random_bytes(16));
        }

        // 1. RSA 加密动态对称密钥
        $pub = $this->getPublicKey();
        $head = Crypt::rsa()->encrypt($key, $pub);
        if ($head === '') {
            throw new HttpException(500, 'RSA 加密动态密钥失败');
        }

        // 2. AES 加密业务数据
        $text = json_encode($body, JSON_UNESCAPED_UNICODE);
        $data = Crypt::aes()->encrypt($text, $key, ['cipher' => $this->cipher]);
        if ($data === '') {
            throw new HttpException(500, 'AES 加密请求体失败');
        }

        // 3. 组装信封
        $context = "{$head}.{$data}";
        $nonce = substr(bin2hex(random_bytes(8)), 0, 10);
        $time = (int) (microtime(true) * 1000);
        $sign = $this->sign($context, $nonce, $time);

        return [
            'headers' => [
                'x-client-context'   => $context,
                'x-client-nonce'     => $nonce,
                'x-client-timestamp' => $time,
                'x-client-sign'      => $sign,
            ],
            'key'     => $key,
            'body'    => $body,
        ];
    }
}
