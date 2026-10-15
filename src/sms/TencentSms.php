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
 * 腾讯云短信，API 3.0 签名 TC3-HMAC-SHA256.
 * 配置项 sms.tencent_appid、tencent_key SecretId、tencent_secret、tencent_sign、tencent_tpl、tencent_region.
 * @class TencentSms
 */
class TencentSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，模板变量按键值顺序传入.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $key = Sms::config('tencent_key');
        $secret = Sms::config('tencent_secret');
        if ($template === '') {
            $template = Sms::config('tencent_tpl');
        }
        if ($key === '' || $secret === '' || $template === '' || Sms::config('tencent_appid') === '') {
            throw new SmsException('请先配置腾讯云短信密钥、应用 ID 与模板！');
        }
        if (!str_starts_with($mobile, '+')) {
            $mobile = '+86' . $mobile;
        }
        $region = Sms::config('tencent_region');
        if ($region === '') {
            $region = 'ap-guangzhou';
        }
        $body = json_encode([
            'PhoneNumberSet' => [$mobile], 'SmsSdkAppId' => Sms::config('tencent_appid'),
            'SignName' => Sms::config('tencent_sign'), 'TemplateId' => $template,
            'TemplateParamSet' => array_map('strval', array_values($params)),
        ], JSON_UNESCAPED_UNICODE);

        $host = 'sms.tencentcloudapi.com';
        $time = time();
        $date = gmdate('Y-m-d', $time);
        $type = 'application/json; charset=utf-8';
        $canonical = "POST\n/\n\ncontent-type:{$type}\nhost:{$host}\nx-tc-action:sendsms\n\ncontent-type;host;x-tc-action\n" . hash('sha256', $body);
        $scope = "{$date}/sms/tc3_request";
        $string = "TC3-HMAC-SHA256\n{$time}\n{$scope}\n" . hash('sha256', $canonical);
        $sign = hash_hmac('sha256', 'tc3_request', hash_hmac('sha256', 'sms', hash_hmac('sha256', $date, "TC3{$secret}", true), true), true);
        $signature = hash_hmac('sha256', $string, $sign);
        $data = $this->request("https://{$host}/", $body, [
            "Authorization: TC3-HMAC-SHA256 Credential={$key}/{$scope}, SignedHeaders=content-type;host;x-tc-action, Signature={$signature}",
            "Content-Type: {$type}", "Host: {$host}", 'X-TC-Action: SendSms', "X-TC-Timestamp: {$time}",
            'X-TC-Version: 2021-01-11', "X-TC-Region: {$region}",
        ]);
        $resp = $data['Response'] ?? [];
        if (isset($resp['Error'])) {
            throw new SmsException('腾讯云短信发送失败：' . strval($resp['Error']['Message'] ?? 'unknown'));
        }
        $item = $resp['SendStatusSet'][0] ?? [];
        if (strtolower(strval($item['Code'] ?? '')) !== 'ok') {
            throw new SmsException('腾讯云短信发送失败：' . strval($item['Message'] ?? 'unknown'));
        }
        return $data;
    }
}
