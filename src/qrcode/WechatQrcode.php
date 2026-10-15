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
use think\admin\facade\Qrcode;
use WeChat\Exceptions\InvalidResponseException;
use WeMini\Qrcode as WeMiniQrcode;

/**
 * 微信小程序码驱动.
 * @class WechatQrcode
 */
class WechatQrcode implements QrcodeInterface
{
    use QrcodeUsageTrait;

    /**
     * 生成微信小程序码.
     * @param string $page 页面路径（不带前导斜杠，不带参数）
     * @param array $query 页面参数
     * @param array $options 驱动选项（mode: unlimited|limited|default, width, auto_color, line_color, is_hyaline, extra, env_version, check_path）
     * @return array{code: int, message: string, content: string, url: string, mime: string, raw: array}
     * @throws QrcodeException
     */
    public function create(string $page, array $query = [], array $options = []): array
    {
        $appid = Qrcode::config('wechat_appid');
        $secret = Qrcode::config('wechat_secret');
        if ($appid === '' || $secret === '') {
            throw new QrcodeException('微信小程序配置未完善，请先配置 AppID 和 AppSecret！');
        }

        $mode = strval($options['mode'] ?? 'unlimited');
        if (!in_array($mode, ['unlimited', 'limited', 'default'], true)) {
            throw new QrcodeException("不支持的微信小程序码模式 [{$mode}]，支持模式: unlimited, limited, default");
        }

        $width = intval($options['width'] ?? 430);
        if ($width < 280) {
            $width = 280;
        } elseif ($width > 1280) {
            $width = 1280;
        }

        $autoColor = (bool)($options['auto_color'] ?? false);
        $lineColor = $options['line_color'] ?? null;
        $isHyaline = (bool)($options['is_hyaline'] ?? true);
        $extra = (array)($options['extra'] ?? []);

        if (isset($options['env_version'])) {
            $extra['env_version'] = strval($options['env_version']);
        }
        if (isset($options['check_path'])) {
            $extra['check_path'] = (bool)$options['check_path'];
        }

        $cachePath = syspath('runtime/wechat');
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }

        // 每次新建实例，杜绝常驻进程下的 isTry 死循环与 token 重试失效问题
        $weMini = new WeMiniQrcode([
            'appid'      => $appid,
            'appsecret'  => $secret,
            'cache_path' => $cachePath,
        ]);

        try {
            if ($mode === 'unlimited') {
                if (isset($options['scene']) && $options['scene'] !== '') {
                    $scene = strval($options['scene']);
                    $len = strlen($scene);
                    if (!preg_match('/^[0-9a-zA-Z!#$&\'()*+,\\/:;=?@\\-._~]{1,32}$/', $scene)) {
                        throw new QrcodeException("微信小程序码 scene 长度不可超过 32 字符且仅允许可见英文字符和符号（当前长度: {$len}），请改存业务表，scene 只放短 ID！");
                    }
                } else {
                    $scene = $this->buildUnlimitedScene($query);
                }
                $cleanPage = ltrim($page, '/');
                $content = $weMini->createMiniScene($scene, $cleanPage, $width, $autoColor, $lineColor, $isHyaline, null, $extra);
            } elseif ($mode === 'limited') {
                $path = $this->buildPath($page, $query);
                $content = $weMini->createMiniPath($path, $width, $autoColor, $lineColor, $isHyaline, null, $extra);
            } else {
                $path = $this->buildPath($page, $query);
                $content = $weMini->createDefault($path, $width, null);
            }

            if (is_array($content)) {
                $errcode = intval($content['errcode'] ?? -1);
                $errmsg = strval($content['errmsg'] ?? 'unknown');
                throw new QrcodeException("微信小程序码生成失败: [{$errcode}] {$errmsg}", $errcode);
            }

            if (!is_string($content) || $content === '') {
                throw new QrcodeException('微信小程序码生成失败：未获取到有效的图片内容');
            }
        } catch (QrcodeException $e) {
            throw $e;
        } catch (InvalidResponseException $e) {
            $code = (int)$e->getCode();
            $msg = $e->getMessage();
            if ($code === 41030) {
                $msg .= '（所请求的页面不存在或小程序尚未发布）';
            } elseif ($code === 45029) {
                $msg .= '（生成码数量已达上限，请改用 unlimited 模式）';
            } elseif ($code === 45009) {
                $msg .= '（接口调用频率超限）';
            }
            throw new QrcodeException("微信小程序码生成失败: [{$code}] {$msg}", $code, $e);
        } catch (\Throwable $e) {
            throw new QrcodeException('微信小程序码请求异常: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }

        $this->logRequest('wechat', 'create', [
            'page'    => $page,
            'query'   => $query,
            'mode'    => $mode,
            'success' => true,
        ]);

        return [
            'code'    => 0,
            'message' => 'success',
            'content' => $content,
            'url'     => '',
            'mime'    => $this->detectMime($content),
            'raw'     => [
                'mode'    => $mode,
                'page'    => $page,
                'query'   => $query,
                'options' => $options,
            ],
        ];
    }

    /**
     * 检查微信小程序码配置是否就绪.
     */
    public function ready(): bool
    {
        return Qrcode::config('wechat_appid') !== '' && Qrcode::config('wechat_secret') !== '';
    }
}
