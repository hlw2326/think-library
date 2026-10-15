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

use AliPay\App;
use AliPay\Scan;
use AliPay\Trade;
use AliPay\Wap;
use AliPay\Web;
use think\admin\contract\PaymentInterface;
use think\admin\contract\PaymentUsageTrait;
use think\admin\exception\PaymentException;
use think\admin\facade\Payment;

/**
 * 支付宝开放平台支付驱动（基于 zoujingli/wechat-developer 封装）.
 * @class AlipayPayment
 */
class AlipayPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 发起支付并返回支付参数.
     * @param array $order 订单数据 [order_id, title, amount, channel?, return_url?, notify_url?, body?]
     * @param array $options 额外参数
     * @return array
     * @throws PaymentException
     */
    public function pay(array $order, array $options = []): array
    {
        $channel = strtolower(strval($order['channel'] ?? 'web'));
        $config = $this->buildConfig($order);

        $bizContent = [
            'out_trade_no' => strval($order['order_id']),
            'total_amount' => sprintf('%.2f', floatval($order['amount'] ?? 0)),
            'subject'      => strval($order['title'] ?? '订单支付'),
        ];
        if (!empty($order['body'])) {
            $bizContent['body'] = strval($order['body']);
        }

        try {
            // 1. 扫码付 / 当面付
            if (in_array($channel, ['qr', 'scan', 'native'])) {
                $bizContent['product_code'] = 'FACE_TO_FACE_PAYMENT';
                $result = Scan::instance($config)->apply($bizContent);
                $qrCode = strval($result['qr_code'] ?? '');
                return [
                    'code'    => 0,
                    'message' => 'success',
                    'qr_code' => $qrCode,
                    'pay_url' => $qrCode,
                    'raw'     => $result,
                ];
            }

            // 2. 手机 App 支付
            if ($channel === 'app') {
                $query = App::instance($config)->apply($bizContent);
                return [
                    'code'     => 0,
                    'message'  => 'success',
                    'pay_url'  => $query,
                    'pay_data' => $query,
                    'raw'      => ['query' => $query],
                ];
            }

            // 3. 手机 WAP / H5 网站支付
            if (in_array($channel, ['wap', 'h5'])) {
                $html = Wap::instance($config)->apply($bizContent);
                $payUrl = $this->extractPayUrl($html);
                return [
                    'code'     => 0,
                    'message'  => 'success',
                    'pay_url'  => $payUrl,
                    'pay_html' => $html,
                    'pay_data' => $this->extractParams($html),
                    'raw'      => ['html' => $html],
                ];
            }

            // 4. PC 网页收银台支付（默认）
            $html = Web::instance($config)->apply($bizContent);
            $payUrl = $this->extractPayUrl($html);
            return [
                'code'     => 0,
                'message'  => 'success',
                'pay_url'  => $payUrl,
                'pay_html' => $html,
                'pay_data' => $this->extractParams($html),
                'raw'      => ['html' => $html],
            ];
        } catch (\Throwable $e) {
            throw new PaymentException('支付宝发起支付失败：' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * 查询订单支付状态.
     * @param string $orderId
     * @return array
     * @throws PaymentException
     */
    public function query(string $orderId): array
    {
        try {
            $result = Trade::instance($this->buildConfig())->query($orderId);
            $tradeStatus = strval($result['trade_status'] ?? '');
            $paid = in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED']);
            $amount = floatval($result['total_amount'] ?? 0);

            return [
                'paid'     => $paid,
                'order_id' => $orderId,
                'trade_no' => strval($result['trade_no'] ?? ''),
                'amount'   => $amount,
                'status'   => $tradeStatus,
                'raw'      => $result,
            ];
        } catch (\Throwable $e) {
            return [
                'paid'     => false,
                'order_id' => $orderId,
                'trade_no' => '',
                'amount'   => 0.0,
                'status'   => 'NOT_FOUND: ' . $e->getMessage(),
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
        $bizContent = [
            'out_trade_no'   => $orderId,
            'refund_amount'  => sprintf('%.2f', $amount),
            'out_request_no' => $refundId,
            'refund_reason'  => strval($options['reason'] ?? '申请退款'),
        ];

        try {
            $result = Trade::instance($this->buildConfig())->refund($bizContent);
            $code = strval($result['code'] ?? '');
            $success = $code === '10000';

            return [
                'success'   => $success,
                'refund_id' => $refundId,
                'amount'    => $amount,
                'status'    => $success ? 'SUCCESS' : 'FAILED',
                'raw'       => $result,
            ];
        } catch (\Throwable $e) {
            throw new PaymentException('支付宝申请退款失败：' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * 异步通知验签与解析.
     * @param array $data
     * @return array
     * @throws PaymentException
     */
    public function notify(array $data = []): array
    {
        if (empty($data)) {
            $data = $_POST;
        }
        if (empty($data)) {
            return ['success' => false, 'order_id' => '', 'trade_no' => '', 'amount' => 0.0, 'status' => 'EMPTY_NOTIFY', 'raw' => []];
        }

        try {
            $verified = Trade::instance($this->buildConfig())->notify(false, $data);
            $tradeStatus = strval($verified['trade_status'] ?? '');
            $paid = in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED']);

            return [
                'success'  => $paid,
                'order_id' => strval($verified['out_trade_no'] ?? ''),
                'trade_no' => strval($verified['trade_no'] ?? ''),
                'amount'   => floatval($verified['total_amount'] ?? 0),
                'status'   => $tradeStatus,
                'raw'      => $verified,
            ];
        } catch (\Throwable $e) {
            return [
                'success'  => false,
                'order_id' => strval($data['out_trade_no'] ?? ''),
                'trade_no' => strval($data['trade_no'] ?? ''),
                'amount'   => floatval($data['total_amount'] ?? 0),
                'status'   => 'VERIFY_FAILED: ' . $e->getMessage(),
                'raw'      => $data,
            ];
        }
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Payment::config('alipay_appid') !== '' &&
            Payment::config('alipay_private_key') !== '';
    }

    /**
     * 构建 AliPay 基础配置数组.
     * @param array $order
     * @return array
     */
    protected function buildConfig(array $order = []): array
    {
        $config = [
            'appid'       => Payment::config('alipay_appid'),
            'public_key'  => Payment::config('alipay_public_key'),
            'private_key' => Payment::config('alipay_private_key'),
            'debug'       => Payment::config('alipay_sandbox') === '1',
        ];

        $returnUrl = strval($order['return_url'] ?? Payment::config('alipay_return_url'));
        if ($returnUrl !== '') {
            $config['return_url'] = $returnUrl;
        }

        $notifyUrl = strval($order['notify_url'] ?? Payment::config('alipay_notify_url'));
        if ($notifyUrl !== '') {
            $config['notify_url'] = $notifyUrl;
        }

        return $config;
    }

    /**
     * 从生成的 HTML 提取直接跳转 URL.
     * @param string $html
     * @return string
     */
    protected function extractPayUrl(string $html): string
    {
        preg_match("/action='(.*?)'/", $html, $act);
        $gateway = $act[1] ?? '';
        $params = $this->extractParams($html);
        if ($gateway !== '' && !empty($params)) {
            return $gateway . (str_contains($gateway, '?') ? '&' : '?') . http_build_query($params);
        }
        return $gateway;
    }

    /**
     * 从生成的 HTML 表单中提取参数数组.
     * @param string $html
     * @return array
     */
    protected function extractParams(string $html): array
    {
        preg_match_all("/<input type='hidden' name='(.*?)' value='(.*?)'\/>/", $html, $matches);
        if (!empty($matches[1])) {
            return array_combine($matches[1], array_map('htmlspecialchars_decode', $matches[2]));
        }
        return [];
    }
}
