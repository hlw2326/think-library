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

use think\admin\exception\StorageException;
use think\admin\facade\Library;
use think\App;
use think\Container;

/**
 * 文件存储公共属性与日志记录.
 * @class StorageUsageTrait
 */
trait StorageUsageTrait
{
    /**
     * @var App
     */
    protected $app;

    /**
     * 链接类型.
     * @var string
     */
    protected $link;

    /**
     * 链接前缀
     * @var string
     */
    protected $domain;

    /**
     * 存储器构造方法.
     * @throws StorageException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->link = sysconf('storage.link_type|raw');
        $this->init();
    }

    /**
     * 记录存储驱动请求日志.
     * @param string $driver 驱动名称
     * @param string $action 操作动作
     * @param array $data 数据内容
     */
    protected function logRequest(string $driver, string $action, array $data = []): void
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (function_exists('sysoplog')) {
            sysoplog("storage.{$action}", "[{$driver}] {$content}");
        }
        if (isset(Library::$sapp->log)) {
            Library::$sapp->log->record("[storage.{$driver}.{$action}] {$content}", 'info');
        }
    }

    /**
     * 重构后兼容处理.
     * @return array|string
     * @throws StorageException
     */
    public function __call(string $method, array $arguments)
    {
        if (strtolower($method) === 'builduploadtoken') {
            if (method_exists($this, 'token')) {
                return $this->token(...$arguments);
            }
        }
        // 调用方法异常处理
        $class = class_basename(static::class);
        throw new StorageException("method not exists: {$class}->{$method}()");
    }

    /**
     * 获取对象实例.
     * @return static
     */
    public static function instance()
    {
        /* @var \think\admin\contract\StorageInterface */
        return Container::getInstance()->make(static::class);
    }

    /**
     * 上传文件内容并返回 URL (若 path 为空则返回上传端点).
     * @param string $path 文件路径
     * @param mixed $content 文件内容
     * @return string
     */
    public function upload(string $path = '', mixed $content = null): string
    {
        if ($path !== '') {
            $info = $this->set($path, is_string($content) ? $content : strval($content));
            $driver = strtolower(str_replace('Storage', '', class_basename(static::class)));
            $this->logRequest($driver, 'upload', ['path' => $path]);
            return strval($info['url'] ?? $this->url($path));
        }
        if (method_exists($this, 'endpoint')) {
            return $this->endpoint();
        }
        return '';
    }

    /**
     * 下载/读取文件内容.
     * @param string $path 文件路径
     */
    public function download(string $path): string
    {
        return $this->get($path);
    }

    /**
     * 删除文件.
     * @param string $path 文件路径
     */
    public function delete(string $path): bool
    {
        return $this->del($path);
    }

    /**
     * 判断文件是否存在.
     * @param string $path 文件路径
     */
    public function exists(string $path): bool
    {
        return $this->has($path);
    }

    /**
     * 自定义初始化方法.
     */
    protected function init() {}

    /**
     * 获取下载链接后缀
     * @param null|string $attname 下载名称
     * @param null|string $filename 文件名称
     */
    protected function getSuffix(?string $attname = null, ?string $filename = null): string
    {
        [$class, $suffix] = [class_basename(get_class($this)), ''];
        if (is_string($filename) && stripos($this->link, 'compress') !== false) {
            $compress = [
                'LocalStorage' => '',
                'QiniuStorage' => '?imageslim',
                'UpyunStorage' => '!/format/webp',
                'TxcosStorage' => '?imageMogr2/format/webp',
                'AliossStorage' => '?x-oss-process=image/format,webp',
            ];
            $extens = strtolower(pathinfo($this->delSuffix($filename), PATHINFO_EXTENSION));
            $suffix = in_array($extens, ['png', 'jpg', 'jpeg']) ? ($compress[$class] ?? '') : '';
        }
        if (is_string($attname) && strlen($attname) > 0 && stripos($this->link, 'full') !== false) {
            if ($class === 'UpyunStorage') {
                $suffix .= ($suffix ? '&' : '?') . '_upd=' . urlencode($attname);
            } else {
                $suffix .= ($suffix ? '&' : '?') . 'attname=' . urlencode($attname);
            }
        }
        return $suffix;
    }

    /**
     * 获取文件基础名称.
     * @param string $name 文件名称
     */
    protected function delSuffix(string $name): string
    {
        if (strpos($name, '?') !== false) {
            return strstr($name, '?', true);
        }
        if (stripos($name, '!') !== false) {
            return strstr($name, '!', true);
        }
        return $name;
    }
}
