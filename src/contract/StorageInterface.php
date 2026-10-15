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

/**
 * 对象存储标准接口.
 * 建议服务商：阿里云 OSS、腾讯云 COS、七牛云、又拍云、MinIO、AWS S3.
 * @class StorageInterface
 */
interface StorageInterface
{
    /**
     * 上传文件内容并返回 URL.
     * @param string $path 文件路径
     * @param mixed $content 文件内容
     * @return string 返回 URL
     */
    public function upload(string $path, mixed $content): string;

    /**
     * 下载/读取文件内容.
     * @param string $path 文件路径
     * @return string
     */
    public function download(string $path): string;

    /**
     * 删除存储文件.
     * @param string $path 文件路径
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * 检查文件是否存在.
     * @param string $path 文件路径
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * 获取文件访问 URL.
     * @param string $path 文件路径
     * @param int|bool $expire 过期时间（秒）或安全模式
     * @return string
     */
    public function url(string $path, int|bool $expire = 0): string;
}
