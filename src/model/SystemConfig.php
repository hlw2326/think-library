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

namespace think\admin\model;

use think\admin\base\Model;

/**
 * 系统配置模型.
 *
 * @property int $id
 * @property string $name 配置名称
 * @property string $type 配置分类
 * @property string $value 配置内容
 * @class SystemConfig
 */
class SystemConfig extends Model
{
    protected $updateTime = false;

    protected $createTime = false;
}
