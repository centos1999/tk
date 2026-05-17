<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!function_exists('ticket_notice_default_rules_zh')) {
function ticket_notice_default_rules_zh()
{
    return [
        1 => ['title' => 'DNS解析提醒', 'items' => ['DNS修改后可能需要时间同步（通常数分钟到48小时）', '请提供完整域名（例如：example.com）', '若使用 Cloudflare，请先关闭代理（橙云）测试'], 'warning' => '信息不完整会导致处理时间延长。'],
        2 => ['title' => 'Abuse 举报提醒', 'items' => ['请提供完整 URL（包含协议）', '请上传截图证据', '请描述违规原因与影响范围'], 'warning' => '无证据或描述不清晰将无法快速受理。'],
        3 => ['title' => 'VPS 技术支持提醒', 'items' => ['请提供服务器 IP', '请提供报错截图或错误日志', '请说明复现步骤与预期结果'], 'warning' => '缺少关键信息可能导致需要反复沟通。'],
    ];
}
}

if (!function_exists('ticket_notice_default_rules_en')) {
function ticket_notice_default_rules_en()
{
    return [
        1 => ['title' => 'DNS Reminder', 'items' => ['DNS updates may take time to propagate.', 'Please provide the full domain name (e.g. example.com).', 'If using Cloudflare, disable proxy (orange cloud) for testing first.'], 'warning' => 'Incomplete details may delay processing.'],
        2 => ['title' => 'Abuse Report Reminder', 'items' => ['Please provide the full URL (including protocol).', 'Please upload screenshot evidence.', 'Please describe the violation reason and impact.'], 'warning' => 'Missing evidence or unclear description may delay handling.'],
        3 => ['title' => 'VPS Technical Support Reminder', 'items' => ['Please provide the server IP.', 'Please provide error screenshots or logs.', 'Please describe reproduction steps and expected result.'], 'warning' => 'Missing key details may require back-and-forth communication.'],
    ];
}
}

if (!function_exists('ticket_notice_config')) {
function ticket_notice_config()
{
    return [
        'name' => 'Ticket Notice',
        'description' => 'Show department-based reminders before ticket submission.',
        'version' => '1.3.0',
        'author' => 'Custom',
        'language' => 'english',
        'fields' => [
            'enabled' => ['FriendlyName' => 'Enable Ticket Notice', 'Type' => 'yesno', 'Description' => 'Enable pre-submit reminder interception.', 'Default' => 'on'],
            'rules_json_zh' => ['FriendlyName' => 'Rules JSON (Chinese)', 'Type' => 'textarea', 'Rows' => '12', 'Cols' => '100', 'Description' => '中文规则 JSON', 'Default' => json_encode(ticket_notice_default_rules_zh(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
            'rules_json_en' => ['FriendlyName' => 'Rules JSON (English)', 'Type' => 'textarea', 'Rows' => '12', 'Cols' => '100', 'Description' => 'English rules JSON', 'Default' => json_encode(ticket_notice_default_rules_en(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
        ],
    ];
}
}

if (!function_exists('ticket_notice_activate')) {
function ticket_notice_activate() { return ['status' => 'success', 'description' => 'Ticket Notice activated']; }
}
if (!function_exists('ticket_notice_deactivate')) {
function ticket_notice_deactivate() { return ['status' => 'success', 'description' => 'Ticket Notice deactivated']; }
}

if (!function_exists('ticket_notice_get_setting')) {
function ticket_notice_get_setting($setting)
{
    try {
        $row = Capsule::table('tbladdonmodules')->where('module', 'ticket_notice')->where('setting', $setting)->first(['value']);
        if ($row && isset($row->value)) {
            return (string) $row->value;
        }
    } catch (\Exception $e) {
    }
    return '';
}
}

if (!function_exists('ticket_notice_set_setting')) {
function ticket_notice_set_setting($setting, $value)
{
    $exists = Capsule::table('tbladdonmodules')->where('module', 'ticket_notice')->where('setting', $setting)->exists();
    if ($exists) {
        Capsule::table('tbladdonmodules')->where('module', 'ticket_notice')->where('setting', $setting)->update(['value' => $value]);
    } else {
        Capsule::table('tbladdonmodules')->insert(['module' => 'ticket_notice', 'setting' => $setting, 'value' => $value]);
    }
}
}

if (!function_exists('ticket_notice_decode_rules_input')) {
function ticket_notice_decode_rules_input($input)
{
    $candidates = [$input, html_entity_decode($input, ENT_QUOTES, 'UTF-8'), stripslashes($input), stripslashes(html_entity_decode($input, ENT_QUOTES, 'UTF-8'))];
    foreach ($candidates as $candidate) {
        $decoded = json_decode((string) $candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return null;
}
}

if (!function_exists('ticket_notice_output')) {
function ticket_notice_output($vars)
{
    $message = '';
    $error = '';

    if (isset($_POST['ticket_notice_save_bilingual']) && $_POST['ticket_notice_save_bilingual'] === '1') {
        $zhInput = isset($_POST['ticket_notice_rules_json_zh']) ? trim((string) $_POST['ticket_notice_rules_json_zh']) : '';
        $enInput = isset($_POST['ticket_notice_rules_json_en']) ? trim((string) $_POST['ticket_notice_rules_json_en']) : '';

        $zhRules = ticket_notice_decode_rules_input($zhInput);
        $enRules = ticket_notice_decode_rules_input($enInput);

        if (!is_array($zhRules) || !is_array($enRules)) {
            $error = '保存失败：JSON 格式无效（请检查中英文规则的括号、引号、逗号）。';
        } else {
            try {
                ticket_notice_set_setting('rules_json_zh', json_encode($zhRules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                ticket_notice_set_setting('rules_json_en', json_encode($enRules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $message = '中英文规则已保存。';
            } catch (\Exception $e) {
                $error = '保存失败：数据库写入异常。';
            }
        }
    }

    $zhCurrent = ticket_notice_get_setting('rules_json_zh');
    $enCurrent = ticket_notice_get_setting('rules_json_en');
    if (trim($zhCurrent) === '') { $zhCurrent = json_encode(ticket_notice_default_rules_zh(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); }
    if (trim($enCurrent) === '') { $enCurrent = json_encode(ticket_notice_default_rules_en(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); }

    echo '<h3>Ticket Notice 双语规则配置</h3>';
    echo '<p>插件会根据用户 WHMCS 语言自动显示中文或英文提醒（非中文默认英文）。</p>';
    if ($message !== '') { echo '<div class="alert alert-success">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>'; }
    if ($error !== '') { echo '<div class="alert alert-danger">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>'; }

    echo '<form method="post">';
    echo '<input type="hidden" name="ticket_notice_save_bilingual" value="1">';
    echo '<h4>中文规则 JSON</h4>';
    echo '<textarea name="ticket_notice_rules_json_zh" rows="14" style="width:100%;">' . htmlspecialchars($zhCurrent, ENT_QUOTES, 'UTF-8') . '</textarea>';
    echo '<h4 style="margin-top:16px;">English Rules JSON</h4>';
    echo '<textarea name="ticket_notice_rules_json_en" rows="14" style="width:100%;">' . htmlspecialchars($enCurrent, ENT_QUOTES, 'UTF-8') . '</textarea>';
    echo '<p style="margin-top:12px;"><button type="submit" class="btn btn-primary">保存双语规则</button></p>';
    echo '</form>';
}
}
