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

namespace think\admin\service;

use think\admin\facade\Library;
use think\admin\base\Service;

/**
 * 系统模块管理.
 * @class ModuleService
 */
class ModuleService extends Service
{
    /**
     * 获取版本号信息.
     */
    public static function getVersion(): string
    {
        $libray = self::getLibrarys('hlw2326/think-library');
        return trim($libray['version'] ?? 'v6.0.0', 'v');
    }

    /**
     * 获取运行参数变量.
     * @param string $field 指定字段
     */
    public static function getRunVar(string $field): string
    {
        $file = syspath('vendor/binarys.php');
        if (is_file($file) && is_array($binarys = include $file)) {
            return $binarys[$field] ?? '';
        }
        return '';
    }

    /**
     * 获取 PHP 执行路径.
     */
    public static function getPhpExec(): string
    {
        if ($phpExec = sysvar($keys = 'phpBinary')) {
            return $phpExec;
        }
        if (ProcessService::isFile($phpExec = self::getRunVar('php'))) {
            return sysvar($keys, $phpExec);
        }
        $phpExec = str_replace('/sbin/php-fpm', '/bin/php', PHP_BINARY);
        $phpExec = preg_replace('#-(cgi|fpm)(\.exe)?$#', '$2', $phpExec);
        return sysvar($keys, ProcessService::isFile($phpExec) ? $phpExec : 'php');
    }

    /**
     * 获取应用模块.
     */
    public static function getModules(array $data = []): array
    {
        $path = Library::$sapp->getBasePath();
        foreach (scandir($path) as $item) {
            if ($item[0] !== '.') {
                if (is_dir($path . $item)) {
                    $data[] = $item;
                }
            }
        }
        return $data;
    }

    /**
     * 获取本地组件.
     * @param ?string $package 指定包名
     * @param bool $force 强制刷新
     * @return null|array|string
     */
    public static function getLibrarys(?string $package = null, bool $force = false)
    {
        $plugs = sysvar($keys = 'think.admin.version');
        if ((empty($plugs) || $force) && is_file($file = syspath('vendor/versions.php'))) {
            $plugs = sysvar($keys, include $file);
        }
        return empty($package) ? $plugs : ($plugs[$package] ?? null);
    }
}
