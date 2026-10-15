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

namespace think\admin\sms;

use think\admin\contract\SmsInterface;
use think\admin\contract\SmsUsageTrait;
use think\admin\exception\SmsException;
use think\admin\facade\Sms;

/**
 * 极光短信推送 (JPush SMS REST API).
 * 配置项 sms.jiguang_dev_key、jiguang_dev_secret、jiguang_tpl_id 模板ID、jiguang_sign_id 签名ID(可选).
 * @class JiguangSms
 */
class JiguangSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送模板短信.
     * @param string $mobile 手机号
     * @param string $template 模板ID（数字）
     * @param array $params 变量字典
     * @return array
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $devKey = Sms::config('jiguang_dev_key');
        $devSecret = Sms::config('jiguang_dev_secret');
        if ($template === '') {
            $template = Sms::config('jiguang_tpl_id');
        }
        if ($devKey === '' || $devSecret === '' || $template === '') {
            $this->logRequest('jiguang', 'send_error', ['mobile' => $mobile, 'error' => '未配置 DevKey 或模板ID']);
            throw new SmsException('请先配置极光短信 DevKey、DevSecret 与模板ID！');
        }

        $phone = $this->formatMobile($mobile, 'plain');
        $auth = base64_encode("{$devKey}:{$devSecret}");
        $headers = [
            "Authorization: Basic {$auth}",
            'Content-Type: application/json; charset=utf-8',
        ];

        $payload = [
            'mobile'    => $phone,
            'temp_id'   => intval($template),
            'temp_para' => $params,
        ];

        $signId = Sms::config('jiguang_sign_id');
        if ($signId !== '') {
            $payload['sign_id'] = intval($signId);
        }

        $data = $this->request('https://api.sms.jpush.cn/v1/messages', $payload, $headers, 'POST');
        if (isset($data['error'])) {
            $msg = strval($data['error']['message'] ?? 'unknown');
            $this->logRequest('jiguang', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('极光短信发送失败：' . $msg);
        }

        $this->logRequest('jiguang', 'send', ['mobile' => $mobile, 'template' => $template, 'msg_id' => $data['msg_id'] ?? '', 'success' => true]);
        return $data;
    }

    /**
     * 查询极光账户短信余量.
     */
    public function balance(): array
    {
        $devKey = Sms::config('jiguang_dev_key');
        $devSecret = Sms::config('jiguang_dev_secret');
        if ($devKey === '' || $devSecret === '') {
            return $this->defaultBalance();
        }
        try {
            $auth = base64_encode("{$devKey}:{$devSecret}");
            $headers = ["Authorization: Basic {$auth}"];
            $data = $this->request('https://api.sms.jpush.cn/v1/accounts/balance', [], $headers, 'GET');
            $extra = intval($data['extra'] ?? 0);
            return [
                'balance' => $extra,
                'unit'    => '条',
                'raw'     => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultBalance();
        }
    }

    /**
     * 查询极光短信送达状态.
     */
    public function receipt(string $msgId): array
    {
        $devKey = Sms::config('jiguang_dev_key');
        $devSecret = Sms::config('jiguang_dev_secret');
        if ($devKey === '' || $devSecret === '') {
            return $this->defaultReceipt($msgId);
        }
        try {
            $auth = base64_encode("{$devKey}:{$devSecret}");
            $headers = ["Authorization: Basic {$auth}"];
            $data = $this->request("https://api.sms.jpush.cn/v1/messages/{$msgId}", [], $headers, 'GET');
            return [
                'status' => strval($data['status'] ?? 'UNKNOWN'),
                'raw'    => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultReceipt($msgId);
        }
    }
}
