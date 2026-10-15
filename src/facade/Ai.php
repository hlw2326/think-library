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

namespace think\admin\facade;

use think\admin\contract\AiInterface;
use think\admin\exception\AiException;
use think\admin\facade\Library;
use think\Container;

/**
 * AI 大模型服务引擎管理，配置全部来自 sysconf 的 ai 分组.
 * @class Ai
 * @method static string chat(string|array $messages, array $options = []) 通过默认大模型发送对话
 * @method static \Generator stream(string|array $messages, ?callable $callable = null, array $options = []) 流式输出对话补全
 * @method static array embeddings(string|array $input, array $options = []) 获取文本向量嵌入
 * @method static string vision(string $prompt, string|array $images, array $options = []) 多模态图像理解与识别
 * @method static int estimateTokens(string|array $messages) 估算消息 Token 数量
 * @method static array estimateCost(string $model, int $promptTokens, int $completionTokens) 估算调用成本
 * @method static \OpenAI\Client client() 获取标准 OpenAI 客户端实例
 */
abstract class Ai
{
    /**
     * 静态访问驱动方法，未指定服务商时使用 ai.type.
     * @throws AiException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($ai = static::instance(), $method)) {
            return call_user_func_array([$ai, $method], $arguments);
        }
        throw new AiException('method not exists: ' . get_class($ai) . "->{$method}()");
    }

    /**
     * 实例化 AI 大模型驱动.
     * @param ?string $name 驱动名称
     * @throws AiException
     */
    public static function instance(?string $name = null): AiInterface
    {
        $name = strtolower(strval($name ?? static::config('type')));
        if (!isset(static::types()[$name])) {
            throw new AiException("AI driver [{$name}] does not exist.");
        }
        return Container::getInstance()->make('think\\admin\\ai\\' . ucfirst($name) . 'Ai');
    }

    /**
     * 读取 AI 配置（支持环境变量回退 AI_{NAME}）.
     * @param string $name 配置名称，对应 sysconf ai.{name}
     */
    public static function config(string $name): string
    {
        $val = strval(sysconf("ai.{$name}|raw") ?? '');
        if ($val === '') {
            $env = 'AI_' . strtoupper(str_replace('.', '_', $name));
            $val = strval(Library::$sapp->env->get($env) ?? getenv($env) ?? '');
        }
        return $val;
    }

    /**
     * AI 服务商配置是否完整.
     */
    public static function ready(?string $name = null): bool
    {
        $type = strtolower(strval($name ?? static::config('type')));
        if ($type === '') {
            return false;
        }
        $key = static::config("{$type}_key");
        return $key !== '';
    }

    /**
     * 获取支持的 AI 大模型服务商.
     */
    public static function types(): array
    {
        return [
            'deepseek' => lang('DeepSeek 深度求索'),
            'openai'   => lang('OpenAI ChatGPT'),
            'claude'   => lang('Anthropic Claude'),
            'gemini'   => lang('Google Gemini'),
            'qwen'     => lang('阿里通义千问'),
            'zhipu'    => lang('智谱清言 GLM'),
            'moonshot' => lang('月之暗面 Kimi'),
            'ernie'    => lang('百度文心一言'),
            'spark'    => lang('科大讯飞星火'),
            'doubao'   => lang('字节跳动豆包'),
            'hunyuan'  => lang('腾讯云混元'),
            'minimax'  => lang('MiniMax 名之梦'),
        ];
    }
}
