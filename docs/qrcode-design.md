# 小程序码生成组件设计（Qrcode）

> 状态：设计稿，尚未实现。适用目录：`plugin/think-library`。
> 范围：各平台「小程序码 / 小程序二维码」的服务端生成。任意文本转二维码不在范围内，见第 9 节。

## 1. 结论

- 微信小程序码不需要新增依赖。`composer.json` 已引入的 `zoujingli/wechat-developer` 自带 `WeMini\Qrcode`，覆盖三类接口，驱动直接封装它。
- 组件沿用库内现有模式：`Interface + UsageTrait + Exception + Facade + 驱动类`，与 Payment、Notice 一致。
- 首期实现微信、QQ。支付宝的接口名和参数没有核实到，列为二期。抖音、百度没拿到可靠的官方参数，暂不实现。

## 2. 各平台能力核实情况

| 平台 | 接口 | 依据 | 状态 |
| :-- | :-- | :-- | :-- |
| 微信小程序 | `getwxacodeunlimit` / `getwxacode` / `createwxaqrcode` | 已读 `vendor/zoujingli/wechat-developer/WeMini/Qrcode.php`，参数与限制对照微信官方文档页 | 已核实 |
| QQ 小程序 | `POST https://api.q.qq.com/api/json/qqa/CreateMiniCode` | QQ 官方文档页（取 token、生成码两页） | 已核实 |
| 支付宝小程序 | `alipay.open.app.qrcode.create`，经 `AliPay\Trade` 通用签名调用 | 官方文档页抓取失败，接口名与参数来自记忆 | 未核实 |
| 抖音小程序 | 无 | 只搜到论坛帖片段，没有官方参数 | 未核实，不实现 |
| 百度智能小程序 | 无 | 官方文档站是前端渲染，抓不到正文 | 未核实，不实现 |

库内其他相关类，不属于本组件：`WeChat\Qrcode`（公众号带参二维码，ticket 方式）、`weopen-developer` 的 `WeMini\Code::getQrcode`（第三方平台体验版二维码）。

## 3. 文件结构

| 文件 | 作用 |
| :-- | :-- |
| `src/contract/QrcodeInterface.php` | 驱动标准接口 |
| `src/contract/QrcodeUsageTrait.php` | `instance()`、`logRequest()`、MIME 识别等公共方法 |
| `src/exception/QrcodeException.php` | 继承 `think\admin\base\Exception` |
| `src/facade/Qrcode.php` | 门面：`types()`、`config()`、`instance()`、`ready()`、`save()`、`dataUri()` |
| `src/qrcode/WechatQrcode.php` | 微信驱动，封装 `WeMini\Qrcode` |
| `src/qrcode/QqQrcode.php` | QQ 驱动，用 `HttpExtend` 自行请求 |
| `src/qrcode/AlipayQrcode.php` | 二期 |

驱动类名由门面按 `think\admin\qrcode\{Name}Qrcode` 拼出，规则与 `Payment::instance()` 相同。

## 4. 接口约定

```php
interface QrcodeInterface
{
    /**
     * @param string $page    小程序页面路径，不带前导斜杠，不带参数
     * @param array  $query   页面参数，由驱动按平台规则编码
     * @param array  $options width、mode 及各平台专有参数
     */
    public function create(string $page, array $query = [], array $options = []): array;

    public function ready(): bool;
}
```

返回结构：

```php
[
    'code'    => 0,
    'message' => 'success',
    'content' => '',   // 图片二进制，微信、QQ 返回此项
    'url'     => '',   // 图片地址，平台直接返回链接时使用（预计支付宝）
    'mime'    => '',   // 由 getimagesizefromstring() 识别，不假定为 PNG
    'raw'     => [],   // 平台原始响应或请求参数
]
```

- `content` 与 `url` 至少有一个非空。
- 失败一律抛 `QrcodeException`，不返回非 0 的 `code`。这点与 Payment 门面的异常风格一致，调用方只需处理一种失败路径。
- `Qrcode::save($result, $path)`：通过 `Storage::upload()` 落盘并返回访问地址。只有 `url` 时先下载再保存。
- `Qrcode::dataUri($result)`：返回 `data:{mime};base64,...`，用于直接内嵌页面。

## 5. 配置

沿用 `sysconf("qrcode.{name}|raw")`，缺失时回退环境变量 `QRCODE_{NAME}`，逻辑与 `Payment::config()` 相同。

