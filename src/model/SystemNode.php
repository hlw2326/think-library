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
 * 授权节点模型.
 *
 * @property int $auth 角色
 * @property int $id
 * @property string $node 节点
 * @class SystemNode
 * @mixin \think\db\Query
 */
class SystemNode extends Model
{
    protected $updateTime = false;

    protected $createTime = false;

    /**
     * 绑定模型名称.
     * @var string
     */
    protected $name = 'SystemAuthNode';
}
