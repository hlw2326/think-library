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
 * 华为云短信，WSSE 身份认证.
 * 配置项 sms.huawei_app_key、huawei_app_secret、huawei_sender、huawei_sign、huawei_tpl、huawei_endpoint.
 * @class HuaweiSms
 */
class HuaweiSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，模板变量按键值顺序传入.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $appKey = Sms::config('huawei_app_key');
        $appSecret = Sms::config('huawei_app_secret');
        $sender = Sms::config('huawei_sender');
        if ($template === '') {
            $template = Sms::config('huawei_tpl');
        }
        if ($appKey === '' || $appSecret === '' || $sender === '' || $template === '') {
            throw new SmsException('请先配置华为云短信 AppKey、AppSecret、通道号与模板！');
        }

        $endpoint = rtrim(Sms::config('huawei_endpoint'), '/');
        if ($endpoint === '') {
            $endpoint = 'https://smsapi.cn-north-4.myhuaweicloud.com:443';
        }

        $created = gmdate('Y-m-d\TH:i:s\Z');
        $nonce = bin2hex(random_bytes(16));
        $passwordDigest = base64_encode(hash('sha256', ($nonce . $created . $appSecret), true));

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            "X-WSSE: UsernameToken Username=\"{$appKey}\", PasswordDigest=\"{$passwordDigest}\", Nonce=\"{$nonce}\", Created=\"{$created}\"",
        ];

        $post = [
            'from' => $sender,
            'to' => $mobile,
            'templateId' => $template,
            'templateParas' => json_encode(array_values(array_map('strval', $params)), JSON_UNESCAPED_UNICODE),
        ];

        $sign = Sms::config('huawei_sign');
        if ($sign !== '') {
            $post['signature'] = $sign;
        }

        $data = $this->request("{$endpoint}/sms/batchSendSms/v1", http_build_query($post), $headers);
        if (($data['code'] ?? '') !== '000000') {
            throw new SmsException('华为云短信发送失败：' . strval($data['description'] ?? 'unknown'));
        }
        return $data;
    }
}
