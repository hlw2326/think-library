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

namespace think\admin\database\migration;

use Phinx\Db\Adapter\MysqlAdapter;

/**
 * Phinx 3.0 的 MySQL 列定义兼容层。
 * @internal
 */
class PhinxMysqlColumns extends MysqlAdapter
{
    use PhinxLegacyColumns;
}
