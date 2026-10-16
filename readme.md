# ThinkLibrary for HlwAdmin

**ThinkLibrary** 是 **HlwAdmin** 面向 **ThinkPHP 6 & 8** 的核心封装库，提供控制器、模型、服务、队列、存储、配置、HTTP、JWT 和常用数据处理能力。

## 业务功能特性

**核心架构功能：**
- **标准控制器基类**: 提供完整的 CRUD 操作封装，包括分页、表单、验证、队列等通用功能
- **基础模型类**: 实现魔术方法和静态助手调用，支持操作日志记录和数据一致性保障
- **自定义服务基类**: 提供依赖注入和实例化机制，支持服务的统一管理和扩展
- **全局函数库**: 包含数据处理、系统配置、HTTP 请求、JWT 认证等实用函数
- **快捷查询逻辑器**: 提供链式查询构建，简化数据库操作
- **表单验证机制**: 支持规则别名和自定义验证，确保数据输入安全
- **异步任务队列**: 支持延时执行和循环任务，提升系统响应性能
- **CSRF 令牌验证**: 内置表单安全验证，防止跨站请求伪造攻击
- **高精度计算支持**: 集成 BC Math 高精度数学函数，确保金融计算的准确性
- **AI 大模型统一接入**: 深度集成主流大语言模型（DeepSeek、OpenAI、Claude、Gemini、通义千问、智谱清言、Kimi、文心一言、讯飞星火、豆包、腾讯混元、MiniMax），支持流式传输、图像多模态与向量化
- **对象存储通用组件**: 统一对象存储标准接口（`StorageInterface`），支持本地存储、阿里云 OSS、腾讯云 COS、七牛云、又拍云、MinIO、AWS S3 与 Alist 聚合存储
- **在线支付网关组件**: 统一聚合支付标准接口（`PaymentInterface`），支持微信支付（V3 API）、支付宝（OpenAPI）、PayPal 国际支付与 Stripe 聚合收单
- **多渠道事件通知广播**: 支持飞书、钉钉、企业微信、公众号模板消息、邮件及短信一键多渠道广播推送
- **多厂商短信引擎封装**: 统一抽象主流短信服务商接口（`SmsInterface`），支持 14 大国内外服务商（阿里云、腾讯云、华为云、百度云、七牛云、创蓝253、助通、容联云、网易云信、云片网络、赛邮SubMail、极光推送、Twilio国际短信、梦网科技），内置单发/群发、验证码助手、余额与回执查询
- **小程序码生成组件**: 统一小程序码生成标准接口（`QrcodeInterface`），支持微信小程序（无限量 Scene 模式、普通 Path 模式、标准二维码）及 QQ 小程序码，内置 MIME 识别、Storage 存储落盘与 Data URI 实时预览


**技术特性：**
- **PSR-12 标准**: 严格遵循 PHP-FIG 编码规范，确保代码质量和可维护性
- **模块化设计**: 各功能模块独立封装，便于扩展和维护
- **异常处理完善**: 完善的异常捕获和日志记录机制，便于问题排查
- **向后兼容**: 保持 API 稳定性，确保平滑升级

## 功能说明

1. **数据列表展示组件**

* 功能：展示数据列表，支持分页、排序和高级搜索。
* 优化点：提供友好的用户界面和交互体验，确保数据展示的准确性和实时性。
* 高级特性：支持多种排序方式、自定义搜索字段和条件。

2. **表单处理模块**

* 功能：用于创建、展示和提交表单数据。
* 优化点：表单验证和错误处理机制，确保数据的有效性和完整性。
* 高级特性：支持多种表单元素、表单验证规则和动态表单生成。

3. **数据状态快速处理模块**

* 功能：根据业务需求快速更新数据状态。
* 优化点：提供简洁的接口和操作方式，支持多字段同时更新。
* 高级特性：支持条件判断和事务处理，确保数据一致性和完整性。

4. **数据安全删除模块**

* 功能：根据业务需求安全地删除数据。
* 优化点：提供软删除和硬删除两种方式，确保数据彻底消失或标记为已删除。
* 高级特性：支持根据条件自动软删除、可配置的软删除标记字段。

5. **文件存储通用组件**

* 功能：支持多种文件存储方式，包括本地服务存储、云存储等。
* 优化点：提供统一的接口和配置方式，方便开发者快速集成和使用。
* 高级特性：支持文件上传、下载、删除和版本控制等功能。

6. **通用数据保存更新模块**

* 功能：根据 key 值及 where 条件判断数据是否存在，进行更新或新增操作。
* 优化点：提供简洁的接口和操作方式，减少冗余代码和重复工作量。
* 高级特性：支持乐观锁和悲观锁机制，确保并发控制和数据一致性。

7. **通用网络请求模块**

* 功能：支持 GET、POST 和 PUT 请求，可配置请求参数、证书等。
* 优化点：提供统一的接口和配置方式，方便开发者快速发起网络请求。
* 高级特性：支持请求重试、超时设置、自动捕获异常等功能。

8. **系统参数通用 g-k-v 配置模块**

* 功能：快速配置系统参数，支持长久化保存。
* 优化点：提供简洁的接口和操作方式，方便开发者管理和维护系统参数。
* 高级特性：支持参数加密存储、权限控制和日志记录等功能。

9. **UTF8 加密算法支持模块**

* 功能：提供 UTF8 字符串的加密和解密功能。
* 优化点：确保加密过程的安全性和数据的机密性。
* 高级特性：支持多种加密算法、密钥管理等功能。

10. **接口 CORS 跨域默认支持模块**

* 功能：默认支持跨域请求，输出标准化 JSON 数据。
* 优化点：减少开发者的工作量，自动处理跨域问题。
* 高级特性：支持定制化响应头、跨域请求限制等功能。

11. **表单 CSRF 安全验证模块**

* 功能：自动为表单添加 CSRF 安全验证字段，防止恶意提交。
* 优化点：简化开发者的工作流程，提高表单提交的安全性。
* 高级特性：支持自定义验证规则、多种验证方式等功能。

12. **AI 大模型通用对话组件**

* 功能：统一封装主流大语言模型 API 接口，提供标准化对话与内容生成能力。
* 优化点：统一请求与返回格式，支持随时在各 AI 模型服务商之间无缝切换。
* 高级特性：支持 DeepSeek、阿里通义千问、智谱清言 GLM、OpenAI ChatGPT 等，支持多轮上下文与参数微调。

13. **多渠道事件通知广播组件**

