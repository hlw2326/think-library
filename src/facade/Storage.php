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

namespace think\admin\facade;

use think\admin\contract\StorageInterface;
use think\admin\exception\StorageException;
use think\admin\facade\Library;
use think\admin\storage\LocalStorage;
use think\Container;

/**
 * 文件存储引擎管理.
 * @class Storage
 * @method static string upload(string $path, mixed $content) 上传文件内容并返回 URL
 * @method static string download(string $path) 读取/下载文件内容
 * @method static boolean delete(string $path) 删除存储文件
 * @method static boolean exists(string $path) 检查文件是否存在
 * @method static array info($name, $safe = false, $attname = null) 文件存储信息
 * @method static array set($name, $file, $safe = false, $attname = null) 储存文件
 * @method static string url($name, $safe = false, $attname = null) 获取文件链接
 * @method static string get($name, $safe = false) 读取文件内容
 * @method static string path($name, $safe = false) 文件存储路径
 * @method static boolean isPathSafe($name) 检查文件路径
 * @method static boolean del($name, $safe = false) 删除存储文件
 * @method static boolean has($name, $safe = false) 检查是否存在
 * @method static string upload() 获取上传地址
 */
abstract class Storage
{
    /**
     * 获取上传地址或上传文件内容并返回 URL.
     * @param ?string $path 文件路径（为 null 时返回上传端点）
     * @param mixed $content 文件内容
     */
    public static function upload(?string $path = null, mixed $content = null): string
    {
        return static::instance()->upload(strval($path ?? ''), $content);
    }

    /**
     * 静态访问启用.
     * @param string $method 方法名称
     * @param array $arguments 调用参数
     * @return mixed
     * @throws StorageException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists($storage = static::instance(), $method)) {
            return call_user_func_array([$storage, $method], $arguments);
        }
        throw new StorageException('method not exists: ' . get_class($storage) . "->{$method}()");
    }

    /**
     * 实例化存储操作对象
     * @param ?string $name 驱动名称
     * @param ?string $class 驱动类名
     * @throws StorageException
     */
    public static function instance(?string $name = null, ?string $class = null): StorageInterface
    {
        if (is_null($class)) {
            $type = ucfirst(strtolower($name ?: static::config('type')));
            $class = "think\\admin\\storage\\{$type}Storage";
        }
        if (class_exists($class)) {
            return Container::getInstance()->make($class);
        }
        throw new StorageException("Storage driver [{$class}] does not exist.");
    }

    /**
     * 读取存储配置（支持环境变量回退 STORAGE_{NAME}）.
     * @param string $name
     * @return string
     */
    public static function config(string $name): string
    {
        $val = strval(sysconf("storage.{$name}|raw") ?? '');
        if ($val === '') {
            $env = 'STORAGE_' . strtoupper(str_replace('.', '_', $name));
            $val = strval(Library::$sapp->env->get($env) ?? getenv($env) ?? '');
        }
        return $val;
    }

