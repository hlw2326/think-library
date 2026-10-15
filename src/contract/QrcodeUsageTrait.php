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

use think\admin\exception\QrcodeException;
use think\admin\facade\Library;
use think\Container;

/**
 * 小程序码驱动公共管理与辅助 Trait.
 * @trait QrcodeUsageTrait
 */
trait QrcodeUsageTrait
{
    /**
     * 获取对象实例.
     */
    public static function instance(): static
    {
        return Container::getInstance()->make(static::class);
    }

    /**
     * 记录小程序码请求日志（仅记录业务参数，绝不记录凭据与Token）.
     * @param string $driver 驱动名称
     * @param string $action 操作动作
     * @param array $data 数据内容
     */
    protected function logRequest(string $driver, string $action, array $data = []): void
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (function_exists('sysoplog')) {
            sysoplog("qrcode.{$action}", "[{$driver}] {$content}");
        }
        if (isset(Library::$sapp->log)) {
            Library::$sapp->log->record("[qrcode.{$driver}.{$action}] {$content}", 'info');
        }
    }

    /**
     * 从二进制数据检测图片真实 MIME 类型.
     * @param string $binary
     * @return string
     */
    protected function detectMime(string $binary): string
    {
        if ($binary !== '') {
            $info = @getimagesizefromstring($binary);
            if (is_array($info) && !empty($info['mime'])) {
                return strval($info['mime']);
            }
        }
        return 'image/png';
    }

    /**
     * 构建无数量限制小程序码 scene 参数串（严格限制 32 位且不可含 %）.
     * @param array $query 参数数组
     * @return string
     * @throws QrcodeException
     */
    protected function buildUnlimitedScene(array $query): string
    {
        if (empty($query)) {
            return 'id=0';
        }

        $parts = [];
        foreach ($query as $k => $v) {
            $parts[] = "{$k}={$v}";
        }
        $scene = join('&', $parts);
        $len = strlen($scene);

        // 字符集白名单校验：数字、大小写字母与特定可见符号
        if (!preg_match('/^[0-9a-zA-Z!#$&\'()*+,\\/:;=?@\\-._~]{1,32}$/', $scene)) {
            throw new QrcodeException("微信小程序码 scene 长度不可超过 32 字符且仅允许可见英文字符和符号（当前长度: {$len}），请改存业务表，scene 只放短 ID！");
        }

        return $scene;
    }

    /**
     * 构建带参页面路径并校验字节长度限制（微信限制 128 字节）.
     * @param string $page 页面地址
     * @param array $query 查询参数
     * @return string
     * @throws QrcodeException
     */
    protected function buildPath(string $page, array $query = []): string
    {
        $path = ltrim($page, '/');
        if (!empty($query)) {
            $sep = '?';
            if (str_contains($path, '?')) {
                $sep = '&';
            }
            $path .= $sep . http_build_query($query);
        }

        $len = strlen($path);
        if ($len > 128) {
            throw new QrcodeException("小程序页面路径及参数长度不能超过 128 字节（当前长度: {$len} 字节）！");
        }

        return $path;
    }
}
