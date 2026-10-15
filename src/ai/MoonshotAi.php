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
 * 月之暗面 Kimi (Moonshot) 大模型.
 * 配置项 ai.moonshot_key、moonshot_model、moonshot_endpoint.
 * @class MoonshotAi
 */
class MoonshotAi implements AiInterface
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
        $key = Ai::config('moonshot_key');
        $endpoint = Ai::config('moonshot_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.moonshot.cn/v1/chat/completions';
        }
        $model = Ai::config('moonshot_model');
        if ($model === '') {
            $model = 'moonshot-v1-8k';
        }
        return $this->requestChat($endpoint, $key, $model, $messages, $options);
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('moonshot_key');
        $endpoint = Ai::config('moonshot_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.moonshot.cn/v1/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('moonshot_key') !== '';
    }
}
