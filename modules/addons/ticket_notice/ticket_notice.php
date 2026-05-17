<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!function_exists('ticket_notice_default_rules_zh')) {
function ticket_notice_default_rules_zh(){return [1=>['title'=>'DNS解析提醒','items'=>['DNS修改后可能需要时间同步（通常数分钟到48小时）','请提供完整域名（例如：example.com）','若使用 Cloudflare，请先关闭代理（橙云）测试'],'warning'=>'信息不完整会导致处理时间延长。'],2=>['title'=>'Abuse 举报提醒','items'=>['请提供完整 URL（包含协议）','请上传截图证据','请描述违规原因与影响范围'],'warning'=>'无证据或描述不清晰将无法快速受理。'],3=>['title'=>'VPS 技术支持提醒','items'=>['请提供服务器 IP','请提供报错截图或错误日志','请说明复现步骤与预期结果'],'warning'=>'缺少关键信息可能导致需要反复沟通。']];}
}
if (!function_exists('ticket_notice_default_rules_en')) {
function ticket_notice_default_rules_en(){return [1=>['title'=>'DNS Reminder','items'=>['DNS updates may take time to propagate.','Please provide the full domain name (e.g. example.com).','If using Cloudflare, disable proxy (orange cloud) for testing first.'],'warning'=>'Incomplete details may delay processing.'],2=>['title'=>'Abuse Report Reminder','items'=>['Please provide the full URL (including protocol).','Please upload screenshot evidence.','Please describe the violation reason and impact.'],'warning'=>'Missing evidence or unclear description may delay handling.'],3=>['title'=>'VPS Technical Support Reminder','items'=>['Please provide the server IP.','Please provide error screenshots or logs.','Please describe reproduction steps and expected result.'],'warning'=>'Missing key details may require back-and-forth communication.']];}
}
if (!function_exists('ticket_notice_config')) {
function ticket_notice_config(){return ['name'=>'Ticket Notice','description'=>'Show department-based reminders before ticket submission.','version'=>'1.5.0','author'=>'Custom','language'=>'english','fields'=>['enabled'=>['FriendlyName'=>'Enable Ticket Notice','Type'=>'yesno','Description'=>'Enable pre-submit reminder interception.','Default'=>'on'],'rules_json_zh'=>['FriendlyName'=>'Rules JSON (Chinese)','Type'=>'textarea','Rows'=>'8','Cols'=>'100','Description'=>'中文规则 JSON（可不手工修改）','Default'=>json_encode(ticket_notice_default_rules_zh(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)],'rules_json_en'=>['FriendlyName'=>'Rules JSON (English)','Type'=>'textarea','Rows'=>'8','Cols'=>'100','Description'=>'English rules JSON (optional to edit manually)','Default'=>json_encode(ticket_notice_default_rules_en(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)],'duplicate_enabled'=>['FriendlyName'=>'Enable Duplicate Blocking','Type'=>'yesno','Description'=>'Block duplicate ticket in same dept/time window','Default'=>'on'],'duplicate_hours'=>['FriendlyName'=>'Duplicate Window Hours','Type'=>'text','Size'=>'8','Default'=>'24'],'keyword_rules_zh'=>['FriendlyName'=>'Keyword Rules JSON (Chinese)','Type'=>'textarea','Rows'=>'8','Cols'=>'100','Default'=>'[]'],'keyword_rules_en'=>['FriendlyName'=>'Keyword Rules JSON (English)','Type'=>'textarea','Rows'=>'8','Cols'=>'100','Default'=>'[]']]];}
}
if (!function_exists('ticket_notice_activate')) { function ticket_notice_activate(){ return ['status'=>'success','description'=>'Ticket Notice activated']; } }
if (!function_exists('ticket_notice_deactivate')) { function ticket_notice_deactivate(){ return ['status'=>'success','description'=>'Ticket Notice deactivated']; } }
if (!function_exists('ticket_notice_get_setting')) { function ticket_notice_get_setting($k){ try{$r=Capsule::table('tbladdonmodules')->where('module','ticket_notice')->where('setting',$k)->first(['value']); return ($r&&isset($r->value))?(string)$r->value:'';}catch(\Exception $e){return '';} } }
if (!function_exists('ticket_notice_set_setting')) { function ticket_notice_set_setting($k,$v){ $q=Capsule::table('tbladdonmodules')->where('module','ticket_notice')->where('setting',$k); if($q->exists()){$q->update(['value'=>$v]);}else{Capsule::table('tbladdonmodules')->insert(['module'=>'ticket_notice','setting'=>$k,'value'=>$v]);}} }
if (!function_exists('ticket_notice_get_departments')) { function ticket_notice_get_departments(){ try{$rows=Capsule::table('tblticketdepartments')->orderBy('order','asc')->orderBy('id','asc')->get(['id','name']); $out=[]; foreach($rows as $r){$out[]=['id'=>(int)$r->id,'name'=>(string)$r->name];} return $out;}catch(\Exception $e){return [];} } }
if (!function_exists('ticket_notice_decode_rules_input')) { function ticket_notice_decode_rules_input($input){ foreach([$input,html_entity_decode((string)$input,ENT_QUOTES,'UTF-8'),stripslashes((string)$input)] as $c){$d=json_decode((string)$c,true); if(is_array($d)) return $d;} return null; } }
if (!function_exists('ticket_notice_parse_keyword_lines')) {
function ticket_notice_parse_keyword_lines($text, $lang)
{
    $text = trim((string) $text);
    if ($text === '') {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $text);
    $rules = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || strpos($line, ':') === false) {
            continue;
        }

        list($kwPart, $answerPart) = explode(':', $line, 2);
        $kwRaw = explode(',', $kwPart);
        $keywords = [];
        foreach ($kwRaw as $k) {
            $k = trim((string) $k);
            if ($k !== '') {
                $keywords[] = $k;
            }
        }

        $answer = trim((string) $answerPart);
        if (empty($keywords) || $answer === '') {
            continue;
        }

        $rules[] = [
            'keywords_any' => $keywords,
            'keywords_all' => [],
            'title' => $lang === 'zh' ? '智能识别建议' : 'Smart Suggestion',
            'suggestions' => [$answer],
            'links' => [],
        ];
    }

    return $rules;
}
}

