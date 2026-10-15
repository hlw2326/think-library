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

namespace think\admin\storage;

use think\admin\base\Exception;
use think\admin\contract\StorageInterface;
use think\admin\contract\StorageUsageTrait;
use think\admin\extend\HttpExtend;
use think\admin\facade\Storage;

/**
 * MinIO 对象存储支持 (兼容 S3 协议).
 * @class MinioStorage
 */
class MinioStorage implements StorageInterface
{
    use StorageUsageTrait;

    /**
     * 服务端点.
     * @var string
     */
    private $endpoint;

    /**
     * 存储空间名称.
     * @var string
     */
    private $bucket;

    /**
     * AccessKey.
     * @var string
     */
    private $accessKey;

    /**
     * SecretKey.
     * @var string
     */
    private $secretKey;

    /**
     * 区域.
     * @var string
     */
    private $region;

    /**
     * 协议类型.
     * @var string
     */
    private $protocol;

    /**
     * 上传文件内容.
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param bool|int $safe 安全模式或超时时间
     * @param ?string $attname 下载名称
     * @return array
     */
    public function set(string $name, string $file, bool $safe = false, ?string $attname = null): array
    {
        $uri = "/{$this->bucket}/" . ltrim($name, '/');
        $headers = $this->buildSigV4Headers('PUT', $uri, $file, [
            'Content-Type' => Storage::mime(pathinfo($name, PATHINFO_EXTENSION)),
        ]);
        if (is_string($attname) && strlen($attname) > 0) {
            $headers['Content-Disposition'] = 'inline;filename=' . urlencode($attname);
        }

        $url = "{$this->protocol}://{$this->endpoint}{$uri}";
        $headerList = [];
        foreach ($headers as $k => $v) {
            $headerList[] = "{$k}: {$v}";
        }

        $result = HttpExtend::request('PUT', $url, [
            'data'         => $file,
            'headers'      => $headerList,
            'returnHeader' => true,
            'timeout'      => 60,
        ]);

        if (is_numeric(stripos($result, '200 OK')) || is_numeric(stripos($result, '204 No Content'))) {
            return ['file' => $this->path($name, $safe), 'url' => $this->url($name, $safe, $attname), 'key' => $name];
        }
        return [];
    }

    /**
     * 读取文件内容.
     * @param string $name 文件名称
     * @param bool|int $safe 安全模式或超时时间
     * @return string
     */
    public function get(string $name, bool $safe = false): string
    {
        return Storage::curlGet($this->url($name, $safe));
    }

    /**
     * 删除存储文件.
     * @param string $name 文件名称
     * @param bool|int $safe 安全模式或超时时间
     * @return bool
     */
    public function del(string $name, bool $safe = false): bool
    {
        [$file] = explode('?', $name);
        $uri = "/{$this->bucket}/" . ltrim($file, '/');
        $headers = $this->buildSigV4Headers('DELETE', $uri, '');
        $headerList = [];
        foreach ($headers as $k => $v) {
            $headerList[] = "{$k}: {$v}";
        }
        $url = "{$this->protocol}://{$this->endpoint}{$uri}";
        $result = HttpExtend::request('DELETE', $url, [
            'headers'      => $headerList,
            'returnHeader' => true,
            'timeout'      => 30,
        ]);
        return is_numeric(stripos($result, '204 No Content')) || is_numeric(stripos($result, '200 OK'));
    }

    /**
     * 判断是否存在.
     * @param string $name 文件名称
     * @param bool|int $safe 安全模式或超时时间
     * @return bool
     */
    public function has(string $name, bool $safe = false): bool
    {
        [$file] = explode('?', $name);
        $uri = "/{$this->bucket}/" . ltrim($file, '/');
        $headers = $this->buildSigV4Headers('HEAD', $uri, '');
        $headerList = [];
        foreach ($headers as $k => $v) {
            $headerList[] = "{$k}: {$v}";
        }
        $url = "{$this->protocol}://{$this->endpoint}{$uri}";
        $result = HttpExtend::request('HEAD', $url, [
            'headers'      => $headerList,
            'returnHeader' => true,
            'timeout'      => 15,
        ]);
        return is_numeric(stripos($result, '200 OK'));
    }

