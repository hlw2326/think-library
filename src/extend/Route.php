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

namespace think\admin\extend;

use think\Route as ThinkRoute;

/**
 * 自定义路由对象
 * @class Route
 */
class Route extends ThinkRoute
{
    /**
     * 重载路由配置.
     * @return $this
     */
    public function reload(): Route
    {
        $this->config = array_merge($this->config, $this->app->config->get('route'));
        return $this;
    }
}
