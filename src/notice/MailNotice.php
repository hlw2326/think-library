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
 * SMTP 邮件通知 (支持纯文本、HTML富文本卡片、图片与文件推送).
 * 配置项 notice.mail_host、mail_port、mail_secure(ssl|tls|空)、mail_user、mail_pass、mail_from、mail_name、mail_to.
 * @class MailNotice
 */
class MailNotice implements NoticeInterface
{
    use NoticeUsageTrait;

    /**
     * SMTP 连接.
     * @var resource
     */
    private $conn;

    /**
     * 发送普通邮件通知.
     * @param string $title 邮件主题
     * @param string $content 邮件内容
     * @param string $to 接收人邮箱，为空时使用 notice.mail_to，多个用逗号隔开
     * @param string|int $priority 优先级
     * @param array $options 额外参数（如 is_html => true）
     * @return array
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $isHtml = !empty($options['is_html']);
        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        return $this->sendRaw($formattedTitle, $content, $to, $priority, $isHtml);
    }

    /**
     * 发送高质感响应式 HTML 卡片邮件.
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $headerBg = match ($priority) {
            'urgent', 3 => 'linear-gradient(135deg, #ff4d4f, #cf1322)',
            'high', 2   => 'linear-gradient(135deg, #fa8c16, #d46b08)',
            default     => 'linear-gradient(135deg, #1677ff, #0958d9)',
        };

        $contentHtml = nl2br(htmlspecialchars($content));
        $btnHtml = '';
        if ($url !== '') {
            $btnText = $btnText !== '' ? $btnText : '查看详情';
            $btnHtml = <<<HTML
            <div style="margin-top:28px;text-align:center;">
                <a href="{$url}" target="_blank" style="display:inline-block;padding:12px 36px;background:#1677ff;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:15px;box-shadow:0 3px 10px rgba(22,119,255,0.35);">{$btnText}</a>
            </div>
HTML;
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{$formattedTitle}</title></head>
<body style="margin:0;padding:24px 10px;background:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.06);overflow:hidden;border:1px solid #eef0f4;">
        <div style="background:{$headerBg};padding:24px 30px;color:#ffffff;">
            <h2 style="margin:0;font-size:20px;font-weight:600;letter-spacing:0.5px;">{$formattedTitle}</h2>
        </div>
        <div style="padding:32px 30px;color:#2c3e50;font-size:15px;line-height:1.75;">
            {$contentHtml}
            {$btnHtml}
        </div>
        <div style="background:#fafbfc;padding:16px 30px;text-align:center;color:#8c98a4;font-size:12px;border-top:1px solid #f0f2f5;">
            此邮件由 HlwAdmin 统一通知中心自动推送，请勿直接回复。
        </div>
    </div>
</body>
</html>
HTML;

        return $this->sendRaw($formattedTitle, $html, $to, $priority, true);
    }

    /**
     * 发送内嵌/外链图片邮件通知.
     */
    public function sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $subject = $title !== '' ? $title : '图片通知';
        $formattedTitle = $this->formatTitleByPriority($subject, $priority);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{$formattedTitle}</title></head>
<body style="margin:0;padding:20px;background:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.06);">
        <div style="background:#1677ff;padding:18px 24px;color:#ffffff;">
            <h3 style="margin:0;font-size:18px;">{$formattedTitle}</h3>
        </div>
        <div style="padding:24px;text-align:center;">
            <img src="{$image}" style="max-width:100%;height:auto;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);" alt="{$subject}" />
            <p style="margin-top:16px;color:#666;font-size:13px;"><a href="{$image}" target="_blank" style="color:#1677ff;text-decoration:none;">点击查看大图</a></p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->sendRaw($formattedTitle, $html, $to, $priority, true);
    }

    /**
     * 发送文件推送通知邮件.
     */
    public function sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $name = $filename !== '' ? $filename : basename($file);
        $title = "📎 文件推送: {$name}";
        $content = "您收到一个推送文件：\n\n文件名称：{$name}\n\n请点击下方按钮直接下载。";
        return $this->sendCard($title, $content, $file, '立即下载文件', $to, $priority, $options);
    }

    /**
     * 底层发送邮件 (支持文本与 HTML).
     * @throws NoticeException
     */
    private function sendRaw(string $title, string $bodyContent, string $to, string|int $priority, bool $isHtml): array
    {
        $host = Notice::config('mail_host');
        $user = Notice::config('mail_user');
        $pass = Notice::config('mail_pass');
        $from = Notice::config('mail_from');
        if ($from === '') {
            $from = $user;
        }
        if ($host === '' || $user === '' || $from === '') {
            throw new NoticeException('请先配置 SMTP 服务器与发件账号！');
        }
        if ($to === '') {
            $to = Notice::config('mail_to');
        }
        $rcpts = array_values(array_filter(array_map('trim', explode(',', $to))));
        if (empty($rcpts)) {
            throw new NoticeException('请先配置邮件接收人！');
        }

        $secure = Notice::config('mail_secure');
        $port = intval(Notice::config('mail_port'));
        if ($port < 1) {
            $port = $secure === 'ssl' ? 465 : 25;
        }
        $scheme = $secure === 'ssl' ? 'ssl' : 'tcp';
        $this->conn = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $error, 15);
        if (!is_resource($this->conn)) {
            throw new NoticeException("SMTP 连接失败：{$error}");
        }

        stream_set_timeout($this->conn, 15);
        try {
            $this->expect(null, 220);
            $this->expect('EHLO hlwadmin', 250);
            if ($secure === 'tls') {
                $this->expect('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new NoticeException('SMTP TLS 握手失败！');
                }
                $this->expect('EHLO hlwadmin', 250);
            }
            $this->expect('AUTH LOGIN', 334);
            $this->expect(base64_encode($user), 334);
            $this->expect(base64_encode($pass), 235);
            $this->expect("MAIL FROM:<{$from}>", 250);
            foreach ($rcpts as $rcpt) {
                $this->expect("RCPT TO:<{$rcpt}>", 250);
            }
            $this->expect('DATA', 354);

            $name = Notice::config('mail_name');
            $contentType = $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';

            $head = [
                'From: =?UTF-8?B?' . base64_encode($name) . "?= <{$from}>",
                'To: ' . join(',', $rcpts),
                'Subject: =?UTF-8?B?' . base64_encode($title) . '?=',
                'Date: ' . date('r'),
                'MIME-Version: 1.0',
                "Content-Type: {$contentType}",
                'Content-Transfer-Encoding: base64',
            ];

            if ($priority === 'urgent' || $priority === 3) {
                $head[] = 'X-Priority: 1 (Highest)';
                $head[] = 'Importance: High';
            } elseif ($priority === 'high' || $priority === 2) {
                $head[] = 'X-Priority: 2 (High)';
                $head[] = 'Importance: High';
            } elseif ($priority === 'low' || $priority === 0) {
                $head[] = 'X-Priority: 5 (Lowest)';
                $head[] = 'Importance: Low';
            }

            $body = chunk_split(base64_encode($bodyContent), 76, "\r\n");
            $this->expect(join("\r\n", $head) . "\r\n\r\n" . $body . '.', 250);
            $this->expect('QUIT', 221);

            $this->logRequest('mail', 'send', ['title' => $title, 'rcpts' => $rcpts, 'priority' => $priority, 'success' => true]);
            return ['rcpt' => $rcpts];
        } finally {
            fclose($this->conn);
        }
    }

    /**
     * 发送指令并校验响应码.
     * @throws NoticeException
     */
    private function expect(?string $cmd, int $code): string
    {
        if ($cmd !== null) {
            fwrite($this->conn, $cmd . "\r\n");
        }
        $line = '';
        while (is_string($row = fgets($this->conn, 1024))) {
            $line .= $row;
            if (strlen($row) < 4 || $row[3] !== '-') {
                break;
            }
        }
        if (intval(substr($line, 0, 3)) !== $code) {
            throw new NoticeException('SMTP 响应异常：' . trim($line));
        }
        return $line;
    }
}
