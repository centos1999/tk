<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!function_exists('ticket_notice_default_rules')) {
    require_once __DIR__ . '/ticket_notice.php';
}

function ticket_notice_load_settings()
{
    $settings = [
        'enabled' => 'on',
        'rules_json' => '',
    ];

    try {
        $rows = Capsule::table('tbladdonmodules')
            ->where('module', 'ticket_notice')
            ->whereIn('setting', ['enabled', 'rules_json'])
            ->get(['setting', 'value']);

        foreach ($rows as $row) {
            $settings[$row->setting] = (string) $row->value;
        }
    } catch (\Exception $e) {
        // fallback to defaults
    }

    return $settings;
}

function ticket_notice_load_rules()
{
    $settings = ticket_notice_load_settings();
    $raw = trim((string) $settings['rules_json']);

    if ($raw === '') {
        return [((string) $settings['enabled']) === 'on', ticket_notice_default_rules()];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [((string) $settings['enabled']) === 'on', ticket_notice_default_rules()];
    }

    return [((string) $settings['enabled']) === 'on', $decoded];
}

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $filename = isset($vars['filename']) ? $vars['filename'] : '';
    if ($filename !== 'submitticket') {
        return '';
    }

    list($enabled, $ticketNoticeRules) = ticket_notice_load_rules();
    if (!$enabled || empty($ticketNoticeRules)) {
        return '';
    }

    $rulesJson = json_encode($ticketNoticeRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($rulesJson === false) {
        return '';
    }

    $moduleWebPath = 'modules/addons/ticket_notice';

    $html = [];
    $html[] = '<link rel="stylesheet" href="' . $moduleWebPath . '/assets/css/ticket_notice.css?v=1.1.0">';
    $html[] = '<script>window.ticketNoticeRules = ' . $rulesJson . ';</script>';

    $modalTpl = __DIR__ . '/templates/modal.tpl';
    if (is_file($modalTpl)) {
        $html[] = file_get_contents($modalTpl);
    }

    $html[] = '<script src="' . $moduleWebPath . '/assets/js/ticket_notice.js?v=1.1.0"></script>';

    return implode(PHP_EOL, $html);
});

add_hook('TicketOpenValidation', 1, function ($vars) {
    list($enabled, $ticketNoticeRules) = ticket_notice_load_rules();
    if (!$enabled || empty($ticketNoticeRules)) {
        return [];
    }

    $deptId = isset($_POST['deptid']) ? (int) $_POST['deptid'] : 0;

    if (!isset($ticketNoticeRules[$deptId]) && !isset($ticketNoticeRules[(string) $deptId])) {
        return [];
    }

    $confirmed = isset($_POST['ticket_notice_confirmed']) ? (int) $_POST['ticket_notice_confirmed'] : 0;
    if ($confirmed !== 1) {
        return ['请先阅读并确认工单提交提醒后再提交工单。'];
    }

    return [];
});
