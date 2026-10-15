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

namespace think\admin\payment;

use think\admin\contract\PaymentInterface;
use think\admin\contract\PaymentUsageTrait;
use think\admin\exception\PaymentException;
use think\admin\extend\HttpExtend;
use think\admin\facade\Payment;

/**
 * Stripe 聚合支付驱动 (Payment Intents API).
 * @class StripePayment
 */
class StripePayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 发起支付并创建 PaymentIntent.
     * @param array $order
     * @param array $options
     * @return array
     * @throws PaymentException
     */
    public function pay(array $order, array $options = []): array
    {
        $currency = Payment::config('stripe_currency');
        if ($currency === '') {
            $currency = 'usd';
        }
        $currency = strtolower($currency);
        $amountCent = $this->toCent($order['amount'] ?? 0);

        $payload = [
            'amount'                             => $amountCent,
            'currency'                           => $currency,
            'description'                        => strval($order['title'] ?? 'Order Payment'),
            'metadata[order_id]'                 => strval($order['order_id']),
            'automatic_payment_methods[enabled]' => 'true',
        ];

        $res = $this->requestStripe('POST', '/v1/payment_intents', $payload);

        return [
            'code'          => 0,
            'message'       => 'success',
            'order_id'      => strval($res['id'] ?? ''),
            'client_secret' => strval($res['client_secret'] ?? ''),
            'pay_data'      => [
                'clientSecret'   => strval($res['client_secret'] ?? ''),
                'publishableKey' => Payment::config('stripe_publishable_key'),
            ],
            'raw'           => $res,
        ];
    }

    /**
     * 查询订单支付状态.
     * @param string $orderId PaymentIntent ID
     * @return array
     * @throws PaymentException
     */
    public function query(string $orderId): array
    {
        $res = $this->requestStripe('GET', "/v1/payment_intents/{$orderId}");

        $status = strval($res['status'] ?? '');
        $paid = $status === 'succeeded';
        $amount = 0.0;
        if (isset($res['amount'])) {
            $amount = $this->toYuan($res['amount'] / 100);
        }

        return [
            'paid'     => $paid,
            'order_id' => $orderId,
            'trade_no' => strval($res['id'] ?? ''),
            'amount'   => $amount,
            'status'   => $status,
            'raw'      => $res,
        ];
    }

    /**
     * 申请退款.
     * @param string $orderId PaymentIntent ID
     * @param float $amount
     * @param array $options
     * @return array
     * @throws PaymentException
     */
    public function refund(string $orderId, float $amount, array $options = []): array
    {
        $payload = [
            'payment_intent' => $orderId,
            'amount'         => $this->toCent($amount),
        ];
        if (!empty($options['reason'])) {
            $payload['reason'] = strval($options['reason']);
        }

        $res = $this->requestStripe('POST', '/v1/refunds', $payload);
        $status = strval($res['status'] ?? '');
        $success = $status === 'succeeded' || $status === 'pending';

        return [
            'success'   => $success,
            'refund_id' => strval($res['id'] ?? ''),
            'amount'    => $amount,
            'status'    => $status,
            'raw'       => $res,
        ];
    }

    /**
     * 异步通知验签与解析 (Stripe Webhook Event).
     * @param array $data
     * @return array
     */
    public function notify(array $data = []): array
    {
        if (empty($data)) {
            $input = file_get_contents('php://input');
            $data = json_decode($input ?: '{}', true) ?? [];
        }

        $type = strval($data['type'] ?? '');
        $object = $data['data']['object'] ?? [];
        $orderId = strval($object['metadata']['order_id'] ?? $object['id'] ?? '');
        $paid = $type === 'payment_intent.succeeded';
        $amount = 0.0;
        if (isset($object['amount'])) {
            $amount = $this->toYuan($object['amount'] / 100);
        }

        return [
            'success'  => $paid,
            'order_id' => $orderId,
            'trade_no' => strval($object['id'] ?? ''),
            'amount'   => $amount,
            'status'   => $type,
            'raw'      => $data,
        ];
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Payment::config('stripe_secret_key') !== '' &&
            Payment::config('stripe_publishable_key') !== '';
    }

    /**
     * 请求 Stripe API.
     * @throws PaymentException
     */
    protected function requestStripe(string $method, string $endpoint, array $payload = []): array
    {
        $url = 'https://api.stripe.com' . $endpoint;
        $secretKey = Payment::config('stripe_secret_key');

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $body = '';
        if (!empty($payload)) {
            $body = http_build_query($payload);
        }

        if ($method === 'GET') {
            $res = HttpExtend::get($url, [], ['headers' => $headers, 'timeout' => 30]);
        } else {
            $res = HttpExtend::post($url, $body, ['headers' => $headers, 'timeout' => 30]);
        }

        $json = json_decode(strval($res), true);
        if (isset($json['error'])) {
            throw new PaymentException('Stripe 请求异常：' . ($json['error']['message'] ?? '未知错误'));
        }
        if (is_array($json)) {
            return $json;
        }
        return [];
    }
}
