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

namespace think\admin\contract;

use think\admin\exception\AiException;
use think\admin\extend\HttpExtend;
use think\admin\facade\Ai;
use think\admin\facade\Library;
use think\Container;

/**
 * AI 大模型驱动公共方法、流式生成、向量化、多模态与成本计量管理.
 * @trait AiUsageTrait
 */
trait AiUsageTrait
{
    /**
     * 获取对象实例.
     */
    public static function instance(): static
    {
        return Container::getInstance()->make(static::class);
    }

    /**
     * 获取当前驱动配置信息.
     * @return array{driver: string, key: string, endpoint: string, model: string, embedding: string, vision: string}
     */
    protected function getDriverConfig(): array
    {
        $driver = strtolower(str_replace('Ai', '', class_basename(static::class)));
        $defaults = [
            'deepseek' => ['endpoint' => 'https://api.deepseek.com', 'model' => 'deepseek-chat', 'embedding' => 'deepseek-chat', 'vision' => 'deepseek-chat'],
            'openai'   => ['endpoint' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini', 'embedding' => 'text-embedding-3-small', 'vision' => 'gpt-4o'],
            'claude'   => ['endpoint' => 'https://api.anthropic.com/v1', 'model' => 'claude-3-5-sonnet-20241022', 'embedding' => 'claude-3-5-sonnet-20241022', 'vision' => 'claude-3-5-sonnet-20241022'],
            'gemini'   => ['endpoint' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'model' => 'gemini-1.5-flash', 'embedding' => 'text-embedding-004', 'vision' => 'gemini-1.5-flash'],
            'qwen'     => ['endpoint' => 'https://dashscope.aliyuncs.com/compatible-mode/v1', 'model' => 'qwen-turbo', 'embedding' => 'text-embedding-v3', 'vision' => 'qwen-vl-max'],
            'zhipu'    => ['endpoint' => 'https://open.bigmodel.cn/api/paas/v4', 'model' => 'glm-4-flash', 'embedding' => 'embedding-3', 'vision' => 'glm-4v-flash'],
            'moonshot' => ['endpoint' => 'https://api.moonshot.cn/v1', 'model' => 'moonshot-v1-8k', 'embedding' => 'moonshot-v1-8k', 'vision' => 'moonshot-v1-8k'],
            'ernie'    => ['endpoint' => 'https://qianfan.baidubce.com/v2', 'model' => 'ernie-speed-128k', 'embedding' => 'bce-embedding-base_v1', 'vision' => 'ernie-speed-128k'],
            'spark'    => ['endpoint' => 'https://spark-api-open.xf-yun.com/v1', 'model' => 'general', 'embedding' => 'general', 'vision' => 'general'],
            'doubao'   => ['endpoint' => 'https://ark.cn-beijing.volces.com/api/v3', 'model' => 'doubao-lite-4k', 'embedding' => 'doubao-embedding', 'vision' => 'doubao-vision-lite'],
            'hunyuan'  => ['endpoint' => 'https://api.hunyuan.cloud.tencent.com/v1', 'model' => 'hunyuan-lite', 'embedding' => 'hunyuan-embedding', 'vision' => 'hunyuan-vision'],
            'minimax'  => ['endpoint' => 'https://api.minimax.chat/v1', 'model' => 'abab6.5s-chat', 'embedding' => 'embo-01', 'vision' => 'abab6.5s-chat'],
        ];

        $def = $defaults[$driver] ?? ['endpoint' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini', 'embedding' => 'text-embedding-3-small', 'vision' => 'gpt-4o'];
        $key = Ai::config("{$driver}_key");
        $endpoint = Ai::config("{$driver}_endpoint") ?: $def['endpoint'];
        $model = Ai::config("{$driver}_model") ?: $def['model'];

        return [
            'driver'    => $driver,
            'key'       => $key,
            'endpoint'  => $endpoint,
            'model'     => $model,
            'embedding' => $def['embedding'],
            'vision'    => $def['vision'],
        ];
    }

    /**
     * 记录驱动请求日志.
     * @param string $driver 驱动名称
     * @param string $action 操作动作
     * @param array $data 数据内容
     */
    protected function logRequest(string $driver, string $action, array $data = []): void
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (function_exists('sysoplog')) {
            sysoplog("ai.{$action}", "[{$driver}] {$content}");
        }
        if (isset(Library::$sapp->log)) {
            Library::$sapp->log->record("[ai.{$driver}.{$action}] {$content}", 'info');
        }
    }

    /**
     * 格式化消息为 OpenAI 标准消息列表.
     * @param string|array $messages
     * @return array
     */
    protected function formatMessages(string|array $messages): array
    {
        if (is_string($messages)) {
            return [
                ['role' => 'user', 'content' => $messages],
            ];
        }
        return $messages;
    }

    /**
     * 获取配置好的 OpenAI 标准客户端.
     * @param string $endpoint 接口地址
     * @param string $apiKey API 密钥
     * @throws AiException
     */
    protected function createClient(string $endpoint, string $apiKey): \OpenAI\Client
    {
        if ($apiKey === '') {
            throw new AiException('请先配置该 AI 引擎的 API Key！');
        }
        if (!class_exists(\OpenAI::class)) {
            throw new AiException('请先安装 OpenAI 客户端扩展包：composer require openai-php/client');
        }
        $factory = \OpenAI::factory()->withApiKey($apiKey);
        if ($endpoint !== '') {
            $baseUri = preg_replace('#/(?:chat/completions|v1/chat/completions)/?$#', '', $endpoint);
            $factory->withBaseUri(rtrim($baseUri, '/') . '/');
        }
        return $factory->make();
    }

    /**
     * 发送兼容 OpenAI 标准的 Chat Completions 请求.
     * @param string $endpoint 接口地址
     * @param string $apiKey API 密钥
     * @param string $defaultModel 默认模型名
     * @param string|array $messages 消息
     * @param array $options 额外参数
     * @return string
     * @throws AiException
     */
    protected function requestChat(string $endpoint, string $apiKey, string $defaultModel, string|array $messages, array $options = []): string
    {
        $driver = strtolower(class_basename(static::class));
        if ($apiKey === '') {
            throw new AiException('请先配置该 AI 引擎的 API Key！');
        }

        $payload = array_merge([
            'model'       => $options['model'] ?? $defaultModel,
            'messages'    => $this->formatMessages($messages),
            'temperature' => floatval($options['temperature'] ?? Ai::config('temperature') ?: 0.7),
        ], $options);

        // 优先使用已安装的 OpenAI 客户端
        if (class_exists(\OpenAI::class)) {
            try {
                $client = $this->createClient($endpoint, $apiKey);
                $response = $client->chat()->create($payload);
                $reply = strval($response->choices[0]->message->content ?? '');
                $promptTokens = intval($response->usage->promptTokens ?? $this->estimateTokens($messages));
                $completionTokens = intval($response->usage->completionTokens ?? $this->estimateTokens($reply));
                $cost = $this->estimateCost($payload['model'], $promptTokens, $completionTokens);

                $this->logRequest($driver, 'chat', [
                    'model'       => $payload['model'],
                    'tokens'      => $promptTokens + $completionTokens,
                    'cost_cny'    => $cost['total_cost'],
                    'success'     => true,
                ]);
                return $reply;
            } catch (\Throwable $exception) {
                $this->logRequest($driver, 'chat_error', ['model' => $payload['model'], 'error' => $exception->getMessage()]);
                throw new AiException('AI 请求失败：' . $exception->getMessage());
            }
        }

        // 原生 cURL HTTP POST 降级支持
        $url = $endpoint;
        if (!str_contains($url, 'chat/completions')) {
            $url = rtrim($url, '/') . '/chat/completions';
        }
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json; charset=utf-8',
        ];
        $result = HttpExtend::post($url, json_encode($payload, JSON_UNESCAPED_UNICODE), ['headers' => $headers, 'timeout' => 60]);
        $json = json_decode(strval($result), true);
        if (isset($json['choices'][0]['message']['content'])) {
            $reply = strval($json['choices'][0]['message']['content']);
            $promptTokens = intval($json['usage']['prompt_tokens'] ?? $this->estimateTokens($messages));
            $completionTokens = intval($json['usage']['completion_tokens'] ?? $this->estimateTokens($reply));
            $cost = $this->estimateCost($payload['model'], $promptTokens, $completionTokens);

            $this->logRequest($driver, 'chat', [
                'model'       => $payload['model'],
                'tokens'      => $promptTokens + $completionTokens,
                'cost_cny'    => $cost['total_cost'],
                'success'     => true,
            ]);
            return $reply;
        }
        if (isset($json['error']['message'])) {
            $this->logRequest($driver, 'chat_error', ['model' => $payload['model'], 'error' => $json['error']['message']]);
            throw new AiException('AI 请求失败：' . $json['error']['message']);
        }
        $this->logRequest($driver, 'chat_error', ['model' => $payload['model'], 'error' => '响应内容异常']);
        throw new AiException('AI 请求失败：响应内容异常');
    }