    /**
     * 获取存储前缀路径.
     * @param ?string $prefix 存储前缀
     */
    public static function prefix(?string $prefix = null): string
    {
        $prefix ??= static::config('prefix');
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', (string)$prefix)) as $part) {
            $part = trim($part, " .\t\n\r\0\x0B\\/");
            if ($part !== '') {
                $parts[] = $part;
            }
        }
        return join('/', $parts);
    }

    /**
     * 获取文件相对名称.
     * @param string $url 文件访问链接
     * @param string $ext 文件后缀名称
     * @param string $pre 文件存储前缀
     * @param string $fun 名称规则方法
     */
    public static function name(string $url, string $ext = '', string $pre = '', string $fun = 'md5'): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $url;
        [$hah, $ext] = [$fun($url), strtolower(trim($ext !== '' ? $ext : pathinfo($path, PATHINFO_EXTENSION), '.\/'))];
        $ext = $ext !== '' ? $ext : 'tmp';

        $parts = [];
        foreach ([static::prefix(), $pre] as $item) {
            if ($item !== '') {
                foreach (explode('/', str_replace('\\', '/', (string)$item)) as $part) {
                    $part = trim($part, " .\t\n\r\0\x0B\\/");
                    if ($part !== '') {
                        $parts[] = $part;
                    }
                }
            }
        }
        $parts[] = $ext;
        $parts[] = substr($hah, 0, 2);
        $parts[] = substr($hah, 2, 30);
        return join('/', $parts) . '.' . $ext;
    }

    /**
     * 检查文件相对名称是否安全.
     */
    public static function isPathSafe(string $name): bool
    {
        if ($name === '' || strlen($name) > 1024) {
            return false;
        }
        if (preg_match('/[\x00-\x1F\x7F%?#\\\]/', $name)) {
            return false;
        }
        return preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.[A-Za-z0-9]+$#D', $name) === 1;
    }

    /**
     * 下载文件到本地.
     * @param string $url 文件URL地址
     * @param bool $force 是否强制下载
     * @param int $expire 文件保留时间
     */
    public static function down(string $url, bool $force = false, int $expire = 0): array
    {
        try {
            $local = LocalStorage::instance();
            $filename = static::name($url, '', 'down/');
            if (empty($force) && $local->has($filename)) {
                if ($expire < 1 || filemtime($local->path($filename)) + $expire > time()) {
                    return $local->info($filename);
                }
            }
            return $local->set($filename, static::curlGet($url));
        } catch (\Exception $exception) {
            return ['url' => $url, 'hash' => md5($url), 'key' => $url, 'file' => $url];
        }
    }

    /**
     * 获取后缀类型.
     * @param array|string $exts 文件后缀
     * @param array $mime 文件信息
     */
    public static function mime($exts, array $mime = []): string
    {
        $mimes = static::mimes();
        foreach (is_string($exts) ? explode(',', $exts) : $exts as $ext) {
            $mime[] = $mimes[strtolower($ext)] ?? 'application/octet-stream';
        }
        return join(',', array_unique($mime));
    }

    /**
     * 获取所有类型.
     */
    public static function mimes(): array
    {
        static $mimes = [];
        if (count($mimes) > 0) {
            return $mimes;
        }
        return $mimes = include __DIR__ . '/storage/bin/mimes.php';
    }

    /**
     * 获取存储类型.
     */
    public static function types(): array
    {
        return [
            'local'  => lang('本地服务器存储'),
            'alist'  => lang('自建Alist存储'),
            'qiniu'  => lang('七牛云对象存储'),
            'upyun'  => lang('又拍云USS存储'),
            'txcos'  => lang('腾讯云COS存储'),
            'alioss' => lang('阿里云OSS存储'),
            'minio'  => lang('MinIO 对象存储'),
            'awss3'  => lang('AWS S3 对象存储'),
        ];
    }

    /**
     * 使用CURL读取网络资源.
     * @param string $url 资源地址
     */
    public static function curlGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $body = curl_exec($ch) ?: '';
        curl_close($ch);
        return $body;
    }

    /**
     * 图片数据存储.
     * @param string $base64 图片内容
     * @param string $prefix 保存前缀
     * @param bool $safemode 安全模式
     * @return array [ url => URL ]
     * @throws Exception
     */
    public static function saveImage(string $base64, string $prefix = 'image', bool $safemode = false): array
    {
        if (preg_match('|^data:image/(.*?);base64,|i', $base64)) {
            [$ext, $img] = explode('|||', preg_replace('|^data:image/(.*?);base64,|i', '$1|||', $base64));
            $name = static::name($img, $ext, $prefix);
            if (empty($ext) || !in_array(strtolower($ext), ['gif', 'png', 'jpg', 'jpeg'])) {
                throw new Exception('内容格式异常！');
            }
            if ($safemode) {
                return LocalStorage::instance()->set($name, base64_decode($img), true);
            }
            return static::instance()->set($name, base64_decode($img));
        }
        return ['url' => $base64];
    }
}
