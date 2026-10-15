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

namespace think\admin\middleware;

use think\admin\service\ApiCryptService;
use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;

/**
 * API 请求解密与验签中间件
 * @class ApiDecrypt
 */
class ApiDecrypt
{
    protected App $app;
    protected ApiCryptService $service;

    public function __construct(App $app, ApiCryptService $service)
    {
        $this->app = $app;
        $this->service = $service;
    }

    /**
     * 中间件调度处理
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $context = (string) $request->header('x-client-context', '');

        // 当请求头携带信封密文时，执行解密与验签流程
        if ($context !== '') {
            $nonce = (string) $request->header('x-client-nonce', '');
            $sign = (string) $request->header('x-client-sign', '');
            $time = (int) $request->header('x-client-timestamp', '');

            if ($nonce === '' || $sign === '' || $time <= 0) {
                throw new HttpException(400, '缺少完整的安全验证请求头 (nonce / sign / timestamp)');
            }

            // 执行核心解密
            $params = $this->service->decrypt($context, $nonce, $sign, $time);

            // 1. 提取 Token 并自动同步注入系统 Header，实现与 JwtSession 认证无缝衔接
            $token = (string) ($params['token'] ?? $params['api_token'] ?? $params['jwt_token'] ?? '');
            $request->token = $token;
            if ($token !== '') {
                $request->withHeader([
                    'jwt-token'     => $token,
                    'authorization' => 'Bearer ' . $token,
                ]);
                $_SERVER['HTTP_JWT_TOKEN'] = $token;
                $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            }

            // 2. 提取设备信息 (支持标准嵌套对象 {"device": {...}} 或平铺字段)
            $device = [];
            if (isset($params['device']) && is_array($params['device'])) {
                $device = $params['device'];
            } else {
                $keys = ['device_id', 'device_name', 'device_model', 'platform', 'os', 'os_version', 'app_version', 'client_version', 'brand'];
                foreach ($keys as $k) {
                    if (isset($params[$k])) {
                        $device[$k] = $params[$k];
                    }
                }
            }
            $request->device = $device;

            // 3. 提取纯业务数据: 前端规范传入 {"data": {...}} 时直接提取；平铺时剥离系统元数据
            $data = [];
            if (isset($params['data']) && is_array($params['data'])) {
                $data = $params['data'];
            } else {
                $data = $params;
                unset($data['token'], $data['device'], $data['jwt_token'], $data['api_token']);
            }
            $request->data = $data;

            // 4. 将业务参数合并到 POST，便于同时兼容框架内置验证器与标准 post()
            $posts = array_merge($params, $data);
            $request->withPost($posts);
            $request->encrypted = true;
            $request->payload = $params;
        }

        return $next($request);
    }
}
