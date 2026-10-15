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
 * 国密 SM4 对称加密驱动.
 * @class Sm4Crypt
 */
class Sm4Crypt implements CryptInterface
{
    /**
     * SM4 对称加密 (采用 SM4-CBC 算法)
     */
    public function encrypt(string $data, string $key, array $extra = []): string
    {
        $secret = substr(hash('sha256', $key, true), 0, 16);
        $iv = random_bytes(16);
        $ciphered = openssl_encrypt($data, 'sm4-cbc', $secret, OPENSSL_RAW_DATA, $iv);
        if ($ciphered === false) {
            return '';
        }

        return base64_encode($iv . $ciphered);
    }

    /**
     * SM4 对称解密
     */
    public function decrypt(string $payload, string $key, array $extra = []): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) <= 16) {
            return null;
        }

        $secret = substr(hash('sha256', $key, true), 0, 16);
        $iv = substr($raw, 0, 16);
        $ciphered = substr($raw, 16);
        $plain = openssl_decrypt($ciphered, 'sm4-cbc', $secret, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            return null;
        }

        return $plain;
    }

    /**
     * SM4 签名 (默认采用 HMAC-SHA256)
     */
    public function sign(string $data, string $key, array $extra = []): ?string
    {
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * SM4 验签
     */
    public function verify(string $data, string $signature, string $key, array $extra = []): bool
    {
        $expect = $this->sign($data, $key, $extra);
        return hash_equals($expect ?? '', strtolower($signature));
    }
}
