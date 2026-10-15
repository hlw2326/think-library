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

/**
 * 自定义数据异常.
 * @class Exception
 */
class Exception extends \Exception
{
    /**
     * 异常数据对象
     * @var mixed
     */
    protected $data = [];

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param mixed $data
     */
    public function __construct($message = '', $code = 0, $data = [])
    {
        parent::__construct($message);
        $this->code = $code;
        $this->data = $data;
        $this->message = $message;
    }

    /**
     * 获取异常停止数据.
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置异常停止数据.
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