    /**
     * 流式对话补全（支持 SSE 回调与 Generator 迭代器）.
     * @param string|array $messages 消息文本或标准消息数组
     * @param ?callable $callable 实时分块接收回调 function(string $chunk, array $data = []): void
     * @param array $options 额外请求参数
     * @return \Generator<string>
     * @throws AiException
     */
    public function stream(string|array $messages, ?callable $callable = null, array $options = []): \Generator
    {
        $conf = $this->getDriverConfig();
        if ($conf['key'] === '') {
            throw new AiException("请先配置 {$conf['driver']} AI 引擎的 API Key！");
        }

        $payload = array_merge([
            'model'       => $options['model'] ?? $conf['model'],
            'messages'    => $this->formatMessages($messages),
            'temperature' => floatval($options['temperature'] ?? Ai::config('temperature') ?: 0.7),
            'stream'      => true,
        ], $options);

        // 1. 如果已安装 openai-php/client，优先使用其原生流式输出
        if (class_exists(\OpenAI::class)) {
            $client = $this->createClient($conf['endpoint'], $conf['key']);
            try {
                $stream = $client->chat()->createStreamed($payload);
                $totalText = '';
                foreach ($stream as $response) {
                    $text = strval($response->choices[0]->delta->content ?? '');
                    if ($text !== '') {
                        $totalText .= $text;
                        if (!is_null($callable)) {
                            $callable($text, $response->toArray());
                        }
                        yield $text;
                    }
                }
                $tokens = $this->estimateTokens($totalText);
                $this->logRequest($conf['driver'], 'stream', ['model' => $payload['model'], 'tokens' => $tokens, 'success' => true]);
                return;
            } catch (\Throwable $e) {
                $this->logRequest($conf['driver'], 'stream_error', ['model' => $payload['model'], 'error' => $e->getMessage()]);
                throw new AiException('AI 流式请求失败：' . $e->getMessage());
            }
        }

        // 2. 原生 cURL SSE 流式处理降级
        $url = $conf['endpoint'];
        if (!str_contains($url, 'chat/completions')) {
            $url = rtrim($url, '/') . '/chat/completions';
        }
        $headers = [
            'Authorization: Bearer ' . $conf['key'],
            'Content-Type: application/json; charset=utf-8',
            'Accept: text/event-stream',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $buffer = '';
        $chunks = [];

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$buffer, &$chunks, $callable) {
            $buffer .= $data;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if (str_starts_with($line, 'data: ')) {
                    $payloadStr = substr($line, 6);
                    if ($payloadStr === '[DONE]') {
                        continue;
                    }
                    $json = json_decode($payloadStr, true);
                    $text = strval($json['choices'][0]['delta']['content'] ?? '');
                    if ($text !== '') {
                        $chunks[] = $text;
                        if (!is_null($callable)) {
                            $callable($text, is_array($json) ? $json : []);
                        }
                    }
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            $this->logRequest($conf['driver'], 'stream_error', ['model' => $payload['model'], 'error' => $err]);
            throw new AiException('AI 流式请求失败：' . $err);
        }

        $fullReply = '';
        foreach ($chunks as $chunk) {
            $fullReply .= $chunk;
            yield $chunk;
        }

        $this->logRequest($conf['driver'], 'stream', ['model' => $payload['model'], 'tokens' => $this->estimateTokens($fullReply), 'success' => true]);
    }

    /**
     * 获取文本向量嵌入 (Embeddings).
     * @param string|array $input 文本或文本列表
     * @param array $options 额外参数（如 model 等）
     * @return array [model => string, vectors => array, embedding => array, tokens => int]
     * @throws AiException
     */
    public function embeddings(string|array $input, array $options = []): array
    {
        $conf = $this->getDriverConfig();
        if ($conf['key'] === '') {
            throw new AiException("请先配置 {$conf['driver']} AI 引擎的 API Key！");
        }

        $model = $options['model'] ?? Ai::config("{$conf['driver']}_embedding_model") ?: $conf['embedding'];
        $payload = [
            'model' => $model,
            'input' => $input,
        ];

        // 优先使用 OpenAI 客户端
        if (class_exists(\OpenAI::class)) {
            try {
                $client = $this->createClient($conf['endpoint'], $conf['key']);
                $response = $client->embeddings()->create($payload);
                $vectors = [];
                foreach ($response->embeddings as $item) {
                    $vectors[] = $item->embedding;
                }
                $tokens = $response->usage->totalTokens ?? $this->estimateTokens($input);
                $this->logRequest($conf['driver'], 'embeddings', ['model' => $model, 'tokens' => $tokens, 'success' => true]);
                return [
                    'model'     => $model,
                    'vectors'   => $vectors,
                    'embedding' => $vectors[0] ?? [],
                    'tokens'    => $tokens,
                ];
            } catch (\Throwable $e) {
                $this->logRequest($conf['driver'], 'embeddings_error', ['model' => $model, 'error' => $e->getMessage()]);
                throw new AiException('向量嵌入请求失败：' . $e->getMessage());
            }
        }

        // cURL HTTP POST 降级
        $base = preg_replace('#/(?:chat/completions|v1/chat/completions)/?$#', '', $conf['endpoint']);
        $url = rtrim($base, '/') . (str_contains($base, '/v1') ? '/embeddings' : '/v1/embeddings');

        $headers = [
            'Authorization: Bearer ' . $conf['key'],
            'Content-Type: application/json; charset=utf-8',
        ];
        $result = HttpExtend::post($url, json_encode($payload, JSON_UNESCAPED_UNICODE), ['headers' => $headers, 'timeout' => 30]);
        $json = json_decode(strval($result), true);

        if (isset($json['data']) && is_array($json['data'])) {
            $vectors = [];
            foreach ($json['data'] as $item) {
                if (isset($item['embedding'])) {
                    $vectors[] = $item['embedding'];
                }
            }
            $tokens = intval($json['usage']['total_tokens'] ?? $this->estimateTokens($input));
            $this->logRequest($conf['driver'], 'embeddings', ['model' => $model, 'tokens' => $tokens, 'success' => true]);
            return [
                'model'     => $model,
                'vectors'   => $vectors,
                'embedding' => $vectors[0] ?? [],
                'tokens'    => $tokens,
            ];
        }

        $err = strval($json['error']['message'] ?? '向量嵌入响应数据异常');
        $this->logRequest($conf['driver'], 'embeddings_error', ['model' => $model, 'error' => $err]);
        throw new AiException('向量嵌入请求失败：' . $err);
    }

    /**
     * 多模态图像理解与识别 (Vision).
     * @param string $prompt 提示词/任务指令
     * @param string|array $images 图像 URL、Base64 或本地文件路径（单个或数组）
     * @param array $options 额外请求参数
     * @return string 模型分析与回复内容
     * @throws AiException
     */
    public function vision(string $prompt, string|array $images, array $options = []): string
    {
        $conf = $this->getDriverConfig();
        $images = is_array($images) ? $images : [$images];
        $content = [
            ['type' => 'text', 'text' => $prompt],
        ];

        foreach ($images as $img) {
            $img = trim(strval($img));
            if ($img === '') {
                continue;
            }
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, 'data:image/')) {
                $content[] = [
                    'type'      => 'image_url',
                    'image_url' => ['url' => $img],
                ];
            } elseif (is_file($img)) {
                $mime = mime_content_type($img) ?: 'image/jpeg';
                $base64 = base64_encode(file_get_contents($img));
                $content[] = [
                    'type'      => 'image_url',
                    'image_url' => ['url' => "data:{$mime};base64,{$base64}"],
                ];
            }
        }

