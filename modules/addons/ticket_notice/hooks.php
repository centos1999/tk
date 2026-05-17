<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!function_exists('ticket_notice_default_rules_zh')) {
    require_once __DIR__ . '/ticket_notice.php';
}

if (!function_exists('ticket_notice_load_settings')) {
function ticket_notice_load_settings()
{
    $settings = [
        'enabled' => 'on',
        'rules_json_zh' => '',
        'rules_json_en' => '',
        'duplicate_enabled' => 'on',
        'duplicate_hours' => '24',
        'keyword_rules_zh' => '',
        'keyword_rules_en' => '',
    ];

    try {
        $rows = Capsule::table('tbladdonmodules')
            ->where('module', 'ticket_notice')
            ->whereIn('setting', ['enabled', 'rules_json_zh', 'rules_json_en', 'duplicate_enabled', 'duplicate_hours', 'keyword_rules_zh', 'keyword_rules_en'])
            ->get(['setting', 'value']);

        foreach ($rows as $row) {
            $settings[$row->setting] = (string) $row->value;
        }
    } catch (\Exception $e) {
    }

    return $settings;
}
}

if (!function_exists('ticket_notice_detect_lang')) {
function ticket_notice_detect_lang($vars)
{
    $lang = '';
    if (isset($vars['language'])) {
        $lang = strtolower((string) $vars['language']);
    } elseif (isset($_SESSION['Language'])) {
        $lang = strtolower((string) $_SESSION['Language']);
    }

    if (strpos($lang, 'chinese') !== false || strpos($lang, 'zh') !== false || strpos($lang, 'cn') !== false) {
        return 'zh';
    }

    return 'en';
}
}

if (!function_exists('ticket_notice_select_rules')) {
function ticket_notice_select_rules($settings, $lang)
{
    $raw = trim((string) ($lang === 'zh' ? $settings['rules_json_zh'] : $settings['rules_json_en']));
    if ($raw === '') {
        return $lang === 'zh' ? ticket_notice_default_rules_zh() : ticket_notice_default_rules_en();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $lang === 'zh' ? ticket_notice_default_rules_zh() : ticket_notice_default_rules_en();
    }

    return $decoded;
}
}

if (!function_exists('ticket_notice_keyword_rules')) {
function ticket_notice_keyword_rules($lang)
{
    $settings = ticket_notice_load_settings();
    $raw = $lang === 'zh' ? trim((string) $settings['keyword_rules_zh']) : trim((string) $settings['keyword_rules_en']);
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    if ($lang === 'zh') {
        return [[
            'keywords_any' => ['解析', 'dns', 'ttl', 'cloudflare'],
            'keywords_all' => ['不生效'],
            'title' => '检测到您可能遇到：DNS缓存问题',
            'suggestions' => ['等待TTL生效', '清理本地DNS缓存', '使用 8.8.8.8 测试解析'],
            'links' => [
                ['label' => 'Cloudflare 教程', 'url' => 'https://developers.cloudflare.com/dns/'],
                ['label' => '橙云说明', 'url' => 'https://developers.cloudflare.com/dns/manage-dns-records/reference/proxied-dns-records/'],
            ],
        ]];
    }

    return [[
        'keywords_any' => ['dns', 'ttl', 'cloudflare', 'propagation'],
        'keywords_all' => ['not working'],
        'title' => 'You may be facing a DNS cache/propagation issue',
        'suggestions' => ['Wait for TTL to propagate', 'Flush local DNS cache', 'Test resolution with 8.8.8.8'],
        'links' => [
            ['label' => 'Cloudflare DNS Guide', 'url' => 'https://developers.cloudflare.com/dns/'],
        ],
    ]];
}
}

