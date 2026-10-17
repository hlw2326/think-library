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

namespace think\admin\install;

/**
 * 加密环境文件安装事件.
 * @class CryptInstall
 */
class CryptInstall
{
    /**
     * 首次安装时生成独立加密环境配置.
     * 已存在的配置文件绝不覆盖，防止更新或重复安装造成密钥轮换。
     */
    public static function onInstall(): void
    {
        $file = getcwd() . DIRECTORY_SEPARATOR . '.env.crypt';
        if (is_file($file)) {
            return;
        }

        $keys = static::generateRsaKeys();
        $data = [
            '# 此文件由 think-library 安装器首次生成，包含敏感密钥，禁止提交版本库。',
            '# RSA PEM 已转换为单行 Base64，config/crypt.php 会在运行时自动解码。',
            'CRYPT_TYPE=aes',
            'CRYPT_AES_KEY=' . base64_encode(random_bytes(32)),
            'CRYPT_AES_CIPHER=aes-256-gcm',
            'CRYPT_SM4_KEY=' . base64_encode(random_bytes(32)),
            'CRYPT_HMAC_KEY=' . base64_encode(random_bytes(32)),
            'CRYPT_HMAC_ALGO=sha256',
            'CRYPT_RSA_PUBLIC_KEY_B64=' . base64_encode($keys['public_key']),
            'CRYPT_RSA_PRIVATE_KEY_B64=' . base64_encode($keys['private_key']),
            'CRYPT_RSA_PASSPHRASE=',
            'CRYPT_OPENSSL_CNF=',
            'CRYPT_API_SIGN_SECRET=' . bin2hex(random_bytes(32)),
            'CRYPT_API_TIME_TOLERANCE=60000',
            'CRYPT_API_NONCE_EXPIRE=120',
            '',
        ];

        if (file_put_contents($file, implode(PHP_EOL, $data), LOCK_EX) === false) {
            throw new \RuntimeException("无法创建加密环境配置文件：{$file}");
        }
    }

    /**
     * 生成 2048 位 RSA 密钥对.
     * @return array{public_key:string,private_key:string}
     */
    private static function generateRsaKeys(): array
    {
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $temporary = static::createOpenSslConfig();
        $resource = false;
        $active = $options;
        foreach (array_merge(static::opensslConfigs(), [$temporary, '']) as $file) {
            $config = $options;
            if ($file !== '') {
                $config['config'] = $file;
            }
            $resource = @openssl_pkey_new($config);
            if ($resource !== false) {
                $active = $config;
                break;
            }
        }
        if ($resource === false) {
            if ($temporary !== '') {
                @unlink($temporary);
            }
            throw new \RuntimeException('生成 RSA 密钥对失败：' . (openssl_error_string() ?: 'OpenSSL 不可用'));
        }

        $private = '';
        $exported = openssl_pkey_export($resource, $private, null, $active);
        if ($temporary !== '') {
            @unlink($temporary);
        }
        if (!$exported) {
            throw new \RuntimeException('导出 RSA 私钥失败：' . (openssl_error_string() ?: '未知错误'));
        }

        $details = openssl_pkey_get_details($resource);
        $public = (string) ($details['key'] ?? '');
        if ($public === '') {
            throw new \RuntimeException('导出 RSA 公钥失败。');
        }

        return ['public_key' => $public, 'private_key' => $private];
    }

    /**
     * 获取 OpenSSL 配置文件候选路径，不绑定任何操作系统固定目录。
     * @return string[]
     */
    private static function opensslConfigs(): array
    {
        $configs = [strval(getenv('OPENSSL_CONF') ?: '')];
        ob_start();
        phpinfo(INFO_MODULES);
        $info = strip_tags(strval(ob_get_clean()));
        if (preg_match('/Openssl default config\s*=>\s*([^\r\n<]+)/i', $info, $match)) {
            $configs[] = trim($match[1]);
        }
        return array_values(array_unique(array_filter($configs, 'is_file')));
    }

    /**
     * 生成 OpenSSL 3 默认 Provider 的临时配置，供无系统配置时回退使用。
     */
    private static function createOpenSslConfig(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'openssl-');
        if ($file === false) {
            return '';
        }
        $config = "openssl_conf = openssl_init\n\n[openssl_init]\nproviders = provider_sect\n\n[provider_sect]\ndefault = default_sect\n\n[default_sect]\nactivate = 1\n";
        if (file_put_contents($file, $config, LOCK_EX) === false) {
            @unlink($file);
            return '';
        }
        return $file;
    }
}