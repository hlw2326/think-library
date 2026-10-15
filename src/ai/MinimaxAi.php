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
 * MiniMax (名之梦) 大模型.
 * 配置项 ai.minimax_key、minimax_model、minimax_endpoint.
 * @class MinimaxAi
 */
class MinimaxAi implements AiInterface
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
        $key = Ai::config('minimax_key');
        $endpoint = Ai::config('minimax_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.minimax.chat/v1/chat/completions';
        }
        $model = Ai::config('minimax_model');
        if ($model === '') {
            $model = 'abab6.5s-chat';
        }
        return $this->requestChat($endpoint, $key, $model, $messages, $options);
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('minimax_key');
        $endpoint = Ai::config('minimax_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.minimax.chat/v1/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('minimax_key') !== '';
    }
}