| 配置项 | 说明 |
| :-- | :-- |
| `qrcode.type` | 默认驱动：`wechat`、`qq` |
| `qrcode.wechat_appid`、`qrcode.wechat_secret` | 微信小程序 |
| `qrcode.qq_appid`、`qrcode.qq_secret` | QQ 小程序 |
| `qrcode.alipay_appid`、`qrcode.alipay_private_key`、`qrcode.alipay_public_key` | 二期 |

凭据单独成组，不回退读取 `payment.*` 或 `notice.*`。支付用的 AppID 和小程序码用的 AppID 不一定是同一个账号，静默回退会在配错时生成别人的码。

## 6. 微信驱动

### 6.1 三种模式

| `options['mode']` | vendor 方法 | 接口 | 限制 |
| :-- | :-- | :-- | :-- |
| `unlimited`（默认） | `createMiniScene($scene, $page, $width, ...)` | `/wxa/getwxacodeunlimit` | `scene` 最长 32 个可见字符；`page` 必须是已发布小程序里存在的页面，否则报 41030 |
| `limited` | `createMiniPath($path, $width, ...)` | `/wxa/getwxacode` | `path` 最长 128 字节；与 `default` 共享 10 万个的生成上限，超出报 45029 |
| `default` | `createDefault($path, $width)` | `/cgi-bin/wxaapp/createwxaqrcode` | 同上；vendor 签名里没有颜色和透明底参数 |

通用限制：

- `width` 取值 280 到 1280，vendor 默认 430。
- 三个接口都只能为已发布的小程序生成。
- 官方文档写明每分钟 5000 次上限且可能调整，批量场景应预生成并缓存结果。
- 以上数字来自微信官方文档页，实现前再核对一次。

默认选 `unlimited`，因为不受数量上限约束。

### 6.2 参数编码

`unlimited` 的 `scene` 字符集只允许数字、大小写字母和 `` !#$&'()*+,/:;=?@-._~ ``，不支持 `%`，所以 `urlencode` 不能用，中文也无法直接放入。

- `unlimited`：把 `$query` 拼成 `k=v&k2=v2`，不用 `http_build_query`（它会产生 `%`）。先校验长度和字符集，不合法直接抛 `QrcodeException`，错误信息提示「改存业务表，scene 只放短 ID」。不做自动截断或静默转码。
- `limited`、`default`：`path = $page . '?' . http_build_query($query)`，校验不超过 128 字节。

小程序端读取 `scene` 的方式（`options.scene` 还是 `options.query.scene`，是否需要解码）这次没有核实，写前端文档前要对照官方说明确认。

### 6.3 AccessToken

vendor 自己取 token，缓存键为 `{appid}_access_token`，有效期 7000 秒，默认写文件缓存，目录由配置项 `cache_path` 决定。

- 多机部署时 `cache_path` 必须指向共享目录，否则各机各自取 token。
- 微信刷新 token 后旧 token 还有约 5 分钟过渡期。库里 `WechatNotice` 用另一个缓存键为公众号取 token，如果两处使用同一个 AppID，会互相刷新。同一 AppID 只允许一处取 token。
- vendor 支持 `GetAccessTokenCallback` 和 `setAccessToken()` 接管 token，可用于统一到一处。这两个接口是读代码得出的结论，没有实际运行验证。

### 6.4 错误处理

成功时 vendor 返回二进制，失败时 `parseResult` 抛 `WeChat\Exceptions\InvalidResponseException`，`getCode()` 即微信 `errcode`。驱动统一捕获后转成 `QrcodeException`，保留 `errcode`。

常见错误码：`41030` 页面不存在或小程序未发布，`45029` 生成数量达上限，`45009` 调用频率超限。

### 6.5 实现时要注意的 vendor 行为

`parseResult` 遇到 `40014`、`40001`、`41001`、`42001` 会清 token 并重试一次，由 `$isTry` 标志防止死循环。我读到的代码里没有把它重置为 `false` 的地方，而 `BasicWeChat::instance()` 会按配置缓存对象。常驻进程（vendor 依赖里有 workerman）里复用同一对象，只有第一次 token 失效能自动重试。

驱动因此每次调用 `new \WeMini\Qrcode($config)`，不使用 `instance()`。这条判断来自读代码，没有复现。

## 7. 其他平台

### 7.1 QQ 小程序（已核实接口）

