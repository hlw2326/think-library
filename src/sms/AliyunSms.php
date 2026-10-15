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
 * 阿里云短信，RPC 签名 V1.
 * 配置项 sms.aliyun_key、aliyun_secret、aliyun_sign 签名名称、aliyun_tpl 默认模板.
 * @class AliyunSms
 */
class AliyunSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，模板变量按键名传入.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $key = Sms::config('aliyun_key');
        $secret = Sms::config('aliyun_secret');
        if ($template === '') {
            $template = Sms::config('aliyun_tpl');
        }
        if ($key === '' || $secret === '' || $template === '') {
            throw new SmsException('请先配置阿里云短信 AccessKey 与模板！');
        }
        $query = [
            'AccessKeyId' => $key, 'Action' => 'SendSms', 'Format' => 'JSON',
            'PhoneNumbers' => preg_replace('/^\+?86/', '', $mobile),
            'RegionId' => 'cn-hangzhou', 'SignName' => Sms::config('aliyun_sign'),
            'SignatureMethod' => 'HMAC-SHA1', 'SignatureNonce' => bin2hex(random_bytes(8)),
            'SignatureVersion' => '1.0', 'TemplateCode' => $template,
            'TemplateParam' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'), 'Version' => '2017-05-25',
        ];
        ksort($query);
        $text = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $sign = base64_encode(hash_hmac('sha1', 'POST&%2F&' . rawurlencode($text), "{$secret}&", true));
        $body = $text . '&Signature=' . rawurlencode($sign);
        $data = $this->request('https://dysmsapi.aliyuncs.com/', $body, ['Content-Type: application/x-www-form-urlencoded']);
        if (($data['Code'] ?? '') !== 'OK') {
            throw new SmsException('阿里云短信发送失败：' . strval($data['Message'] ?? 'unknown'));
        }
        return $data;
    }
}
