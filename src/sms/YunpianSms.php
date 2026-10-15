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
 * 云片网络短信 (API v2).
 * 配置项 sms.yunpian_apikey、yunpian_tpl 模板ID.
 * @class YunpianSms
 */
class YunpianSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送模板短信.
     * @param string $mobile 手机号
     * @param string $template 模板ID
     * @param array $params 变量键值对
     * @return array
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $apikey = Sms::config('yunpian_apikey');
        if ($template === '') {
            $template = Sms::config('yunpian_tpl');
        }
        if ($apikey === '' || $template === '') {
            $this->logRequest('yunpian', 'send_error', ['mobile' => $mobile, 'error' => '未配置 API Key 或模板']);
            throw new SmsException('请先配置云片网络 API Key 与模板！');
        }

        $phone = $this->formatMobile($mobile, 'e164');
        $tplValues = [];
        foreach ($params as $k => $v) {
            $tplValues[] = urlencode("#{$k}#") . '=' . urlencode(strval($v));
        }

        $postData = http_build_query([
            'apikey'    => $apikey,
            'mobile'    => $phone,
            'tpl_id'    => $template,
            'tpl_value' => implode('&', $tplValues),
        ]);

        $headers = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8'];
        $data = $this->request('https://sms.yunpian.com/v2/sms/tpl_single_send.json', $postData, $headers);
        if (intval($data['code'] ?? -1) !== 0) {
            $msg = strval($data['msg'] ?? 'unknown');
            $this->logRequest('yunpian', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('云片网络短信发送失败：' . $msg);
        }

        $this->logRequest('yunpian', 'send', ['mobile' => $mobile, 'template' => $template, 'sid' => $data['sid'] ?? '', 'success' => true]);
        return $data;
    }

    /**
     * 查询账户余额与赠送短信配额.
     */
    public function balance(): array
    {
        $apikey = Sms::config('yunpian_apikey');
        if ($apikey === '') {
            return $this->defaultBalance();
        }
        try {
            $postData = http_build_query(['apikey' => $apikey]);
            $data = $this->request('https://sms.yunpian.com/v2/user/get.json', $postData, ['Content-Type: application/x-www-form-urlencoded']);
            $balance = floatval($data['balance'] ?? 0);
            return [
                'balance' => $balance,
                'unit'    => 'CNY',
                'raw'     => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultBalance();
        }
    }
}
