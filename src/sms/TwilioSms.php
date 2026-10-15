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
 * Twilio 全球国际短信驱动 (E.164 国际标准格式).
 * 配置项 sms.twilio_sid、twilio_token、twilio_from 发信人号码或 SenderID、twilio_tpl 默认文案模板.
 * @class TwilioSms
 */
class TwilioSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送全球国际短信.
     * @param string $mobile E.164 手机号（国内自动补齐 +86，国际如 +14155552671）
     * @param string $template 模板内容或消息文本
     * @param array $params 替换变量
     * @return array
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $sid = Sms::config('twilio_sid');
        $token = Sms::config('twilio_token');
        $from = Sms::config('twilio_from');
        if ($template === '') {
            $template = Sms::config('twilio_tpl');
        }
        if ($sid === '' || $token === '' || $from === '') {
            $this->logRequest('twilio', 'send_error', ['mobile' => $mobile, 'error' => '未配置 Twilio SID 或发信号码']);
            throw new SmsException('请先配置 Twilio Account SID、Auth Token 与 From 发信号码！');
        }

        $phone = $this->formatMobile($mobile, 'e164');
        $body = $template !== '' ? $template : 'Your verification code is: {code}';
        foreach ($params as $k => $v) {
            $body = str_replace(["{{$k}}", "{#{$k}#}"], strval($v), $body);
        }

        $auth = base64_encode("{$sid}:{$token}");
        $headers = [
            "Authorization: Basic {$auth}",
            'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
        ];

        $postData = http_build_query([
            'From' => $from,
            'To'   => $phone,
            'Body' => $body,
        ]);

        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $data = $this->request($endpoint, $postData, $headers);

        if (isset($data['status']) && in_array($data['status'], ['failed', 'undelivered'])) {
            $msg = strval($data['message'] ?? 'delivery failed');
            $this->logRequest('twilio', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('Twilio 国际短信发送失败：' . $msg);
        }

        if (isset($data['error_code']) && !empty($data['error_code'])) {
            $msg = strval($data['message'] ?? 'unknown');
            $this->logRequest('twilio', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('Twilio 国际短信异常 [' . $data['error_code'] . ']: ' . $msg);
        }

        $this->logRequest('twilio', 'send', ['mobile' => $mobile, 'sid' => $data['sid'] ?? '', 'status' => $data['status'] ?? '', 'success' => true]);
        return $data;
    }

    /**
     * 查询 Twilio 账户余额.
     */
    public function balance(): array
    {
        $sid = Sms::config('twilio_sid');
        $token = Sms::config('twilio_token');
        if ($sid === '' || $token === '') {
            return $this->defaultBalance();
        }
        try {
            $auth = base64_encode("{$sid}:{$token}");
            $headers = ["Authorization: Basic {$auth}"];
            $data = $this->request("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Balance.json", [], $headers, 'GET');
            $balance = floatval($data['balance'] ?? 0);
            return [
                'balance' => $balance,
                'unit'    => strval($data['currency'] ?? 'USD'),
                'raw'     => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultBalance();
        }
    }

    /**
     * 查询 Twilio 短信状态.
     */
    public function receipt(string $msgId): array
    {
        $sid = Sms::config('twilio_sid');
        $token = Sms::config('twilio_token');
        if ($sid === '' || $token === '') {
            return $this->defaultReceipt($msgId);
        }
        try {
            $auth = base64_encode("{$sid}:{$token}");
            $headers = ["Authorization: Basic {$auth}"];
            $data = $this->request("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages/{$msgId}.json", [], $headers, 'GET');
            return [
                'status' => strval($data['status'] ?? 'UNKNOWN'),
                'raw'    => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultReceipt($msgId);
        }
    }
}
