{{--
    Bell with unread badge and a dropdown of the latest notifications.

    Used by both the admin layout and the customer dashboard, so there is one
    implementation rather than two that drift. Styling is inline because the
    two skins define different class vocabularies; only the behaviour is shared.

    The badge count is refreshed by assets/js/notification-bell.js, which the
    dashboards also feed from their existing poll rather than adding a second one.
--}}
<div class="apx-bell-wrap" style="position:relative;display:inline-flex;">
    <button type="button" id="apxBellBtn" title="Notifications" aria-label="Notifications"
            style="position:relative;background:none;border:1px solid transparent;color:inherit;cursor:pointer;
                   width:36px;height:36px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
        <i class="fas fa-bell"></i>
        <span id="apxBellCount"
              style="display:none;position:absolute;top:2px;right:2px;min-width:16px;height:16px;padding:0 4px;
                     border-radius:999px;background:var(--red);color:#fff;font-size:.62rem;font-weight:700;
                     line-height:16px;text-align:center;">0</span>
    </button>

    <div id="apxBellPanel"
         style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:340px;max-width:calc(100vw - 32px);
                background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:10px;
                box-shadow:0 18px 44px rgba(0,0,0,.18);z-index:9500;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;
                    border-bottom:1px solid var(--border,#e5e7eb);">
            <strong style="font-size:.82rem;">Notifications</strong>
            <button type="button" id="apxBellReadAll"
                    style="background:none;border:none;color:var(--red);font-size:.74rem;font-weight:600;cursor:pointer;">
                Mark all read
            </button>
        </div>

        <div id="apxBellList" style="max-height:340px;overflow-y:auto;">
            <div style="padding:18px;text-align:center;color:var(--text-muted,#888);font-size:.8rem;">Loading…</div>
        </div>

        <a href="{{ route('notifications.index') }}"
           style="display:block;padding:10px;text-align:center;font-size:.78rem;font-weight:600;
                  color:var(--red);text-decoration:none;border-top:1px solid var(--border,#e5e7eb);">
            View all notifications
        </a>
    </div>
</div>
