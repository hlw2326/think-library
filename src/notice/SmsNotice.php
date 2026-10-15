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

namespace think\admin\notice;

use think\admin\contract\NoticeInterface;
use think\admin\contract\NoticeUsageTrait;
use think\admin\exception\NoticeException;
use think\admin\facade\Notice;
use think\admin\facade\Sms;

/**
 * 手机短信通知，委托 Sms 引擎按 sms.type 选择服务商.
 * 额外配置 notice.sms_to 默认手机号，多个用逗号分隔.
 * @class SmsNotice
 */
class SmsNotice implements NoticeInterface
{
    use NoticeUsageTrait;

    /**
     * 发送短信，模板变量为 title 与 content，模板使用服务商默认模板.
     * @param string $title 标题
     * @param string $content 内容
     * @param string $to 手机号
     * @param string|int $priority 优先级
     * @param array $options 额外参数
     * @return array
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array
    {
        if ($to === '') {
            $to = Notice::config('sms_to');
        }
        $formattedTitle = $this->formatTitleByPriority($title, $priority);
        $result = [];
        foreach (array_filter(array_map('trim', explode(',', $to))) as $mobile) {
            $result[$mobile] = Sms::instance()->send($mobile, '', [
                'title'   => $formattedTitle,
                'content' => $content,
            ]);
        }
        if (empty($result)) {
            throw new NoticeException('请先配置短信接收手机号！');
        }

        $this->logRequest('sms', 'send', ['title' => $title, 'to' => $to, 'priority' => $priority, 'success' => true]);
        return $result;
    }
}
