<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$ticketNoticeRules = [
    1 => [
        'title' => 'DNS解析提醒',
        'items' => [
            'DNS修改后可能需要时间同步（通常数分钟到48小时）',
            '请提供完整域名（例如：example.com）',
            '若使用 Cloudflare，请先关闭代理（橙云）测试',
        ],
        'warning' => '信息不完整会导致处理时间延长。',
    ],
    2 => [
        'title' => 'Abuse 举报提醒',
        'items' => [
            '请提供完整 URL（包含协议）',
            '请上传截图证据',
            '请描述违规原因与影响范围',
        ],
        'warning' => '无证据或描述不清晰将无法快速受理。',
    ],
    3 => [
        'title' => 'VPS 技术支持提醒',
        'items' => [
            '请提供服务器 IP',
            '请提供报错截图或错误日志',
            '请说明复现步骤与预期结果',
        ],
        'warning' => '缺少关键信息可能导致需要反复沟通。',
    ],
];

add_hook('ClientAreaFooterOutput', 1, function ($vars) use ($ticketNoticeRules) {
    $filename = isset($vars['filename']) ? $vars['filename'] : '';
    if ($filename !== 'submitticket') {
        return '';
    }

    $rulesJson = json_encode($ticketNoticeRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($rulesJson === false) {
        return '';
    }

    $moduleWebPath = 'modules/addons/ticket_notice';

    $html = [];
    $html[] = '<link rel="stylesheet" href="' . $moduleWebPath . '/assets/css/ticket_notice.css?v=1.0.0">';
    $html[] = '<script>window.ticketNoticeRules = ' . $rulesJson . ';</script>';

    $modalTpl = __DIR__ . '/templates/modal.tpl';
    if (is_file($modalTpl)) {
        $html[] = file_get_contents($modalTpl);
    }

    $html[] = '<script src="' . $moduleWebPath . '/assets/js/ticket_notice.js?v=1.0.0"></script>';

    return implode(PHP_EOL, $html);
});

add_hook('TicketOpenValidation', 1, function ($vars) use ($ticketNoticeRules) {
    $deptId = isset($_POST['deptid']) ? (int) $_POST['deptid'] : 0;

    if (!isset($ticketNoticeRules[$deptId])) {
        return [];
    }

    $confirmed = isset($_POST['ticket_notice_confirmed']) ? (int) $_POST['ticket_notice_confirmed'] : 0;
    if ($confirmed !== 1) {
        return ['请先阅读并确认工单提交提醒后再提交工单。'];
    }

    return [];
});
