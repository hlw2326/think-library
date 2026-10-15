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
 * 智谱清言 (GLM) 大模型.
 * 配置项 ai.zhipu_key、zhipu_model、zhipu_endpoint.
 * @class ZhipuAi
 */
class ZhipuAi implements AiInterface
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
        $key = Ai::config('zhipu_key');
        $endpoint = Ai::config('zhipu_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
        }
        $model = Ai::config('zhipu_model');
        if ($model === '') {
            $model = 'glm-4-flash';
        }
        return $this->requestChat($endpoint, $key, $model, $messages, $options);
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('zhipu_key');
        $endpoint = Ai::config('zhipu_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://open.bigmodel.cn/api/paas/v4/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('zhipu_key') !== '';
    }
}