if (!function_exists('ticket_notice_output')) {
function ticket_notice_output($vars)
{
    $message=''; $error='';
    if (isset($_POST['ticket_notice_save_bilingual']) && $_POST['ticket_notice_save_bilingual']==='1') {
        if (!isset($_POST['token']) || !function_exists('check_token') || !check_token('WHMCS.admin.default')) {
            $error='保存失败：CSRF 校验失败，请刷新后重试。';
        } else {
            $rows=ticket_notice_decode_rules_input(isset($_POST['ticket_notice_rows_json'])?$_POST['ticket_notice_rows_json']:'[]');
            if (!is_array($rows)) {
                $error='保存失败：规则数据格式无效。';
            } else {
                $zh=[]; $en=[];
                foreach($rows as $row){
                    $deptid=isset($row['deptid'])?trim((string)$row['deptid']):'';
                    if($deptid===''||!preg_match('/^\d+$/',$deptid)){ continue; }
                    $titleZh=trim((string)($row['title_zh']??'')); $titleEn=trim((string)($row['title_en']??''));
                    $itemsZh=is_array($row['items_zh']??null)?array_values(array_filter(array_map('trim',$row['items_zh']))):[];
                    $itemsEn=is_array($row['items_en']??null)?array_values(array_filter(array_map('trim',$row['items_en']))):[];
                    if($titleZh===''||$titleEn===''){ $error='保存失败：部门ID '.$deptid.' 缺少中英文标题。'; break; }
                    if(empty($itemsZh)||empty($itemsEn)){ $error='保存失败：部门ID '.$deptid.' 的中英文提醒项不能为空。'; break; }
                    $zh[(int)$deptid]=['title'=>$titleZh,'items'=>$itemsZh,'warning'=>trim((string)($row['warning_zh']??''))];
                    $en[(int)$deptid]=['title'=>$titleEn,'items'=>$itemsEn,'warning'=>trim((string)($row['warning_en']??''))];
                }
                if($error===''){
                    ticket_notice_set_setting('rules_json_zh',json_encode($zh,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                    ticket_notice_set_setting('rules_json_en',json_encode($en,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                    ticket_notice_set_setting('duplicate_enabled', isset($_POST['duplicate_enabled']) ? 'on' : '');
                    $dupHours = isset($_POST['duplicate_hours']) ? (int) $_POST['duplicate_hours'] : 24;
                    if ($dupHours <= 0) { $dupHours = 24; }
                    ticket_notice_set_setting('duplicate_hours', (string) $dupHours);
                    $kwZhInput = isset($_POST['keyword_rules_zh']) ? (string) $_POST['keyword_rules_zh'] : '[]';
                    $kwEnInput = isset($_POST['keyword_rules_en']) ? (string) $_POST['keyword_rules_en'] : '[]';
                    $kwZh = ticket_notice_decode_rules_input($kwZhInput);
                    $kwEn = ticket_notice_decode_rules_input($kwEnInput);
                    if (!is_array($kwZh)) { $kwZh = ticket_notice_parse_keyword_lines($kwZhInput, 'zh'); }
                    if (!is_array($kwEn)) { $kwEn = ticket_notice_parse_keyword_lines($kwEnInput, 'en'); }
                    ticket_notice_set_setting('keyword_rules_zh', json_encode(is_array($kwZh) ? $kwZh : [], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                    ticket_notice_set_setting('keyword_rules_en', json_encode(is_array($kwEn) ? $kwEn : [], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                    $message='中英文规则与智能配置已保存。';
                }
            }
        }
    }

    $zhRaw=ticket_notice_get_setting('rules_json_zh'); $enRaw=ticket_notice_get_setting('rules_json_en');
    $zhRules=is_array(json_decode($zhRaw,true))?json_decode($zhRaw,true):ticket_notice_default_rules_zh();
    $enRules=is_array(json_decode($enRaw,true))?json_decode($enRaw,true):ticket_notice_default_rules_en();
    $deptIds=array_unique(array_merge(array_keys($zhRules),array_keys($enRules))); sort($deptIds,SORT_NUMERIC);
    $rows=[]; foreach($deptIds as $id){$z=$zhRules[$id]??[];$e=$enRules[$id]??[];$rows[]=['deptid'=>(string)$id,'title_zh'=>$z['title']??'','items_zh'=>$z['items']??[],'warning_zh'=>$z['warning']??'','title_en'=>$e['title']??'','items_en'=>$e['items']??[],'warning_en'=>$e['warning']??''];}
    $departments=ticket_notice_get_departments();

    echo '<h3>Ticket Notice v1.4 稳定增强</h3><p>同一行配置：1=中文，2=英文；前台按 WHMCS 语言自动显示。</p>';
    if($message!=='') echo '<div class="alert alert-success">'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</div>';
    if($error!=='') echo '<div class="alert alert-danger">'.htmlspecialchars($error,ENT_QUOTES,'UTF-8').'</div>';

    echo '<form method="post" id="ticketNoticeBilingualForm"><input type="hidden" name="ticket_notice_save_bilingual" value="1"><input type="hidden" name="token" value="'.(isset($_SESSION['token'])?htmlspecialchars((string)$_SESSION['token'],ENT_QUOTES,'UTF-8'):'').'"><textarea id="ticketNoticeRowsJson" name="ticket_notice_rows_json" style="display:none"></textarea>';
    echo '<table class="table table-bordered" id="ticketNoticeRuleTable"><thead><tr><th>部门</th><th>中文标题(1)</th><th>中文提醒项(1)</th><th>中文警告(1)</th><th>English Title (2)</th><th>English Items (2)</th><th>English Warning (2)</th><th>操作</th></tr></thead><tbody></tbody></table>';
    $dupEnabled = ticket_notice_get_setting('duplicate_enabled');
    $dupHours = ticket_notice_get_setting('duplicate_hours'); if ($dupHours === '') { $dupHours = '24'; }
    $kwZhCurrent = ticket_notice_get_setting('keyword_rules_zh'); if ($kwZhCurrent === '') { $kwZhCurrent = '[]'; }
    $kwEnCurrent = ticket_notice_get_setting('keyword_rules_en'); if ($kwEnCurrent === '') { $kwEnCurrent = '[]'; }
    echo '<p><button type="button" class="btn btn-default" id="ticketNoticeAddRow">+ 添加规则</button> <button type="submit" class="btn btn-primary">保存双语规则</button></p>';
    echo '<hr><h4>重复工单检测配置</h4>';
    echo '<p><label><input type="checkbox" name="duplicate_enabled" ' . ($dupEnabled === 'on' ? 'checked' : '') . '> 启用重复工单拦截</label> 时间窗口(小时): <input type="number" min="1" name="duplicate_hours" value="' . htmlspecialchars((string) $dupHours, ENT_QUOTES, 'UTF-8') . '" style="width:90px"></p>';
    echo '<h4>关键词智能规则（中文）</h4><p class="text-muted">支持两种格式：1) JSON；2) 简写行格式，例如：<code>1,2,3,4,5,6,7,8:我是答案</code></p><textarea name="keyword_rules_zh" rows="6" style="width:100%;">' . htmlspecialchars((string) $kwZhCurrent, ENT_QUOTES, 'UTF-8') . '</textarea>';
    echo '<h4>Keyword Smart Rules (English)</h4><p class="text-muted">Supports JSON or shorthand lines: <code>dns,ttl,cloudflare:Try 8.8.8.8</code></p><textarea name="keyword_rules_en" rows="6" style="width:100%;">' . htmlspecialchars((string) $kwEnCurrent, ENT_QUOTES, 'UTF-8') . '</textarea>';
    echo '</form>';

    $rowsJson=json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); if($rowsJson===false)$rowsJson='[]';
    $depsJson=json_encode($departments,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); if($depsJson===false)$depsJson='[]';
    echo '<script>(function(){var rows='.$rowsJson.';var deps='.$depsJson.';var f=document.getElementById("ticketNoticeBilingualForm"),tb=document.querySelector("#ticketNoticeRuleTable tbody"),h=document.getElementById("ticketNoticeRowsJson"),add=document.getElementById("ticketNoticeAddRow");if(!f||!tb||!h||!add)return;function e(v){return String(v||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\"/g,"&quot;");} function opts(val){if(!deps.length)return "<input class=\\"form-control tn-deptid\\" value=\\""+e(val)+"\\">";var o="<select class=\\"form-control tn-deptid\\">";for(var i=0;i<deps.length;i++){var d=deps[i];o+="<option value=\\""+e(d.id)+"\\""+(String(d.id)===String(val)?" selected":"")+">"+e(d.name)+" (#"+e(d.id)+")</option>";}return o+="</select>";} function row(r){r=r||{};return "<td>"+opts(r.deptid||"")+"</td><td><input class=\\"form-control tn-title-zh\\" value=\\""+e(r.title_zh||"")+"\\"></td><td><textarea class=\\"form-control tn-items-zh\\" rows=\\"3\\">"+e((r.items_zh||[]).join("\\n"))+"</textarea></td><td><input class=\\"form-control tn-warning-zh\\" value=\\""+e(r.warning_zh||"")+"\\"></td><td><input class=\\"form-control tn-title-en\\" value=\\""+e(r.title_en||"")+"\\"></td><td><textarea class=\\"form-control tn-items-en\\" rows=\\"3\\">"+e((r.items_en||[]).join("\\n"))+"</textarea></td><td><input class=\\"form-control tn-warning-en\\" value=\\""+e(r.warning_en||"")+"\\"></td><td><button type=\\"button\\" class=\\"btn btn-danger btn-sm tn-del\\">删除</button></td>";} function addRow(r){var tr=document.createElement("tr");tr.innerHTML=row(r);tb.appendChild(tr);} if(!rows.length)addRow({}); else for(var i=0;i<rows.length;i++)addRow(rows[i]); add.addEventListener("click",function(){addRow({});}); tb.addEventListener("click",function(ev){var t=ev.target||ev.srcElement;if(t&&String(t.className).indexOf("tn-del")!==-1){var tr=t;while(tr&&tr.tagName!=="TR")tr=tr.parentNode;if(tr&&tr.parentNode)tr.parentNode.removeChild(tr);}}); f.addEventListener("submit",function(ev){var out=[];var trs=tb.querySelectorAll("tr");for(var i=0;i<trs.length;i++){var tr=trs[i],deptid=(tr.querySelector(".tn-deptid").value||"").trim();if(!deptid)continue;if(!/^\\d+$/.test(deptid)){alert("部门ID必须是数字");ev.preventDefault();return false;}var tzh=(tr.querySelector(".tn-title-zh").value||"").trim(),ten=(tr.querySelector(".tn-title-en").value||"").trim();var izh=(tr.querySelector(".tn-items-zh").value||"").split(/\\n+/).map(function(v){return v.trim();}).filter(Boolean),ien=(tr.querySelector(".tn-items-en").value||"").split(/\\n+/).map(function(v){return v.trim();}).filter(Boolean);if(!tzh||!ten||!izh.length||!ien.length){alert("请完整填写中英文标题与提醒项");ev.preventDefault();return false;}out.push({deptid:deptid,title_zh:tzh,items_zh:izh,warning_zh:(tr.querySelector(".tn-warning-zh").value||"").trim(),title_en:ten,items_en:ien,warning_en:(tr.querySelector(".tn-warning-en").value||"").trim()});}h.value=JSON.stringify(out);});})();</script>';
}
}
