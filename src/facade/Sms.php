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

namespace think\admin\facade;

use think\admin\contract\SmsInterface;
use think\admin\exception\SmsException;
use think\admin\facade\Library;
use think\Container;

/**
 * 短信服务商引擎管理，配置全部来自 sysconf 的 sms 分组.
 * 新增服务商只需实现 SmsInterface，命名为 {Type}Sms 放入 think\admin\sms，并在 types 中登记.
 * @class Sms
 * @method static array send(string $mobile, string $template = '', array $params = []) 通过默认服务商发送
 * @method static array sendBatch(array $mobiles, string $template = '', array $params = []) 批量发送短信
 * @method static array sendCode(string $mobile, string $code, int $expire = 5) 发送验证码短信
 * @method static array balance() 查询服务商账户余额
 * @method static array receipt(string $msgId) 查询短信发送回执
 */
abstract class Sms
{
    /**
     * 静态访问服务商方法，未指定服务商时使用 sms.type.
     * @throws SmsException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($sms = static::instance(), $method)) {
            return call_user_func_array([$sms, $method], $arguments);
        }
        throw new SmsException('method not exists: ' . get_class($sms) . "->{$method}()");
    }

    /**
     * 实例化短信服务商.
     * @param ?string $name 服务商名称
     * @throws SmsException
     */
    public static function instance(?string $name = null): SmsInterface
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if (!isset(static::types()[$name])) {
            throw new SmsException("Sms driver [{$name}] does not exist.");
        }
        return Container::getInstance()->make('think\\admin\\sms\\' . ucfirst($name) . 'Sms');
    }

    /**
     * 读取短信配置（支持环境变量回退 SMS_{NAME}）.
     * @param string $name 配置名称，对应 sysconf sms.{name}
     */
    public static function config(string $name): string
    {
        $val = strval(sysconf("sms.{$name}|raw") ?? '');
        if ($val === '') {
            $env = 'SMS_' . strtoupper(str_replace('.', '_', $name));
            $val = strval(Library::$sapp->env->get($env) ?? getenv($env) ?? '');
        }
        return $val;
    }

    /**
     * 服务商必填配置是否完整.
     */
    public static function ready(?string $name = null): bool
    {
        $need = [
            'aliyun'  => ['aliyun_key', 'aliyun_secret', 'aliyun_sign', 'aliyun_tpl'],
            'tencent' => ['tencent_appid', 'tencent_key', 'tencent_secret', 'tencent_sign', 'tencent_tpl'],
            'huawei'  => ['huawei_app_key', 'huawei_app_secret', 'huawei_sender', 'huawei_tpl'],
            'baidu'   => ['baidu_ak', 'baidu_sk', 'baidu_sign', 'baidu_tpl'],
            'qiniu'   => ['qiniu_ak', 'qiniu_sk', 'qiniu_tpl'],
            'cl253'   => ['cl253_account', 'cl253_password', 'cl253_sign'],
            'ztsms'   => ['ztsms_username', 'ztsms_password', 'ztsms_sign'],
            'cloopen' => ['cloopen_sid', 'cloopen_token', 'cloopen_appid', 'cloopen_tpl'],
            'netease' => ['netease_appkey', 'netease_appsecret', 'netease_tpl'],
            'yunpian' => ['yunpian_apikey', 'yunpian_tpl'],
            'submail' => ['submail_appid', 'submail_appkey', 'submail_tpl'],
            'jiguang' => ['jiguang_dev_key', 'jiguang_dev_secret', 'jiguang_tpl_id'],
            'twilio'  => ['twilio_sid', 'twilio_token', 'twilio_from'],
            'montn'   => ['montn_user', 'montn_pwd'],
        ];
        $type = strtolower(strval($name ?? static::config('type')));
        $list = $need[$type] ?? [];
        foreach ($list as $key) {
            if (static::config($key) === '') {
                return false;
            }
        }
        return !empty($list);
    }

    /**
     * 获取短信服务商类型.
     */
    public static function types(): array
    {
        return [
            'aliyun'  => lang('阿里云短信'),
            'tencent' => lang('腾讯云短信'),
            'huawei'  => lang('华为云短信'),
            'baidu'   => lang('百度云短信'),
            'qiniu'   => lang('七牛云短信'),
            'cl253'   => lang('创蓝253短信'),
            'ztsms'   => lang('助通短信'),
            'cloopen' => lang('容联云通讯'),
            'netease' => lang('网易云信短信'),
            'yunpian' => lang('云片网络短信'),
            'submail' => lang('赛邮SubMail短信'),
            'jiguang' => lang('极光短信推送'),
            'twilio'  => lang('Twilio国际短信'),
            'montn'   => lang('梦网科技短信'),
        ];
    }
}
