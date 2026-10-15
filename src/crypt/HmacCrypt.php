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
 * HMAC 散列认证驱动.
 * @class HmacCrypt
 */
class HmacCrypt implements CryptInterface
{
    /**
     * 不支持对称内容加密，返回空字符串
     */
    public function encrypt(string $data, string $key, array $extra = []): string
    {
        return '';
    }

    /**
     * 不支持内容解密，返回 null
     */
    public function decrypt(string $payload, string $key, array $extra = []): ?string
    {
        return null;
    }

    /**
     * 计算 HMAC-SHA256 签名
     */
    public function sign(string $data, string $key, array $extra = []): ?string
    {
        $algorithm = (string) ($extra['algo'] ?? 'sha256');
        $raw = (bool) ($extra['raw'] ?? false);
        return hash_hmac($algorithm, $data, $key, $raw);
    }

    /**
     * 校验 HMAC 签名
     */
    public function verify(string $data, string $signature, string $key, array $extra = []): bool
    {
        $expect = $this->sign($data, $key, $extra);
        return hash_equals($expect ?? '', strtolower($signature));
    }
}