- 取 token：`GET https://api.q.qq.com/api/getToken?grant_type=client_credential&appid=...&secret=...`，返回 `access_token`、`expires_in`（最长 7200 秒）。
- 生成码：`POST https://api.q.qq.com/api/json/qqa/CreateMiniCode?access_token=...`，请求体必须是 JSON 字符串，字段 `appid`、`path`（可选，缺省进首页）。
- 成功返回图片二进制，失败返回含 `errcode`、`errmsg` 的 JSON。文档里只列了 `45029`（生成数量达上限）。
- 只能为已发布的应用生成。

vendor 里没有 QQ 封装，驱动用 `HttpExtend` 自行请求，token 缓存方式参照 `WechatNotice`：`cache()` 加按 AppID 哈希的键，过期时间取 `expires_in` 减去余量。

未核实：`path` 能否带 `?query` 参数。官方页面只说明了扫码后打开的页面，没有说参数。实现时先用真实账号测一次，再决定 `$query` 是否支持。

### 7.2 支付宝小程序（二期，接口未核实）

`AliPay\Trade` 是通用签名入口：`setMethod()` 设置接口名，`apply($bizContent)` 发请求，返回码非 `10000` 时抛 `InvalidResponseException`。我记得二维码接口是 `alipay.open.app.qrcode.create`，业务参数包含页面路径、query 参数和描述，响应里带图片链接，但官方文档没抓到，字段名不能照记忆写进代码。二期开始前先拿到官方文档。

该接口返回的是 `url` 而不是二进制，这也是返回结构里保留 `url` 字段的原因。

### 7.3 抖音、百度

没有拿到可靠的官方接口说明，这次不设计具体驱动。接入新平台的步骤固定：新增 `{Name}Qrcode` 实现 `QrcodeInterface`，在 `Qrcode::types()` 登记，在 `Qrcode::ready()` 里加配置检查，无需改动其他代码。

## 8. 调用示例

```php
use think\admin\facade\Qrcode;

// 默认驱动，无限量模式，scene 只放短 ID
$res = Qrcode::create('pages/goods/detail', ['id' => 1024], ['width' => 430]);
$url = Qrcode::save($res, 'qrcode/goods-1024.png');

// 指定平台与模式
$res = Qrcode::instance('wechat')->create('pages/index/index', ['from' => 'poster'], [
    'mode'  => 'limited',
    'width' => 600,
]);
$img = Qrcode::dataUri($res);

// 失败处理
try {
    Qrcode::create('pages/not/exist');
} catch (\think\admin\exception\QrcodeException $e) {
    // 微信 41030：页面不存在或小程序未发布
}
```

驱动内通过 `logRequest()` 写操作日志（`qrcode.create`），日志内容只记录 page、query、mode，不记录 secret 与 token。

## 9. 不在范围内

- 任意文本、链接转二维码。当前 vendor 目录里没有二维码编码库（未找到 endroid、bacon 等），要做就得新增 composer 依赖，需要你决定是否引入。
- 公众号带参二维码、体验版二维码，库里已有对应 vendor 类，直接用即可。
- 二维码的海报合成。

## 10. 风险与待确认

| 项目 | 说明 |
| :-- | :-- |
| 支付宝、抖音、百度接口 | 未核实，实现前要拿到官方文档 |
| QQ `path` 带参数 | 未核实，需真实账号测试 |
| 小程序端读取 `scene` | 未核实，影响前端对接文档 |
| `HttpExtend` 关闭了 SSL 校验 | `request()` 在 `setopt` 之后又强制 `CURLOPT_SSL_VERIFYPEER=false`，所以无法通过 `setopt` 重新打开。QQ 取 token 时 secret 在 URL 里，经此通道发送存在被中间人截获的风险。建议给 `HttpExtend` 加校验开关，或 QQ 驱动自己管 curl |
| 测试 | 没有真实小程序凭据，网络调用无法在本地验证。可单测的部分：`scene` 校验、`path` 长度、`query` 编码、MIME 识别 |
| 版本 | `composer.json` 写 `php >7.1`，但库内已大量使用 `match` 和联合类型，新代码沿用现状 |

## 11. 实施顺序

1. 建 `QrcodeInterface`、`QrcodeUsageTrait`、`QrcodeException`、`Qrcode` 门面。
2. 实现 `WechatQrcode` 三种模式，并补 `scene`、`path` 校验的单元测试。
3. 实现 `QqQrcode`，先解决 SSL 校验问题。
4. 在 `think-library/readme.md` 补使用说明，在 `think-plugs-admin` 的配置页补 `qrcode` 分组（参照 `payment-wechat.html`）。
5. 拿到官方文档后再做支付宝。
