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
 * DeepSeek 深度求索大模型.
 * 配置项 ai.deepseek_key、deepseek_model、deepseek_endpoint.
 * @class DeepseekAi
 */
class DeepseekAi implements AiInterface
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
        $key = Ai::config('deepseek_key');
        $endpoint = Ai::config('deepseek_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.deepseek.com/chat/completions';
        }
        $model = Ai::config('deepseek_model');
        if ($model === '') {
            $model = 'deepseek-chat';
        }
        return $this->requestChat($endpoint, $key, $model, $messages, $options);
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('deepseek_key');
        $endpoint = Ai::config('deepseek_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.deepseek.com/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('deepseek_key') !== '';
    }
}
