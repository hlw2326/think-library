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

namespace think\admin\crypt;

use think\admin\contract\CryptInterface;

/**
 * RSA 非对称加密与数字签名驱动.
 * @class RsaCrypt
 */
class RsaCrypt implements CryptInterface
{
    /**
     * RSA 公钥分段加密
     */
    public function encrypt(string $data, string $key, array $extra = []): string
    {
        $resource = $this->formatPublicKey($key);
        if ($resource === false) {
            return '';
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false) {
            return '';
        }

        $length = intval($details['bits'] / 8) - 11;
        $output = '';
        while ($data !== '') {
            $chunk = substr($data, 0, $length);
            $data = substr($data, $length);
            $block = '';
            if (!openssl_public_encrypt($chunk, $block, $resource)) {
                return '';
            }
            $output .= $block;
        }

        return base64_encode($output);
    }

    /**
     * RSA 私钥分段解密
     */
    public function decrypt(string $payload, string $key, array $extra = []): ?string
    {
        $passphrase = isset($extra['passphrase']) ? (string) $extra['passphrase'] : null;
        $resource = $this->formatPrivateKey($key, $passphrase);
        if ($resource === false) {
            return null;
        }

        $raw = base64_decode($payload, true);
        if ($raw === false) {
            return null;
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false) {
            return null;
        }

        $length = intval($details['bits'] / 8);
        $output = '';
        while ($raw !== '') {
            $chunk = substr($raw, 0, $length);
            $raw = substr($raw, $length);
            $block = '';
            if (!openssl_private_decrypt($chunk, $block, $resource)) {
                return null;
            }
            $output .= $block;
        }

        return $output;
    }

    /**
     * RSA 私钥签名
     */
    public function sign(string $data, string $key, array $extra = []): ?string
    {
        $passphrase = isset($extra['passphrase']) ? (string) $extra['passphrase'] : null;
        $resource = $this->formatPrivateKey($key, $passphrase);
        if ($resource === false) {
            return null;
        }

        $algorithm = (int) ($extra['algorithm'] ?? OPENSSL_ALGO_SHA256);
        $signature = '';
        if (openssl_sign($data, $signature, $resource, $algorithm)) {
            return base64_encode($signature);
        }

        return null;
    }

    /**
     * RSA 公钥验签
     */
    public function verify(string $data, string $signature, string $key, array $extra = []): bool
    {
        $resource = $this->formatPublicKey($key);
        if ($resource === false) {
            return false;
        }

        $raw = base64_decode($signature, true);
        if ($raw === false) {
            return false;
        }

        $algorithm = (int) ($extra['algorithm'] ?? OPENSSL_ALGO_SHA256);
        return openssl_verify($data, $raw, $resource, $algorithm) === 1;
    }

    /**
     * 格式化公钥资源
     */
    public function formatPublicKey(string $key): mixed
    {
        if (is_file($key)) {
            $key = (string) file_get_contents($key);
        }
        if (!str_contains($key, '-----BEGIN')) {
            $key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }
        return openssl_pkey_get_public($key);
    }

    /**
     * 格式化私钥资源
     */
    public function formatPrivateKey(string $key, ?string $passphrase = null): mixed
    {
        if (is_file($key)) {
            $key = (string) file_get_contents($key);
        }
        if (!str_contains($key, '-----BEGIN')) {
            $key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        }
        return openssl_pkey_get_private($key, $passphrase ?? '');
    }

    /**
     * 动态生成 RSA 密钥对
     * @param int $bits 密钥位数 (默认 2048)
     * @param ?string $config 自定义 openssl.cnf 路径 (留空时自动跨平台适配)
     * @return array{public_key: string, private_key: string}
     */
    public function generate(int $bits = 2048, ?string $config = null): array
    {
        $conf = [
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // 1. 若显式指定或环境变量存在 OPENSSL_CONF，直接读取
        $cnf = $config;
        if ($cnf === null || $cnf === '') {
            $env = getenv('OPENSSL_CONF');
            if ($env !== false && $env !== '') {
                $cnf = $env;
            }
        }
        if ($cnf !== null && is_file($cnf)) {
            $conf['config'] = $cnf;
        }

        // 2. 尝试标准生成
        $res = @openssl_pkey_new($conf);
        $tmp = null;

        // 3. 针对无默认配置文件的系统 (如 Windows)，动态采用安全临时配置自愈
        if ($res === false) {
            $tmp = tempnam(sys_get_temp_dir(), 'ssl');
            if ($tmp !== false) {
                $conf['config'] = $tmp;
                $res = @openssl_pkey_new($conf);
            }
        }

        if ($res === false) {
            if ($tmp !== null && is_file($tmp)) {
                @unlink($tmp);
            }
            throw new \RuntimeException('生成 RSA 密钥对失败: ' . openssl_error_string());
        }

        $private = '';
        openssl_pkey_export($res, $private, null, $conf);
        if ($tmp !== null && is_file($tmp)) {
            @unlink($tmp);
        }

        $details = openssl_pkey_get_details($res);
        $public = (string) ($details['key'] ?? '');

        return [
            'public_key'  => $public,
            'private_key' => $private,
        ];
    }
}
