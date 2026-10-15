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

namespace think\admin\ai;

use think\admin\base\Exception;
use think\admin\contract\AiInterface;
use think\admin\contract\AiUsageTrait;
use think\admin\facade\Ai;

/**
 * 腾讯混元 (Tencent Hunyuan) 大模型.
 * 配置项 ai.hunyuan_key、hunyuan_model、hunyuan_endpoint.
 * @class HunyuanAi
 */
class HunyuanAi implements AiInterface
{
    use AiUsageTrait;

    /**
     * 发送对话请求.
     * @param string|array $messages
     * @param array $options
     * @return string
     * @throws Exception
     */
    public function chat(string|array $messages, array $options = []): string
    {
        $key = Ai::config('hunyuan_key');
        $endpoint = Ai::config('hunyuan_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.hunyuan.cloud.tencent.com/v1/chat/completions';
        }
        $model = Ai::config('hunyuan_model');
        if ($model === '') {
            $model = 'hunyuan-standard';
        }
        return $this->requestChat($endpoint, $key, $model, $messages, $options);
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('hunyuan_key');
        $endpoint = Ai::config('hunyuan_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.hunyuan.cloud.tencent.com/v1/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('hunyuan_key') !== '';
    }
}
