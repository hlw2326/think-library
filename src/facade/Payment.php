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

use think\admin\contract\PaymentInterface;
use think\admin\exception\PaymentException;
use think\admin\facade\Library;
use think\Container;

/**
 * 统一支付网关门面.
 * @class Payment
 * @method static array pay(array $order, array $options = []) 发起支付
 * @method static array query(string $orderId) 查询支付状态
 * @method static array refund(string $orderId, float $amount, array $options = []) 申请退款
 * @method static array notify(array $data = []) 异步通知验签解析
 */
abstract class Payment
{
    /**
     * 静态魔术调用驱动方法.
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws PaymentException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($payment = static::instance(), $method)) {
            return call_user_func_array([$payment, $method], $arguments);
        }
        throw new PaymentException('method not exists: ' . get_class($payment) . "->{$method}()");
    }

    /**
     * 获取支付驱动实例.
     * @param ?string $name 驱动名称 (wechat, alipay, paypal, stripe)
     * @return PaymentInterface
     * @throws PaymentException
     */
    public static function instance(?string $name = null): PaymentInterface
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if (!isset(static::types()[$name])) {
            throw new PaymentException("Payment driver [{$name}] does not exist.");
        }
        return Container::getInstance()->make('think\\admin\\payment\\' . ucfirst($name) . 'Payment');
    }

    /**
     * 读取支付系统配置（支持环境变量回退 PAYMENT_{NAME}）.
     * @param string $name
     * @return string
     */
    public static function config(string $name): string
    {
        $val = strval(sysconf("payment.{$name}|raw") ?? '');
        if ($val === '') {
            $env = 'PAYMENT_' . strtoupper(str_replace('.', '_', $name));
            $val = strval(Library::$sapp->env->get($env) ?? getenv($env) ?? '');
        }
        return $val;
    }

    /**
     * 检查支付驱动配置是否有效可用.
     * @param ?string $name
     * @return bool
     */
    public static function ready(?string $name = null): bool
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if ($name === '' || !isset(static::types()[$name])) {
            return false;
        }
        try {
            return static::instance($name)->ready();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 获取支持的支付服务商列表.
     * @return array
     */
    public static function types(): array
    {
        return [
            'wechat' => lang('微信支付'),
            'alipay' => lang('支付宝支付'),
            'paypal' => lang('PayPal 国际支付'),
            'stripe' => lang('Stripe 聚合支付'),
        ];
    }
}
