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
 * 统一支付网关标准接口.
 * 建议服务商：微信支付、支付宝、PayPal、Stripe.
 * @class PaymentInterface
 */
interface PaymentInterface
{
    /**
     * 发起支付并返回支付参数.
     * @param array $order 订单数据 [order_id, title, amount, openid?, return_url?, notify_url?, channel?]
     * @return array 返回支付参数 [code => int, message => string, pay_url? => string, pay_data? => mixed, raw => array]
     */
    public function pay(array $order): array;

    /**
     * 查询订单支付状态.
     * @param string $orderId 商户订单号
     * @return array [paid => bool, order_id => string, trade_no => string, amount => float, status => string, raw => array]
     */
    public function query(string $orderId): array;

    /**
     * 申请退款.
     * @param string $orderId 商户订单号
     * @param float $amount 退款金额（元）
     * @return array [success => bool, refund_id => string, amount => float, raw => array]
     */
    public function refund(string $orderId, float $amount): array;

    /**
     * 异步通知验签与解析.
     * @param array $data 异步通知数据
     * @return array [success => bool, order_id => string, trade_no => string, amount => float, status => string, raw => array]
     */
    public function notify(array $data): array;
}
