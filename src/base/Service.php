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

namespace think\admin\base;

use think\App;
use think\Container;

/**
 * 自定义服务基类.
 * @class Service
 */
abstract class Service
{
    /**
     * 应用实例.
     * @var App
     */
    protected $app;

    /**
     * Constructor.
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    /**
     * 静态实例对象
     * @param array $var 实例参数
     * @param bool $new 创建新实例
     * @return mixed|static
     */
    public static function instance(array $var = [], bool $new = false)
    {
        return Container::getInstance()->make(static::class, $var, $new);
    }

    /**
     * 初始化服务
     */
    protected function initialize() {}
}
