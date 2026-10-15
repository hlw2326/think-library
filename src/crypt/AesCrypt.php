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
 * AES 对称加密驱动 (支持 GCM / CBC).
 * @class AesCrypt
 */
class AesCrypt implements CryptInterface
{
    /**
     * 数据加密
     */
    public function encrypt(string $data, string $key, array $extra = []): string
    {
        $hash = hash('sha256', $key, true);
        $cipher = strtolower((string) ($extra['cipher'] ?? 'aes-256-gcm'));

        if ($cipher === 'aes-256-gcm') {
            $iv = random_bytes(12);
            $tag = '';
            $ciphered = openssl_encrypt($data, 'aes-256-gcm', $hash, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
            if ($ciphered === false) {
                return '';
            }
            return base64_encode($iv . $tag . $ciphered);
        }

        $length = openssl_cipher_iv_length($cipher);
        if ($length === false) {
            return '';
        }
        $iv = random_bytes($length);
        $ciphered = openssl_encrypt($data, $cipher, $hash, OPENSSL_RAW_DATA, $iv);
        if ($ciphered === false) {
            return '';
        }
        return base64_encode($iv . $ciphered);
    }

    /**
     * 数据解密
     */
    public function decrypt(string $payload, string $key, array $extra = []): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false) {
            return null;
        }

        $hash = hash('sha256', $key, true);
        $cipher = strtolower((string) ($extra['cipher'] ?? 'aes-256-gcm'));

        if ($cipher === 'aes-256-gcm') {
            if (strlen($raw) < 28) {
                return null;
            }
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $ciphered = substr($raw, 28);
            $plain = openssl_decrypt($ciphered, 'aes-256-gcm', $hash, OPENSSL_RAW_DATA, $iv, $tag);
            if ($plain === false) {
                return null;
            }
            return $plain;
        }

        $length = openssl_cipher_iv_length($cipher);
        if ($length === false || strlen($raw) <= $length) {
            return null;
        }
        $iv = substr($raw, 0, $length);
        $ciphered = substr($raw, $length);
        $plain = openssl_decrypt($ciphered, $cipher, $hash, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            return null;
        }
        return $plain;
    }

    /**
     * 数据签名 (对称密钥基于 HMAC-SHA256 签名)
     */
    public function sign(string $data, string $key, array $extra = []): ?string
    {
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * 数据验签
     */
    public function verify(string $data, string $signature, string $key, array $extra = []): bool
    {
        $expect = $this->sign($data, $key, $extra);
        return hash_equals($expect ?? '', strtolower($signature));
    }
}
