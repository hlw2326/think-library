<?php

declare(strict_types=1);

namespace think\admin\extend;

/**
 * 常用正则校验与文本处理扩展
 * @class RegexExtend
 * @package think\admin\extend
 */
class RegexExtend
{
    /**
     * 常用正则表达式常量
     */
    public const MOBILE = '/^1[3-9]\d{9}$/';
    public const TEL = '/^(0\d{2,3}-?)?\d{7,8}$/';
    public const EMAIL = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    public const IDCARD = '/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/';
    public const URL = '/^https?:\/\/[^\s\/$.?#].[^\s]*$/i';
    public const IPV4 = '/^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/';
    public const IPV6 = '/^([\da-fA-F]{1,4}:){7}[\da-fA-F]{1,4}$/';
    public const DATE = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';
    public const DATETIME = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\s([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/';
    public const TIME = '/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/';
    public const NUMBER = '/^\d+$/';
    public const INTEGER = '/^-?\d+$/';
    public const DECIMAL = '/^-?\d+(\.\d+)?$/';
    public const CURRENCY = '/^-?\d+(\.\d{1,2})?$/';
    public const ZIPCODE = '/^\d{6}$/';
    public const CHINESE = '/^[\x{4e00}-\x{9fa5}]+$/u';
    public const USERNAME = '/^[a-zA-Z][a-zA-Z0-9_]{2,19}$/';
    public const PASSWORD = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\W]{8,32}$/';
    public const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    public const DOMAIN = '/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/';
    public const WX_OPENID = '/^[a-zA-Z0-9_-]{28}$/';
    public const PLATE_NUMBER = '/^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领][A-HJ-NP-Z](?:[A-HJ-NP-Z0-9]{4,5}[A-HJ-NP-Z0-9挂学警港澳]|[DF][A-HJ-NP-Z0-9]\d{4}|\d{5}[DF])$/u';
    public const CREDIT_CODE = '/^[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}$/';
    public const BANKCARD = '/^\d{16,19}$/';

    /**
     * 自定义正则匹配
     * @param string $pattern 正则表达式
     * @param string $value 待检测字符串
     * @return bool
     */
    public static function is(string $pattern, string $value): bool
    {
        return preg_match($pattern, $value) === 1;
    }

    /**
     * 验证是否为有效手机号（大陆11位手机号）
     * @param string $value 手机号
     * @return bool
     */
    public static function isMobile(string $value): bool
    {
        return static::is(self::MOBILE, $value);
    }

    /**
     * 验证是否为有效座机号码
     * @param string $value 座机号
     * @return bool
     */
    public static function isTel(string $value): bool
    {
        return static::is(self::TEL, $value);
    }

    /**
     * 验证是否为有效电子邮箱
     * @param string $value 邮箱地址
     * @return bool
     */
    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false || static::is(self::EMAIL, $value);
    }

