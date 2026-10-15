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
 * 飞书自定义机器人通知 (支持普通文本、交互式卡片、图片与文件推送).
 * 配置项 notice.feishu_webhook，可选 notice.feishu_secret 签名密钥.
 * @class FeishuNotice
 */
class FeishuNotice implements NoticeInterface
{
    use NoticeUsageTrait;

    /**
     * 发送飞书消息.
     * @param string $title 标题
     * @param string $content 内容
     * @param string $to 接收人标识（可选）
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = Notice::config('feishu_webhook');
        if ($hook === '') {
            throw new NoticeException('请先配置飞书机器人 Webhook 地址！');
        }

        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $color = match ($priority) {
            'urgent', 3 => 'red',
            'high', 2   => 'orange',
            'low', 0    => 'grey',
            default     => 'blue',
        };

        // 使用高质感交互卡片展现
        $card = [
            'config' => ['wide_screen_mode' => true],
            'header' => [
                'title'    => ['tag' => 'plain_text', 'content' => $formattedTitle],
                'template' => $color,
            ],
            'elements' => [
                [
                    'tag'  => 'div',
                    'text' => ['tag' => 'lark_md', 'content' => $content],
                ],
            ],
        ];

        $body = ['msg_type' => 'interactive', 'card' => $card];
        $this->applySign($body);

        $data = $this->request($hook, $body);
        if (intval($data['code'] ?? $data['StatusCode'] ?? -1) !== 0) {
            $msg = strval($data['msg'] ?? $data['StatusMessage'] ?? 'unknown');
            $this->logRequest('feishu', 'send_error', ['title' => $title, 'error' => $msg]);
            throw new NoticeException('飞书发送失败：' . $msg);
        }

        $this->logRequest('feishu', 'send', ['title' => $title, 'priority' => $priority, 'success' => true]);
        return $data;
    }

    /**
     * 发送飞书原生交互卡片.
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = Notice::config('feishu_webhook');
        if ($hook === '') {
            throw new NoticeException('请先配置飞书机器人 Webhook 地址！');
        }

        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $color = match ($priority) {
            'urgent', 3 => 'red',
            'high', 2   => 'orange',
            'low', 0    => 'grey',
            default     => 'blue',
        };

        $elements = [
            [
                'tag'  => 'div',
                'text' => ['tag' => 'lark_md', 'content' => $content],
            ],
        ];

        if ($url !== '') {
            $elements[] = [
                'tag'     => 'action',
                'actions' => [
                    [
                        'tag'  => 'button',
                        'text' => ['tag' => 'plain_text', 'content' => $btnText !== '' ? $btnText : '查看详情'],
                        'type' => ($priority === 'urgent' || $priority === 3) ? 'danger' : 'primary',
                        'url'  => $url,
                    ],
                ],
            ];
        }

        $card = [
            'config'   => ['wide_screen_mode' => true],
            'header'   => [
                'title'    => ['tag' => 'plain_text', 'content' => $formattedTitle],
                'template' => $color,
            ],
            'elements' => $elements,
        ];

        $body = ['msg_type' => 'interactive', 'card' => $card];
        $this->applySign($body);

        $data = $this->request($hook, $body);
        if (intval($data['code'] ?? $data['StatusCode'] ?? -1) !== 0) {
            $msg = strval($data['msg'] ?? $data['StatusMessage'] ?? 'unknown');
            $this->logRequest('feishu', 'send_card_error', ['title' => $title, 'error' => $msg]);
            throw new NoticeException('飞书卡片发送失败：' . $msg);
        }

        $this->logRequest('feishu', 'send_card', ['title' => $title, 'url' => $url, 'success' => true]);
        return $data;
    }

    /**
     * 发送飞书图片卡片.
     */
    public function sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $subject = $title !== '' ? $title : '图片通知';
        $content = "![{$subject}]({$image})";
        return $this->sendCard($subject, $content, $image, '查看原图', $to, $priority, $options);
    }

    /**
     * 发送飞书文件推送卡片.
     */
    public function sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $name = $filename !== '' ? $filename : basename($file);
        $title = "📎 文件推送: {$name}";
        $content = "您收到一个推送文件：**{$name}**\n请点击下方按钮直接下载文件。";
        return $this->sendCard($title, $content, $file, '下载文件', $to, $priority, $options);
    }

    /**
     * 附加飞书安全加签.
     */
    private function applySign(array &$body): void
    {
        $secret = Notice::config('feishu_secret');
        if ($secret !== '') {
            $body['timestamp'] = strval(time());
            $body['sign'] = base64_encode(hash_hmac('sha256', '', "{$body['timestamp']}\n{$secret}", true));
        }
    }
}
