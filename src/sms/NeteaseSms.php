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
 * 网易云信短信 (CheckSum 鉴权).
 * 配置项 sms.netease_appkey、netease_appsecret、netease_tpl 默认模板ID.
 * @class NeteaseSms
 */
class NeteaseSms implements SmsInterface
{
    use SmsUsageTrait;

    /**
     * 发送短信.
     * @param string $mobile 手机号
     * @param string $template 模板ID
     * @param array $params 变量数组（按顺序）
     * @return array
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array
    {
        $appKey = Sms::config('netease_appkey');
        $appSecret = Sms::config('netease_appsecret');
        if ($template === '') {
            $template = Sms::config('netease_tpl');
        }
        if ($appKey === '' || $appSecret === '' || $template === '') {
            $this->logRequest('netease', 'send_error', ['mobile' => $mobile, 'error' => '未配置 AppKey 或模板']);
            throw new SmsException('请先配置网易云信 AppKey、AppSecret 与模板！');
        }

        $phone = $this->formatMobile($mobile, 'plain');
        $nonce = md5(uniqid('', true));
        $curTime = (string)time();
        $checkSum = sha1("{$appSecret}{$nonce}{$curTime}");

        $headers = [
            "AppKey: {$appKey}",
            "Nonce: {$nonce}",
            "CurTime: {$curTime}",
            "CheckSum: {$checkSum}",
            'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
        ];

        $postData = http_build_query([
            'templateid' => $template,
            'mobiles'    => json_encode([$phone]),
            'params'     => json_encode(array_values($params), JSON_UNESCAPED_UNICODE),
        ]);

        $data = $this->request('https://api.netease.im/sms/sendtemplate.action', $postData, $headers);
        if (intval($data['code'] ?? 0) !== 200) {
            $msg = strval($data['msg'] ?? 'unknown');
            $this->logRequest('netease', 'send_error', ['mobile' => $mobile, 'error' => $msg]);
            throw new SmsException('网易云信短信发送失败：' . $msg);
        }

        $this->logRequest('netease', 'send', ['mobile' => $mobile, 'template' => $template, 'msg_id' => $data['obj'] ?? '', 'success' => true]);
        return $data;
    }
}