* 功能：提供系统事件向管理人员与外部群聊多渠道推送预警通知的能力。
* 优化点：单接口多渠道同时广播（`Notice::push` / `pushCard` / `pushImage` / `pushFile`），提供强大的卡片消息交互、图片通知与文件推送。
* 高级特性：
  - 核心渠道：深度支持飞书机器人、钉钉机器人、企业微信群机器人、微信公众号模板消息、SMTP 邮件及手机短信。
  - 交互卡片：支持 `sendCard()` 原生交互式卡片（飞书 Interactive 卡片、企业微信 TextCard、钉钉 ActionCard、微信模板消息跳转、高质感 HTML 卡片邮件）。
  - 富媒体推送：支持 `sendImage()` 原生图片推送与 `sendFile()` 专用文件下载分发。
  - 消息模板管理：内置系统告警 (`system_alert`)、任务执行 (`task_notice`)、安全验证码 (`user_verify`)、交易提醒 (`order_notice`) 与异地登录 (`login_warn`)，支持模板变量动态渲染与自定义模板持久化。
  - 发送优先级控制：支持 `urgent` (紧急)、`high` (重要)、`normal` (普通)、`low` (低) 四级优先级调度，紧急通知自动触发群 `@all` 与红色强提醒。

14. **多厂商短信引擎通用组件**

* 功能：支持国内主流云通讯与国际短信服务商接口，提供单发、群发、验证码快捷发送、余额查询与投递回执。
* 优化点：统一 `SmsInterface` 规范与环境变量回退（`SMS_*`），规范国内 Plain 纯 11 位与全球 E.164 号码格式，集成操作审计日志。
* 高级特性：全面支持 14 大服务商：阿里云、腾讯云、华为云、百度云、七牛云、创蓝253、助通科技、容联云、网易云信、云片网络、赛邮SubMail、极光推送、Twilio国际短信及梦网科技。

15. **对象存储通用组件**

* 功能：统一封装主流对象存储与自建存储驱动，提供标准的文件上传、下载、删除、存在检查与带签名访问 URL 生成。
* 优化点：统一 `StorageInterface` 规范与 `STORAGE_*` 环境变量回退，彻底解决多云对象存储迁移痛点。
* 高级特性：全面支持本地服务存储、阿里云 OSS、腾讯云 COS、七牛云 Kodo、又拍云 USS、MinIO 私有云、AWS S3 全球存储与 Alist 聚合存储网关。

16. **在线支付网关组件**

* 功能：统一国内与国际主流在线收单接口，支持支付统一下单、订单状态查询、退款申请与异步通知验签。
* 优化点：统一 `PaymentInterface` 规范与 `PAYMENT_*` 环境变量回退，开箱即用，支持多租户与分站配置隔离。
* 高级特性：全面支持微信支付 (V3 API)、支付宝 (OpenAPI)、PayPal 国际支付及 Stripe 聚合收单。

17. **小程序码生成组件**

* 功能：统一国内各平台小程序码与小程序二维码的服务端生成，提供标准的小程序码获取、落盘存储及 Data URI 转换。
* 优化点：统一 `QrcodeInterface` 规范与 `QRCODE_*` 环境变量回退，开箱即用，凭据独立隔离。
* 高级特性：全面支持微信小程序码（无限量 Scene 模式、普通限量 Path 模式、通用二维码模式）、QQ 小程序码，内置二进制 MIME 自动嗅探、Storage 存储落盘及 `dataUri()` 网页直接嵌入。


> 备注：本项目基于 ThinkAdmin 进行魔改。

## 数据库安装包兼容性

`php think xadmin:package` 可从 MySQL 或 SQLite 生成结构及数据安装包。字段优先使用 `integer`、`string`、`text`、`binary`、`float`、`decimal`，日期时间及 JSON 按实际用途保留。MySQL 的整数宽度、长文本、二进制等原始定义通过选项保存，在 MySQL 上恢复时保持原有类型；SQLite 使用对应通用类型，不依赖不同版本 Phinx 的长度常量。

MySQL 的 `ENUM` / `SET`、`BIT`、`YEAR`、`ZEROFILL`、`ON UPDATE` 及全文/前缀等专有索引不能直接转换到 SQLite，迁移会在操作对应表之前报错。SQLite 的部分索引、表达式索引、生成列及 `CHECK`、外键、`ON CONFLICT`、`STRICT`、`WITHOUT ROWID` 等尚不支持导出的结构也会明确报错。`--force` 会更新字段和同名索引，保留其他字段及索引；已有表的主键不同或 SQLite 表被外键引用时停止，需要另写专用迁移。SQLite 升级失败会回滚，重建表保留原有触发器和自增序号。

安装包始终使用迁移配置的连接和表前缀。新版逐行备份支持二进制、空值和大整数，并兼容读取旧 JSONL 数据文件。恢复到 SQLite 整数列时，超出 64 位有符号整数范围的记录会明确报错并回滚本次恢复，避免自动转为浮点数丢失精度。SQLite 的时间默认表达式与同名字符串、主键字段的可空属性分别保留。更新后请重新生成原来的安装包；运行新生成的脚本也需要同步更新 ThinkLibrary。生成或写入失败时保留同类旧脚本及备份，成功发布后才替换。

打包忽略名单同时接受带前缀的物理表名和不带前缀的逻辑表名。迁移记录表始终排除；未配置忽略名单时，数据备份默认排除任务队列与操作日志，带表前缀时同样生效。

SQLite 数据备份使用 v2 记录每个值的实际存储类型，保留 TEXT 列中的 BLOB、BLOB 列中的文本与数值以及浮点精度；读取时仍兼容 v1 和原 JSONL。`INT`、`BIGINT` 等主键不会被改成有自动编号语义的 `INTEGER PRIMARY KEY`。MySQL 备份在读取日期时间字段时保留小数秒，兼容 PHP 7.2 的旧驱动。MySQL 8 的文本常量默认值和 `CURRENT_TIMESTAMP` 表达式可再次导出，其他未支持的默认表达式仍会明确报错。

SQLite 回归测试直接使用内存数据库。可通过 `PHINX_TEST_MYSQL_PORT=端口 vendor/bin/phpunit -c phpunit.xml.dist` 额外运行 MySQL 往返验证（在 ThinkLibrary 目录执行）。该测试只用于可丢弃的本机 MySQL 实例：数据库名为 `phinx_fixture`，账号 `root`、空密码，会创建并清理结构、默认值和备份测试表及空的 `system_menu` 表。测试覆盖真实打包入口和带前缀恢复，不应指向业务数据库。

## 使用说明

