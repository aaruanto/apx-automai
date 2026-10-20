@props(['id' => 'apx-alert-modal'])
{{--
    Single shared alert/confirm modal instance for a page. Content is filled
    in dynamically by public/assets/js/alert-modal.js (window.ApxAlertModal)
    since most flows in this app are AJAX success/error callbacks, not
    page-reload/session-flash. Keeps the .modal-overlay/.modal class names the
    admin panel and customer dashboard already style, so it inherits both
    skins — but no longer DEPENDS on them.

    It used to. The assumption was that every page carrying this component
    also carried one of those stylesheets, and the landing page carries
    neither: the overlay rendered unstyled, with no positioning and no
    z-index, and the inner .modal collided with Bootstrap's own .modal, which
    is display:none. The result was an alert box of zero height sitting behind
    the booking dialog, so a failed booking looked like a dead Confirm button
    — the error was raised, written to the console, and never seen.

    The styles below are scoped to this component's id, so they win over a
    page's generic .modal rules without touching any other modal, and they
    read the admin tokens where those exist while falling back to literals
    where they do not.

    Usage from JS:
        ApxAlertModal.show({
            variant: 'success' | 'error' | 'info' | 'confirm',
            title: '...', message: '...',
            confirmText: 'OK', cancelText: 'Cancel',
            onConfirm: () => {}, onCancel: () => {}
        });
--}}
<style>
    /* Scoped to this id so it cannot disturb any other modal on the page. */
    #{{ $id }} {
        position: fixed;
        inset: 0;
        /* Above Bootstrap's modal (1055) and backdrop (1050), and above the
           admin overlay (9000), so an error is never raised behind the thing
           that caused it. */
        z-index: 10050;
        background: rgba(0, 0, 0, .5);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    #{{ $id }}.open { display: flex; }

    /* Re-declared because `.modal` is also a Bootstrap class, and on any page
       that loads Bootstrap it would otherwise be display:none with Bootstrap's
       own positioning. */
    #{{ $id }} > .modal {
        position: static;
        display: block;
        /* Bootstrap's .modal is `position:fixed; height:100%`. The overrides
           below used to reset position, display, overflow and width but not
           height, so on any page that loads Bootstrap this box stretched to
           fill the overlay — 868px tall on a 900px screen, with the message
           and the OK button at the top and roughly 670px of empty white
           beneath them. Only the height was ever wrong; the content was fine.

           max-height keeps a long message scrollable instead of letting the
           dialog grow past the screen. */
        height: auto;
        max-height: 90vh;
        overflow-x: hidden;
        overflow-y: auto;
        width: 100%;
        max-width: 420px;
        background: var(--surface, #fff);
        border: 1px solid var(--border, #e8e8e8);
        border-radius: 12px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .3);
        color: var(--text, #222);
        font-family: 'Ubuntu', system-ui, sans-serif;
    }
    #{{ $id }} .modal-header,
    #{{ $id }} .modal-footer {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
    }
    #{{ $id }} .modal-header { justify-content: space-between; border-bottom: 1px solid var(--border, #eee); }
    #{{ $id }} .modal-footer { justify-content: flex-end; border-top: 1px solid var(--border, #eee); }
    #{{ $id }} .modal-body { padding: 16px 18px; }
    #{{ $id }} .modal-title { font-weight: 700; font-size: 1rem; }
    #{{ $id }} .modal-close {
        background: none; border: none; cursor: pointer; font-size: 1rem;
        color: var(--text-muted, #888); line-height: 1; padding: 4px;
    }
    #{{ $id }} .apx-alert-icon {
        flex: 0 0 auto; width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
</style>

<div class="modal-overlay" id="{{ $id }}">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title apx-alert-title"></div>
            <button type="button" class="modal-close" data-apx-alert-close aria-label="Close">
                {{-- fa-times, not fa-xmark. The admin layout loads Font
                     Awesome 6 and the public pages load 5, and fa-xmark only
                     exists in 6 — so on the landing page this close button
                     rendered as an empty square. v6 keeps the v5 name as an
                     alias, so this one resolves under both. --}}
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="display:flex;gap:14px;align-items:flex-start;">
            <div class="apx-alert-icon" aria-hidden="true"><i class="fas fa-info-circle"></i></div>
            {{-- pre-line so a multi-line message (e.g. one line per channel of
                 a test send) keeps its breaks. The message is set with
                 textContent, so markup is never interpreted. --}}
            <p class="apx-alert-message" style="color:var(--text-muted);margin:0;flex:1;white-space:pre-line;"></p>
        </div>
        <div class="modal-footer apx-alert-footer"></div>
    </div>
</div>
