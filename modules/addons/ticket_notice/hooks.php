<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!function_exists('ticket_notice_default_rules_zh')) {
    require_once __DIR__ . '/ticket_notice.php';
}

function ticket_notice_load_settings()
{
    $settings = [
        'enabled' => 'on',
        'rules_json_zh' => '',
        'rules_json_en' => '',
    ];

    try {
        $rows = Capsule::table('tbladdonmodules')
            ->where('module', 'ticket_notice')
            ->whereIn('setting', ['enabled', 'rules_json_zh', 'rules_json_en'])
            ->get(['setting', 'value']);

        foreach ($rows as $row) {
            $settings[$row->setting] = (string) $row->value;
        }
    } catch (\Exception $e) {
    }

    return $settings;
}

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

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $filename = isset($vars['filename']) ? $vars['filename'] : '';
    if ($filename !== 'submitticket') {
        return '';
    }

    $settings = ticket_notice_load_settings();
    $enabled = ((string) $settings['enabled']) === 'on';
    if (!$enabled) {
        return '';
    }

    $lang = ticket_notice_detect_lang($vars);
    $ticketNoticeRules = ticket_notice_select_rules($settings, $lang);
    if (empty($ticketNoticeRules)) {
        return '';
    }

    $rulesJson = json_encode($ticketNoticeRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($rulesJson === false) {
        return '';
    }

    $moduleWebPath = 'modules/addons/ticket_notice';
    $html = [];
    $html[] = '<link rel="stylesheet" href="' . $moduleWebPath . '/assets/css/ticket_notice.css?v=1.3.0">';
    $html[] = '<script>window.ticketNoticeRules = ' . $rulesJson . ';</script>';
    $modalTpl = __DIR__ . '/templates/modal.tpl';
    if (is_file($modalTpl)) {
        $html[] = file_get_contents($modalTpl);
    }
    $html[] = '<script src="' . $moduleWebPath . '/assets/js/ticket_notice.js?v=1.3.0"></script>';

    return implode(PHP_EOL, $html);
});

add_hook('TicketOpenValidation', 1, function ($vars) {
    $settings = ticket_notice_load_settings();
    $enabled = ((string) $settings['enabled']) === 'on';
    if (!$enabled) {
        return [];
    }

    $lang = ticket_notice_detect_lang([]);
    $ticketNoticeRules = ticket_notice_select_rules($settings, $lang);
    if (empty($ticketNoticeRules)) {
        return [];
    }

    $deptId = isset($_POST['deptid']) ? (int) $_POST['deptid'] : 0;
    if (!isset($ticketNoticeRules[$deptId]) && !isset($ticketNoticeRules[(string) $deptId])) {
        return [];
    }

    $confirmed = isset($_POST['ticket_notice_confirmed']) ? (int) $_POST['ticket_notice_confirmed'] : 0;
    if ($confirmed !== 1) {
        return $lang === 'zh' ? ['请先阅读并确认工单提交提醒后再提交工单。'] : ['Please read and confirm the ticket notice before submitting.'];
    }

    return [];
});
