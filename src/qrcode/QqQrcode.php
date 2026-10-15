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

namespace think\admin\qrcode;

use think\admin\contract\QrcodeInterface;
use think\admin\contract\QrcodeUsageTrait;
use think\admin\exception\QrcodeException;
use think\admin\extend\HttpExtend;
use think\admin\facade\Qrcode;

/**
 * QQ 小程序码驱动.
 * @class QqQrcode
 */
class QqQrcode implements QrcodeInterface
{
    use QrcodeUsageTrait;

    /**
     * 生成 QQ 小程序码.
     * @param string $page 页面路径（不带前导斜杠，不带参数）
     * @param array $query 页面参数
     * @param array $options 驱动选项（extra 额外参数等）
     * @return array{code: int, message: string, content: string, url: string, mime: string, raw: array}
     * @throws QrcodeException
     */
    public function create(string $page, array $query = [], array $options = []): array
    {
        $appid = Qrcode::config('qq_appid');
        $secret = Qrcode::config('qq_secret');
        if ($appid === '' || $secret === '') {
            throw new QrcodeException('QQ小程序配置未完善，请先配置 AppID 和 AppSecret！');
        }

        $path = $this->buildPath($page, $query);
        $payload = ['appid' => $appid];
        if ($path !== '') {
            $payload['path'] = $path;
        }

        if (!empty($options['extra']) && is_array($options['extra'])) {
            $payload = array_merge($payload, $options['extra']);
        }

        $content = $this->requestMiniCode($appid, $secret, $payload);

        $this->logRequest('qq', 'create', [
            'page'    => $page,
            'query'   => $query,
            'options' => $options,
            'success' => true,
        ]);

        return [
            'code'    => 0,
            'message' => 'success',
            'content' => $content,
            'url'     => '',
            'mime'    => $this->detectMime($content),
            'raw'     => [
                'page'    => $page,
                'query'   => $query,
                'options' => $options,
            ],
        ];
    }

    /**
     * 检查 QQ 小程序码配置是否就绪.
     */
    public function ready(): bool
    {
        return Qrcode::config('qq_appid') !== '' && Qrcode::config('qq_secret') !== '';
    }

    /**
     * 请求生成小程序码二进制流，支持 Token 失效自动重试一次.
     * @param string $appid
     * @param string $secret
     * @param array $payload
     * @param bool $retry
     * @return string
     * @throws QrcodeException
     */
    private function requestMiniCode(string $appid, string $secret, array $payload, bool $retry = true): string
    {
        $token = $this->getAccessToken($appid, $secret);
        $url = "https://api.q.qq.com/api/json/qqa/CreateMiniCode?access_token={$token}";

        $response = HttpExtend::request('post', $url, [
            'data'       => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'headers'    => ['Content-Type: application/json; charset=utf-8'],
            'ssl_verify' => true,
            'timeout'    => 30,
        ]);

        if (!is_string($response) || $response === '') {
            throw new QrcodeException('QQ小程序码接口请求无响应');
        }

        $json = json_decode($response, true);
        if (is_array($json) && isset($json['errcode']) && $json['errcode'] !== 0) {
            $errcode = intval($json['errcode']);
            $errmsg = strval($json['errmsg'] ?? 'unknown');

            // 遇到 Token 失效错误码，清空缓存并重试一次
            if ($retry && in_array($errcode, [40001, 40014, 42001, 40002], true)) {
                $this->clearAccessToken($appid, $secret);
                return $this->requestMiniCode($appid, $secret, $payload, false);
            }

            if ($errcode === 45029) {
                $errmsg .= '（生成数量达上限）';
            }

            throw new QrcodeException("QQ小程序码生成失败: [{$errcode}] {$errmsg}", $errcode);
        }

        return $response;
    }

    /**
     * 获取 QQ 小程序 AccessToken（带缓存）.
     * @param string $appid
     * @param string $secret
     * @return string
     * @throws QrcodeException
     */
    private function getAccessToken(string $appid, string $secret): string
    {
        $cacheKey = $this->tokenCacheKey($appid, $secret);
        $token = strval(cache($cacheKey) ?? '');
        if ($token !== '') {
            return $token;
        }

        $query = http_build_query([
            'grant_type' => 'client_credential',
            'appid'      => $appid,
            'secret'     => $secret,
        ]);
        $url = "https://api.q.qq.com/api/getToken?{$query}";

        $response = HttpExtend::get($url, [], [
            'ssl_verify' => true,
            'timeout'    => 30,
        ]);

        if (!is_string($response) || $response === '') {
            throw new QrcodeException('QQ小程序获取 Token 接口无响应');
        }

        $json = json_decode($response, true);
        if (!is_array($json) || empty($json['access_token'])) {
            $errcode = intval($json['errcode'] ?? -1);
            $errmsg = strval($json['errmsg'] ?? 'unknown');
            throw new QrcodeException("QQ小程序 AccessToken 获取失败: [{$errcode}] {$errmsg}", $errcode);
        }

        $accessToken = strval($json['access_token']);
        $expiresIn = intval($json['expires_in'] ?? 7200);
        $ttl = max($expiresIn - 300, 60);

        cache($cacheKey, $accessToken, $ttl);
        return $accessToken;
    }

    /**
     * 清除 AccessToken 缓存.
     */
    private function clearAccessToken(string $appid, string $secret): void
    {
        cache($this->tokenCacheKey($appid, $secret), null);
    }

    /**
     * 生成 Token 缓存键名.
     */
    private function tokenCacheKey(string $appid, string $secret): string
    {
        return 'qq_minicode_token_' . md5($appid . $secret);
    }
}
