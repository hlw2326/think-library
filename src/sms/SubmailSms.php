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
 * 赛邮 Submail 短信 (支持国内与国际短信).
 * 配置项 sms.submail_appid、submail_appkey、submail_tpl 模板ID.
 * @class SubmailSms
 */
class SubmailSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信.
     * @param string $mobile 手机号
     * @param string $template 模板项目代码
     * @param array $params 模板变量
     * @return array
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $appId = Sms::config('submail_appid');
        $appKey = Sms::config('submail_appkey');
        if ($template === '') {
            $template = Sms::config('submail_tpl');
        }
        if ($appId === '' || $appKey === '' || $template === '') {
            $this->logRequest('submail', 'send_error', ['mobile' => $mobile, 'error' => '未配置 AppID 或模板']);
            throw new SmsException('请先配置赛邮 SubMail AppID、AppKey 与模板！');
        }

        $phone = $this->formatMobile($mobile, 'plain');
        $isInternational = str_starts_with($mobile, '+') && !str_starts_with($mobile, '+86');
        $endpoint = $isInternational
            ? 'https://api-v4.mysubmail.com/internationalsms/xsend.json'
            : 'https://api-v4.mysubmail.com/sms/xsend.json';

        $postData = [
            'appid'     => $appId,
            'signature' => $appKey,
            'project'   => $template,
            'to'        => $isInternational ? $mobile : $phone,
            'vars'      => json_encode($params, JSON_UNESCAPED_UNICODE),
        ];

        $headers = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8'];
        $data = $this->request($endpoint, http_build_query($postData), $headers);

        if (strtolower(strval($data['status'] ?? '')) !== 'success') {
            $msg = strval($data['msg'] ?? 'unknown');
            $this->logRequest('submail', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('赛邮 SubMail 短信发送失败：' . $msg);
        }

        $this->logRequest('submail', 'send', ['mobile' => $mobile, 'template' => $template, 'send_id' => $data['send_id'] ?? '', 'success' => true]);
        return $data;
    }

    /**
     * 查询账户剩余短信额度.
     */
    public function balance(): array
    {
        $appId = Sms::config('submail_appid');
        $appKey = Sms::config('submail_appkey');
        if ($appId === '' || $appKey === '') {
            return $this->defaultBalance();
        }
        try {
            $postData = http_build_query(['appid' => $appId, 'signature' => $appKey]);
            $data = $this->request('https://api-v4.mysubmail.com/balance/sms.json', $postData, ['Content-Type: application/x-www-form-urlencoded']);
            $balance = floatval($data['balance'] ?? 0);
            return [
                'balance' => $balance,
                'unit'    => '条',
                'raw'     => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultBalance();
        }
    }
}
