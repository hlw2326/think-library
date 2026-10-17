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

namespace think\admin\facade;

use think\admin\base\Exception;
use think\admin\contract\CryptInterface;
use think\Container;

/**
 * 现代高安全加密引擎管理.
 * @class Crypt
 * @method static string encrypt(string $data, string $key, array $extra = []) 默认驱动数据加密
 * @method static ?string decrypt(string $payload, string $key, array $extra = []) 默认驱动数据解密
 * @method static ?string sign(string $data, string $key, array $extra = []) 默认驱动数据签名
 * @method static bool verify(string $data, string $signature, string $key, array $extra = []) 默认驱动数据验签
 */
abstract class Crypt
{
    /**
     * 静态魔术调度 (默认调用 default 驱动的方法)
     * @throws Exception
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($crypt = static::instance(), $method)) {
            return call_user_func_array([$crypt, $method], $arguments);
        }
        throw new Exception("method not exists: " . get_class($crypt) . "->{$method}()");
    }

    /**
     * 实例化加密驱动
     * @param ?string $name 驱动名称 (aes / rsa / sm4 / hmac)
     * @throws Exception
     */
    public static function instance(?string $name = null): CryptInterface
    {
        $name = strtolower(strval($name ?: config('crypt.type', 'aes')));
        if (!isset(static::types()[$name])) {
            throw new Exception("Crypt driver [{$name}] does not exist.");
        }
        $class = "think\\admin\\crypt\\" . ucfirst($name) . "Crypt";
        if (class_exists($class)) {
            return Container::getInstance()->make($class);
        }
        throw new Exception("Crypt driver [{$class}] does not exist.");
    }

    /**
     * AES 驱动快捷访问
     */
    public static function aes(): CryptInterface
    {
        return static::instance('aes');
    }

    /**
     * RSA 驱动快捷访问
     */
    public static function rsa(): CryptInterface
    {
        return static::instance('rsa');
    }

    /**
     * SM4 国密驱动快捷访问
     */
    public static function sm4(): CryptInterface
    {
        return static::instance('sm4');
    }

    /**
     * HMAC 散列驱动快捷访问
     */
    public static function hmac(): CryptInterface
    {
        return static::instance('hmac');
    }

    /**
     * 支持的驱动类型列表
     */
    public static function types(): array
    {
        return [
            'aes'  => 'AES-256 对称加密 (GCM/CBC)',
            'rsa'  => 'RSA-2048 非对称加密与签名',
            'sm4'  => '国密 SM4 对称加密',
            'hmac' => 'HMAC-SHA256 签名散列',
        ];
    }
}
