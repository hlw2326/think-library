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

use think\admin\contract\SmsUsageTrait;
use think\admin\contract\SmsInterface;
use think\admin\exception\SmsException;
use think\admin\facade\Sms;

/**
 * 容联·云通讯短信.
 * 配置项 sms.cloopen_sid、cloopen_token、cloopen_appid、cloopen_tpl 模板ID.
 * @class CloopenSms
 */
class CloopenSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，模板变量按键值顺序传入.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $sid = Sms::config('cloopen_sid');
        $token = Sms::config('cloopen_token');
        $appId = Sms::config('cloopen_appid');
        if ($template === '') {
            $template = Sms::config('cloopen_tpl');
        }
        if ($sid === '' || $token === '' || $appId === '' || $template === '') {
            throw new SmsException('请先配置容联云短信 AccountSid、AuthToken、AppId 与模板ID！');
        }

        $time = date('YmdHis');
        $sig = strtoupper(md5($sid . $token . $time));
        $url = "https://app.cloopen.com:8883/2013-12-26/Accounts/{$sid}/SMS/TemplateSMS?sig={$sig}";
        $auth = base64_encode("{$sid}:{$time}");

        $body = [
            'to' => preg_replace('/^\+?86/', '', $mobile),
            'appId' => $appId,
            'templateId' => $template,
            'datas' => array_values(array_map('strval', $params)),
        ];

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            "Authorization: {$auth}",
        ];

        $data = $this->request($url, $body, $headers);
        if (strval($data['statusCode'] ?? '') !== '000000') {
            throw new SmsException('容联云短信发送失败：' . strval($data['statusMsg'] ?? 'unknown'));
        }
        return $data;
    }
}
