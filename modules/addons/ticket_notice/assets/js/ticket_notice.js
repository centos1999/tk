(function ($) {
    'use strict';

    $(function () {
        var $form = $('form[action*="submitticket.php"]:has(select[name="deptid"])').first();
        if (!$form.length) {
            return;
        }

        var $dept = $form.find('select[name="deptid"]');
        var $modal = $('#ticketNoticeModal');
        var $title = $('#ticketNoticeTitle');
        var $list = $('#ticketNoticeList');
        var $warning = $('#ticketNoticeWarning');
        var $checkbox = $('#ticketNoticeConfirmCheckbox');
        var $checkboxError = $('#ticketNoticeCheckboxError');
        var $proceedBtn = $('#ticketNoticeProceedBtn');

        var rules = window.ticketNoticeRules || {};
        var keywordRules = window.ticketNoticeKeywordRules || [];
        var duplicateTicket = window.ticketNoticeDuplicate || null;
        var i18n = window.ticketNoticeI18n || {};
        var allowSubmit = false;

        $('#ticketNoticeModalLabel').text(i18n.modalTitle || '提交工单前请阅读');
        $('#ticketNoticeConfirmLabel').text(i18n.confirmLabel || '我已阅读并理解以上内容');
        $('#ticketNoticeCheckboxError').text(i18n.checkboxError || '请先勾选确认后再继续提交。');
        $('#ticketNoticeCancelBtn').text(i18n.btnCancel || '返回修改');
        $('#ticketNoticeProceedBtn').text(i18n.btnProceed || '确认并提交');

        function getTextPayload() {
            var subject = ($form.find('input[name="subject"]').val() || '').toLowerCase();
            var message = ($form.find('textarea[name="message"]').val() || '').toLowerCase();
            return subject + ' ' + message;
        }

        function matchSmartRule(text) {
            for (var i = 0; i < keywordRules.length; i++) {
                var r = keywordRules[i] || {};
                var any = r.keywords_any || [];
                var all = r.keywords_all || [];
                var anyMatched = any.length === 0;
                for (var j = 0; j < any.length; j++) {
                    if (text.indexOf(String(any[j]).toLowerCase()) !== -1) { anyMatched = true; break; }
                }
                var allMatched = true;
                for (var k = 0; k < all.length; k++) {
                    if (text.indexOf(String(all[k]).toLowerCase()) === -1) { allMatched = false; break; }
                }
                if (anyMatched && allMatched) { return r; }
            }
            return null;
        }

        function renderSmart(rule) {
            var $block = $('#ticketNoticeSmartBlock');
            var $sTitle = $('#ticketNoticeSmartTitle');
            var $sList = $('#ticketNoticeSmartList');
            var $sLinks = $('#ticketNoticeSmartLinks');
            $sList.empty(); $sLinks.empty();
            if (!rule) { $block.hide(); return; }
            $sTitle.text(rule.title || i18n.smartTitle || 'Smart Suggestions');
            var suggestions = rule.suggestions || [];
            for (var i = 0; i < suggestions.length; i++) { $sList.append('<li>' + escapeHtml(suggestions[i]) + '</li>'); }
            var links = rule.links || [];
            for (var j = 0; j < links.length; j++) {
                var link = links[j] || {};
                if (link.url) { $sLinks.append('<div><a href="' + escapeHtml(link.url) + '" target="_blank" rel="noopener">' + escapeHtml(link.label || link.url) + '</a></div>'); }
            }
            $block.show();
        }

        if (!$form.find('input[name="ticket_notice_confirmed"]').length) {
            $form.append('<input type="hidden" name="ticket_notice_confirmed" value="0">');
        }

        function getRuleByDeptId(deptId) {
            var id = String(deptId || '');
            return rules[id] || null;
        }

        function escapeHtml(text) {
            return $('<div>').text(text || '').html();
        }

        function renderModal(rule) {
            $title.text(rule.title || i18n.defaultTitle || '提交工单提醒');
            $list.empty();

            var items = rule.items || [];
            for (var i = 0; i < items.length; i++) {
                $list.append('<li>' + escapeHtml(items[i]) + '</li>');
            }

            $warning.text(rule.warning || '');
            $checkbox.prop('checked', false);
            $checkboxError.hide();
        }

        function showModal(rule) {
            renderModal(rule);
            $modal.modal('show');
        }

        $form.on('submit.ticketNotice', function (e) {
            if (duplicateTicket && duplicateTicket.id) {
                e.preventDefault();
                alert((i18n.dupWarn || 'You already have a pending ticket: ') + '#' + (duplicateTicket.tid || duplicateTicket.id));
                return false;
            }
            if (allowSubmit) {
                return true;
            }

            var deptId = $dept.val();
            var rule = getRuleByDeptId(deptId);

            if (!rule) {
                return true;
            }

            e.preventDefault();
            renderSmart(matchSmartRule(getTextPayload()));
            showModal(rule);
            return false;
        });

        $proceedBtn.on('click.ticketNotice', function () {
            if (!$checkbox.is(':checked')) {
                $checkboxError.show();
                return;
            }

            $form.find('input[name="ticket_notice_confirmed"]').val('1');
            allowSubmit = true;
            $modal.modal('hide');
            $form.trigger('submit');
        });

        $modal.on('hidden.bs.modal', function () {
            if (!allowSubmit) {
                $form.find('input[name="ticket_notice_confirmed"]').val('0');
            }
        });
    });
})(jQuery);
