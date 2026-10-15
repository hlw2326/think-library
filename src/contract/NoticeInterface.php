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

namespace think\admin\contract;

use think\admin\exception\NoticeException;

/**
 * 通知渠道驱动接口.
 * @class NoticeInterface
 */
interface NoticeInterface
{
    /**
     * 发送普通文本通知.
     * @param string $title 通知标题
     * @param string $content 通知正文
     * @param string $to 接收人，为空时使用渠道默认接收人
     * @param string|int $priority 优先级：low, normal, high, urgent
     * @param array $options 扩展配置选项
     * @return array 渠道原始响应
     * @throws NoticeException
     */
    public function send(string $title, string $content, string $to = '', string|int $priority = 'normal', array $options = []): array;

    /**
     * 发送卡片通知（带标题、正文描述与操作跳转按钮）.
     * @param string $title 卡片标题
     * @param string $content 卡片描述内容
     * @param string $url 动作跳转链接
     * @param string $btnText 按钮文字
     * @param string $to 接收人
     * @param string|int $priority 优先级：low, normal, high, urgent
     * @param array $options 扩展配置选项
     * @return array 渠道原始响应
     * @throws NoticeException
     */
    public function sendCard(string $title, string $content, string $url = '', string $btnText = '查看详情', string $to = '', string|int $priority = 'normal', array $options = []): array;

    /**
     * 发送图片通知.
     * @param string $image 图片 URL 或本地文件路径
     * @param string $title 图片标题说明
     * @param string $to 接收人
     * @param string|int $priority 优先级：low, normal, high, urgent
     * @param array $options 扩展配置选项
     * @return array 渠道原始响应
     * @throws NoticeException
     */
    public function sendImage(string $image, string $title = '', string $to = '', string|int $priority = 'normal', array $options = []): array;

    /**
     * 发送文件推送.
     * @param string $file 文件 URL 或本地文件路径
     * @param string $filename 显示文件名
     * @param string $to 接收人
     * @param string|int $priority 优先级：low, normal, high, urgent
     * @param array $options 扩展配置选项
     * @return array 渠道原始响应
     * @throws NoticeException
     */
    public function sendFile(string $file, string $filename = '', string $to = '', string|int $priority = 'normal', array $options = []): array;
}
