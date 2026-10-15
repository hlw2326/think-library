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

use think\admin\exception\SmsException;
use think\admin\extend\HttpExtend;
use think\admin\facade\Library;
use think\Container;

/**
 * 短信驱动公共请求方法、手机号格式化、批量发送与余额查询.
 * @trait SmsUsageTrait
 */
trait SmsUsageTrait
{
    /**
     * 获取对象实例.
     */
    public static function instance(): static
    {
        return Container::getInstance()->make(static::class);
    }

    /**
     * 记录短信驱动请求日志.
     * @param string $driver 驱动名称
     * @param string $action 操作动作
     * @param array $data 数据内容
     */
    protected function logRequest(string $driver, string $action, array $data = []): void
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (function_exists('sysoplog')) {
            sysoplog("sms.{$action}", "[{$driver}] {$content}");
        }
        if (isset(Library::$sapp->log)) {
            Library::$sapp->log->record("[sms.{$driver}.{$action}] {$content}", 'info');
        }
    }

    /**
     * 格式化手机号码（兼容国内与国际格式）.
     * @param string $mobile 原始手机号
     * @param string $format 格式类型：plain (纯11位), e164 (+86xxx 或 +1xxx), with86 (+86xxx)
     * @return string
     */
    protected function formatMobile(string $mobile, string $format = 'plain'): string
    {
        $clean = preg_replace('/[\s\-()]+/', '', $mobile);
        if ($format === 'plain') {
            return preg_replace('/^\+?86/', '', $clean);
        }
        if ($format === 'e164') {
            if (str_starts_with($clean, '+')) {
                return $clean;
            }
            if (preg_match('/^1[3-9]\d{9}$/', $clean)) {
                return '+86' . $clean;
            }
            if (str_starts_with($clean, '861') && strlen($clean) === 13) {
                return '+' . $clean;
            }
            return '+' . $clean;
        }
        if ($format === 'with86') {
            if (str_starts_with($clean, '+86')) {
                return $clean;
            }
            return '+86' . preg_replace('/^\+?86/', '', $clean);
        }
        return $clean;
    }

    /**
     * 批量发送短信.
     * @param array $mobiles 手机号列表
     * @param string $template 模板编号
     * @param array $params 模板变量
     * @return array{total: int, success: int, failed: int, results: array}
     */
    public function sendBatch(array $mobiles, string $template = '', array $params = []): array
    {
        $driver = strtolower(str_replace('Sms', '', class_basename(static::class)));
        $total = count($mobiles);
        $success = 0;
        $failed = 0;
        $results = [];

        foreach ($mobiles as $mobile) {
            $mobile = trim(strval($mobile));
            if ($mobile === '') {
                continue;
            }
            try {
                $res = $this->send($mobile, $template, $params);
                $success++;
                $results[$mobile] = ['ok' => true, 'raw' => $res];
            } catch (\Throwable $e) {
                $failed++;
                $results[$mobile] = ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        $this->logRequest($driver, 'send_batch', ['total' => $total, 'success' => $success, 'failed' => $failed]);
        return [
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
            'results' => $results,
        ];
    }

    /**
     * 发送验证码快捷助手.
     * @param string $mobile 手机号
     * @param string $code 验证码内容
     * @param int $expire 有效时间（分钟）
     * @return array
     */
    public function sendCode(string $mobile, string $code, int $expire = 5): array
    {
        return $this->send($mobile, '', [
            'code' => $code,
            'time' => $expire,
            'min'  => $expire,
        ]);
    }

    /**
     * 默认账户余额查询（未提供专用 API 时返回提示）.
     * @return array{balance: float|int, unit: string, raw: array}
     */
    public function balance(): array
    {
        return $this->defaultBalance();
    }

    /**
     * 默认余额返回结构.
     * @return array{balance: float|int, unit: string, raw: array}
     */
    protected function defaultBalance(): array
    {
        return [
            'balance' => -1,
            'unit'    => 'unknown',
            'raw'     => ['message' => '该服务商未开放余额查询接口或需在官方控制台查看'],
        ];
    }

    /**
     * 默认短信发送回执查询.
     * @param string $msgId 消息流水号
     * @return array{status: string, raw: array}
     */
    public function receipt(string $msgId): array
    {
        return $this->defaultReceipt($msgId);
    }

    /**
     * 默认回执返回结构.
     * @param string $msgId 消息流水号
     * @return array{status: string, raw: array}
     */
    protected function defaultReceipt(string $msgId): array
    {
        return [
            'status' => 'UNKNOWN',
            'raw'    => ['msg_id' => $msgId, 'message' => '服务商回执需通过异步 Webhook 接收'],
        ];
    }

    /**
     * 提交请求并解析 JSON 响应.
     * @param string $url 请求地址
     * @param array|string $body 请求内容，数组时按 JSON 提交
     * @param array $headers 请求头
     * @param string $method 请求方式
     * @throws SmsException
     */
    protected function request(string $url, array|string $body = [], array $headers = [], string $method = 'POST'): array
    {
        $options = ['headers' => $headers, 'timeout' => 15];
        if (is_array($body)) {
            $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $options['headers'][] = 'Content-Type: application/json; charset=utf-8';
        }
        if (strtoupper($method) !== 'GET') {
            $options['data'] = $body;
        }
        $result = HttpExtend::request($method, $url, $options);
        $data = json_decode(strval($result), true);
        if (!is_array($data)) {
            throw new SmsException('短信接口请求失败，响应内容无法解析！');
        }
        return $data;
    }
}
