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

namespace think\admin\contract;

use think\admin\exception\NoticeException;
use think\admin\extend\HttpExtend;
use think\admin\facade\Library;
use think\Container;

/**
 * 通知驱动公共请求方法、通用卡片/图片/文件降级处理与日志记录.
 * @trait NoticeUsageTrait
 */
trait NoticeUsageTrait
{
    /**
     * 获取对象实例.
     */
    public static function instance(): static
    {
        return Container::getInstance()->make(static::class);
    }

    /**
     * 根据优先级规范化标题.
     */
    protected function formatTitleByPriority(string $title, string|int $priority): string
    {
        $p = is_int($priority) ? match ($priority) {
            3 => 'urgent',
            2 => 'high',
            0 => 'low',
            default => 'normal',
        } : strtolower(trim($priority));

        if ($p === 'urgent' && !str_starts_with($title, '[')) {
            return "[紧急告警] {$title}";
        }
        if ($p === 'high' && !str_starts_with($title, '[')) {
            return "[重要通知] {$title}";
        }
        return $title;
    }

    /**
     * 发送卡片通知通用降级实现（各渠道可定制更丰富的原生交互卡片）.
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $body = $content;
        if ($url !== '') {
            $btn = $btnText !== '' ? $btnText : '查看详情';
            $body .= "\n\n🔗 [{$btn}]: {$url}";
        }
        return $this->send($title, $body, $to, $priority, $options);
    }

    /**
     * 发送图片通知通用降级实现.
     */
    public function sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $subject = $title !== '' ? $title : '图片通知';
        $body = "🖼️ 查看图片：{$image}";
        return $this->send($subject, $body, $to, $priority, $options);
    }

    /**
     * 发送文件推送通用降级实现.
     */
    public function sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $name = $filename !== '' ? $filename : basename($file);
        $subject = "📎 文件推送: {$name}";
        $body = "您收到一个推送文件【{$name}】\n下载地址：{$file}";
        return $this->send($subject, $body, $to, $priority, $options);
    }

    /**
     * 记录通知驱动请求日志.
     * @param string $driver 驱动名称
     * @param string $action 操作动作
     * @param array $data 数据内容
     */
    protected function logRequest(string $driver, string $action, array $data = []): void
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (function_exists('sysoplog')) {
            sysoplog("notice.{$action}", "[{$driver}] {$content}");
        }
        if (isset(Library::$sapp->log)) {
            Library::$sapp->log->record("[notice.{$driver}.{$action}] {$content}", 'info');
        }
    }

    /**
     * 提交请求并解析 JSON 响应.
     * @param string $url 请求地址
     * @param array|string $body 请求内容，数组时按 JSON 提交
     * @param array $headers 请求头
     * @param string $method 请求方式
     * @throws NoticeException
     */
    protected function request(string $url, array|string $body = [], array $headers = [], string $method = 'POST'): array
    {
        $options = ['headers' => $headers, 'timeout' => 15];
        if (is_array($body)) {
            $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $options['headers'][] = 'Content-Type: application/json; charset=utf-8';
        }
        if (strtoupper($method) !== 'GET') {
            $options['data'] = $body;
        }
        $result = HttpExtend::request($method, $url, $options);
        $data = json_decode(strval($result), true);
        if (!is_array($data)) {
            throw new NoticeException('通知接口请求失败，响应内容无法解析！');
        }
        return $data;
    }
}
