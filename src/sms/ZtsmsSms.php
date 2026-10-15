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
 * 新助通科技短信服务.
 * 配置项 sms.ztsms_username、ztsms_password、ztsms_sign 签名名称、ztsms_tpl 默认模板内容.
 * @class ZtsmsSms
 */
class ZtsmsSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信，支持模板变量替换.
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $user = Sms::config('ztsms_username');
        $pass = Sms::config('ztsms_password');
        if ($template === '') {
            $template = Sms::config('ztsms_tpl');
        }
        if ($user === '' || $pass === '' || $template === '') {
            throw new SmsException('请先配置助通短信账号、密码与短信模板！');
        }

        $content = $template;
        foreach ($params as $key => $val) {
            $content = str_replace(['{$' . $key . '}', '{' . $key . '}'], strval($val), $content);
        }

        $sign = Sms::config('ztsms_sign');
        if ($sign !== '' && !str_starts_with($content, '【')) {
            $content = "【{$sign}】{$content}";
        }

        $tKey = time();
        $password = md5(md5($pass) . $tKey);

        $body = [
            'username' => $user,
            'password' => $password,
            'tKey' => $tKey,
            'mobile' => preg_replace('/^\+?86/', '', $mobile),
            'content' => $content,
        ];

        $data = $this->request('https://api.mix2.zthysms.com/v2/sendSms', $body);
        if (intval($data['code'] ?? 0) !== 200) {
            throw new SmsException('助通短信发送失败：' . strval($data['msg'] ?? 'unknown'));
        }
        return $data;
    }
}
