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

use think\admin\exception\QrcodeException;

/**
 * 小程序码生成驱动标准接口.
 * @class QrcodeInterface
 */
interface QrcodeInterface
{
    /**
     * 生成小程序码.
     * @param string $page 小程序页面路径，不带前导斜杠，不带参数
     * @param array $query 页面参数，由驱动按平台规则编码
     * @param array $options width、mode 及各平台专有参数
     * @return array{code: int, message: string, content: string, url: string, mime: string, raw: array}
     * @throws QrcodeException
     */
    public function create(string $page, array $query = [], array $options = []): array;

    /**
     * 检查当前驱动配置是否完整有效.
     */
    public function ready(): bool;
}
