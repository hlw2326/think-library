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
 * 系统数据模型.
 *
 * @property int $id
 * @property string $create_time 创建时间
 * @property string $name 配置名
 * @property string $update_time 更新时间
 * @property string $value 配置值
 * @class SystemData
 */
class SystemData extends Model {}
