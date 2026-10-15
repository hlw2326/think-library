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

namespace think\admin\contract;

/**
 * 加密驱动标准接口.
 * @class CryptInterface
 */
interface CryptInterface
{
    /**
     * 数据加密
     * @param string $data 明文数据
     * @param string $key 密钥
     * @param array $extra 扩展选项
     * @return string 密文 Base64 字符串
     */
    public function encrypt(string $data, string $key, array $extra = []): string;

    /**
     * 数据解密
     * @param string $payload 密文字符串
     * @param string $key 密钥
     * @param array $extra 扩展选项
     * @return ?string 解密后的明文，失败返回 null
     */
    public function decrypt(string $payload, string $key, array $extra = []): ?string;

    /**
     * 数据签名
     * @param string $data 待签名数据
     * @param string $key 签名秘钥
     * @param array $extra 扩展选项
     * @return ?string Base64 签名，失败返回 null
     */
    public function sign(string $data, string $key, array $extra = []): ?string;

    /**
     * 数据验签
     * @param string $data 原始数据
     * @param string $signature Base64 签名
     * @param string $key 验签秘钥
     * @param array $extra 扩展选项
     * @return bool 验签结果
     */
    public function verify(string $data, string $signature, string $key, array $extra = []): bool;
}