    /**
     * 验证是否为有效二代身份证（支持末位 ISO 7064:1983.MOD 11-2 校验码严谨验证）
     * @param string $value 身份证号码
     * @param bool $strict 是否启用校验码严格验证
     * @return bool
     */
    public static function isIdCard(string $value, bool $strict = true): bool
    {
        $value = strtoupper(trim($value));
        if (!static::is(self::IDCARD, $value)) {
            return false;
        }
        if (!$strict) {
            return true;
        }

        // 校验出生日期有效性
        $birth = substr($value, 6, 8);
        $time = strtotime($birth);
        if ($time === false || date('Ymd', $time) !== $birth) {
            return false;
        }

        // ISO 7064:1983.MOD 11-2 校验码加权运算
        $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $codes = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += intval($value[$i]) * $weights[$i];
        }
        return $codes[$sum % 11] === $value[17];
    }

    /**
     * 验证是否为 HTTP/HTTPS 链接
     * @param string $value 网址
     * @return bool
     */
    public static function isUrl(string $value): bool
    {
        if (static::is(self::URL, $value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 验证是否以 http:// 或 https:// 协议开头
     * @param string $value 网址
     * @return bool
     */
    public static function isHttp(string $value): bool
    {
        $lower = strtolower(trim($value));
        return str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://');
    }

    /**
     * 验证是否为有效 IP 地址（IPv4 或 IPv6）
     * @param string $value IP地址
     * @return bool
     */
    public static function isIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 验证是否为有效 IPv4 地址
     * @param string $value IPv4地址
     * @return bool
     */
    public static function isIpv4(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * 验证是否为有效 IPv6 地址
     * @param string $value IPv6地址
     * @return bool
     */
    public static function isIpv6(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * 验证是否为标准日期（YYYY-MM-DD）
     * @param string $value 日期字符串
     * @return bool
     */
    public static function isDate(string $value): bool
    {
        if (!static::is(self::DATE, $value)) {
            return false;
        }
        $time = strtotime($value);
        return $time !== false && date('Y-m-d', $time) === $value;
    }

    /**
     * 验证是否为标准日期时间（YYYY-MM-DD HH:ii:ss）
     * @param string $value 日期时间字符串
     * @return bool
     */
    public static function isDateTime(string $value): bool
    {
        if (!static::is(self::DATETIME, $value)) {
            return false;
        }
        $time = strtotime($value);
        return $time !== false && date('Y-m-d H:i:s', $time) === $value;
    }

    /**
     * 验证是否为时间（HH:ii:ss）
     * @param string $value 时间字符串
     * @return bool
     */
    public static function isTime(string $value): bool
    {
        return static::is(self::TIME, $value);
    }

    /**
     * 验证是否为纯数字
     * @param string $value 字符串
     * @return bool
     */
    public static function isNumber(string $value): bool
    {
        return static::is(self::NUMBER, $value);
    }

    /**
     * 验证是否为整数
     * @param string $value 字符串
     * @return bool
     */
    public static function isInteger(string $value): bool
    {
        return static::is(self::INTEGER, $value);
    }

    /**
     * 验证是否为小数/浮点数
     * @param string $value 字符串
     * @return bool
     */
    public static function isDecimal(string $value): bool
    {
        return static::is(self::DECIMAL, $value);
    }

    /**
     * 验证是否为金额（支持最多两位小数）
     * @param string $value 金额字符串
     * @return bool
     */
    public static function isCurrency(string $value): bool
    {
        return static::is(self::CURRENCY, $value);
    }

    /**
     * 验证是否为纯中文汉字
     * @param string $value 字符串
     * @return bool
     */
    public static function isChinese(string $value): bool
    {
        return static::is(self::CHINESE, $value);
    }

    /**
     * 验证是否为合法账号用户名（以英文字母开头，3-20位英数字或下划线）
     * @param string $value 用户名
     * @return bool
     */
    public static function isUsername(string $value): bool
    {
        return static::is(self::USERNAME, $value);
    }

    /**
     * 验证是否为强密码（8-32位，同时包含大小写字母和数字）
     * @param string $value 密码
     * @return bool
     */
    public static function isPassword(string $value): bool
    {
        return static::is(self::PASSWORD, $value);
    }

    /**
     * 验证是否为合法 UUID (v1-v7)
     * @param string $value UUID字符串
     * @return bool
     */
    public static function isUuid(string $value): bool
    {
        return static::is(self::UUID, $value);
    }

    /**
     * 验证是否为域名
     * @param string $value 域名
     * @return bool
     */
    public static function isDomain(string $value): bool
    {
        return static::is(self::DOMAIN, $value);
    }

    /**
     * 验证是否为微信 OpenId 格式
     * @param string $value OpenId
     * @return bool
     */
    public static function isWxOpenid(string $value): bool
    {
        return static::is(self::WX_OPENID, $value);
    }

    /**
     * 正则捕获首个匹配项
     * @param string $pattern 正则表达式
     * @param string $subject 目标字符串
     * @return array|null 匹配结果，未匹配返回 null
     */
    public static function match(string $pattern, string $subject): ?array
    {
        if (preg_match($pattern, $subject, $matches)) {
            return $matches;
        }
        return null;
    }

    /**
     * 正则捕获所有匹配项
     * @param string $pattern 正则表达式
     * @param string $subject 目标字符串
     * @return array 所有匹配结果
     */
    public static function matchAll(string $pattern, string $subject): array
    {
        if (preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER)) {
            return $matches;
        }
        return [];
    }

    /**
     * 手机号脱敏（如：138****8888）
     * @param string $mobile 手机号
     * @return string
     */
    public static function maskMobile(string $mobile): string
    {
        return preg_replace('/^(\d{3})\d{4}(\d{4})$/', '$1****$2', $mobile);
    }

    /**
     * 身份证脱敏（如：110101********1234）
     * @param string $idcard 身份证号
     * @return string
     */
    public static function maskIdCard(string $idcard): string
    {
        return preg_replace('/^(\d{6})\d{8}(\w{4})$/', '$1********$2', $idcard);
    }

    /**
     * 邮箱脱敏（如：a***@example.com）
     * @param string $email 邮箱地址
     * @return string
     */
    public static function maskEmail(string $email): string
    {
        return preg_replace_callback('/^(.)(.*)(@.*)$/', function ($matches) {
            return $matches[1] . str_repeat('*', max(3, strlen($matches[2]))) . $matches[3];
        }, $email);
    }

    /**
     * 中文姓名脱敏（如：张*、李*华）
     * @param string $name 姓名
     * @return string
     */
    public static function maskName(string $name): string
    {
        $len = mb_strlen($name, 'utf-8');
        if ($len <= 1) {
            return $name;
        }
        if ($len === 2) {
            return mb_substr($name, 0, 1, 'utf-8') . '*';
        }
        return mb_substr($name, 0, 1, 'utf-8') . str_repeat('*', $len - 2) . mb_substr($name, -1, 1, 'utf-8');
    }

    /**
     * 验证是否为有效车牌号码（含新能源与普通民用车牌）
     * @param string $value 车牌号
     * @return bool
     */
    public static function isPlateNumber(string $value): bool
    {
        return static::is(self::PLATE_NUMBER, $value);
    }

    /**
     * 验证是否为统一社会信用代码（18位，支持 GB 32100-2015 校验码严格校验）
     * @param string $value 统一社会信用代码
     * @param bool $strict 是否验证校验码
     * @return bool
     */
    public static function isCreditCode(string $value, bool $strict = true): bool
    {
        $value = strtoupper(trim($value));
        if (!static::is(self::CREDIT_CODE, $value)) {
            return false;
        }
        if (!$strict) {
            return true;
        }
        $chars = '0123456789ABCDEFGHJKLMNPQRTUWXY';
        $weights = [1, 3, 9, 27, 19, 26, 16, 17, 20, 29, 25, 13, 8, 24, 10, 30, 28];
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $pos = strpos($chars, $value[$i]);
            if ($pos === false) {
                return false;
            }
            $sum += $pos * $weights[$i];
        }
        $mod = 31 - ($sum % 31);
        if ($mod === 31) {
            $mod = 0;
        }
        return $chars[$mod] === $value[17];
    }

    /**
     * 验证是否为有效银行卡号（16-19位数字，支持标准 Luhn 模10校验）
     * @param string $value 银行卡号
     * @param bool $strict 是否执行 Luhn 校验
     * @return bool
     */
    public static function isBankCard(string $value, bool $strict = true): bool
    {
        $value = trim($value);
        if (!static::is(self::BANKCARD, $value)) {
            return false;
        }
        if (!$strict) {
            return true;
        }
        $sum = 0;
        $len = strlen($value);
        $shouldDouble = false;
        for ($i = $len - 1; $i >= 0; $i--) {
            $digit = intval($value[$i]);
            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }
        return $sum % 10 === 0;
    }

    /**
     * 银行卡号脱敏（如：622202******1234）
     * @param string $card 银行卡号
     * @return string
     */
    public static function maskBankCard(string $card): string
    {
        $card = trim($card);
        $len = strlen($card);
        if ($len < 8) {
            return $card;
        }
        return substr($card, 0, 6) . str_repeat('*', $len - 10) . substr($card, -4);
    }

    /**
     * 从文本中提取所有手机号（去重）
     * @param string $text 文本内容
     * @return string[]
     */
    public static function extractMobiles(string $text): array
    {
        if (preg_match_all('/(?<!\d)(1[3-9]\d{9})(?!\d)/', $text, $matches)) {
            return array_values(array_unique($matches[1]));
        }
        return [];
    }

    /**
     * 从文本中提取所有邮箱（去重）
     * @param string $text 文本内容
     * @return string[]
     */
    public static function extractEmails(string $text): array
    {
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return array_values(array_unique($matches[0]));
        }
        return [];
    }

    /**
     * 从文本中提取所有网址链接（显式支持 http:// 与 https://，自动去重并剔除尾部中英标点）
     * @param string $text 文本内容
     * @return string[]
     */
    public static function extractUrls(string $text): array
    {
        if (preg_match_all('/(?:https?|http):\/\/[^\s\x{4e00}-\x{9fa5}<>"\'`，。！？；、（）\(\)\[\]]+/iu', $text, $matches)) {
            $urls = [];
            foreach ($matches[0] as $url) {
                $cleaned = rtrim($url, '.,;:!?');
                if ($cleaned !== '') {
                    $urls[] = $cleaned;
                }
            }
            return array_values(array_unique($urls));
        }
        return [];
    }
}
