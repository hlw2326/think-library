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

/**
 * AI 大模型驱动接口.
 * @interface AiInterface
 */
interface AiInterface
{
    /**
     * 获取驱动实例.
     */
    public static function instance(): static;

    /**
     * 发送对话补全请求.
     * @param string|array $messages 消息文本或标准消息数组
     * @param array $options 额外请求参数（如 model、temperature 等）
     * @return string 模型回复文本内容
     */
    public function chat(string|array $messages, array $options = []): string;

    /**
     * 流式对话补全（支持 SSE 回调与 Generator 迭代器）.
     * @param string|array $messages 消息文本或标准消息数组
     * @param ?callable $callable 实时分块接收回调 function(string $chunk, array $data = []): void
     * @param array $options 额外请求参数
     * @return \Generator<string>
     */
    public function stream(string|array $messages, ?callable $callable = null, array $options = []): \Generator;

    /**
     * 获取文本向量嵌入 (Embeddings).
     * @param string|array $input 文本或文本列表
     * @param array $options 额外参数（如 model 等）
     * @return array [model => string, vectors => array, embedding => array, tokens => int]
     */
    public function embeddings(string|array $input, array $options = []): array;

    /**
     * 多模态图像理解与识别 (Vision).
     * @param string $prompt 提示词/任务指令
     * @param string|array $images 图像 URL、Base64 或本地文件路径（单个或数组）
     * @param array $options 额外请求参数
     * @return string 模型分析与回复内容
     */
    public function vision(string $prompt, string|array $images, array $options = []): string;

    /**
     * 获取标准 OpenAI 客户端实例.
     */
    public function client(): \OpenAI\Client;

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool;
}
