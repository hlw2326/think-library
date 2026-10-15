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
use think\admin\facade\Payment;
use WePayV3\Contracts\DecryptAes;
use WePayV3\Order;

/**
 * 微信支付 V3 驱动（基于 zoujingli/wechat-developer 封装）.
 * @class WechatPayment
 */
class WechatPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 发起支付并返回支付参数.
     * @param array $order 订单数据 [order_id, title, amount, openid?, channel?, notify_url?]
     * @param array $options 额外参数
     * @return array
     * @throws PaymentException
     */
    public function pay(array $order, array $options = []): array
    {
        $channel = strtolower(strval($order['channel'] ?? 'native'));
        $type = match ($channel) {
            'jsapi', 'mp', 'miniapp' => Order::WXPAY_JSAPI,
            'h5', 'wap'              => Order::WXPAY_H5,
            'app'                    => Order::WXPAY_APP,
            default                  => Order::WXPAY_NATIVE,
        };

        $mchId = Payment::config('wechat_mch_id');
        $appId = Payment::config('wechat_appid');
        $amountCent = $this->toCent($order['amount'] ?? 0);

        $payload = [
            'mchid'        => $mchId,
            'appid'        => $appId,
            'description'  => strval($order['title'] ?? '订单支付'),
            'out_trade_no' => strval($order['order_id']),
            'notify_url'   => strval($order['notify_url'] ?? Payment::config('wechat_notify_url')),
            'amount'       => ['total' => $amountCent, 'currency' => 'CNY'],
        ];

        if ($type === Order::WXPAY_JSAPI) {
            $payload['payer'] = ['openid' => strval($order['openid'] ?? '')];
        } elseif ($type === Order::WXPAY_H5) {
            $payload['scene_info'] = [
                'payer_client_ip' => request()->ip(),
                'h5_info'         => ['type' => 'Wap'],
            ];
        }

        try {
            $result = Order::instance($this->buildConfig())->create($type, $payload);

            // Native 扫码支付返回
            if (isset($result['code_url'])) {
                return [
                    'code'    => 0,
                    'message' => 'success',
                    'qr_code' => $result['code_url'],
                    'pay_url' => $result['code_url'],
                    'raw'     => $result,
                ];
            }

            // H5 支付返回
            if (isset($result['h5_url'])) {
                return [
                    'code'    => 0,
                    'message' => 'success',
                    'pay_url' => $result['h5_url'],
                    'raw'     => $result,
                ];
            }

            // JSAPI / APP 预支付参数已包含签名
            return [
                'code'     => 0,
                'message'  => 'success',
                'pay_data' => $result,
                'raw'      => $result,
            ];
        } catch (\Throwable $e) {
            throw new PaymentException('微信支付发起支付失败：' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * 查询订单支付状态.
     * @param string $orderId
     * @return array
     */
    public function query(string $orderId): array
    {
        try {
            $result = Order::instance($this->buildConfig())->query($orderId);
            $state = strval($result['trade_state'] ?? '');
            $paid = $state === 'SUCCESS';
            $amount = 0.0;
            if (isset($result['amount']['total'])) {
                $amount = $this->toYuan($result['amount']['total'] / 100);
            }

            return [
                'paid'     => $paid,
                'order_id' => $orderId,
                'trade_no' => strval($result['transaction_id'] ?? ''),
                'amount'   => $amount,
                'status'   => $state,
                'raw'      => $result,
            ];
        } catch (\Throwable $e) {
            return [
                'paid'     => false,
                'order_id' => $orderId,
                'trade_no' => '',
                'amount'   => 0.0,
                'status'   => 'QUERY_ERROR: ' . $e->getMessage(),
                'raw'      => [],
            ];
        }
    }

    /**
     * 申请退款.
     * @param string $orderId
     * @param float $amount
     * @param array $options
     * @return array
     * @throws PaymentException
     */
    public function refund(string $orderId, float $amount, array $options = []): array
    {
        $refundId = strval($options['refund_id'] ?? ('R' . date('YmdHis') . rand(1000, 9999)));
        $totalAmount = $this->toCent($options['total_amount'] ?? $amount);
        $refundAmount = $this->toCent($amount);

        $payload = [
            'out_trade_no'  => $orderId,
            'out_refund_no' => $refundId,
            'amount'        => [
                'refund'   => $refundAmount,
                'total'    => $totalAmount,
                'currency' => 'CNY',
            ],
        ];
        if (!empty($options['reason'])) {
            $payload['reason'] = strval($options['reason']);
        }
        if (!empty($options['notify_url'])) {
            $payload['notify_url'] = strval($options['notify_url']);
        }

        try {
            $result = Order::instance($this->buildConfig())->createRefund($payload);
            $status = strval($result['status'] ?? '');
            $success = in_array($status, ['SUCCESS', 'PROCESSING'], true);

            return [
                'success'   => $success,
                'refund_id' => strval($result['refund_id'] ?? $refundId),
                'amount'    => $amount,
                'status'    => $status,
                'raw'       => $result,
            ];
        } catch (\Throwable $e) {
            throw new PaymentException('微信支付申请退款失败：' . $e->getMessage(), (int)$e->getCode(), $e);
        }
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
        if (empty($data) || empty($data['resource'])) {
            return ['success' => false, 'order_id' => '', 'trade_no' => '', 'amount' => 0.0, 'status' => 'INVALID_PAYLOAD', 'raw' => $data];
        }

        $v3Key = Payment::config('wechat_api_v3_key');
        if ($v3Key === '') {
            return ['success' => false, 'order_id' => '', 'trade_no' => '', 'amount' => 0.0, 'status' => 'MISSING_API_V3_KEY', 'raw' => $data];
        }

        try {
            $aes = new DecryptAes($v3Key);
            $decrypted = $aes->decryptToString(
                strval($data['resource']['associated_data'] ?? ''),
                strval($data['resource']['nonce'] ?? ''),
                strval($data['resource']['ciphertext'] ?? '')
            );
            $result = json_decode(strval($decrypted), true) ?? [];
            $state = strval($result['trade_state'] ?? '');
            $paid = $state === 'SUCCESS';
            $amount = 0.0;
            if (isset($result['amount']['total'])) {
                $amount = $this->toYuan($result['amount']['total'] / 100);
            }

            return [
                'success'  => $paid,
                'order_id' => strval($result['out_trade_no'] ?? ''),
                'trade_no' => strval($result['transaction_id'] ?? ''),
                'amount'   => $amount,
                'status'   => $state,
                'raw'      => $result,
            ];
        } catch (\Throwable $e) {
            return [
                'success'  => false,
                'order_id' => '',
                'trade_no' => '',
                'amount'   => 0.0,
                'status'   => 'DECRYPT_FAILED: ' . $e->getMessage(),
                'raw'      => $data,
            ];
        }
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Payment::config('wechat_mch_id') !== '' &&
            Payment::config('wechat_appid') !== '' &&
            Payment::config('wechat_api_v3_key') !== '' &&
            Payment::config('wechat_private_key') !== '';
    }

    /**
     * 构建 WePayV3 驱动基础配置数组.
     * @return array
     */
    protected function buildConfig(): array
    {
        $config = [
            'appid'        => Payment::config('wechat_appid'),
            'mch_id'       => Payment::config('wechat_mch_id'),
            'mch_v3_key'   => Payment::config('wechat_api_v3_key'),
            'cert_private' => $this->formatPrivateKey(Payment::config('wechat_private_key')),
            'cert_public'  => $this->formatCert(Payment::config('wechat_cert_public')),
        ];

        $serialNo = Payment::config('wechat_serial_no');
        if ($serialNo !== '') {
            $config['cert_serial'] = $serialNo;
        }

        $platformCert = Payment::config('wechat_platform_cert');
        if ($platformCert !== '') {
            $config['mp_cert_content'] = $this->formatCert($platformCert);
            $platformSerial = Payment::config('wechat_platform_serial');
            if ($platformSerial !== '') {
                $config['mp_cert_serial'] = $platformSerial;
            }
        }

        return $config;
    }

    /**
     * 格式化私钥内容（自动兼容 PKCS#1 与 PKCS#8 格式）.
     * @param string $key
     * @return string
     */
    protected function formatPrivateKey(string $key): string
    {
        $key = trim($key);
        if (str_contains($key, '-----BEGIN')) {
            $key = str_replace('BEGIN RSA PRIVATE KEY', 'BEGIN PRIVATE KEY', $key);
            $key = str_replace('END RSA PRIVATE KEY', 'END PRIVATE KEY', $key);
            return $key;
        }
        $key = str_replace(["\r", "\n", ' '], '', $key);
        return "-----BEGIN PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
    }

    /**
     * 格式化 X509 证书内容（保证以 BEGIN CERTIFICATE 标识符封装）.
     * @param string $cert
     * @return string
     */
    protected function formatCert(string $cert): string
    {
        $cert = trim($cert);
        if ($cert === '') {
            return '';
        }
        if (str_contains($cert, '-----BEGIN CERTIFICATE-----')) {
            return $cert;
        }
        $clean = str_replace(["\r", "\n", ' '], '', $cert);
        return "-----BEGIN CERTIFICATE-----\n" . wordwrap($clean, 64, "\n", true) . "\n-----END CERTIFICATE-----";
    }
}
