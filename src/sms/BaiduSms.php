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
 * 百度云短信，BCE Auth v1 签名.
 * 配置项 sms.baidu_ak、baidu_sk、baidu_sign 签名ID、baidu_tpl 模板ID、baidu_endpoint.
 * @class BaiduSms
 */
class BaiduSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，模板变量按键名传入.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $ak = Sms::config('baidu_ak');
        $sk = Sms::config('baidu_sk');
        $sign = Sms::config('baidu_sign');
        if ($template === '') {
            $template = Sms::config('baidu_tpl');
        }
        if ($ak === '' || $sk === '' || $sign === '' || $template === '') {
            throw new SmsException('请先配置百度云短信 AccessKey、SecretKey、签名ID 与模板！');
        }

        $endpoint = rtrim(Sms::config('baidu_endpoint'), '/');
        if ($endpoint === '') {
            $endpoint = 'https://smsv3.bj.baidubce.com';
        }

        $host = parse_url($endpoint, PHP_URL_HOST) ?? 'smsv3.bj.baidubce.com';
        $path = '/api/v3/sendSms';
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $authPrefix = "bce-auth-v1/{$ak}/{$timestamp}/1800";
        $signingKey = hash_hmac('sha256', $authPrefix, $sk);
        $canonicalHeaders = "host:{$host}";
        $signedHeaders = 'host';
        $canonicalReq = "POST\n{$path}\n\n{$canonicalHeaders}";
        $signature = hash_hmac('sha256', $canonicalReq, $signingKey);
        $authHeader = "{$authPrefix}/{$signedHeaders}/{$signature}";

        $body = json_encode([
            'mobile' => $mobile,
            'template' => $template,
            'signatureId' => $sign,
            'contentVar' => $params,
        ], JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            "Host: {$host}",
            "Authorization: {$authHeader}",
            "x-bce-date: {$timestamp}",
        ];

        $data = $this->request("{$endpoint}{$path}", $body, $headers);
        if (($data['code'] ?? '') !== '1000') {
            throw new SmsException('百度云短信发送失败：' . strval($data['message'] ?? 'unknown'));
        }
        return $data;
    }
}