        $messages = [
            ['role' => 'user', 'content' => $content],
        ];

        $options['model'] = $options['model'] ?? Ai::config("{$conf['driver']}_vision_model") ?: $conf['vision'];
        return $this->chat($messages, $options);
    }

    /**
     * 估算消息 Token 消耗数量.
     * @param string|array $messages
     * @return int
     */
    public function estimateTokens(string|array $messages): int
    {
        $text = '';
        if (is_string($messages)) {
            $text = $messages;
        } elseif (is_array($messages)) {
            foreach ($messages as $m) {
                if (is_array($m)) {
                    if (is_string($m['content'] ?? '')) {
                        $text .= $m['content'];
                    } elseif (is_array($m['content'] ?? [])) {
                        foreach ($m['content'] as $part) {
                            $text .= strval($part['text'] ?? '');
                        }
                    }
                } elseif (is_string($m)) {
                    $text .= $m;
                }
            }
        }
        $len = mb_strlen($text, 'UTF-8');
        if ($len === 0) {
            return 0;
        }
        preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $text, $matches);
        $cnCount = count($matches[0]);
        $otherCount = $len - $cnCount;
        return (int)ceil($cnCount * 1.5 + $otherCount * 0.8);
    }

    /**
     * 估算模型调用费用 (单位：元 CNY).
     * @param string $model 模型名称
     * @param int $promptTokens 输入 token
     * @param int $completionTokens 输出 token
     * @return array{currency: string, model: string, prompt_tokens: int, completion_tokens: int, total_tokens: int, prompt_cost: float, completion_cost: float, total_cost: float}
     */
    public function estimateCost(string $model, int $promptTokens, int $completionTokens): array
    {
        // 典型大模型每 1000 tokens 费率表（单位：元 CNY）
        $pricing = [
            'deepseek-chat'              => ['prompt' => 0.001, 'completion' => 0.002],
            'deepseek-reasoner'          => ['prompt' => 0.004, 'completion' => 0.016],
            'gpt-4o'                     => ['prompt' => 0.018, 'completion' => 0.072],
            'gpt-4o-mini'                => ['prompt' => 0.001, 'completion' => 0.004],
            'claude-3-5-sonnet-20241022' => ['prompt' => 0.021, 'completion' => 0.105],
            'gemini-1.5-flash'           => ['prompt' => 0.0005, 'completion' => 0.002],
            'gemini-1.5-pro'             => ['prompt' => 0.009, 'completion' => 0.036],
            'qwen-turbo'                 => ['prompt' => 0.002, 'completion' => 0.006],
            'qwen-plus'                  => ['prompt' => 0.004, 'completion' => 0.012],
            'glm-4-flash'                => ['prompt' => 0.0001, 'completion' => 0.0001],
            'moonshot-v1-8k'             => ['prompt' => 0.012, 'completion' => 0.012],
            'doubao-lite-4k'             => ['prompt' => 0.0003, 'completion' => 0.0006],
        ];

        $rate = $pricing[strtolower($model)] ?? ['prompt' => 0.005, 'completion' => 0.015];
        $promptCost = round(($promptTokens / 1000) * $rate['prompt'], 6);
        $completionCost = round(($completionTokens / 1000) * $rate['completion'], 6);
        $totalCost = round($promptCost + $completionCost, 6);

        return [
            'currency'          => 'CNY',
            'model'             => $model,
            'prompt_tokens'     => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens'      => $promptTokens + $completionTokens,
            'prompt_cost'       => $promptCost,
            'completion_cost'   => $completionCost,
            'total_cost'        => $totalCost,
        ];
    }
}