if (!function_exists('ticket_notice_find_duplicate_ticket')) {
function ticket_notice_find_duplicate_ticket($userId, $deptId)
{
    if ($userId <= 0 || $deptId <= 0) {
        return null;
    }

    $settings = ticket_notice_load_settings();
    if ((string) $settings['duplicate_enabled'] !== 'on') {
        return null;
    }
    $hours = (int) $settings['duplicate_hours'];
    if ($hours <= 0) {
        $hours = 24;
    }
    $since = date('Y-m-d H:i:s', time() - ($hours * 3600));
    return Capsule::table('tbltickets')
        ->where('userid', $userId)
        ->where('did', $deptId)
        ->whereNotIn('status', ['Closed', 'Resolved'])
        ->where('date', '>=', $since)
        ->orderBy('id', 'desc')
        ->first(['id', 'tid', 'status']);
}
}

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $filename = isset($vars['filename']) ? $vars['filename'] : '';
    if ($filename !== 'submitticket') {
        return '';
    }

    $settings = ticket_notice_load_settings();
    if (((string) $settings['enabled']) !== 'on') {
        return '';
    }

    $lang = ticket_notice_detect_lang($vars);
    $ticketNoticeRules = ticket_notice_select_rules($settings, $lang);
    if (empty($ticketNoticeRules)) {
        return '';
    }

    $rulesJson = json_encode($ticketNoticeRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $modalI18n = $lang === 'zh'
        ? [
            'modalTitle' => '提交工单前请阅读',
            'confirmLabel' => '我已阅读并理解以上内容',
            'checkboxError' => '请先勾选确认后再继续提交。',
            'btnCancel' => '返回修改',
            'btnProceed' => '确认并提交',
            'defaultTitle' => '提交工单提醒',
        ]
        : [
            'modalTitle' => 'Please read before submitting',
            'confirmLabel' => 'I have read and understood the above content',
            'checkboxError' => 'Please check confirmation before continuing.',
            'btnCancel' => 'Back to edit',
            'btnProceed' => 'Confirm and submit',
            'defaultTitle' => 'Ticket Submission Notice',
        ];
    $modalI18n['smartTitle'] = $lang === 'zh' ? '智能建议' : 'Smart Suggestions';
    $modalI18n['dupWarn'] = $lang === 'zh' ? '您已有待处理工单：' : 'You already have a pending ticket:';
    $modalI18nJson = json_encode($modalI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $keywordRulesJson = json_encode(ticket_notice_keyword_rules($lang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($rulesJson === false || $modalI18nJson === false || $keywordRulesJson === false) {
        return '';
    }

    $userId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    $deptId = isset($_REQUEST['deptid']) ? (int) $_REQUEST['deptid'] : 0;
    $dup = ticket_notice_find_duplicate_ticket($userId, $deptId);
    if ($dup) {
        $dup->url = 'viewticket.php?tid=' . (isset($dup->tid) ? $dup->tid : $dup->id);
    }
    $dupJson = json_encode($dup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($dupJson === false) { $dupJson = 'null'; }

    $moduleWebPath = 'modules/addons/ticket_notice';
    $html = [];
    $html[] = '<link rel="stylesheet" href="' . $moduleWebPath . '/assets/css/ticket_notice.css?v=1.4.0">';
    $html[] = '<script>window.ticketNoticeRules=' . $rulesJson . ';window.ticketNoticeI18n=' . $modalI18nJson . ';window.ticketNoticeKeywordRules=' . $keywordRulesJson . ';window.ticketNoticeDuplicate=' . $dupJson . ';</script>';

    $modalTpl = __DIR__ . '/templates/modal.tpl';
    if (is_file($modalTpl)) {
        $html[] = file_get_contents($modalTpl);
    }

    $html[] = '<script src="' . $moduleWebPath . '/assets/js/ticket_notice.js?v=1.4.0"></script>';

    return implode(PHP_EOL, $html);
});

add_hook('TicketOpenValidation', 1, function ($vars) {
    $settings = ticket_notice_load_settings();
    if (((string) $settings['enabled']) !== 'on') {
        return [];
    }

    $lang = ticket_notice_detect_lang($vars);
    $ticketNoticeRules = ticket_notice_select_rules($settings, $lang);
    if (empty($ticketNoticeRules)) {
        return [];
    }

    $deptId = isset($_POST['deptid']) ? (int) $_POST['deptid'] : 0;

    $userId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    $dup = ticket_notice_find_duplicate_ticket($userId, $deptId);
    if ($dup) {
        $ticketNo = isset($dup->tid) ? '#' . $dup->tid : '#' . $dup->id;
        return $lang === 'zh'
            ? ['您已有24小时内未关闭的同部门工单：' . $ticketNo . '，请勿重复提交。']
            : ['You already have an unclosed ticket in this department within 24 hours: ' . $ticketNo . '.'];
    }
    if (!isset($ticketNoticeRules[$deptId]) && !isset($ticketNoticeRules[(string) $deptId])) {
        return [];
    }

    $confirmed = isset($_POST['ticket_notice_confirmed']) ? (int) $_POST['ticket_notice_confirmed'] : 0;
    if ($confirmed !== 1) {
        return $lang === 'zh'
            ? ['请先阅读并确认工单提交提醒后再提交工单。']
            : ['Please read and confirm the ticket notice before submitting.'];
    }

    return [];
});