1. **依赖管理**：ThinkLibrary 需要 Composer 支持进行安装和依赖管理。
2. **安装指南**：您可以使用以下命令通过 Composer 安装 ThinkLibrary：`composer require hlw2326/think-library`。
3. **使用示例**：在使用 ThinkLibrary 时，确保您的控制器类继承自 `think\admin\Controller`。一旦继承完成，您就可以通过 `$this` 对象访问并使用全部功能。

```php
// 定义 MyController 控制器
class MyController extend \think\admin\Controller {

    // 指定当前数据表名
    protected $dbQuery = '数据表名';
    
    // 显示数据列表
    public function index(){
        $this->_page($this->dbQuery);
    }
    
    // 当前列表数据处理
    protected function _index_page_filter(&$data){
         foreach($data as &$vo){
            // @todo 修改原列表
         }
    }
    
}
```

* 必要数据库表SQL（sysdata 函数需要用这个表）

```sql
CREATE TABLE `system_data`
(
    `id`    bigint(11) unsigned NOT NULL AUTO_INCREMENT,
    `name`  varchar(100) DEFAULT NULL COMMENT '配置名',
    `value` longtext COMMENT '配置值',
    PRIMARY KEY (`id`) USING BTREE,
    KEY     `idx_system_data_name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统-数据';