    /**
     * 获取访问地址.
     * @param string $name 文件名称
     * @param bool|int $safe 安全模式或超时时间
     * @param ?string $attname 下载名称
     * @return string
     */
    public function url(string $name, bool|int $safe = false, ?string $attname = null): string
    {
        return "{$this->domain}/" . ltrim($name, '/') . $this->getSuffix($attname, $name);
    }

    /**
     * 获取存储路径.
     * @param string $name 文件名称
     * @param bool|int $safe 安全模式或超时时间
     * @return string
     */
    public function path(string $name, bool $safe = false): string
    {
        return $this->url($name, $safe);
    }

    /**
     * 获取文件信息.
     * @param string $name 文件名称
     * @param bool|int $safe 安全模式或超时时间
     * @param ?string $attname 下载名称
     * @return array
     */
    public function info(string $name, bool $safe = false, ?string $attname = null): array
    {
        return $this->has($name, $safe) ? [
            'url' => $this->url($name, $safe, $attname),
            'key' => $name, 'file' => $this->path($name, $safe),
        ] : [];
    }

    /**
     * 获取上传地址.
     * @return string
     */
    public function endpoint(): string
    {
        return "{$this->protocol}://{$this->endpoint}/{$this->bucket}";
    }

    /**
     * 获取存储区域.
     * @return array
     */
    public static function region(): array
    {
        return [
            'us-east-1' => lang('标准区域 (us-east-1)'),
            'default'   => lang('默认区域 (default)'),
        ];
    }

    /**
     * 初始化入口.
     * @throws Exception
     */
    protected function init()
    {
        $this->endpoint = trim(strval(sysconf('storage.minio_endpoint|raw') ?? ''));
        $this->bucket = trim(strval(sysconf('storage.minio_bucket|raw') ?? ''));
        $this->accessKey = trim(strval(sysconf('storage.minio_access_key|raw') ?? ''));
        $this->secretKey = trim(strval(sysconf('storage.minio_secret_key|raw') ?? ''));
        $this->region = trim(strval(sysconf('storage.minio_region|raw') ?? '')) ?: 'us-east-1';

        $proto = strtolower(strval(sysconf('storage.minio_http_protocol|raw') ?? 'http'));
        $this->protocol = in_array($proto, ['http', 'https']) ? $proto : 'http';

        $domain = trim(strval(sysconf('storage.minio_http_domain|raw') ?? ''));
        if ($domain === '') {
            $domain = "{$this->endpoint}/{$this->bucket}";
        }
        $domain = preg_replace('#^https?://#i', '', rtrim($domain, '/'));
        if ($proto === 'auto') {
            $this->domain = "//{$domain}";
        } else {
            $this->domain = "{$this->protocol}://{$domain}";
        }
    }

    /**
     * 构建 AWS Signature V4 签名头.
     * @param string $method 请求方式
     * @param string $uri 请求路径
     * @param string $body 请求体
     * @param array $extraHeaders 额外头
     * @return array
     */
    private function buildSigV4Headers(string $method, string $uri, string $body, array $extraHeaders = []): array
    {
        $date = gmdate('Ymd');
        $datetime = gmdate('Ymd\THis\Z');
        $payloadHash = hash('sha256', $body);
        $host = $this->endpoint;

        $headers = array_merge([
            'Host'                 => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $datetime,
        ], $extraHeaders);

        // 规范头小写字典序
        $canonicalHeaders = '';
        $signedHeadersList = [];
        $lowerHeaders = [];
        foreach ($headers as $k => $v) {
            $lowerHeaders[strtolower($k)] = trim(strval($v));
        }
        ksort($lowerHeaders);
        foreach ($lowerHeaders as $k => $v) {
            $canonicalHeaders .= "{$k}:{$v}\n";
            $signedHeadersList[] = $k;
        }
        $signedHeaders = join(';', $signedHeadersList);

        $canonicalRequest = "{$method}\n{$uri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $scope = "{$date}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$datetime}\n{$scope}\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        return $headers;
    }
}
