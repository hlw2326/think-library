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
 * 创蓝253云通讯短信.
 * 配置项 sms.cl253_account、cl253_password、cl253_sign 签名名称、cl253_tpl 默认内容模板.
 * @class Cl253Sms
 */
class Cl253Sms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，支持模板变量替换.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $account = Sms::config('cl253_account');
        $password = Sms::config('cl253_password');
        if ($template === '') {
            $template = Sms::config('cl253_tpl');
        }
        if ($account === '' || $password === '' || $template === '') {
            throw new SmsException('请先配置创蓝253短信 API 账号、密码与短信模板！');
        }

        $msg = $template;
        foreach ($params as $key => $val) {
            $msg = str_replace(['{$' . $key . '}', '{' . $key . '}'], strval($val), $msg);
        }

        $sign = Sms::config('cl253_sign');
        if ($sign !== '' && !str_starts_with($msg, '【')) {
            $msg = "【{$sign}】{$msg}";
        }

        $body = [
            'account' => $account,
            'password' => $password,
            'phone' => preg_replace('/^\+?86/', '', $mobile),
            'msg' => $msg,
        ];

        $data = $this->request('https://smssh1.253.com/msg/v1/send/json', $body);
        if (strval($data['code'] ?? '') !== '0') {
            throw new SmsException('创蓝253短信发送失败：' . strval($data['errorMsg'] ?? 'unknown'));
        }
        return $data;
    }
}