```

* 必要数据库表SQl（sysoplog 函数需要用的这个表）

```sql
CREATE TABLE `system_oplog`
(
    `id`        bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `node`      varchar(200)  NOT NULL DEFAULT '' COMMENT '当前操作节点',
    `geoip`     varchar(15)   NOT NULL DEFAULT '' COMMENT '操作者IP地址',
    `action`    varchar(200)  NOT NULL DEFAULT '' COMMENT '操作行为名称',
    `content`   varchar(1024) NOT NULL DEFAULT '' COMMENT '操作内容描述',
    `username`  varchar(50)   NOT NULL DEFAULT '' COMMENT '操作人用户名',
    `create_at` timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统-日志';
```

* 必要数据库表SQL（`sysconf`函数需要用到这个表）

```sql
CREATE TABLE `system_config`
(
    `type`  varchar(20)  DEFAULT '' COMMENT '分类',
    `name`  varchar(100) DEFAULT '' COMMENT '配置名',
    `value` varchar(500) DEFAULT '' COMMENT '配置值',
    KEY     `idx_system_config_type` (`type`),
    KEY     `idx_system_config_name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统-配置';
```

* 系统任务列队支持需要的数据表

```sql
CREATE TABLE `system_queue`
(
    `id`         bigint(20) NOT NULL AUTO_INCREMENT,
    `code`       varchar(20)          DEFAULT '' COMMENT '任务编号',
    `title`      varchar(50) NOT NULL DEFAULT '' COMMENT '任务名称',
    `command`    varchar(500)         DEFAULT '' COMMENT '执行指令',
    `exec_data`  longtext COMMENT '执行参数',
    `exec_time`  bigint(20) unsigned DEFAULT '0' COMMENT '执行时间',
    `exec_desc`  varchar(500)         DEFAULT '' COMMENT '状态描述',
    `enter_time` bigint(20) DEFAULT '0' COMMENT '开始时间',
    `outer_time` bigint(20) DEFAULT '0' COMMENT '结束时间',
    `attempts`   bigint(20) DEFAULT '0' COMMENT '执行次数',
    `rscript`    tinyint(1) DEFAULT '1' COMMENT '单例模式',
    `status`     tinyint(1) DEFAULT '1' COMMENT '任务状态(1新任务,2处理中,3成功,4失败)',
    `create_at`  timestamp   NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY          `idx_system_queue_code` (`code`),
    KEY          `idx_system_queue_title` (`title`) USING BTREE,
    KEY          `idx_system_queue_status` (`status`) USING BTREE,
    KEY          `idx_system_queue_rscript` (`rscript`) USING BTREE,
    KEY          `idx_system_queue_create_at` (`create_at`) USING BTREE,
    KEY          `idx_system_queue_exec_time` (`exec_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统-任务';
```

#### 列表处理

```php
// 列表展示
$this->_page($dbQuery, $isPage, $isDisplay, $total);

// 列表展示搜索器（按 name、title 模糊搜索；按 status 精确搜索）
$this->_query($dbQuery)->like('name,title')->equal('status')->page();

// 对列表查询器进行二次处理
$query = $this->_query($dbQuery)->like('name, title')->equal('status');
$db = $query->db(); // @todo 这里可以对db进行操作
$this->_page($db); // 显示列表分页
```

#### 表单处理

```php
// 表单显示及数据更新
$this->_form($dbQuery, $tplFile, $pkField , $where, $data);
```

#### 删除处理

```php
// 数据删除处理
$this->_deleted($dbQuery);
```

#### 禁用启用处理

```php
// 数据禁用处理
$this->_save($dbQuery, ['status'=>'0']);

// 数据启用处理
$this->_save($dbQuery, ['status'=>'1']);
```

#### 对象存储组件（支持本地及国内外 8 大主流存储引擎）

系统支持通过统一 `Storage` 门面调度、动态指定存储引擎以及各存储类直接单调三种模式。现已原生支持现代对象存储标准方法：`upload()`、`download()`、`delete()`、`exists()`、`url()`，同时 100% 兼容经典 `set()`、`get()`、`del()`、`has()`、`info()` 方法：

```php
use think\admin\facade\Storage;
use think\admin\storage\LocalStorage;
use think\admin\storage\AliossStorage;
use think\admin\storage\TxcosStorage;
use think\admin\storage\QiniuStorage;
use think\admin\storage\UpyunStorage;
use think\admin\storage\AlistStorage;
use think\admin\storage\MinioStorage;
use think\admin\storage\Awss3Storage;

// 1. 获取支持的所有存储引擎列表
$types = Storage::types();
// ['local' => '本地服务器存储', 'alist' => '自建Alist存储', 'qiniu' => '七牛云对象存储', 'upyun' => '又拍云USS存储', 'txcos' => '腾讯云COS存储', 'alioss' => '阿里云OSS存储', 'minio' => 'MinIO 对象存储', 'awss3' => 'AWS S3 对象存储']

// 2. 现代统一对象存储标准操作（基于后台配置的默认引擎）
// 上传文件内容并直接获取访问 URL
$url = Storage::upload('documents/contract.pdf', $fileContent);

// 读取 / 下载文件内容
$content = Storage::download('documents/contract.pdf');

// 检查文件是否存在
$exists = Storage::exists('documents/contract.pdf');

// 获取文件完整访问链接
$url = Storage::url('documents/contract.pdf');

// 删除存储文件
$deleted = Storage::delete('documents/contract.pdf');

// 3. 动态指定具体存储引擎操作
$url1 = Storage::instance('alioss')->upload('avatar.jpg', $imageData);
$url2 = Storage::instance('txcos')->upload('video.mp4', $videoData);
$url3 = Storage::instance('minio')->upload('data.bin', $binaryData);
$url4 = Storage::instance('awss3')->upload('backup.zip', $zipData);

// 4. 直接调用具体存储类（支持驱动单调）
$alioss = AliossStorage::instance();
$alioss->upload('files/report.xlsx', $excelContent);

$minio  = MinioStorage::instance();
$minio->upload('files/private.log', $logContent);

$awss3  = Awss3Storage::instance();
$awss3->upload('assets/app.dmg', $dmgContent);
```

**支持的存储驱动与配置项对照表：**

| 引擎代码 | 驱动类 | 特性与适用场景 | 配置参数项 (`sysconf`) |
| :--- | :--- | :--- | :--- |
| `local` | `think\admin\storage\LocalStorage` | 本地磁盘存储 (`public/upload`) | `storage.allow_exts`, `storage.link_type` |
| `alioss` | `think\admin\storage\AliossStorage` | 阿里云 OSS 对象存储 (支持 CORS 直传) | `storage.alioss_bucket`, `storage.alioss_access_key`, `storage.alioss_secret_key`, `storage.alioss_point`, `storage.alioss_http_domain` |
| `txcos` | `think\admin\storage\TxcosStorage` | 腾讯云 COS 对象存储 | `storage.txcos_bucket`, `storage.txcos_access_key`, `storage.txcos_secret_key`, `storage.txcos_point`, `storage.txcos_http_domain` |
| `qiniu` | `think\admin\storage\QiniuStorage` | 七牛云 Kodo 对象存储 | `storage.qiniu_bucket`, `storage.qiniu_access_key`, `storage.qiniu_secret_key`, `storage.qiniu_region`, `storage.qiniu_http_domain` |
| `upyun` | `think\admin\storage\UpyunStorage` | 又拍云 USS 对象存储 | `storage.upyun_bucket`, `storage.upyun_user`, `storage.upyun_pass`, `storage.upyun_http_domain` |
| `alist` | `think\admin\storage\AlistStorage` | 自建 Alist 网盘聚合服务 | `storage.alist_url`, `storage.alist_user`, `storage.alist_pass` |
| `minio` | `think\admin\storage\MinioStorage` | MinIO 高性能自建存储 (AWS SigV4 标准协议) | `storage.minio_endpoint`, `storage.minio_bucket`, `storage.minio_access_key`, `storage.minio_secret_key`, `storage.minio_region`, `storage.minio_http_domain` |
| `awss3` | `think\admin\storage\Awss3Storage` | Amazon AWS S3 国际云存储 (AWS SigV4 标准协议) | `storage.awss3_bucket`, `storage.awss3_access_key`, `storage.awss3_secret_key`, `storage.awss3_region`, `storage.awss3_http_domain` |

#### 通用数据保存

```php
// 指定关键列更新（$where 为扩展条件）
boolean data_save($dbQuery, $data, 'pkname', $where);
```

#### 通用网络请求

```php
// 发起get请求
$result = http_get($url, $query, $options);

// 发起post请求
$result = http_post($url, $data, $options);
```

#### 系统参数配置（基于 system_config 数据表）

```php
// 设置参数
sysconf($keyname, $keyvalue);

// 获取参数
$keyvalue = sysconf($kename);
```

### AI 大模型引擎

系统深度整合国内外 12 大主流 AI 大模型服务商，支持统一门面调度、动态指定引擎以及具体 AI 驱动类直接单调三种使用模式：

```php
use think\admin\facade\Ai;
use think\admin\ai\DeepseekAi;
use think\admin\ai\OpenaiAi;
use think\admin\ai\ClaudeAi;
use think\admin\ai\GeminiAi;
use think\admin\ai\QwenAi;
use think\admin\ai\ZhipuAi;
use think\admin\ai\MoonshotAi;
use think\admin\ai\ErnieAi;
use think\admin\ai\SparkAi;
use think\admin\ai\DoubaoAi;
use think\admin\ai\HunyuanAi;
use think\admin\ai\MinimaxAi;

// 1. 获取支持的所有模型引擎列表
$types = Ai::types(); 
// 输出: ['deepseek' => 'DeepSeek 深度求索', 'openai' => 'OpenAI ChatGPT', 'claude' => 'Anthropic Claude', 'gemini' => 'Google Gemini', 'qwen' => '阿里通义千问', 'zhipu' => '智谱清言 GLM', 'moonshot' => '月之暗面 Kimi', 'ernie' => '百度文心一言', 'spark' => '科大讯飞星火', 'doubao' => '字节跳动豆包', 'hunyuan' => '腾讯云混元', 'minimax' => 'MiniMax 名之梦']

// 2. 检查特定引擎或当前默认引擎是否已配置可用
if (Ai::ready()) {
    // 方式一：使用后台配置的默认模型引擎对话
    $reply = Ai::chat('请简要介绍 ThinkLibrary 的核心架构与特色');
}

// 方式二：通过门面动态指定引擎类型
$reply = Ai::instance('deepseek')->chat('分析一下这个 SQL 的执行计划优化方案');
$reply = Ai::instance('claude')->chat('重构这段复杂的 PHP 核心调度逻辑');
$reply = Ai::instance('gemini')->chat('总结这篇多模态图像识别算法的最新进展');
$reply = Ai::instance('moonshot')->chat('提炼并分析这份十万字的技术规范文档要点');
$reply = Ai::instance('qwen')->chat('帮我写一个高可用分布式锁实现方案');
$reply = Ai::instance('doubao')->chat('生成一段极速客服智能回复模板');

// 方式三：直接调用具体 AI 驱动类（支持 ClaudeAi::instance() 单调）
$reply = DeepseekAi::instance()->chat('进行代码安全性审查与重构建议');
$reply = ClaudeAi::instance()->chat('深度审查以下高并发架构设计方案');
$reply = GeminiAi::instance()->chat('分析以下多模态与视觉识别方案');
$reply = MoonshotAi::instance()->chat('深度阅读并归纳超长行业研究报告');
$reply = ErnieAi::instance()->chat('按政企合规规范起草一份企业数据安全管理条例');
$reply = SparkAi::instance()->chat('生成一段适合语音播报的中文交互提示语');
$reply = DoubaoAi::instance()->chat('批量生成高转化率商品文案');
$reply = HunyuanAi::instance()->chat('结合腾讯云架构设计一套消息队列削峰方案');
$reply = MinimaxAi::instance()->chat('设计一个充满生动个性的智能客服人设对话');
$reply = QwenAi::instance()->chat('生成一段节日营销活动策划');
$reply = ZhipuAi::instance()->chat('将以下英文技术文档翻译为地道的中文');
$reply = OpenaiAi::instance()->chat('分析以下系统异常堆栈日志');

// 多轮上下文对话与高级微调参数
$messages = [
    ['role' => 'system', 'content' => '你是一个资深的 PHP 架构师助手。'],
    ['role' => 'user', 'content' => '你好，请帮我设计一个高并发订单队列处理架构。']
];
$reply = Ai::chat($messages, [
    'temperature' => 0.7,
    'max_tokens'  => 2048,
]);

// 4. 流式输出（实时打字机效果，支持 Generator 迭代或 Callable 回调）
foreach (Ai::stream('请写一篇关于量子计算的短文') as $chunk) {
    echo $chunk; // 实时逐字输出
    ob_flush();
    flush();
}

// 5. 文本向量嵌入（RAG 知识库检索与 pgvector 语义搜索）
$vectorData = Ai::embeddings('ThinkLibrary 统一存储模块使用指南');
// $vectorData['embedding'] 包含 1536 维标准向量浮点数组
// $vectorData['tokens'] 包含向量化所消耗的 Token 数

// 6. 多模态图像识别与理解（Vision）
$analysis = Ai::vision('请分析并描述图片中的商品缺陷', [
    'https://example.com/item1.jpg',
    'public/upload/sample.png', // 自动识别本地文件并 base64 转码
]);

// 7. Token 消耗统计与调用成本估算
$tokens = Ai::estimateTokens($messages); // 估算 Prompt 消耗数量
$cost = Ai::estimateCost('deepseek-chat', 1500, 800);
// 返回: ['total_tokens' => 2300, 'total_cost' => 0.0031 元, 'currency' => 'CNY']
```

**支持的 AI 驱动与配置项对照表：**

| 引擎代码 | 驱动类 | 说明与推荐模型 | 配置参数项 (`sysconf`) |
| :--- | :--- | :--- | :--- |
| `deepseek` | `think\admin\ai\DeepseekAi` | DeepSeek 深度求索 (`deepseek-chat`, `deepseek-reasoner`) | `ai.deepseek_key`, `ai.deepseek_model`, `ai.deepseek_endpoint` |
| `openai` | `think\admin\ai\OpenaiAi` | OpenAI ChatGPT (`gpt-4o`, `gpt-4o-mini`，支持自建代理端点) | `ai.openai_key`, `ai.openai_model`, `ai.openai_endpoint` |
| `claude` | `think\admin\ai\ClaudeAi` | Anthropic Claude (`claude-3-5-sonnet-20241022`, `claude-3-5-haiku-20241022`) | `ai.claude_key`, `ai.claude_model`, `ai.claude_endpoint` |
| `gemini` | `think\admin\ai\GeminiAi` | Google Gemini (`gemini-1.5-flash`, `gemini-1.5-pro` 原生多模态) | `ai.gemini_key`, `ai.gemini_model`, `ai.gemini_endpoint` |
| `qwen` | `think\admin\ai\QwenAi` | 阿里通义千问 (`qwen-plus`, `qwen-turbo`, `qwen-max`) | `ai.qwen_key`, `ai.qwen_model`, `ai.qwen_endpoint` |
| `zhipu` | `think\admin\ai\ZhipuAi` | 智谱清言 GLM (`glm-4-flash`, `glm-4`) | `ai.zhipu_key`, `ai.zhipu_model`, `ai.zhipu_endpoint` |
| `moonshot` | `think\admin\ai\MoonshotAi` | 月之暗面 Kimi (`moonshot-v1-8k`, `moonshot-v1-32k`, `moonshot-v1-128k`) | `ai.moonshot_key`, `ai.moonshot_model`, `ai.moonshot_endpoint` |
| `ernie` | `think\admin\ai\ErnieAi` | 百度文心一言 (`ernie-4.0-turbo-8k`, `ernie-speed-128k` 千帆平台) | `ai.ernie_key`, `ai.ernie_model`, `ai.ernie_endpoint` |
| `spark` | `think\admin\ai\SparkAi` | 科大讯飞星火 (`generalv3.5`, `generalv3`, `lite`) | `ai.spark_key`, `ai.spark_model`, `ai.spark_endpoint` |
| `doubao` | `think\admin\ai\DoubaoAi` | 字节跳动豆包 (`doubao-pro-32k`, 火山方舟 Endpoint ID) | `ai.doubao_key`, `ai.doubao_model`, `ai.doubao_endpoint` |
| `hunyuan` | `think\admin\ai\HunyuanAi` | 腾讯云混元 (`hunyuan-standard`, `hunyuan-turbo`, `hunyuan-lite`) | `ai.hunyuan_key`, `ai.hunyuan_model`, `ai.hunyuan_endpoint` |
| `minimax` | `think\admin\ai\MinimaxAi` | MiniMax 名之梦 (`abab6.5s-chat`, `abab6.5t-chat`, `MiniMax-Text-01`) | `ai.minimax_key`, `ai.minimax_model`, `ai.minimax_endpoint` |

---

### 多渠道事件通知

支持系统事件（如监控报警、审核待办、支付成功、账户异动）向管理员或外部群聊多渠道推送：

```php
use think\admin\facade\Notice;
use think\admin\notice\FeishuNotice;
use think\admin\notice\DingtalkNotice;
use think\admin\notice\QywechatNotice;
use think\admin\notice\WechatNotice;
use think\admin\notice\MailNotice;
use think\admin\notice\SmsNotice;

// 1. 获取支持的所有通知渠道列表
$types = Notice::types(); 
// 输出: ['feishu', 'dingtalk', 'qywechat', 'wechat', 'mail', 'sms']

// 方式一：多渠道广播推送（根据后台已勾选启用的所有渠道批量同时推送）
$result = Notice::push('系统告警', '检测到数据库连接池占满，请立即排查！');

// 方式二：通过门面动态指定渠道推送
Notice::instance('feishu')->send('服务重启通知', '生产服后台任务进程已成功重启完成。');
Notice::instance('dingtalk')->send('新订单提醒', '用户张三刚完成支付，订单号：OD20261014001');

// 方式三：直接调用具体通知类（支持 FeishuNotice::instance() 单调）
FeishuNotice::instance()->send('监控告警', '服务器内存使用率超过 90%');
DingtalkNotice::instance()->send('待办提醒', '收到新的商户进件待审核');
QywechatNotice::instance()->send('工单指派', '技术支持收到加急工单：#1082');
WechatNotice::instance()->send('订单状态更新', '您的订单已完成出库发货');
MailNotice::instance()->send('安全审计周报', '上周管理后台操作审计报表请查收附件。');
SmsNotice::instance()->send('动态验证码', '您的手机动态安全验证码为 889922。');
```

**支持的通知渠道与配置项对照表：**

| 渠道代码 | 驱动类 | 推送形式与特点 | 配置参数项 (`sysconf`) |
| :--- | :--- | :--- | :--- |
| `feishu` | `think\admin\notice\FeishuNotice` | 飞书群自定义机器人 (Webhook + 签名校验) | `notice.feishu_url`, `notice.feishu_secret` |
| `dingtalk` | `think\admin\notice\DingtalkNotice` | 钉钉群自定义机器人 (Webhook + 加签 Secret + @ 手机号) | `notice.dingtalk_url`, `notice.dingtalk_secret`, `notice.dingtalk_mobile` |
| `qywechat` | `think\admin\notice\QywechatNotice` | 企业微信群机器人 (Webhook + 提醒成员手机号 / `@all`) | `notice.qywechat_url`, `notice.qywechat_mobile` |
| `wechat` | `think\admin\notice\WechatNotice` | 微信公众号服务号模板消息 (直达管理员个人微信) | `notice.wechat_appid`, `notice.wechat_secret`, `notice.wechat_tpl`, `notice.wechat_openid` |
| `mail` | `think\admin\notice\MailNotice` | 邮件标准 SMTP 发送 (支持 SSL/TLS 安全加密通道) | `notice.mail_host`, `notice.mail_port`, `notice.mail_user`, `notice.mail_pass`, `notice.mail_to` |
| `sms` | `think\admin\notice\SmsNotice` | 手机短信通知 (自动联动底层选定的短信引擎) | `notice.sms_to` |

---

### 多厂商短信引擎

深度整合国内 8 大主流短信服务商，统一抽象发送标准与模板变量机制，支持三种调用方式：

```php
use think\admin\facade\Sms;
use think\admin\sms\AliyunSms;
use think\admin\sms\TencentSms;
use think\admin\sms\HuaweiSms;
use think\admin\sms\BaiduSms;
use think\admin\sms\QiniuSms;
use think\admin\sms\Cl253Sms;
use think\admin\sms\ZtSms;
use think\admin\sms\CloopenSms;

// 1. 检查当前短信引擎是否已完成配置
if (Sms::ready()) {
    // 方式一：使用后台当前选中的默认短信服务商下发
    // 参数直接传模板变量数组，模板 ID 默认读取后台配置
    $result = Sms::send('13800138000', '', ['code' => '889922']);
}

// 方式二：通过统一门面动态指定具体服务商下发
Sms::instance('aliyun')->send('13800138000', 'SMS_123456789', ['code' => '889922']);
Sms::instance('tencent')->send('13800138000', '123456', ['889922', '5']);

// 方式三：直接调用具体服务商类实例（支持 AliyunSms::instance() 单独调用）
$aliyun = AliyunSms::instance();
$aliyun->send('13800138000', 'SMS_123456789', [
    'code' => '889922'
]);

$tencent = TencentSms::instance();
$tencent->send('13800138000', '123456', ['889922', '5']);

$huawei = HuaweiSms::instance();
$huawei->send('13800138000', 'template_id_xxx', ['889922']);

$baidu = BaiduSms::instance();
$baidu->send('13800138000', 'sms-tmpl-xxx', ['code' => '889922']);

$qiniu = QiniuSms::instance();
$qiniu->send('13800138000', 'template_id_xxx', ['code' => '889922']);

$cl253 = Cl253Sms::instance();
$cl253->send('13800138000', '', ['code' => '889922']);

$ztsms = ZtSms::instance();
$ztsms->send('13800138000', '', ['code' => '889922']);

$cloopen = CloopenSms::instance();
$cloopen->send('13800138000', 'template_id_xxx', ['889922', '5']);
```

**支持的短信服务商驱动与配置项对照表：**

| 服务商标识 | 驱动类 | 服务商名称 | 特性与配置项 (`sysconf`) |
| :--- | :--- | :--- | :--- |
| `aliyun` | `think\admin\sms\AliyunSms` | 阿里云短信 (Dysmsapi) | `sms.aliyun_key`, `sms.aliyun_secret`, `sms.aliyun_sign`, `sms.aliyun_tpl` |
| `tencent` | `think\admin\sms\TencentSms` | 腾讯云短信 (SMS 3.0) | `sms.tencent_appid`, `sms.tencent_key`, `sms.tencent_secret`, `sms.tencent_sign`, `sms.tencent_tpl` |
| `huawei` | `think\admin\sms\HuaweiSms` | 华为云短信 (MSGSMS) | `sms.huawei_key`, `sms.huawei_secret`, `sms.huawei_endpoint`, `sms.huawei_channel`, `sms.huawei_sign`, `sms.huawei_tpl` |
| `baidu` | `think\admin\sms\BaiduSms` | 百度智能云短信 (BCE v3) | `sms.baidu_key`, `sms.baidu_secret`, `sms.baidu_sign`, `sms.baidu_tpl` |
| `qiniu` | `think\admin\sms\QiniuSms` | 七牛云短信 (RESTful Token) | `sms.qiniu_key`, `sms.qiniu_secret`, `sms.qiniu_tpl` |
| `cl253` | `think\admin\sms\Cl253Sms` | 创蓝253短信 (直连三网验证码) | `sms.cl253_account`, `sms.cl253_password`, `sms.cl253_sign`, `sms.cl253_tpl` |
| `ztsms` | `think\admin\sms\ZtSms` | 助通科技短信 (高到达率通道) | `sms.ztsms_account`, `sms.ztsms_password`, `sms.ztsms_sign`, `sms.ztsms_tpl` |
| `cloopen` | `think\admin\sms\CloopenSms` | 容联云通讯短信 (电信级通道) | `sms.cloopen_sid`, `sms.cloopen_token`, `sms.cloopen_appid`, `sms.cloopen_tpl` |

---

### 统一支付网关 (Payment)

系统内置企业级统一支付网关模块，统一微信支付 (V3)、支付宝开放平台、PayPal 国际支付与 Stripe 聚合支付四大主流渠道，支持统一门面调度、动态指定渠道及具体驱动类直接调用三种模式：

```php
use think\admin\facade\Payment;
use think\admin\payment\WechatPayment;
use think\admin\payment\AlipayPayment;
use think\admin\payment\PaypalPayment;
use think\admin\payment\StripePayment;

// 1. 获取支持的所有支付渠道
$types = Payment::types();
// ['wechat' => '微信支付', 'alipay' => '支付宝支付', 'paypal' => 'PayPal 国际支付', 'stripe' => 'Stripe 聚合支付']

// 2. 统一订单支付（使用后台默认选中的支付渠道）
$order = [
    'order_id'   => 'OD' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'title'      => 'VIP会员年费订阅',                         // 商品标题
    'amount'     => 199.00,                                   // 支付金额（元）
    'channel'    => 'native',                                 // 支付通道：native (扫码), wap/h5 (手机网页), web (PC), jsapi (公众号/小程序), app
    'openid'     => 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o',          // 微信 JSAPI 必传
    'return_url' => 'https://yourdomain.com/pay/return',      // 同步跳转页
    'notify_url' => 'https://yourdomain.com/pay/notify',      // 异步回调地址
];
$res = Payment::pay($order);
// Native/扫码：$res['qr_code'] 二维码链接
// Web/WAP/H5：$res['pay_url'] 收银台跳转 URL
// JSAPI/App：$res['pay_data'] 前端调起控件所需完整参数包

// 3. 动态指定具体支付渠道发起支付
$alipayRes = Payment::instance('alipay')->pay($order);
$paypalRes = Payment::instance('paypal')->pay($order);
$stripeRes = Payment::instance('stripe')->pay($order);

// 4. 直接调用具体支付类（支持驱动单调）
$wechat = WechatPayment::instance();
$result = $wechat->pay($order);

// 5. 查询订单支付状态
$status = Payment::query('OD202610140001');
if ($status['paid']) {
    echo "支付成功，第三方流水号：" . $status['trade_no'];
}

// 6. 申请订单退款
$refund = Payment::refund('OD202610140001', 99.00, [
    'reason' => '协商退款',
]);

// 7. 异步通知验签与解析
$notify = Payment::notify();
if ($notify['success']) {
    $orderId = $notify['order_id'];
    $tradeNo = $notify['trade_no'];
    $amount  = $notify['amount'];
    // 执行业务入账与修改订单状态...
}
```

**支持的支付服务商与配置项对照表：**

| 服务商标识 | 驱动类 | 支持支付方式与能力 | 配置参数项 (`sysconf`) |
| :--- | :--- | :--- | :--- |
| `wechat` | `think\admin\payment\WechatPayment` | 微信支付 V3 API (Native 扫码、JSAPI 公众号/小程序、H5 网页、App 支付，AEAD_AES_256_GCM 自动解密) | `payment.wechat_mch_id`, `payment.wechat_appid`, `payment.wechat_api_v3_key`, `payment.wechat_serial_no`, `payment.wechat_private_key`, `payment.wechat_notify_url` |
| `alipay` | `think\admin\payment\AlipayPayment` | 支付宝开放平台 OpenAPI (电脑网站支付、手机网站支付、当面付预下单二维码、App 支付，RSA2 签名与验签，支持沙箱环境) | `payment.alipay_appid`, `payment.alipay_private_key`, `payment.alipay_public_key`, `payment.alipay_sandbox`, `payment.alipay_return_url`, `payment.alipay_notify_url` |
| `paypal` | `think\admin\payment\PaypalPayment` | PayPal 国际支付 (REST API v2 Orders API 创建订单、Capture 扣款、Refund 退款，Sandbox 沙箱联调) | `payment.paypal_client_id`, `payment.paypal_secret`, `payment.paypal_mode`, `payment.paypal_currency`, `payment.paypal_return_url`, `payment.paypal_cancel_url` |
| `stripe` | `think\admin\payment\StripePayment` | Stripe 聚合支付 (Payment Intents 现代接口，支持全球信用卡、Apple Pay、Google Pay、Webhooks 自动验签) | `payment.stripe_publishable_key`, `payment.stripe_secret_key`, `payment.stripe_webhook_secret`, `payment.stripe_currency` |

---

### 小程序码统一生成 (Qrcode)

系统提供微信与 QQ 小程序码的服务端统一生成与落盘能力，统一通过 `Qrcode` 门面调度，支持无数量限制的小程序码生成、指定模式/颜色定制、二进制 MIME 自动嗅探、通过 `Storage` 对象存储持久化以及生成前端直接可用的 Data URI：

```php
use think\admin\facade\Qrcode;
use think\admin\exception\QrcodeException;

// 1. 获取支持的小程序码平台
$types = Qrcode::types();
// ['wechat' => '微信小程序码', 'qq' => 'QQ小程序码']

// 2. 默认驱动生成无数量限制的小程序码（unlimited 模式，scene 传参）
try {
    $res = Qrcode::create('pages/goods/detail', ['id' => 1024], [
        'width' => 430,
    ]);
    // 返回格式：['code' => 0, 'message' => 'success', 'content' => '二进制图片', 'url' => '', 'mime' => 'image/jpeg', 'raw' => [...]]

    // 3. 落盘保存并上传到当前存储引擎（如阿里云 OSS、本地存储等），返回访问 URL
    $imageUrl = Qrcode::save($res, 'qrcode/goods-1024.png');

    // 4. 或直接转换为前端可嵌入显示的 Data URI (data:image/jpeg;base64,...)
    $dataUri = Qrcode::dataUri($res);

} catch (QrcodeException $e) {
    // 微信错误码保留在 getCode()，例如 41030 页面不存在，45029 超过数量上限
    echo "小程序码生成失败: [{$e->getCode()}] " . $e->getMessage();
}

// 5. 指定平台与高级模式调用
// 微信限量普通小程序码 (Path 模式，支持 10 万个)
$resLimited = Qrcode::instance('wechat')->create('pages/index/index', ['from' => 'share'], [
    'mode'        => 'limited',
    'width'       => 600,
    'auto_color'  => false,
    'line_color'  => ['r' => 255, 'g' => 0, 'b' => 0], // 自定义主线条颜色
    'is_hyaline'  => true,                              // 背景透明
]);

// QQ 小程序码生成
$qqRes = Qrcode::instance('qq')->create('pages/index/index', ['tag' => 'activity']);
$qqUrl = Qrcode::save($qqRes);
```

**支持的小程序码服务平台与配置项对照表：**

| 平台标识 | 驱动类 | 支持模式与特性 | 配置参数项 (`sysconf` / 环境变量) |
| :--- | :--- | :--- | :--- |
| `wechat` | `think\admin\qrcode\WechatQrcode` | 无限量码 (`unlimited`)、限量码 (`limited`)、标准二维码 (`default`)，支持颜色与透明背景定制 | `qrcode.wechat_appid`, `qrcode.wechat_secret` / `QRCODE_WECHAT_APPID`, `QRCODE_WECHAT_SECRET` |
| `qq` | `think\admin\qrcode\QqQrcode` | 基于官方 CreateMiniCode 接口，支持带参页面、Token 自动缓存与失效刷新，内置 SSL 证书验证 | `qrcode.qq_appid`, `qrcode.qq_secret` / `QRCODE_QQ_APPID`, `QRCODE_QQ_SECRET` |

---

### 架构增强与设计规范

#### 1. 配置管理增强（环境变量自动回退）

所有服务引擎（AI、Sms、Notice、Payment、Storage）的 `config(string $name)` 均全面支持 **配置中心与环境变量双通道回退**：
- **优先级**：数据库配置中心 `sysconf("{module}.{$name}|raw")` > `.env` 文件变量 `Library::$sapp->env->get("{MODULE}_{NAME}")` > 系统环境变量 `getenv("{MODULE}_{NAME}")` > 空字符串 `''`。
- **开发与部署收益**：本地与容器化（Docker / K8s / CI/CD）部署时无需手动往数据库灌入密钥，直接在 `.env` 或环境变量注入即可生效，支持生产环境敏感密钥无落盘管理。

```env
# 示例：通过 .env 配置 AI、短信、通知、支付与存储
AI_TYPE=deepseek
AI_DEEPSEEK_KEY=sk-xxxxxx

SMS_TYPE=aliyun
SMS_ALIYUN_KEY=LTAI5txxxxx
SMS_ALIYUN_SECRET=xxxxxx
SMS_ALIYUN_SIGN=登录验证
SMS_ALIYUN_TPL=SMS_123456

NOTICE_ENABLE=qywechat,feishu
NOTICE_QYWECHAT_URL=https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=xxxx

PAYMENT_TYPE=wechat
PAYMENT_WECHAT_MCH_ID=190000xxxx
PAYMENT_WECHAT_API_V3_KEY=xxxxxx

STORAGE_TYPE=txcos
STORAGE_TXCOS_BUCKET=my-bucket
```

#### 2. 异常处理统一（专用领域异常类）

各业务模块全面摒弃原有的通用 `\Exception` 抛出，统一派生自 `think\admin\base\Exception`，实现精准的领域异常拦截与业务自愈：

```php
use think\admin\exception\AiException;
use think\admin\exception\NoticeException;
use think\admin\exception\PaymentException;
use think\admin\exception\QrcodeException;
use think\admin\exception\SmsException;
use think\admin\exception\StorageException;

// 1. 拦截 AI 对话请求异常
try {
    $reply = Ai::chat('请介绍一下 ThinkLibrary');
} catch (AiException $e) {
    // 专用 AI 异常捕获（如模型鉴权失败、额度用尽、超时）
    echo "AI 调度异常：" . $e->getMessage();
}

// 2. 拦截短信下发异常
try {
    Sms::send('13800000000', 'SMS_123456', ['code' => '8888']);
} catch (SmsException $e) {
    echo "短信通道发送异常：" . $e->getMessage();
}

// 3. 拦截支付网关异常
try {
    Payment::pay($order);
} catch (PaymentException $e) {
    echo "支付通道发起异常：" . $e->getMessage();
}
```

*注：所有专用异常类均直接继承自 `think\admin\base\Exception`（底层继承 PHP `\Exception`），原有按 `\Exception` 或 `\Throwable` 捕获的旧业务代码 100% 完全向上兼容。*

#### 3. 驱动统一审计日志（logRequest）

所有驱动 Trait（`AiUsageTrait`、`SmsUsageTrait`、`NoticeUsageTrait`、`PaymentUsageTrait`、`StorageUsageTrait`）内置统一审计与链路追踪方法 `logRequest()`：
- **双通道记录**：
  1. **系统操作日志表**：自动调用 `sysoplog("{module}.{action}", ...)` 写入 PostgreSQL `system_oplog` 数据表，后台管理员可直接在「系统运维 -> 操作日志」中进行可视化检索与审计。
  2. **框架运行时日志**：自动调用 `Library::$sapp->log->record(...)` 输出至 `runtime/log/` 文件，方便运维通过 ELK / Promtail 进行集中式日志采集。

```php
// Trait 中内置的标准日志追踪规范
protected function logRequest(string $driver, string $action, array $data = []): void
{
    $content = json_encode($data, JSON_UNESCAPED_UNICODE);
    if (function_exists('sysoplog')) {
        sysoplog("{$module}.{$action}", "[{$driver}] {$content}");
    }
    if (isset(Library::$sapp->log)) {
        Library::$sapp->log->record("[{$module}.{$driver}.{$action}] {$content}", 'info');
    }
}
```

---

### 数据加密

**自研 UTF8 加密**

```php
// 自研 UTF8 字符串加密操作
$string = encode($content);

// 自研 UTF8 加密字符串解密
$content = decode($string);
```

**数据解密**

```php
use think\admin\extend\CodeExtend;

// 数据 AES-256-CBC 对称加密
$encrypt = CodeExtend::encrypt($content, $enckey);

// 数据 AES-256-CBC 对称解密
$content = CodeExtend::decrypt($encrypt, $enckey);
```

**文本转 UTF8 编码**

```php
use think\admin\extend\CodeExtend;

// 文本转 UTF8 编码
$content = CodeExtend::text2utf8($content)
```

**文本 Base64 URL 编码**

```php
use think\admin\extend\CodeExtend;

// 文本 Base64 URL 编码
$safe64 = CodeExtend::enSafe64($content)

// 文本 Base64 URL 解码
$content = CodeExtend::deSafe64($safe64)
```

### 数据压缩处理

```php
use think\admin\extend\CodeExtend;

// 数据压缩 ( 内容越大效果越好 )
$enzip = CodeExtend::enzip($content)

// 数据解压 ( 内容越大效果越好 )
$content = CodeExtend::dezip($enzip)
```

### 数组结构处理

```php
use think\admin\extend\CodeExtend;

// 二维数组 转为 立体数据结构，需要存在 id 及 pid 关系
$tree = CodeExtend::arr2tree($list);

// 二维数组 转为 扁平数据结构，需要存在 id 及 pid 关系
$tree = CodeExtend::arr2table($list);
