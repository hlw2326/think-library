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

namespace think\admin\notice;

use think\admin\contract\NoticeInterface;
use think\admin\contract\NoticeUsageTrait;
use think\admin\exception\NoticeException;
use think\admin\facade\Notice;

/**
 * 钉钉自定义机器人通知 (支持普通文本、ActionCard 交互卡片、Markdown 图文与文件推送).
 * 配置项 notice.dingtalk_webhook，可选 notice.dingtalk_secret 加签密钥.
 * @class DingtalkNotice
 */
class DingtalkNotice implements NoticeInterface
{
    use NoticeUsageTrait;

    /**
     * 发送群机器人文本消息.
     * @param string $title 标题
     * @param string $content 内容
     * @param string $to 接收人手机号（逗号隔开）
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = $this->buildHook();
        if ($to === '') {
            $to = Notice::config('dingtalk_to');
        }

        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $mobiles = array_values(array_filter(array_map('trim', explode(',', $to))));
        $isUrgent = in_array($priority, ['urgent', 3], true);

        $body = [
            'msgtype' => 'text',
            'text'    => ['content' => "{$formattedTitle}\n{$content}"],
            'at'      => [
                'atMobiles' => $mobiles,
                'isAtAll'   => $isUrgent && empty($mobiles),
            ],
        ];

        $data = $this->request($hook, $body);
        if (intval($data['errcode'] ?? -1) !== 0) {
            $msg = strval($data['errmsg'] ?? 'unknown');
            $this->logRequest('dingtalk', 'send_error', ['title' => $title, 'error' => $msg]);
            throw new NoticeException('钉钉发送失败：' . $msg);
        }

        $this->logRequest('dingtalk', 'send', ['title' => $title, 'priority' => $priority, 'success' => true]);
        return $data;
    }

    /**
     * 发送钉钉独立 ActionCard 交互卡片.
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = $this->buildHook();
        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $text = "### {$formattedTitle}\n\n{$content}";

        $body = [
            'msgtype'    => 'actionCard',
            'actionCard' => [
                'title'       => $formattedTitle,
                'text'        => $text,
                'singleTitle' => $btnText !== '' ? $btnText : '查看详情',
                'singleURL'   => $url !== '' ? $url : 'https://hlwadmin.top',
            ],
        ];

        $data = $this->request($hook, $body);
        if (intval($data['errcode'] ?? -1) !== 0) {
            $msg = strval($data['errmsg'] ?? 'unknown');
            $this->logRequest('dingtalk', 'send_card_error', ['title' => $title, 'error' => $msg]);
            throw new NoticeException('钉钉 ActionCard 发送失败：' . $msg);
        }

        $this->logRequest('dingtalk', 'send_card', ['title' => $title, 'url' => $url, 'success' => true]);
        return $data;
    }

    /**
     * 发送钉钉 Markdown 图片通知.
     */
    public function sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = $this->buildHook();
        $subject = $title !== '' ? $title : '图片通知';
        $formattedTitle = $this->formatTitleByPriority($subject, $priority);
        $text = "### {$formattedTitle}\n\n![{$subject}]({$image})";

        $body = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'title' => $formattedTitle,
                'text'  => $text,
            ],
        ];

        $data = $this->request($hook, $body);
        if (intval($data['errcode'] ?? -1) !== 0) {
            $msg = strval($data['errmsg'] ?? 'unknown');
            $this->logRequest('dingtalk', 'send_image_error', ['title' => $subject, 'error' => $msg]);
            throw new NoticeException('钉钉图片发送失败：' . $msg);
        }

        $this->logRequest('dingtalk', 'send_image', ['title' => $subject, 'image' => $image, 'success' => true]);
        return $data;
    }

    /**
     * 发送钉钉文件推送 ActionCard.
     */
    public function sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $name = $filename !== '' ? $filename : basename($file);
        $title = "📎 文件推送: {$name}";
        $content = "您收到一个推送文件：\n\n- 文件名称：**{$name}**\n\n请点击下方按钮直接下载文件。";
        return $this->sendCard($title, $content, $file, '立即下载', $to, $priority, $options);
    }

    /**
     * 构建加签 Webhook 请求地址.
     */
    private function buildHook(): string
    {
        $hook = Notice::config('dingtalk_webhook');
        if ($hook === '') {
            throw new NoticeException('请先配置钉钉机器人 Webhook 地址！');
        }
        $secret = Notice::config('dingtalk_secret');
        if ($secret !== '') {
            $time = strval(intval(microtime(true) * 1000));
            $sign = base64_encode(hash_hmac('sha256', "{$time}\n{$secret}", $secret, true));
            $hook .= (str_contains($hook, '?') ? '&' : '?') . 'timestamp=' . $time . '&sign=' . rawurlencode($sign);
        }
        return $hook;
    }
}
