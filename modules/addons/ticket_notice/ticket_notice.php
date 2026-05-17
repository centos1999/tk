<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (function_exists('ticket_notice_config')) {
    return;
}

if (!function_exists('ticket_notice_default_rules')) {
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
}


if (!function_exists('ticket_notice_config')) {
function ticket_notice_config()
{
    return [
        'name' => 'Ticket Notice',
        'description' => 'Show department-based reminders before ticket submission.',
        'version' => '1.2.0',
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
}


if (!function_exists('ticket_notice_activate')) {
function ticket_notice_activate()
{
    return ['status' => 'success', 'description' => 'Ticket Notice activated'];
}
}


if (!function_exists('ticket_notice_deactivate')) {
function ticket_notice_deactivate()
{
    return ['status' => 'success', 'description' => 'Ticket Notice deactivated'];
}
}


if (!function_exists('ticket_notice_get_stored_rules_json')) {
function ticket_notice_get_stored_rules_json()
{
    try {
        $row = Capsule::table('tbladdonmodules')
            ->where('module', 'ticket_notice')
            ->where('setting', 'rules_json')
            ->first(['value']);

        if ($row && isset($row->value) && trim((string) $row->value) !== '') {
            return (string) $row->value;
        }
    } catch (\Exception $e) {
    }

    return json_encode(ticket_notice_default_rules(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
}


if (!function_exists('ticket_notice_set_stored_rules_json')) {
function ticket_notice_set_stored_rules_json($json)
{
    $exists = Capsule::table('tbladdonmodules')
        ->where('module', 'ticket_notice')
        ->where('setting', 'rules_json')
        ->exists();

    if ($exists) {
        Capsule::table('tbladdonmodules')
            ->where('module', 'ticket_notice')
            ->where('setting', 'rules_json')
            ->update(['value' => $json]);
        return;
    }

    Capsule::table('tbladdonmodules')->insert([
        'module' => 'ticket_notice',
        'setting' => 'rules_json',
        'value' => $json,
    ]);
}
}


if (!function_exists('ticket_notice_output')) {
function ticket_notice_output($vars)
{
    $message = '';
    $error = '';

    if (isset($_POST['ticket_notice_visual_save']) && $_POST['ticket_notice_visual_save'] === '1') {
        $input = isset($_POST['ticket_notice_rules_json']) ? trim((string) $_POST['ticket_notice_rules_json']) : '';
        if ($input === '') {
            $error = '保存失败：规则不能为空。';
        } else {
            $decoded = json_decode($input, true);
            if (!is_array($decoded)) {
                $error = '保存失败：JSON 格式无效。';
            } else {
                try {
                    ticket_notice_set_stored_rules_json(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    $message = '规则已保存。';
                } catch (\Exception $e) {
                    $error = '保存失败：数据库写入异常。';
                }
            }
        }
    }

    $rulesJson = ticket_notice_get_stored_rules_json();
    $safeRulesJson = htmlspecialchars($rulesJson, ENT_QUOTES, 'UTF-8');

    echo '<h3>Ticket Notice 可视化规则编辑器</h3>';
    echo '<p>在此编辑部门提醒规则，保存后立即生效（hooks 会读取同一份 rules_json）。</p>';

    if ($message !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    if ($error !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    echo '<form method="post" id="ticketNoticeVisualForm">';
    echo '<input type="hidden" name="ticket_notice_visual_save" value="1">';
    echo '<table class="table table-bordered" id="ticketNoticeRuleTable">';
    echo '<thead><tr><th style="width:120px;">部门ID</th><th style="width:180px;">标题</th><th>提醒项（每行一条）</th><th>红字警告</th><th style="width:90px;">操作</th></tr></thead><tbody></tbody></table>';
    echo '<p><button type="button" class="btn btn-default" id="ticketNoticeAddRow">+ 添加规则</button></p>';
    echo '<textarea name="ticket_notice_rules_json" id="ticketNoticeRulesJson" rows="12" style="width:100%;">' . $safeRulesJson . '</textarea>';
    echo '<p class="text-muted" style="margin-top:6px;">可视化编辑不可用时，可直接修改以上 JSON。</p>';
    echo '<p><button type="submit" class="btn btn-primary">保存规则</button></p>';
    echo '</form>';

    $rulesForJs = json_encode(json_decode($rulesJson, true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($rulesForJs === false) {
        $rulesForJs = '{}';
    }

    echo '<script>(function(){
'
        . 'var form=document.getElementById("ticketNoticeVisualForm"); if(!form){return;}
'
        . 'var tableBody=document.querySelector("#ticketNoticeRuleTable tbody");
'
        . 'var jsonField=document.getElementById("ticketNoticeRulesJson");
'
        . 'var addBtn=document.getElementById("ticketNoticeAddRow");
'
        . 'var source=' . $rulesForJs . ';
'
        . 'function esc(v){return (v||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
'
        . 'function addRow(dept,title,items,warning){var tr=document.createElement("tr"); tr.innerHTML="<td><input class=\"form-control tn-dept\" value=\""+esc(dept)+"\"></td>"+"<td><input class=\"form-control tn-title\" value=\""+esc(title)+"\"></td>"+"<td><textarea class=\"form-control tn-items\" rows=\"4\">"+esc((items||[]).join("\\n"))+"</textarea></td>"+"<td><input class=\"form-control tn-warning\" value=\""+esc(warning)+"\"></td>"+"<td><button type=\"button\" class=\"btn btn-danger btn-sm tn-del\">删除</button></td>"; tableBody.appendChild(tr);}
'
        . 'Object.keys(source||{}).forEach(function(k){var r=source[k]||{};addRow(k,r.title||"",Array.isArray(r.items)?r.items:[],r.warning||"");});
'
        . 'if(!tableBody.children.length){addRow("","",[],"");}
'
        . 'addBtn.onclick=function(){addRow("","",[],"");};
'
        . 'tableBody.onclick=function(e){var t=e.target||e.srcElement; if(t && t.className.indexOf("tn-del")!==-1){var tr=t; while(tr && tr.tagName!=="TR"){tr=tr.parentNode;} if(tr&&tr.parentNode){tr.parentNode.removeChild(tr);}}};
'
        . 'form.onsubmit=function(e){var data={}; var ok=true; var rows=tableBody.querySelectorAll("tr"); for(var i=0;i<rows.length;i++){var tr=rows[i]; var dept=(tr.querySelector(".tn-dept").value||"").trim(); if(!dept){continue;} if(!/^\\d+$/.test(dept)){ok=false; break;} var title=(tr.querySelector(".tn-title").value||"").trim(); var warning=(tr.querySelector(".tn-warning").value||"").trim(); var items=(tr.querySelector(".tn-items").value||"").split(/\\n+/).map(function(v){return v.trim();}).filter(function(v){return v;}); data[dept]={title:title,items:items,warning:warning}; } if(!ok){alert("部门ID必须是数字"); if(e&&e.preventDefault){e.preventDefault();} return false;} jsonField.value=JSON.stringify(data,null,2); return true;};
'
        . '})();</script>';
}
}

