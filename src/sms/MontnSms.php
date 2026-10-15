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
 * 梦网科技短信 (三网合一通道).
 * 配置项 sms.montn_user、montn_pwd、montn_sign 签名、montn_tpl 默认文案模板.
 * @class MontnSms
 */
class MontnSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送单条短信.
     * @param string $mobile 手机号
     * @param string $template 模板内容
     * @param array $params 变量字典
     * @return array
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $user = Sms::config('montn_user');
        $pwd = Sms::config('montn_pwd');
        $sign = Sms::config('montn_sign');
        if ($template === '') {
            $template = Sms::config('montn_tpl');
        }
        if ($user === '' || $pwd === '') {
            $this->logRequest('montn', 'send_error', ['mobile' => $mobile, 'error' => '未配置账号或密码']);
            throw new SmsException('请先配置梦网科技短信 UserID 与 Password！');
        }

        $phone = $this->formatMobile($mobile, 'plain');
        $text = $template !== '' ? $template : '您的验证码是{code}，请于{time}分钟内使用。';
        foreach ($params as $k => $v) {
            $text = str_replace(["{{$k}}", "{#{$k}#}"], strval($v), $text);
        }

        $content = ($sign !== '' ? "【{$sign}】" : '') . $text;
        $timestamp = date('mdHis');
        $encryptedPwd = md5($user . '00000000' . $pwd . $timestamp);

        $payload = [
            'userid'    => $user,
            'pwd'       => $encryptedPwd,
            'mobile'    => $phone,
            'content'   => $content,
            'timestamp' => $timestamp,
        ];

        $headers = ['Content-Type: application/json; charset=utf-8'];
        $data = $this->request('http://api01.monyun.cn:7901/sms/v2/std/single_send', $payload, $headers, 'POST');

        if (intval($data['result'] ?? -1) !== 0) {
            $msg = strval($data['message'] ?? 'result=' . ($data['result'] ?? 'unknown'));
            $this->logRequest('montn', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('梦网科技短信发送失败：' . $msg);
        }

        $this->logRequest('montn', 'send', ['mobile' => $mobile, 'msgid' => $data['msgid'] ?? '', 'success' => true]);
        return $data;
    }

    /**
     * 查询梦网短信账户余额.
     */
    public function balance(): array
    {
        $user = Sms::config('montn_user');
        $pwd = Sms::config('montn_pwd');
        if ($user === '' || $pwd === '') {
            return $this->defaultBalance();
        }
        try {
            $timestamp = date('mdHis');
            $encryptedPwd = md5($user . '00000000' . $pwd . $timestamp);
            $payload = [
                'userid'    => $user,
                'pwd'       => $encryptedPwd,
                'timestamp' => $timestamp,
            ];
            $data = $this->request('http://api01.monyun.cn:7901/sms/v2/std/get_balance', $payload, ['Content-Type: application/json; charset=utf-8'], 'POST');
            $money = floatval($data['money'] ?? 0);
            return [
                'balance' => $money,
                'unit'    => '元',
                'raw'     => $data,
            ];
        } catch (\Throwable) {
            return $this->defaultBalance();
        }
    }
}
