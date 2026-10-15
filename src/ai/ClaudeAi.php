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
use think\admin\extend\HttpExtend;
use think\admin\facade\Ai;

/**
 * Anthropic Claude 大模型.
 * 配置项 ai.claude_key、claude_model、claude_endpoint.
 * @class ClaudeAi
 */
class ClaudeAi implements AiInterface
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
        $key = Ai::config('claude_key');
        if ($key === '') {
            throw new Exception('请先配置 Claude 的 API Key！');
        }
        $endpoint = Ai::config('claude_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.anthropic.com/v1/messages';
        }
        $model = Ai::config('claude_model');
        if ($model === '') {
            $model = 'claude-3-5-sonnet-20241022';
        }

        // 若使用第三方 OpenAI 兼容网关 (如 OneAPI/NewAPI/OpenRouter)
        if (!str_contains($endpoint, 'anthropic.com')) {
            return $this->requestChat($endpoint, $key, $model, $messages, $options);
        }

        // Anthropic 原生 Messages API
        $formatted = [];
        $system = '';
        foreach ($this->formatMessages($messages) as $item) {
            $role = strtolower(strval($item['role'] ?? 'user'));
            $content = strval($item['content'] ?? '');
            if ($role === 'system') {
                $system = $content;
            } else {
                $formatted[] = [
                    'role'    => $role === 'assistant' ? 'assistant' : 'user',
                    'content' => $content,
                ];
            }
        }
        if (empty($formatted)) {
            $formatted[] = ['role' => 'user', 'content' => is_string($messages) ? $messages : 'Hello'];
        }

        $payload = [
            'model'      => $options['model'] ?? $model,
            'max_tokens' => intval($options['max_tokens'] ?? 4096),
            'messages'   => $formatted,
        ];
        if ($system !== '') {
            $payload['system'] = $system;
        }
        if (isset($options['temperature']) || Ai::config('temperature') !== '') {
            $payload['temperature'] = floatval($options['temperature'] ?? (Ai::config('temperature') ?: 0.7));
        }

        $headers = [
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json; charset=utf-8',
        ];

        $result = HttpExtend::post($endpoint, json_encode($payload, JSON_UNESCAPED_UNICODE), [
            'headers' => $headers,
            'timeout' => 60,
        ]);
        $json = json_decode(strval($result), true);
        if (isset($json['content'][0]['text'])) {
            return strval($json['content'][0]['text']);
        }
        if (isset($json['error']['message'])) {
            throw new Exception('Claude 请求失败：' . $json['error']['message']);
        }
        throw new Exception('Claude 请求失败：响应内容异常');
    }

    /**
     * 获取标准 OpenAI 客户端.
     * @throws Exception
     */
    public function client(): \OpenAI\Client
    {
        $key = Ai::config('claude_key');
        $endpoint = Ai::config('claude_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://api.anthropic.com/v1/';
        }
        return $this->createClient($endpoint, $key);
    }

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool
    {
        return Ai::config('claude_key') !== '';
    }
}
