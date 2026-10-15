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
 * 企业微信群机器人 Webhook 通知 (支持 Markdown、文本卡片、图片与文件推送).
 * 配置项 notice.qywechat_url (Webhook 地址)、qywechat_mobile (默认 @ 手机号).
 * @class QywechatNotice
 */
class QywechatNotice implements NoticeInterface
{
    use NoticeUsageTrait;

    /**
     * 发送企业微信群机器人消息.
     * @param string $title 消息标题
     * @param string $content 消息正文
     * @param string $to 指定 @ 的手机号，多个用逗号隔开，传 @all 表示全员提醒
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $url = Notice::config('qywechat_url');
        if ($url === '') {
            throw new NoticeException('请先配置企业微信群机器人 Webhook 地址！');
        }

        if ($to === '') {
            $to = Notice::config('qywechat_mobile');
        }

        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $mobiles = [];
        $isUrgent = in_array($priority, ['urgent', 3], true);

        if ($isUrgent && $to === '') {
            $mobiles = ['@all'];
        } elseif ($to !== '') {
            $mobiles = array_filter(array_map('trim', explode(',', $to)));
        }

        $markdown = "### {$formattedTitle}\n{$content}";
        $body = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'content' => $markdown,
            ],
        ];

        if (!empty($mobiles)) {
            $body['markdown']['mentioned_mobile_list'] = $mobiles;
        }

        $data = $this->request($url, $body);
        if (intval($data['errcode'] ?? -1) !== 0) {
            $msg = strval($data['errmsg'] ?? 'unknown');
            $this->logRequest('qywechat', 'send_error', ['title' => $title, 'error' => $msg]);
            throw new NoticeException('企业微信机器人发送失败：' . $msg);
        }

        $this->logRequest('qywechat', 'send', ['title' => $title, 'priority' => $priority, 'success' => true]);
        return $data;
    }

    /**
     * 发送企业微信原生文本卡片 (textcard).
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = Notice::config('qywechat_url');
        if ($hook === '') {
            throw new NoticeException('请先配置企业微信群机器人 Webhook 地址！');
        }

        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $body = [
            'msgtype'  => 'textcard',
            'textcard' => [
                'title'       => $formattedTitle,
                'description' => $content,
                'url'         => $url !== '' ? $url : 'https://hlwadmin.top',
                'btntxt'      => $btnText !== '' ? $btnText : '查看详情',
            ],
        ];

        $data = $this->request($hook, $body);
        if (intval($data['errcode'] ?? -1) !== 0) {
            // textcard 若因未配置域名校验失败，平滑降级为 markdown 卡片推送
            return $this->send($formattedTitle, "{$content}\n\n[{$btnText}]({$url})", $to, $priority, $options);
        }

        $this->logRequest('qywechat', 'send_card', ['title' => $title, 'url' => $url, 'success' => true]);
        return $data;
    }

    /**
     * 发送企业微信图片通知.
     */
    public function sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $hook = Notice::config('qywechat_url');
        if ($hook === '') {
            throw new NoticeException('请先配置企业微信群机器人 Webhook 地址！');
        }

        // 若为本地文件且小于 2MB，可直接使用原生 base64 + md5 发送
        if (file_exists($image) && is_file($image) && filesize($image) <= 2097152) {
            $raw = file_get_contents($image);
            $body = [
                'msgtype' => 'image',
                'image'   => [
                    'base64' => base64_encode($raw),
                    'md5'    => md5($raw),
                ],
            ];
            $data = $this->request($hook, $body);
            if (intval($data['errcode'] ?? -1) === 0) {
                return $data;
            }
        }

        // URL 或降级：使用 Markdown 图片语法展示
        $subject = $title !== '' ? $title : '图片通知';
        $markdown = "### " . $this->formatTitleByPriority($subject, $priority) . "\n\n![{$subject}]({$image})";
        return $this->send($subject, $markdown, $to, $priority, $options);
    }

    /**
     * 发送企业微信文件推送通知.
     */
    public function sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $name = $filename !== '' ? $filename : basename($file);
        $title = "📎 文件推送: {$name}";
        $content = "您收到一个推送文件：<font color=\"info\">{$name}</font>\n\n> [点击此处直接下载文件]({$file})";
        return $this->send($title, $content, $to, $priority, $options);
    }
}
