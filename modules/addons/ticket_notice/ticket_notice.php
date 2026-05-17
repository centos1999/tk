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
        $rowsInput = isset($_POST['ticket_notice_rows_json']) ? trim((string) $_POST['ticket_notice_rows_json']) : '';
        $rows = ticket_notice_decode_rules_input($rowsInput);

        if (!is_array($rows)) {
            $error = '保存失败：规则格式无效。';
        } else {
            $zhRules = [];
            $enRules = [];

            foreach ($rows as $row) {
                $deptId = isset($row['deptid']) ? trim((string) $row['deptid']) : '';
                if ($deptId === '' || !preg_match('/^\d+$/', $deptId)) {
                    continue;
                }

                $itemsZh = isset($row['items_zh']) && is_array($row['items_zh']) ? $row['items_zh'] : [];
                $itemsEn = isset($row['items_en']) && is_array($row['items_en']) ? $row['items_en'] : [];

                $zhRules[(int) $deptId] = [
                    'title' => isset($row['title_zh']) ? trim((string) $row['title_zh']) : '',
                    'items' => array_values(array_filter(array_map('trim', $itemsZh), 'strlen')),
                    'warning' => isset($row['warning_zh']) ? trim((string) $row['warning_zh']) : '',
                ];

                $enRules[(int) $deptId] = [
                    'title' => isset($row['title_en']) ? trim((string) $row['title_en']) : '',
                    'items' => array_values(array_filter(array_map('trim', $itemsEn), 'strlen')),
                    'warning' => isset($row['warning_en']) ? trim((string) $row['warning_en']) : '',
                ];
            }

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

    $zhRules = json_decode($zhCurrent, true);
    $enRules = json_decode($enCurrent, true);
    if (!is_array($zhRules)) { $zhRules = ticket_notice_default_rules_zh(); }
    if (!is_array($enRules)) { $enRules = ticket_notice_default_rules_en(); }

    $deptIds = array_unique(array_merge(array_map('strval', array_keys($zhRules)), array_map('strval', array_keys($enRules))));
    sort($deptIds, SORT_NATURAL);
    $rows = [];
    foreach ($deptIds as $deptId) {
        $zh = isset($zhRules[$deptId]) ? $zhRules[$deptId] : (isset($zhRules[(int)$deptId]) ? $zhRules[(int)$deptId] : []);
        $en = isset($enRules[$deptId]) ? $enRules[$deptId] : (isset($enRules[(int)$deptId]) ? $enRules[(int)$deptId] : []);
        $rows[] = [
            'deptid' => $deptId,
            'title_zh' => isset($zh['title']) ? (string) $zh['title'] : '',
            'items_zh' => isset($zh['items']) && is_array($zh['items']) ? $zh['items'] : [],
            'warning_zh' => isset($zh['warning']) ? (string) $zh['warning'] : '',
            'title_en' => isset($en['title']) ? (string) $en['title'] : '',
            'items_en' => isset($en['items']) && is_array($en['items']) ? $en['items'] : [],
            'warning_en' => isset($en['warning']) ? (string) $en['warning'] : '',
        ];
    }

    echo '<h3>Ticket Notice 双语可视化规则配置</h3>';
    echo '<p>可直接添加规则：同一行里 1=中文内容，2=英文内容。保存后自动拆分到中英文规则。</p>';
    if ($message !== '') { echo '<div class="alert alert-success">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>'; }
    if ($error !== '') { echo '<div class="alert alert-danger">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>'; }

    echo '<form method="post" id="ticketNoticeBilingualForm">';
    echo '<input type="hidden" name="ticket_notice_save_bilingual" value="1">';
    echo '<table class="table table-bordered" id="ticketNoticeRuleTable">';
    echo '<thead><tr><th>部门ID</th><th>中文标题(1)</th><th>中文提醒项(1, 每行一条)</th><th>中文警告(1)</th><th>English Title (2)</th><th>English Items (2, one per line)</th><th>English Warning (2)</th><th>操作</th></tr></thead><tbody></tbody></table>';
    echo '<p><button type="button" class="btn btn-default" id="ticketNoticeAddRow">+ 添加规则</button></p>';
    echo '<textarea name="ticket_notice_rows_json" id="ticketNoticeRowsJson" rows="8" style="display:none;width:100%;"></textarea>';
    echo '<p style="margin-top:12px;"><button type="submit" class="btn btn-primary">保存双语规则</button></p>';
    echo '</form>';

    $rowsJson = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($rowsJson === false) {
        $rowsJson = '[]';
    }

    echo '<script>(function(){\n'
        . 'var rows=' . $rowsJson . ';\n'
        . 'var form=document.getElementById("ticketNoticeBilingualForm");\n'
        . 'var tbody=document.querySelector("#ticketNoticeRuleTable tbody");\n'
        . 'var hidden=document.getElementById("ticketNoticeRowsJson");\n'
        . 'function esc(v){return (v||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}\n'
        . 'function rowHtml(r){return "<td><input class=\\"form-control tn-deptid\\" value=\\""+esc(r.deptid||"")+"\\"></td>"+"<td><input class=\\"form-control tn-title-zh\\" value=\\""+esc(r.title_zh||"")+"\\"></td>"+"<td><textarea class=\\"form-control tn-items-zh\\" rows=\\"3\\">"+esc((r.items_zh||[]).join("\\n"))+"</textarea></td>"+"<td><input class=\\"form-control tn-warning-zh\\" value=\\""+esc(r.warning_zh||"")+"\\"></td>"+"<td><input class=\\"form-control tn-title-en\\" value=\\""+esc(r.title_en||"")+"\\"></td>"+"<td><textarea class=\\"form-control tn-items-en\\" rows=\\"3\\">"+esc((r.items_en||[]).join("\\n"))+"</textarea></td>"+"<td><input class=\\"form-control tn-warning-en\\" value=\\""+esc(r.warning_en||"")+"\\"></td>"+"<td><button type=\\"button\\" class=\\"btn btn-danger btn-sm tn-del\\">删除</button></td>";}\n'
        . 'function addRow(r){var tr=document.createElement("tr");tr.innerHTML=rowHtml(r||{});tbody.appendChild(tr);}\n'
        . 'if(!rows.length){addRow({});}else{for(var i=0;i<rows.length;i++){addRow(rows[i]);}}\n'
        . 'document.getElementById("ticketNoticeAddRow").onclick=function(){addRow({});};\n'
        . 'tbody.onclick=function(e){var t=e.target||e.srcElement;if(t&&t.className.indexOf("tn-del")!==-1){var tr=t;while(tr&&tr.tagName!=="TR"){tr=tr.parentNode;}if(tr&&tr.parentNode){tr.parentNode.removeChild(tr);}}};\n'
        . 'form.onsubmit=function(e){var out=[];var trs=tbody.querySelectorAll("tr");for(var i=0;i<trs.length;i++){var tr=trs[i];var deptid=(tr.querySelector(".tn-deptid").value||"").trim();if(!deptid){continue;}if(!/^\\d+$/.test(deptid)){alert("部门ID必须是数字");if(e&&e.preventDefault){e.preventDefault();}return false;}out.push({deptid:deptid,title_zh:(tr.querySelector(".tn-title-zh").value||"").trim(),items_zh:(tr.querySelector(".tn-items-zh").value||"").split(/\\n+/).map(function(v){return v.trim();}).filter(Boolean),warning_zh:(tr.querySelector(".tn-warning-zh").value||"").trim(),title_en:(tr.querySelector(".tn-title-en").value||"").trim(),items_en:(tr.querySelector(".tn-items-en").value||"").split(/\\n+/).map(function(v){return v.trim();}).filter(Boolean),warning_en:(tr.querySelector(".tn-warning-en").value||"").trim()});}hidden.value=JSON.stringify(out);return true;};\n'
        . '})();</script>';
}
}
