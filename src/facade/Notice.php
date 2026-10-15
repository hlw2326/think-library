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

use think\admin\contract\NoticeInterface;
use think\admin\exception\NoticeException;
use think\admin\facade\Library;
use think\Container;

/**
 * 通知渠道引擎管理，配置全部来自 sysconf 的 notice 分组.
 * 支持企业微信、飞书、钉钉、微信公众号、邮件与手机短信.
 * 支持普通文本、交互卡片 (sendCard)、图片通知 (sendImage)、文件推送 (sendFile)、优先级控制与消息模板管理.
 * @class Notice
 * @method static array send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []) 发送文本通知
 * @method static array sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []) 发送卡片通知
 * @method static array sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []) 发送图片通知
 * @method static array sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []) 发送文件通知
 */
abstract class Notice
{
    /** 发送优先级常量 */
    public const PRIORITY_LOW = 'low';       // 低优先级 (免打扰/日常报表)
    public const PRIORITY_NORMAL = 'normal'; // 普通优先级 (默认业务消息)
    public const PRIORITY_HIGH = 'high';     // 高优先级 (重要业务/订单交易)
    public const PRIORITY_URGENT = 'urgent'; // 紧急优先级 (系统故障/安全告警，触发强提醒)

    /**
     * 静态访问渠道方法，未指定渠道时使用 notice.type.
     * @throws NoticeException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($notice = static::instance(), $method)) {
            return call_user_func_array([$notice, $method], $arguments);
        }
        throw new NoticeException('method not exists: ' . get_class($notice) . "->{$method}()");
    }

    /**
     * 实例化通知渠道.
     * @param ?string $name 渠道名称
     * @throws NoticeException
     */
    public static function instance(?string $name = null): NoticeInterface
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if (!isset(static::types()[$name])) {
            throw new NoticeException("Notice driver [{$name}] does not exist.");
        }
        return Container::getInstance()->make('think\\admin\\notice\\' . ucfirst($name) . 'Notice');
    }

    /**
     * 规范化优先级标识.
     */
    public static function normalizePriority(string|int $priority): string
    {
        if (is_int($priority)) {
            return match ($priority) {
                3 => self::PRIORITY_URGENT,
                2 => self::PRIORITY_HIGH,
                0 => self::PRIORITY_LOW,
                default => self::PRIORITY_NORMAL,
            };
        }
        $p = strtolower(trim($priority));
        return in_array($p, [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT], true)
            ? $p
            : self::PRIORITY_NORMAL;
    }

    /**
     * 向所有已启用渠道广播推送，单个渠道失败不影响其他渠道.
     * @param string $title 标题
     * @param string $content 内容
     * @param string $to 接收人
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array<string, array{ok: bool, info: string, raw?: array}>
     */
    public static function push(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $result = [];
        $priority = static::normalizePriority($priority);
        foreach (array_filter(explode(',', static::config('enable'))) as $name) {
            try {
                $res = static::instance($name)->send($title, $content, $to, $priority, $options);
                $result[$name] = ['ok' => true, 'info' => 'ok', 'raw' => $res];
            } catch (\Throwable $exception) {
                $result[$name] = ['ok' => false, 'info' => $exception->getMessage()];
            }
        }
        return $result;
    }

    /**
     * 向所有已启用渠道广播卡片通知.
     * @param string $title 标题
     * @param string $content 描述内容
     * @param string $url 跳转链接
     * @param string $btnText 按钮文字
     * @param string $to 接收人
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array<string, array{ok: bool, info: string, raw?: array}>
     */
    public static function pushCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $result = [];
        $priority = static::normalizePriority($priority);
        foreach (array_filter(explode(',', static::config('enable'))) as $name) {
            try {
                $res = static::instance($name)->sendCard($title, $content, $url, $btnText, $to, $priority, $options);
                $result[$name] = ['ok' => true, 'info' => 'ok', 'raw' => $res];
            } catch (\Throwable $exception) {
                $result[$name] = ['ok' => false, 'info' => $exception->getMessage()];
            }
        }
        return $result;
    }

    /**
     * 向所有已启用渠道广播图片通知.
     * @param string $image 图片 URL 或 本地路径
     * @param string $title 图片标题
     * @param string $to 接收人
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array<string, array{ok: bool, info: string, raw?: array}>
     */
    public static function pushImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $result = [];
        $priority = static::normalizePriority($priority);
        foreach (array_filter(explode(',', static::config('enable'))) as $name) {
            try {
                $res = static::instance($name)->sendImage($image, $title, $to, $priority, $options);
                $result[$name] = ['ok' => true, 'info' => 'ok', 'raw' => $res];
            } catch (\Throwable $exception) {
                $result[$name] = ['ok' => false, 'info' => $exception->getMessage()];
            }
        }
        return $result;
    }

    /**
     * 向所有已启用渠道广播文件通知.
     * @param string $file 文件 URL 或 本地路径
     * @param string $filename 显示文件名
     * @param string $to 接收人
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array<string, array{ok: bool, info: string, raw?: array}>
     */
    public static function pushFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $result = [];
        $priority = static::normalizePriority($priority);
        foreach (array_filter(explode(',', static::config('enable'))) as $name) {
            try {
                $res = static::instance($name)->sendFile($file, $filename, $to, $priority, $options);
                $result[$name] = ['ok' => true, 'info' => 'ok', 'raw' => $res];
            } catch (\Throwable $exception) {
                $result[$name] = ['ok' => false, 'info' => $exception->getMessage()];
            }
        }
        return $result;
    }

    /**
     * 默认内置消息模板列表.
     * @return array<string, array{name: string, title: string, content: string, priority: string, channels: string}>
     */
    public static function defaultTemplates(): array
    {
        return [
            'system_alert' => [
                'name'     => '系统告警通知',
                'title'    => '【系统告警】{level} - {title}',
                'content'  => "事件名称：{event}\n发生时间：{time}\n详细信息：{detail}",
                'priority' => self::PRIORITY_HIGH,
                'channels' => '',
            ],
            'task_notice'  => [
                'name'     => '异步任务通知',
                'title'    => '【任务通知】{task_name}',
                'content'  => "任务名称：{task_name}\n执行状态：{status}\n执行耗时：{duration}\n结果摘要：{summary}",
                'priority' => self::PRIORITY_NORMAL,
                'channels' => '',
            ],
            'user_verify'  => [
                'name'     => '安全验证码',
                'title'    => '【安全验证】您的动态验证码',
                'content'  => "您的安全验证码为：{code}，请于 {expire} 分钟内使用，请勿向任何人泄露！",
                'priority' => self::PRIORITY_HIGH,
                'channels' => 'sms,mail',
            ],
            'order_notice' => [
                'name'     => '订单交易提醒',
                'title'    => '【订单通知】您有新的交易订单',
                'content'  => "订单单号：{order_no}\n支付金额：{amount} 元\n下单时间：{time}\n备注说明：{remark}",
                'priority' => self::PRIORITY_HIGH,
                'channels' => '',
            ],
            'login_warn'   => [
                'name'     => '异地登录告警',
                'title'    => '【安全提醒】账号异地登录告警',
                'content'  => "登录账号：{username}\n登录IP：{ip} ({location})\n登录时间：{time}\n若非本人操作，请立即登录系统并修改登录密码！",
                'priority' => self::PRIORITY_URGENT,
                'channels' => '',
            ],
        ];
    }

    /**
     * 获取全部消息模板（内置模板合并自定义持久化模板）.
     * @return array<string, array>
     */
    public static function templates(): array
    {
        $defaults = static::defaultTemplates();
        $customs = [];
        try {
            if (function_exists('sysdata')) {
                $saved = sysdata('notice_templates');
                if (is_array($saved)) {
                    $customs = $saved;
                }
            }
        } catch (\Throwable) {
            $customs = [];
        }
        return array_merge($defaults, $customs);
    }

    /**
     * 获取指定消息模板.
     */
    public static function template(string $key): ?array
    {
        $all = static::templates();
        return $all[$key] ?? null;
    }

    /**
     * 保存或更新自定义消息模板.
     * @param string $key 模板唯一标识
     * @param array $data 模板参数: name, title, content, priority, channels
     */
    public static function saveTemplate(string $key, array $data): bool
    {
        $customs = [];
        try {
            if (function_exists('sysdata')) {
                $saved = sysdata('notice_templates');
                if (is_array($saved)) {
                    $customs = $saved;
                }
            }
        } catch (\Throwable) {
            $customs = [];
        }
        $customs[$key] = array_merge($customs[$key] ?? [], $data, ['updated_at' => date('Y-m-d H:i:s')]);
        return (bool)sysdata('notice_templates', $customs);
    }

    /**
     * 删除自定义消息模板.
     */
    public static function removeTemplate(string $key): bool
    {
        $customs = [];
        try {
            if (function_exists('sysdata')) {
                $saved = sysdata('notice_templates');
                if (is_array($saved)) {
                    $customs = $saved;
                }
            }
        } catch (\Throwable) {
            return false;
        }
        if (isset($customs[$key])) {
            unset($customs[$key]);
            return (bool)sysdata('notice_templates', $customs);
        }
        return true;
    }

    /**
     * 渲染消息模板.
     * @param string $key 模板标识
     * @param array $vars 动态替换变量
     * @return array{key: string, title: string, content: string, priority: string, channels: string, url: string, btnText: string}
     * @throws NoticeException
     */
    public static function renderTemplate(string $key, array $vars = []): array
    {
        $tpl = static::template($key);
        if (is_null($tpl)) {
            throw new NoticeException("Notice template [{$key}] not found.");
        }

        // 自动注入常用全局变量
        $vars = array_merge([
            'time'      => date('Y-m-d H:i:s'),
            'date'      => date('Y-m-d'),
            'site_name' => strval(sysconf('site_name') ?? '系统通知中心'),
            'level'     => '警告',
            'detail'    => '请登录系统后台查看详情',
            'status'    => '已完成',
            'duration'  => '0.5s',
            'summary'   => '执行成功',
            'expire'    => '5',
            'remark'    => '无',
        ], $vars);

        $replace = [];
        foreach ($vars as $k => $v) {
            if (is_scalar($v)) {
                $replace["{{$k}}"] = strval($v);
                $replace["{{{$k}}}"] = strval($v);
            }
        }

        $title = str_replace(array_keys($replace), array_values($replace), strval($tpl['title'] ?? ''));
        $content = str_replace(array_keys($replace), array_values($replace), strval($tpl['content'] ?? ''));
        $priority = static::normalizePriority($vars['priority'] ?? $tpl['priority'] ?? self::PRIORITY_NORMAL);
        $channels = strval($vars['channels'] ?? $tpl['channels'] ?? '');
        $url = strval($vars['url'] ?? '');
        $btnText = strval($vars['btnText'] ?? '查看详情');

        return [
            'key'      => $key,
            'title'    => $title,
            'content'  => $content,
            'priority' => $priority,
            'channels' => $channels,
            'url'      => $url,
            'btnText'  => $btnText,
        ];
    }

    /**
     * 根据模板发送通知.
     * @param string $key 模板标识
     * @param array $vars 模板变量数组
     * @param string $to 接收人
     * @param ?string $channel 指定渠道（为空时自动依据模板配置或全部启用渠道）
     * @param string|int|null $priority 覆盖优先级
     * @return array
     * @throws NoticeException
     */
    public static function sendTemplate(string $key, array $vars = [], string $to = '', ?string $channel = null, string|int|null $priority = null): array
    {
        $rendered = static::renderTemplate($key, $vars);
        $usePriority = !is_null($priority) ? static::normalizePriority($priority) : $rendered['priority'];
        $hasUrl = $rendered['url'] !== '';

        if (!empty($channel)) {
            $inst = static::instance($channel);
            return $hasUrl
                ? $inst->sendCard($rendered['title'], $rendered['content'], $rendered['url'], $rendered['btnText'], $to, $usePriority)
                : $inst->send($rendered['title'], $rendered['content'], $to, $usePriority);
        }

        // 检查模板指定渠道或全局渠道
        $targetChannels = array_filter(explode(',', $rendered['channels']));
        if (!empty($targetChannels)) {
            $result = [];
            foreach ($targetChannels as $c) {
                try {
                    $inst = static::instance(trim($c));
                    $res = $hasUrl
                        ? $inst->sendCard($rendered['title'], $rendered['content'], $rendered['url'], $rendered['btnText'], $to, $usePriority)
                        : $inst->send($rendered['title'], $rendered['content'], $to, $usePriority);
                    $result[$c] = ['ok' => true, 'info' => 'ok', 'raw' => $res];
                } catch (\Throwable $e) {
                    $result[$c] = ['ok' => false, 'info' => $e->getMessage()];
                }
            }
            return $result;
        }

        return $hasUrl
            ? static::pushCard($rendered['title'], $rendered['content'], $rendered['url'], $rendered['btnText'], $to, $usePriority)
            : static::push($rendered['title'], $rendered['content'], $to, $usePriority);
    }

    /**
     * 读取通知配置（支持环境变量回退 NOTICE_{NAME}）.
     * @param string $name 配置名称，对应 sysconf notice.{name}
     */
    public static function config(string $name): string
    {
        $val = strval(sysconf("notice.{$name}|raw") ?? '');
        if ($val === '') {
            $env = 'NOTICE_' . strtoupper(str_replace('.', '_', $name));
            $val = strval(Library::$sapp->env->get($env) ?? getenv($env) ?? '');
        }
        return $val;
    }

    /**
     * 获取通知渠道类型.
     */
    public static function types(): array
    {
        return [
            'qywechat' => lang('企业微信群机器人'),
            'feishu'   => lang('飞书机器人'),
            'dingtalk' => lang('钉钉机器人'),
            'wechat'   => lang('公众号模板消息'),
            'mail'     => lang('邮件通知'),
            'sms'      => lang('手机短信'),
        ];
    }

    /**
     * 判断指定渠道或已启用渠道是否配置完整.
     */
    public static function ready(?string $name = null): bool
    {
        $check = static function (string $type): bool {
            return match ($type) {
                'qywechat' => static::config('qywechat_url') !== '',
                'feishu'   => static::config('feishu_webhook') !== '',
                'dingtalk' => static::config('dingtalk_webhook') !== '',
                'wechat'   => static::config('wechat_appid') !== '' && static::config('wechat_tpl') !== '',
                'mail'     => static::config('mail_host') !== '' && static::config('mail_user') !== '' && static::config('mail_pass') !== '',
                'sms'      => Sms::ready(),
                default    => false,
            };
        };

        if (!is_null($name)) {
            return $check(strtolower($name));
        }

        $enables = array_filter(explode(',', static::config('enable')));
        if (empty($enables)) {
            return false;
        }
        foreach ($enables as $type) {
            if ($check($type)) {
                return true;
            }
        }
        return false;
    }
}
