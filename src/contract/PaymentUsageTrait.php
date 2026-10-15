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

use think\admin\facade\Library;
use think\Container;

/**
 * 支付驱动公共方法与实例管理.
 * @trait PaymentUsageTrait
 */
trait PaymentUsageTrait
{
    /**
     * 获取对象实例.
     */
    public static function instance(): static
    {
        return Container::getInstance()->make(static::class);
    }

    /**
     * 记录支付驱动请求日志.
     * @param string $driver 驱动名称
     * @param string $action 操作动作
     * @param array $data 数据内容
     */
    protected function logRequest(string $driver, string $action, array $data = []): void
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (function_exists('sysoplog')) {
            sysoplog("payment.{$action}", "[{$driver}] {$content}");
        }
        if (isset(Library::$sapp->log)) {
            Library::$sapp->log->record("[payment.{$driver}.{$action}] {$content}", 'info');
        }
    }

    /**
     * 格式化金额为分（整型）.
     * @param float|int|string $amount
     * @return int
     */
    protected function toCent(float|int|string $amount): int
    {
        return (int)round(floatval($amount) * 100);
    }

    /**
     * 格式化金额为元（保留2位小数的浮点数）.
     * @param float|int|string $amount
     * @return float
     */
    protected function toYuan(float|int|string $amount): float
    {
        return round(floatval($amount), 2);
    }

    /**
     * 格式化私钥内容（自动补齐 PEM 标头和换行）.
     * @param string $key
     * @return string
     */
    protected function formatPrivateKey(string $key): string
    {
        $key = trim($key);
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }
        $key = str_replace(["\r", "\n", ' '], '', $key);
        return "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
    }

    /**
     * 格式化公钥/证书内容（自动补齐 PEM 标头和换行）.
     * @param string $key
     * @return string
     */
    protected function formatPublicKey(string $key): string
    {
        $key = trim($key);
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }
        $key = str_replace(["\r", "\n", ' '], '', $key);
        return "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    }
}
