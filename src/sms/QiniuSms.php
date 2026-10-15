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
 * 七牛云短信，Qiniu Token 鉴权.
 * 配置项 sms.qiniu_ak、qiniu_sk、qiniu_tpl 模板ID、qiniu_sign 签名ID(可选).
 * @class QiniuSms
 */
class QiniuSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，模板变量按键名传入.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $ak = Sms::config('qiniu_ak');
        $sk = Sms::config('qiniu_sk');
        if ($template === '') {
            $template = Sms::config('qiniu_tpl');
        }
        if ($ak === '' || $sk === '' || $template === '') {
            throw new SmsException('请先配置七牛云短信 AccessKey、SecretKey 与模板！');
        }

        $path = '/v1/message/single';
        $payload = [
            'template_id' => $template,
            'mobile' => preg_replace('/^\+?86/', '', $mobile),
            'parameters' => (object)$params,
        ];
        $sign = Sms::config('qiniu_sign');
        if ($sign !== '') {
            $payload['signature_id'] = $sign;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signData = "POST {$path}\nHost: sms.qiniuapi.com\nContent-Type: application/json\n\n{$body}";
        $digest = base64_encode(hash_hmac('sha1', $signData, $sk, true));
        $encodedSign = strtr($digest, ['+' => '-', '/' => '_']);
        $token = "Qiniu {$ak}:{$encodedSign}";

        $headers = [
            'Content-Type: application/json',
            "Authorization: {$token}",
        ];

        $data = $this->request("https://sms.qiniuapi.com{$path}", $body, $headers);
        if (isset($data['error'])) {
            throw new SmsException('七牛云短信发送失败：' . strval($data['error']));
        }
        return $data;
    }
}
