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
        var i18n = window.ticketNoticeI18n || {};
        var allowSubmit = false;

        $('#ticketNoticeModalLabel').text(i18n.modalTitle || '提交工单前请阅读');
        $('#ticketNoticeConfirmLabel').text(i18n.confirmLabel || '我已阅读并理解以上内容');
        $('#ticketNoticeCheckboxError').text(i18n.checkboxError || '请先勾选确认后再继续提交。');
        $('#ticketNoticeCancelBtn').text(i18n.btnCancel || '返回修改');
        $('#ticketNoticeProceedBtn').text(i18n.btnProceed || '确认并提交');

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
            if (allowSubmit) {
                return true;
            }

            var deptId = $dept.val();
            var rule = getRuleByDeptId(deptId);

            if (!rule) {
                return true;
            }

            e.preventDefault();
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
