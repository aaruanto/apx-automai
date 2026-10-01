@props(['id' => 'apx-alert-modal'])
{{--
    Single shared alert/confirm modal instance for a page. Content is filled
    in dynamically by public/assets/js/alert-modal.js (window.ApxAlertModal)
    since most flows in this app are AJAX success/error callbacks, not
    page-reload/session-flash. Reuses the existing .modal-overlay/.modal/...
    classes already defined for the admin panel and customer dashboard so it
    renders correctly in both skins without new CSS.

    Usage from JS:
        ApxAlertModal.show({
            variant: 'success' | 'error' | 'info' | 'confirm',
            title: '...', message: '...',
            confirmText: 'OK', cancelText: 'Cancel',
            onConfirm: () => {}, onCancel: () => {}
        });
--}}
<div class="modal-overlay" id="{{ $id }}">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title apx-alert-title"></div>
            <button type="button" class="modal-close" data-apx-alert-close aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body" style="display:flex;gap:14px;align-items:flex-start;">
            <div class="apx-alert-icon" aria-hidden="true"><i class="fas"></i></div>
            <p class="apx-alert-message" style="color:var(--text-muted);margin:0;flex:1;"></p>
        </div>
        <div class="modal-footer apx-alert-footer"></div>
    </div>
</div>
