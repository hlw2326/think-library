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

use think\admin\contract\QrcodeInterface;
use think\admin\exception\QrcodeException;
use think\admin\extend\HttpExtend;
use think\Container;

/**
 * 小程序码生成门面.
 * @class Qrcode
 * @method static array create(string $page, array $query = [], array $options = []) 生成小程序码
 */
abstract class Qrcode
{
    /**
     * 静态魔术调用驱动方法.
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws QrcodeException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $qrcode = static::instance();
        if (method_exists($qrcode, $method)) {
            return call_user_func_array([$qrcode, $method], $arguments);
        }
        throw new QrcodeException('method not exists: ' . get_class($qrcode) . "->{$method}()");
    }

    /**
     * 获取小程序码驱动实例.
     * @param ?string $name 驱动名称 (wechat, qq)
     * @return QrcodeInterface
     * @throws QrcodeException
     */
    public static function instance(?string $name = null): QrcodeInterface
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if ($name === '') {
            $name = 'wechat';
        }
        if (!isset(static::types()[$name])) {
            throw new QrcodeException("Qrcode driver [{$name}] does not exist.");
        }
        return Container::getInstance()->make('think\\admin\\qrcode\\' . ucfirst($name) . 'Qrcode');
    }

    /**
     * 读取小程序码配置（支持环境变量回退 QRCODE_{NAME}）.
     * @param string $name
     * @return string
     */
    public static function config(string $name): string
    {
        $val = strval(sysconf("qrcode.{$name}|raw") ?? '');
        if ($val === '') {
            $env = 'QRCODE_' . strtoupper(str_replace('.', '_', $name));
            $val = strval(Library::$sapp->env->get($env) ?? getenv($env) ?? '');
        }
        return $val;
    }

    /**
     * 检查小程序码驱动配置是否有效可用.
     * @param ?string $name
     * @return bool
     */
    public static function ready(?string $name = null): bool
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if ($name === '') {
            $name = 'wechat';
        }
        if (!isset(static::types()[$name])) {
            return false;
        }
        try {
            return static::instance($name)->ready();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 获取支持的小程序码服务平台列表.
     * @return array
     */
    public static function types(): array
    {
        return [
            'wechat' => lang('微信小程序码'),
            'qq'     => lang('QQ小程序码'),
        ];
    }

    /**
     * 将生成的小程序码通过 Storage 存储引擎落盘保存并返回访问链接.
     * @param array $result 驱动返回的数据结构
     * @param string $path 自定义存储相对路径，留空按日期+MD5生成
     * @return string
     * @throws QrcodeException
     */
    public static function save(array $result, string $path = ''): string
    {
        $content = strval($result['content'] ?? '');
        if ($content === '' && !empty($result['url'])) {
            $content = strval(HttpExtend::get(strval($result['url'])));
        }
        if ($content === '') {
            throw new QrcodeException('小程序码内容为空，无法进行保存落盘！');
        }

        if ($path === '') {
            $mime = strval($result['mime'] ?? 'image/png');
            $ext = 'png';
            if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
                $ext = 'jpg';
            } elseif (str_contains($mime, 'gif')) {
                $ext = 'gif';
            } elseif (str_contains($mime, 'webp')) {
                $ext = 'webp';
            }
            $path = 'qrcode/' . date('Ymd') . '/' . md5($content) . '.' . $ext;
        }

        try {
            return Storage::upload($path, $content);
        } catch (\Throwable $e) {
            throw new QrcodeException('小程序码上传保存失败：' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * 将小程序码生成为可直接内嵌网页显示的 Data URI.
     * @param array $result 驱动返回的数据结构
     * @return string
     * @throws QrcodeException
     */
    public static function dataUri(array $result): string
    {
        $content = strval($result['content'] ?? '');
        if ($content === '' && !empty($result['url'])) {
            $content = strval(HttpExtend::get(strval($result['url'])));
        }
        if ($content === '') {
            throw new QrcodeException('小程序码内容为空，无法生成 Data URI！');
        }

        $mime = strval($result['mime'] ?? 'image/png');
        if ($mime === '') {
            $mime = 'image/png';
        }
        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
