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

use think\admin\facade\Ai;
use think\admin\contract\AiInterface;
use think\admin\contract\AiUsageTrait;
use think\admin\base\Exception;

/**
 * OpenAI ChatGPT 大模型.
 * 配置项 ai.openai_key、openai_model、openai_endpoint.
 * @class OpenaiAi
 */
class OpenaiAi implements AiInterface
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
        $key = Ai::config('openai_key');
        $endpoint = Ai::config('openai_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.openai.com/v1/chat/completions';
        }
        $model = Ai::config('openai_model');
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }
        return $this->requestChat($endpoint, $key, $model, $messages, $options);
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('openai_key');
        $endpoint = Ai::config('openai_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.openai.com/v1/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('openai_key') !== '';
    }
}
