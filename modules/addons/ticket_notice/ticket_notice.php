<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function ticket_notice_default_rules()
{
    return [
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
}

function ticket_notice_config()
{
    return [
        'name' => 'Ticket Notice',
        'description' => 'Show department-based reminders before ticket submission.',
        'version' => '1.1.0',
        'author' => 'Custom',
        'language' => 'english',
        'fields' => [
            'enabled' => [
                'FriendlyName' => 'Enable Ticket Notice',
                'Type' => 'yesno',
                'Description' => 'Check to enable pre-submit reminder interception.',
                'Default' => 'on',
            ],
            'rules_json' => [
                'FriendlyName' => 'Rules JSON',
                'Type' => 'textarea',
                'Rows' => '16',
                'Cols' => '100',
                'Description' => 'Department rules in JSON format. Leave empty to use defaults.',
                'Default' => json_encode(ticket_notice_default_rules(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
        ],
    ];
}

function ticket_notice_activate()
{
    return ['status' => 'success', 'description' => 'Ticket Notice activated'];
}

function ticket_notice_deactivate()
{
    return ['status' => 'success', 'description' => 'Ticket Notice deactivated'];
}

function ticket_notice_output($vars)
{
    echo '<h3>Ticket Notice MVP</h3>';
    echo '<p>可在本页直接配置是否启用与规则 JSON（无需改 hooks.php）。</p>';
    echo '<p>JSON 结构示例：<code>{"1":{"title":"DNS解析提醒","items":["提示1"],"warning":"红字提醒"}}</code></p>';
}
