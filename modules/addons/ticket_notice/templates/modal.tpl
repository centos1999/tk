<div class="modal fade" id="ticketNoticeModal" tabindex="-1" role="dialog" aria-labelledby="ticketNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketNoticeModalLabel">提交工单前请阅读</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4 class="ticket-notice-title" id="ticketNoticeTitle"></h4>
                <ul class="ticket-notice-list" id="ticketNoticeList"></ul>
                <p class="ticket-notice-warning text-danger" id="ticketNoticeWarning"></p>
                <div id="ticketNoticeSmartBlock" style="display:none;">
                    <hr>
                    <h5 id="ticketNoticeSmartTitle"></h5>
                    <ul id="ticketNoticeSmartList"></ul>
                    <div id="ticketNoticeSmartLinks"></div>
                </div>

                <div class="checkbox ticket-notice-checkbox-wrapper">
                    <label>
                        <input type="checkbox" id="ticketNoticeConfirmCheckbox">
                        <span id="ticketNoticeConfirmLabel">我已阅读并理解以上内容</span>
                    </label>
                </div>
                <p class="text-danger ticket-notice-checkbox-error" id="ticketNoticeCheckboxError">请先勾选确认后再继续提交。</p>
                <div class="alert alert-warning" id="ticketNoticeDuplicateBlock" style="display:none; margin-top:10px;">
                    <div id="ticketNoticeDuplicateText"></div>
                    <a href="#" id="ticketNoticeDuplicateLink" class="btn btn-warning btn-sm" style="margin-top:8px; display:none;">查看已有工单</a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><span id="ticketNoticeCancelBtn">返回修改</span></button>
                <button type="button" class="btn btn-primary" id="ticketNoticeProceedBtn">确认并提交</button>
            </div>
        </div>
    </div>
</div>
