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
 * 微信公众号模板消息通知.
 * 配置项 notice.wechat_appid、wechat_secret、wechat_tpl 模板编号、wechat_to 默认 OpenID.
 * 模板需包含 {{title.DATA}} 与 {{content.DATA}} 两个字段.
 * @class WechatNotice
 */
class WechatNotice implements NoticeInterface
{
    use NoticeUsageTrait;

    /**
     * 发送模板消息，$to 为 OpenID，多个用逗号分隔.
     * @param string $title 标题
     * @param string $content 内容
     * @param string $to 接收人 OpenID
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $tpl = Notice::config('wechat_tpl');
        if ($tpl === '') {
            throw new NoticeException('请先配置公众号模板消息编号！');
        }
        if ($to === '') {
            $to = Notice::config('wechat_to');
        }
        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $result = [];
        foreach (array_filter(array_map('trim', explode(',', $to))) as $openid) {
            $result[$openid] = $this->push($openid, $tpl, $formattedTitle, $content);
        }
        if (empty($result)) {
            throw new NoticeException('请先配置公众号接收人 OpenID！');
        }

        $this->logRequest('wechat', 'send', ['title' => $title, 'to' => $to, 'priority' => $priority, 'success' => true]);
        return $result;
    }

    /**
     * 发送微信公众号卡片跳转模板消息.
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        $tpl = Notice::config('wechat_tpl');
        if ($tpl === '') {
            throw new NoticeException('请先配置公众号模板消息编号！');
        }
        if ($to === '') {
            $to = Notice::config('wechat_to');
        }
        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $result = [];
        foreach (array_filter(array_map('trim', explode(',', $to))) as $openid) {
            $result[$openid] = $this->push($openid, $tpl, $formattedTitle, $content, $url);
        }
        if (empty($result)) {
            throw new NoticeException('请先配置公众号接收人 OpenID！');
        }

        $this->logRequest('wechat', 'send_card', ['title' => $title, 'url' => $url, 'priority' => $priority, 'success' => true]);
        return $result;
    }

    /**
     * 向单个 OpenID 推送，Token 失效时刷新后重试一次.
     * @throws NoticeException
     */
    private function push(string $openid, string $tpl, string $title, string $content, string $jumpUrl = '', bool $retry = true): array
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $this->token();
        $payload = [
            'touser'      => $openid,
            'template_id' => $tpl,
            'data'        => [
                'title'   => ['value' => $title],
                'content' => ['value' => $content],
            ],
        ];
        if ($jumpUrl !== '') {
            $payload['url'] = $jumpUrl;
        }

        $data = $this->request($url, $payload);
        $code = intval($data['errcode'] ?? -1);
        if ($code === 0) {
            return $data;
        }
        if ($retry && in_array($code, [40001, 40014, 42001], true)) {
            cache($this->tokenKey(), null);
            return $this->push($openid, $tpl, $title, $content, $jumpUrl, false);
        }
        throw new NoticeException('公众号发送失败：' . strval($data['errmsg'] ?? 'unknown'));
    }

    /**
     * 按 AppID 隔离的 Token 缓存键，避免分站互相覆盖.
     */
    private function tokenKey(): string
    {
        return 'notice_wechat_token_' . md5(Notice::config('wechat_appid'));
    }

    /**
     * 获取并缓存 AccessToken.
     * @throws NoticeException
     */
    private function token(): string
    {
        $token = strval(cache($this->tokenKey()) ?? '');
        if ($token !== '') {
            return $token;
        }
        $appid = Notice::config('wechat_appid');
        $secret = Notice::config('wechat_secret');
        if ($appid === '' || $secret === '') {
            throw new NoticeException('请先配置公众号 AppID 与 AppSecret！');
        }
        $query = http_build_query(['grant_type' => 'client_credential', 'appid' => $appid, 'secret' => $secret]);
        $data = $this->request("https://api.weixin.qq.com/cgi-bin/token?{$query}", '', [], 'GET');
        if (empty($data['access_token'])) {
            throw new NoticeException('获取公众号 Token 失败：' . strval($data['errmsg'] ?? 'unknown'));
        }
        cache($this->tokenKey(), $data['access_token'], max(intval($data['expires_in'] ?? 7200) - 300, 60));
        return strval($data['access_token']);
    }
}
