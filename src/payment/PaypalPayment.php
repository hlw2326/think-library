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
 * PayPal 国际支付驱动 (REST API v2).
 * @class PaypalPayment
 */
class PaypalPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 发起支付并创建 PayPal Order.
     * @param array $order
     * @param array $options
     * @return array
     * @throws PaymentException
     */
    public function pay(array $order, array $options = []): array
    {
        $currency = Payment::config('paypal_currency');
        if ($currency === '') {
            $currency = 'USD';
        }
        $currency = strtoupper($currency);
        $amount = sprintf('%.2f', floatval($order['amount'] ?? 0));

        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => strval($order['order_id']),
                    'description'  => strval($order['title'] ?? 'Order Payment'),
                    'amount'       => [
                        'currency_code' => $currency,
                        'value'         => $amount,
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => strval($order['return_url'] ?? Payment::config('paypal_return_url')),
                'cancel_url' => strval($order['cancel_url'] ?? Payment::config('paypal_cancel_url')),
            ],
        ];

        $token = $this->getAccessToken();
        $res = $this->requestApi('POST', '/v2/checkout/orders', $payload, $token);

        $approveUrl = '';
        foreach ($res['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approveUrl = strval($link['href']);
                break;
            }
        }

        return [
            'code'     => 0,
            'message'  => 'success',
            'order_id' => strval($res['id'] ?? ''),
            'pay_url'  => $approveUrl,
            'raw'      => $res,
        ];
    }

    /**
     * 查询订单支付状态.
     * @param string $orderId
     * @return array
     * @throws PaymentException
     */
    public function query(string $orderId): array
    {
        $token = $this->getAccessToken();
        $res = $this->requestApi('GET', "/v2/checkout/orders/{$orderId}", [], $token);

        $status = strval($res['status'] ?? '');
        $paid = in_array($status, ['COMPLETED', 'APPROVED'], true);
        $amount = floatval($res['purchase_units'][0]['amount']['value'] ?? 0);

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
     * @param string $orderId Capture ID (或 Order ID)
     * @param float $amount 退款金额
     * @param array $options 额外参数
     * @return array
     * @throws PaymentException
     */
    public function refund(string $orderId, float $amount, array $options = []): array
    {
        $token = $this->getAccessToken();
        $currency = Payment::config('paypal_currency');
        if ($currency === '') {
            $currency = 'USD';
        }
        $currency = strtoupper($currency);

        $payload = [
            'amount' => [
                'value'         => sprintf('%.2f', $amount),
                'currency_code' => $currency,
            ],
            'note_to_payer' => strval($options['reason'] ?? 'Refund'),
        ];

        // 优先尝试以 captureId 发起退款，若传入的是 orderId，先获取 captureId
        $captureId = strval($options['capture_id'] ?? '');
        if ($captureId === '') {
            $orderInfo = $this->requestApi('GET', "/v2/checkout/orders/{$orderId}", [], $token);
            $captureId = strval($orderInfo['purchase_units'][0]['payments']['captures'][0]['id'] ?? '');
        }

        if ($captureId === '') {
            throw new PaymentException('无法获取 PayPal Capture ID，请确认订单已支付完成！');
        }

        $res = $this->requestApi('POST', "/v2/payments/captures/{$captureId}/refund", $payload, $token);
        $status = strval($res['status'] ?? '');
        $success = in_array($status, ['COMPLETED', 'PENDING'], true);

        return [
            'success'   => $success,
            'refund_id' => strval($res['id'] ?? ''),
            'amount'    => $amount,
            'status'    => $status,
            'raw'       => $res,
        ];
    }

    /**
     * 异步通知验签与解析.
     * @param array $data
     * @return array
     */
    public function notify(array $data = []): array
    {
        if (empty($data)) {
            $input = file_get_contents('php://input');
            $data = json_decode($input ?: '{}', true) ?? [];
        }

        $eventType = strval($data['event_type'] ?? '');
        $resource = $data['resource'] ?? [];
        $orderId = strval($resource['id'] ?? '');
        $status = strval($resource['status'] ?? '');
        $paid = $eventType === 'CHECKOUT.ORDER.APPROVED' || $status === 'COMPLETED';

        return [
            'success'  => $paid,
            'order_id' => $orderId,
            'trade_no' => strval($data['id'] ?? ''),
            'amount'   => floatval($resource['amount']['value'] ?? 0),
            'status'   => $eventType,
            'raw'      => $data,
        ];
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Payment::config('paypal_client_id') !== '' &&
            Payment::config('paypal_secret') !== '';
    }

    /**
     * 获取 API 基础 URL.
     */
    protected function baseUrl(): string
    {
        $mode = strtolower(Payment::config('paypal_mode'));
        if ($mode === 'live') {
            return 'https://api-m.paypal.com';
        }
        return 'https://api-m.sandbox.paypal.com';
    }

    /**
     * 获取 OAuth2 访问令牌.
     * @throws PaymentException
     */
    protected function getAccessToken(): string
    {
        $clientId = Payment::config('paypal_client_id');
        $secret = Payment::config('paypal_secret');
        $auth = base64_encode("{$clientId}:{$secret}");

        $url = $this->baseUrl() . '/v1/oauth2/token';
        $res = HttpExtend::post($url, 'grant_type=client_credentials', [
            'headers' => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'timeout' => 30,
        ]);

        $json = json_decode(strval($res), true) ?? [];
        if (!empty($json['access_token'])) {
            return strval($json['access_token']);
        }
        throw new PaymentException('PayPal 授权失败：' . ($json['error_description'] ?? '无效的 ClientId 或 Secret'));
    }

    /**
     * 发送 API 请求.
     * @throws PaymentException
     */
    protected function requestApi(string $method, string $endpoint, array $payload = [], string $token = ''): array
    {
        $url = $this->baseUrl() . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8',
        ];

        $body = '';
        if (!empty($payload)) {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        if ($method === 'GET') {
            $res = HttpExtend::get($url, [], ['headers' => $headers, 'timeout' => 30]);
        } else {
            $res = HttpExtend::post($url, $body, ['headers' => $headers, 'timeout' => 30]);
        }

        $json = json_decode(strval($res), true);
        if (isset($json['name']) && isset($json['message']) && ($json['name'] === 'UNPROCESSABLE_ENTITY' || $json['name'] === 'AUTHENTICATION_FAILURE')) {
            throw new PaymentException("PayPal 请求异常 [{$json['name']}]: {$json['message']}");
        }
        if (is_array($json)) {
            return $json;
        }
        return [];
    }
}
