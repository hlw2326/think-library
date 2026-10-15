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

namespace think\admin\command;

use think\admin\facade\Command;
use think\admin\facade\Crypt as CryptEngine;
use think\admin\service\ApiCryptService;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * API 加解密与密钥运维管理指令
 * @class Crypt
 */
class Crypt extends Command
{
    /**
     * 指令配置
     */
    public function configure()
    {
        $this->setName('xadmin:crypt');
        $this->addArgument('action', Argument::OPTIONAL, '操作类型 (info|keys|clean)', 'info');
        $this->addOption('bits', 'b', Option::VALUE_OPTIONAL, 'RSA 密钥位数 (1024/2048/4096)', 2048);
        $this->addOption('force', 'f', Option::VALUE_NONE, '强制重新生成并覆盖现有密钥');
        $this->setDescription('Cryptographic engine inspection and keypair management for HlwAdmin');
    }

    /**
     * 初始化环境 (加解密运维指令无需连接数据库队列)
     */
    protected function initialize(Input $input, Output $output): Command
    {
        return $this;
    }

    /**
     * 任务执行入口
     */
    protected function execute(Input $input, Output $output): void
    {
        $action = (string) $input->getArgument('action');
        if (method_exists($this, $method = "_{$action}")) {
            $this->{$method}();
        } else {
            $this->output->error("未知操作 [{$action}]，仅支持: info, keys, clean");
        }
    }

    /**
     * 检查当前环境加密驱动与配置状态
     */
    protected function _info(): void
    {
        $this->output->writeln('<info>==================================================</info>');
        $this->output->writeln('<info>        HlwAdmin 密码学与加解密引擎状态诊断        </info>');
        $this->output->writeln('<info>==================================================</info>');

        // 1. OpenSSL 扩展状态
        $sslOpen = extension_loaded('openssl');
        $sslVer = $sslOpen ? OPENSSL_VERSION_TEXT : '未安装/未加载';
        $this->output->writeln("OpenSSL 扩展: " . ($sslOpen ? "<info>已启用</info>" : "<error>未加载</error>"));
        $this->output->writeln("OpenSSL 版本: <comment>{$sslVer}</comment>");

        // 2. 支持的加密驱动与算法
        $this->output->writeln("\n<comment>[支持的加密引擎驱动]</comment>");
        foreach (CryptEngine::types() as $name => $desc) {
            $this->output->writeln("  - <info>{$name}</info>: {$desc}");
        }

        // 3. RSA 密钥对证书状态
        $dir = $this->app->getRootPath() . 'safefile' . DIRECTORY_SEPARATOR . 'certs';
        $priv = $dir . DIRECTORY_SEPARATOR . 'api_rsa_private.key';
        $pub = $dir . DIRECTORY_SEPARATOR . 'api_rsa_public.key';

        $this->output->writeln("\n<comment>[RSA 证书存储状态]</comment>");
        $this->output->writeln("  存储目录: {$dir}");

        if (is_file($priv) && is_file($pub)) {
            $pubContent = (string) file_get_contents($pub);
            $res = openssl_pkey_get_public($pubContent);
            $bits = 0;
            if ($res !== false) {
                $details = openssl_pkey_get_details($res);
                $bits = (int) ($details['bits'] ?? 0);
            }
            $hash = strtoupper(substr(hash('sha256', $pubContent), 0, 32));

            $this->output->writeln("  私钥状态: <info>存在</info> (" . filesize($priv) . " bytes)");
            $this->output->writeln("  公钥状态: <info>存在</info> (" . filesize($pub) . " bytes, {$bits} 位)");
            $this->output->writeln("  公钥指纹: <comment>SHA256:{$hash}</comment>");
            $this->output->writeln("  更新时间: " . date('Y-m-d H:i:s', (int) filemtime($pub)));
        } else {
            $this->output->writeln("  证书状态: <error>未生成或不完整</error>");
            $this->output->writeln("  提示: 可执行 <info>php think xadmin:crypt keys</info> 立即自动生成 2048 位密钥对。");
        }

        $this->output->writeln('');
    }

    /**
     * 生成或轮换 RSA 密钥对
     */
    protected function _keys(): void
    {
        $bits = (int) $this->input->getOption('bits');
        if ($bits <= 0) {
            $bits = 2048;
        }

        $force = (bool) $this->input->getOption('force');

        $dir = $this->app->getRootPath() . 'safefile' . DIRECTORY_SEPARATOR . 'certs';
        $priv = $dir . DIRECTORY_SEPARATOR . 'api_rsa_private.key';
        $pub = $dir . DIRECTORY_SEPARATOR . 'api_rsa_public.key';

        if (is_file($priv) && is_file($pub) && !$force) {
            $this->output->comment("RSA 密钥对已存在于 safefile/certs/ 中。");
            $this->output->writeln("如需强制重新生成并覆盖，请附加 <info>--force</info> 参数，例如: <comment>php think xadmin:crypt keys --force</comment>");
            $this->output->writeln("\n<info>当前公钥内容 (可直接复制配置到前端):</info>");
            $this->output->writeln((string) file_get_contents($pub));
            return;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->output->writeln("正在生成 {$bits} 位 RSA 密钥对，请稍候...");
        $cnf = $this->app->config->get('api.openssl_cnf');
        $keys = CryptEngine::rsa()->generate($bits, is_string($cnf) ? $cnf : null);

        file_put_contents($priv, $keys['private_key']);
        file_put_contents($pub, $keys['public_key']);

        $hash = strtoupper(substr(hash('sha256', $keys['public_key']), 0, 32));

        $this->output->writeln("<info>RSA 密钥对生成成功！</info>");
        $this->output->writeln("  - 私钥路径: <comment>{$priv}</comment>");
        $this->output->writeln("  - 公钥路径: <comment>{$pub}</comment>");
        $this->output->writeln("  - 公钥指纹: <comment>SHA256:{$hash}</comment>");
        $this->output->writeln("\n<info>新生成的 RSA 公钥内容 (供客户端下载/内置):</info>");
        $this->output->writeln($keys['public_key']);
    }

    /**
     * 清理当前生成的证书
     */
    protected function _clean(): void
    {
        $force = (bool) $this->input->getOption('force');
        if (!$force) {
            $this->output->error("安全警告: 清理密钥将导致已有客户端无法解密！请使用 --force 参数确认执行: php think xadmin:crypt clean --force");
            return;
        }

        $dir = $this->app->getRootPath() . 'safefile' . DIRECTORY_SEPARATOR . 'certs';
        $priv = $dir . DIRECTORY_SEPARATOR . 'api_rsa_private.key';
        $pub = $dir . DIRECTORY_SEPARATOR . 'api_rsa_public.key';

        if (is_file($priv)) {
            unlink($priv);
        }
        if (is_file($pub)) {
            unlink($pub);
        }

        $this->output->writeln("<info>RSA 密钥对证书已安全清理！</info>");
    }
}
