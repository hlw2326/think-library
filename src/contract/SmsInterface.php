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

use think\admin\exception\SmsException;

/**
 * 短信服务商驱动接口.
 * @class SmsInterface
 */
interface SmsInterface
{
    /**
     * 发送短信，失败抛出 SmsException.
     * @param string $mobile 手机号，国内号码可不带 +86
     * @param string $template 模板编号，为空时使用服务商配置的默认模板
     * @param array $params 模板变量
     * @return array 服务商原始响应
     * @throws SmsException
     */
    public function send(string $mobile, string $template = '', array $params = []): array;

    /**
     * 批量发送短信.
     * @param array $mobiles 手机号列表
     * @param string $template 模板编号
     * @param array $params 模板变量
     * @return array{total: int, success: int, failed: int, results: array}
     */
    public function sendBatch(array $mobiles, string $template = '', array $params = []): array;

    /**
     * 发送短信验证码.
     * @param string $mobile 手机号
     * @param string $code 验证码内容
     * @param int $expire 有效时间（分钟）
     * @return array
     */
    public function sendCode(string $mobile, string $code, int $expire = 5): array;

    /**
     * 查询服务商账户余额或剩余额度.
     * @return array{balance: float|int, unit: string, raw: array}
     */
    public function balance(): array;

    /**
     * 查询发送回执状态.
     * @param string $msgId 消息流水号或批次号
     * @return array{status: string, raw: array}
     */
    public function receipt(string $msgId): array;
}
