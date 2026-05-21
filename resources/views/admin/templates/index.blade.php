@extends('layouts.admin')

@section('title', 'Message Templates')

@push('styles')
<style>
.msg-tab {
    background: none; border: none; cursor: pointer;
    padding: 9px 16px; font-size: .84rem; font-weight: 600;
    font-family: 'Barlow', sans-serif; color: var(--text-muted);
    border-bottom: 2px solid transparent; margin-bottom: -2px;
    border-radius: 6px 6px 0 0; display: flex; align-items: center; gap: 6px;
}
.msg-tab:hover { color: var(--text); background: var(--surface-2); }
.msg-tab.active { color: var(--red); border-bottom-color: var(--red); }
.tab-panel { display: none; }
.tab-panel.active { display: block; }
.placeholder-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 4px; font-size: .75rem; font-weight: 600;
    font-family: 'Barlow Condensed', sans-serif; letter-spacing: .02em;
    background: var(--red-glow); color: var(--red); border: 1px solid rgba(232,25,44,.2);
    cursor: pointer; transition: all .15s;
}
.placeholder-chip:hover { background: var(--red); color: #fff; }
.toolbar-btn {
    background: none; border: 1px solid var(--border); border-radius: 5px;
    padding: 5px 8px; cursor: pointer; color: var(--text-muted); font-size: .8rem;
    transition: all .15s;
}
.toolbar-btn:hover { background: var(--surface-2); color: var(--text); }
.toolbar-btn.active { background: var(--red-glow); color: var(--red); border-color: rgba(232,25,44,.3); }
.preview-shell {
    background: #f0f0f0; border-radius: 10px; padding: 16px;
    display: flex; flex-direction: column; align-items: center; gap: 12px;
}
.preview-browser-bar {
    width: 100%; background: #fff; border-radius: 6px; padding: 8px 12px;
    display: flex; align-items: center; gap: 8px; border: 1px solid rgba(0,0,0,.08);
}
.browser-dot { width: 8px; height: 8px; border-radius: 50%; }
.preview-email-body {
    width: 100%; max-width: 600px; background: #fff;
    border-radius: 8px; overflow: hidden; border: 1px solid rgba(0,0,0,.08); font-family: sans-serif;
}
.trigger-card {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 8px; padding: 14px 16px; display: flex; align-items: center; gap: 14px;
}
.trigger-toggle { position: relative; width: 42px; height: 22px; flex-shrink: 0; }
.trigger-toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; inset: 0; background: var(--surface-3);
    border-radius: 11px; cursor: pointer; transition: background .2s;
}
.toggle-slider::before {
    content: ''; position: absolute; height: 16px; width: 16px;
    left: 3px; top: 3px; background: #fff; border-radius: 50%;
    transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.trigger-toggle input:checked + .toggle-slider { background: var(--red); }
.trigger-toggle input:checked + .toggle-slider::before { transform: translateX(20px); }
.sms-counter { font-size: .72rem; color: var(--text-muted); text-align: right; margin-top: 4px; }
.sms-counter.warn { color: var(--warning); }
.sms-counter.over { color: var(--red); }
#emailBody {
    min-height: 280px; background: var(--surface-2);
    border: 1px solid var(--border); border-top: none;
    border-radius: 0 0 7px 7px; padding: 14px;
    font-size: .85rem; line-height: 1.7; color: var(--text); outline: none;
}
#emailBody:focus { border-color: rgba(232,25,44,.3); }
.editor-toolbar {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 7px 7px 0 0; padding: 8px 10px;
    display: flex; flex-wrap: wrap; gap: 4px; align-items: center;
}
.toolbar-sep { width: 1px; height: 20px; background: var(--border); margin: 0 4px; }
.test-banner {
    background: rgba(59,130,246,.08); border: 1px solid rgba(59,130,246,.2);
    border-radius: 8px; padding: 12px 16px;
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
}
</style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Message <span>Templates</span></h1>
            <ol class="breadcrumb">
                <li>Operations</li>
                <li class="active">Message Templates</li>
            </ol>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-ghost" onclick="openModal('testSendModal')">
                <i class="fas fa-paper-plane"></i> Send Test
            </button>
            <button class="btn btn-primary" onclick="saveAllTemplates()">
                <i class="fas fa-floppy-disk"></i> Save All Templates
            </button>
        </div>
    </div>

    <div class="test-banner" style="margin-bottom:24px;">
        <i class="fas fa-circle-info" style="color:#3b82f6;font-size:1rem;flex-shrink:0;"></i>
        <span style="font-size:.83rem;color:var(--text-muted);">
            Templates are sent automatically via <strong style="color:var(--text);">scheduled cron jobs</strong>.
            Use <strong style="color:var(--red);">{placeholders}</strong> to personalize each message for the recipient.
            Changes take effect on the next scheduled run.
        </span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

        <!-- LEFT — EDITOR -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <div class="card">
                <div style="display:flex;gap:4px;border-bottom:2px solid var(--border);padding:0 18px;" id="channelTabs">
                    <button class="msg-tab active" data-tab="email" onclick="switchChannel('email',this)">
                        <i class="fas fa-envelope"></i> Email Template
                    </button>
                    <button class="msg-tab" data-tab="sms" onclick="switchChannel('sms',this)">
                        <i class="fas fa-mobile-screen"></i> SMS Template
                    </button>
                </div>

                <!-- EMAIL EDITOR -->
                <div class="tab-panel active" id="panel-email">
                    <div class="card-body" style="padding:18px;">
                        <div class="form-group">
                            <label class="form-label">Email Subject Line</label>
                            <input class="form-control" type="text" id="emailSubject"
                                   value="{{ $settings['email_subject'] ?? 'Reminder: Your {service} appointment is on {date}' }}"
                                   placeholder="e.g. Your appointment at APX AutoMai is coming up!" />
                            <div class="form-hint">Use placeholders like {name}, {date}, {service} to personalize.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Preheader Text <span style="font-weight:400;color:var(--text-muted);">(preview text shown in inbox)</span></label>
                            <input class="form-control" type="text" id="emailPreheader"
                                   value="{{ $settings['email_preheader'] ?? 'Hi {name}, just a reminder about your upcoming {service}.' }}"
                                   placeholder="Short preview shown under the subject in inbox" />
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Email Body</label>
                            <div class="editor-toolbar">
                                <button class="toolbar-btn" onclick="execCmd('bold')" title="Bold"><i class="fas fa-bold"></i></button>
                                <button class="toolbar-btn" onclick="execCmd('italic')" title="Italic"><i class="fas fa-italic"></i></button>
                                <button class="toolbar-btn" onclick="execCmd('underline')" title="Underline"><i class="fas fa-underline"></i></button>
                                <div class="toolbar-sep"></div>
                                <button class="toolbar-btn" onclick="execCmd('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                                <button class="toolbar-btn" onclick="execCmd('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
                                <div class="toolbar-sep"></div>
                                <button class="toolbar-btn" onclick="execCmd('justifyLeft')"><i class="fas fa-align-left"></i></button>
                                <button class="toolbar-btn" onclick="execCmd('justifyCenter')"><i class="fas fa-align-center"></i></button>
                                <div class="toolbar-sep"></div>
                                <select class="filter-select" style="padding:4px 8px;font-size:.78rem;" onchange="execCmd('fontSize', this.value); this.value=''">
                                    <option value="">Font size</option>
                                    <option value="2">Small</option>
                                    <option value="3">Normal</option>
                                    <option value="4">Large</option>
                                    <option value="5">X-Large</option>
                                </select>
                                <div class="toolbar-sep"></div>
                                <button class="toolbar-btn" onclick="insertLink()"><i class="fas fa-link"></i></button>
                                <button class="toolbar-btn" onclick="insertDivider()"><i class="fas fa-minus"></i></button>
                                <div class="toolbar-sep"></div>
                                <button class="toolbar-btn" onclick="resetEmailBody()" style="font-size:.7rem;padding:5px 10px;">
                                    <i class="fas fa-rotate-left"></i> Reset
                                </button>
                            </div>
                            <div id="emailBody" contenteditable="true"></div>
                        </div>
                    </div>
                </div>

                <!-- SMS EDITOR -->
                <div class="tab-panel" id="panel-sms">
                    <div class="card-body" style="padding:18px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">SMS Message Body</label>
                            <textarea class="form-control" id="smsBody" rows="5"
                                      oninput="updateSmsCounter(this)"
                                      style="resize:none;font-family:'Barlow',sans-serif;line-height:1.6;">{{ $settings['sms_body'] ?? 'Hi {name}! This is a reminder from APX AutoMai. Your {service} appointment is scheduled on {date} at {time}. Please arrive 10 minutes early. Reply STOP to unsubscribe.' }}</textarea>
                            <div class="sms-counter" id="smsCounter">0 / 160 characters (1 SMS)</div>
                        </div>
                        <div style="margin-top:12px;background:var(--surface-2);border-radius:8px;padding:12px;">
                            <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">SMS Preview</div>
                            <div style="background:#e5e5ea;border-radius:14px 14px 2px 14px;padding:10px 14px;font-size:.84rem;line-height:1.5;max-width:280px;color:#000;" id="smsPreview">
                                Hi {name}! This is a reminder from APX AutoMai...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMAIL PREVIEW -->
            <div class="card" id="emailPreviewCard">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-eye"></i> Live Email Preview</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button class="toolbar-btn active" id="previewDesktop" onclick="setPreviewWidth('600px', this)"><i class="fas fa-desktop"></i></button>
                        <button class="toolbar-btn" id="previewMobile" onclick="setPreviewWidth('375px', this)"><i class="fas fa-mobile-screen"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="preview-shell">
                        <div class="preview-browser-bar">
                            <div class="browser-dot" style="background:#ff5f56;"></div>
                            <div class="browser-dot" style="background:#ffbd2e;"></div>
                            <div class="browser-dot" style="background:#27c93f;"></div>
                            <div style="flex:1;background:#f0f0f0;border-radius:4px;padding:4px 10px;font-size:.72rem;color:#999;">
                                APX AutoMai — Booking Reminder
                            </div>
                        </div>
                        <div id="emailPreviewWrap" style="width:600px;max-width:100%;transition:width .3s;">
                            <div class="preview-email-body" id="emailPreviewBody"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT — SIDEBAR -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Placeholders -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-tags"></i> Available Placeholders</div>
                </div>
                <div class="card-body" style="padding:14px;">
                    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:10px;">Click to insert at cursor position</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @php
                            $placeholders = [
                                ['{name}','Customer full name'],['{first_name}','First name only'],
                                ['{service}','Service booked'],['{date}','Booking date'],
                                ['{time}','Booking time'],['{plate}','Vehicle plate number'],
                                ['{vehicle}','Vehicle make & model'],['{reference}','Booking reference #'],
                                ['{branch}','Branch name'],['{contact}','Branch contact number'],
                                ['{days_until}','Days until appointment'],['{loyalty_tier}','Customer loyalty tier'],
                            ];
                        @endphp
                        @foreach($placeholders as [$tag, $desc])
                        <span class="placeholder-chip" title="{{ $desc }}" onclick="insertPlaceholder('{{ $tag }}')">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Trigger Schedule -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-clock"></i> Trigger Schedule</div>
                </div>
                <div class="card-body" style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:4px;">
                        Enable which reminders are sent automatically.
                    </div>
                    @php
                        $triggerDefs = [
                            ['key'=>'remind_7d','label'=>'7 days before','desc'=>'One week reminder','icon'=>'fa-calendar-week'],
                            ['key'=>'remind_3d','label'=>'3 days before','desc'=>'Three day reminder','icon'=>'fa-calendar-days'],
                            ['key'=>'remind_1d','label'=>'24 hours before','desc'=>'Day-of reminder','icon'=>'fa-calendar-day'],
                            ['key'=>'remind_2h','label'=>'2 hours before','desc'=>'Same-day final nudge','icon'=>'fa-clock'],
                        ];
                    @endphp
                    @foreach($triggerDefs as $t)
                    <div class="trigger-card">
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--red-glow);display:flex;align-items:center;justify-content:center;color:var(--red);font-size:.8rem;flex-shrink:0;">
                            <i class="fas {{ $t['icon'] }}"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:.84rem;font-weight:600;color:var(--text);">{{ $t['label'] }}</div>
                            <div style="font-size:.73rem;color:var(--text-muted);">{{ $t['desc'] }}</div>
                        </div>
                        <label class="trigger-toggle">
                            <input type="checkbox" name="{{ $t['key'] }}"
                                {{ ($settings['triggers'][$t['key']] ?? false) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Channel Settings -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-sliders"></i> Channel Settings</div>
                </div>
                <div class="card-body" style="padding:14px;display:flex;flex-direction:column;gap:12px;">
                    @php
                        $channelDefs = [
                            ['key'=>'send_email','label'=>'Send via Email','desc'=>'Uses configured SMTP/Gmail'],
                            ['key'=>'send_sms','label'=>'Send via SMS','desc'=>'Uses configured SMS gateway'],
                        ];
                    @endphp
                    @foreach($channelDefs as $ch)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div>
                            <div style="font-size:.84rem;font-weight:600;color:var(--text);">{{ $ch['label'] }}</div>
                            <div style="font-size:.72rem;color:var(--text-muted);">{{ $ch['desc'] }}</div>
                        </div>
                        <label class="trigger-toggle">
                            <input type="checkbox" name="{{ $ch['key'] }}"
                                {{ ($settings['channels'][$ch['key']] ?? false) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    @endforeach
                    <hr style="border:none;border-top:1px solid var(--border);" />
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:.78rem;">Sender Name</label>
                        <input class="form-control" type="text" name="sender_name"
                               value="{{ $settings['sender_name'] ?? 'APX AutoMai' }}" />
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:.78rem;">Reply-To Email</label>
                        <input class="form-control" type="email" name="reply_to"
                               value="{{ $settings['reply_to'] ?? '' }}"
                               placeholder="apxmotors@email.com" />
                    </div>
                </div>
            </div>

            <!-- Cron Status -->
            <div class="card" style="border-color:rgba(34,197,94,.2);">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-terminal"></i> Cron Status</div>
                    <span class="badge badge-active">Running</span>
                </div>
                <div class="card-body" style="padding:14px;">
                    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:8px;">
                        Add this to your server's crontab to enable automated reminders:
                    </div>
                    <div style="background:var(--surface-3);border-radius:6px;padding:10px 12px;font-family:monospace;font-size:.73rem;color:var(--text);word-break:break-all;">
                        * * * * * cd /path/to/apx-automai && php artisan schedule:run >> /dev/null 2>&1
                    </div>
                    <div style="margin-top:10px;font-size:.75rem;color:var(--text-muted);">
                        <i class="fas fa-circle" style="color:#22c55e;font-size:.5rem;margin-right:4px;"></i>
                        Last run: <strong style="color:var(--text);">{{ now()->format('M d, Y g:i A') }}</strong>
                    </div>
                    <div style="margin-top:4px;font-size:.75rem;color:var(--text-muted);">
                        <i class="fas fa-circle-info" style="font-size:.65rem;margin-right:4px;"></i>
                        Next run in: <strong style="color:var(--text);" id="nextRunTimer">--:--</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('modals')
<div class="modal-overlay" id="testSendModal">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-paper-plane" style="color:var(--red);margin-right:8px;"></i> Send Test Message</div>
            <button class="modal-close" onclick="closeModal('testSendModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.templates.test') }}">
            @csrf
            <div class="modal-body">
                <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.8rem;color:var(--text-muted);">
                    <i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i>
                    Placeholders will be filled with <strong style="color:var(--text);">sample data</strong> for the test send.
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Test Email</label>
                        <input class="form-control" type="email" name="test_email" placeholder="you@example.com" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Test Phone (SMS)</label>
                        <input class="form-control" type="tel" name="test_phone" placeholder="09XXXXXXXXX" />
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Which template to test</label>
                    <select class="form-control" name="template_type">
                        <option value="7d">7-day reminder</option>
                        <option value="3d">3-day reminder</option>
                        <option value="1d">24-hour reminder</option>
                        <option value="2h">2-hour reminder</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('testSendModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Test</button>
            </div>
        </form>
    </div>
</div>

<div id="saveToast" style="
    position:fixed;bottom:24px;right:24px;z-index:9999;
    background:#22c55e;color:#fff;padding:12px 20px;border-radius:8px;
    font-size:.84rem;font-weight:600;display:none;align-items:center;gap:8px;
    box-shadow:0 4px 20px rgba(0,0,0,.2);">
    <i class="fas fa-circle-check"></i> Templates saved successfully
</div>
@endsection

@push('scripts')
<script>
    // Expose PHP data to JS
    const _csrfToken   = '{{ csrf_token() }}';
    const _savedBody   = {!! json_encode($settings['email_body'] ?? '') !!};
    const _savedSms    = {!! json_encode($settings['sms_body'] ?? '') !!};
</script>
<script>
@verbatim
function defaultEmailTemplate() {
    return `
<div style="background:#f4f5f7;padding:32px 16px;font-family:sans-serif;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#E8192C;padding:28px 32px;text-align:center;">
      <div style="font-family:sans-serif;font-weight:900;font-size:1.4rem;color:#fff;letter-spacing:.04em;">APX <span style="font-weight:400;">AUTOMAI</span></div>
      <div style="color:rgba(255,255,255,.7);font-size:.8rem;margin-top:4px;letter-spacing:.1em;text-transform:uppercase;">Service Reminder</div>
    </div>
    <div style="padding:32px;">
      <p style="font-size:1rem;color:#111;margin:0 0 12px;font-weight:600;">Hi {name},</p>
      <p style="font-size:.9rem;color:#4b5563;line-height:1.7;margin:0 0 20px;">
        This is a friendly reminder that your <strong>{service}</strong> appointment is coming up soon.
        We want to make sure you're all set!
      </p>
      <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin:0 0 24px;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:12px;">Booking Details</div>
        <table style="width:100%;border-collapse:collapse;">
          <tr><td style="padding:6px 0;font-size:.83rem;color:#6b7280;">Service</td><td style="padding:6px 0;font-size:.83rem;font-weight:600;color:#111;text-align:right;">{service}</td></tr>
          <tr><td style="padding:6px 0;font-size:.83rem;color:#6b7280;border-top:1px solid #f3f4f6;">Date</td><td style="padding:6px 0;font-size:.83rem;font-weight:600;color:#111;text-align:right;border-top:1px solid #f3f4f6;">{date}</td></tr>
          <tr><td style="padding:6px 0;font-size:.83rem;color:#6b7280;border-top:1px solid #f3f4f6;">Time</td><td style="padding:6px 0;font-size:.83rem;font-weight:600;color:#111;text-align:right;border-top:1px solid #f3f4f6;">{time}</td></tr>
          <tr><td style="padding:6px 0;font-size:.83rem;color:#6b7280;border-top:1px solid #f3f4f6;">Vehicle</td><td style="padding:6px 0;font-size:.83rem;font-weight:600;color:#111;text-align:right;border-top:1px solid #f3f4f6;">{plate} — {vehicle}</td></tr>
          <tr><td style="padding:6px 0;font-size:.83rem;color:#6b7280;border-top:1px solid #f3f4f6;">Reference</td><td style="padding:6px 0;font-size:.83rem;font-weight:600;color:#E8192C;text-align:right;border-top:1px solid #f3f4f6;">{reference}</td></tr>
        </table>
      </div>
      <p style="font-size:.85rem;color:#4b5563;line-height:1.6;margin:0 0 24px;">
        Please arrive at least <strong>10 minutes early</strong>. If you need to reschedule or have any questions,
        contact us at <strong>{contact}</strong>.
      </p>
      <div style="text-align:center;margin:0 0 24px;">
        <a href="#" style="display:inline-block;background:#E8192C;color:#fff;padding:12px 28px;border-radius:7px;text-decoration:none;font-weight:700;font-size:.88rem;letter-spacing:.02em;">View My Booking</a>
      </div>
    </div>
    <div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;">
      <div style="font-size:.75rem;color:#9ca3af;line-height:1.6;">
        <strong style="color:#6b7280;">APX AutoMai</strong> &mdash; {branch}<br />
        You received this because you have an upcoming booking.
      </div>
    </div>
  </div>
</div>`;
}

const emailBody = document.getElementById('emailBody');

// Load saved email body or default
if (_savedBody && _savedBody.trim() !== '') {
    emailBody.innerHTML = _savedBody;
} else {
    emailBody.innerHTML = defaultEmailTemplate();
}

// Load saved SMS body
if (_savedSms && _savedSms.trim() !== '') {
    document.getElementById('smsBody').value = _savedSms;
}

updateEmailPreview();
updateSmsCounter(document.getElementById('smsBody'));

function resetEmailBody() {
    emailBody.innerHTML = defaultEmailTemplate();
    updateEmailPreview();
}

function execCmd(cmd, val = null) {
    emailBody.focus();
    document.execCommand(cmd, false, val);
    updateEmailPreview();
}

function insertLink() {
    const url = prompt('Enter URL:', 'https://');
    if (url) { emailBody.focus(); document.execCommand('createLink', false, url); updateEmailPreview(); }
}

function insertDivider() {
    emailBody.focus();
    document.execCommand('insertHTML', false, '<hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0;" />');
    updateEmailPreview();
}

function updateEmailPreview() {
    const preview = document.getElementById('emailPreviewBody');
    const body    = emailBody.innerHTML;
    const sampleData = {
        '{name}': 'Juan dela Cruz', '{first_name}': 'Juan',
        '{service}': 'Full Car Wash', '{date}': 'May 5, 2026',
        '{time}': '9:00 AM', '{plate}': 'ABC 1234',
        '{vehicle}': 'Toyota Vios 2021', '{reference}': '#BK-0042',
        '{branch}': 'Tandang Sora Branch', '{contact}': '(02) 8123-4567',
        '{days_until}': '3', '{loyalty_tier}': 'Gold',
    };
    let previewHtml = body;
    Object.entries(sampleData).forEach(([k, v]) => {
        previewHtml = previewHtml.replaceAll(k, `<span style="color:#E8192C;font-weight:600;">${v}</span>`);
    });
    preview.innerHTML = previewHtml;
}

emailBody.addEventListener('input', updateEmailPreview);
document.getElementById('emailSubject').addEventListener('input', updateEmailPreview);

function setPreviewWidth(w, btn) {
    document.getElementById('emailPreviewWrap').style.width = w;
    document.querySelectorAll('#previewDesktop,#previewMobile').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function switchChannel(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.msg-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    btn.classList.add('active');
    document.getElementById('emailPreviewCard').style.display = tab === 'email' ? '' : 'none';
}

function insertPlaceholder(tag) {
    const activeTab = document.querySelector('.msg-tab.active').dataset.tab;
    if (activeTab === 'sms') {
        const ta = document.getElementById('smsBody');
        const s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.substring(0, s) + tag + ta.value.substring(e);
        ta.selectionStart = ta.selectionEnd = s + tag.length;
        ta.focus();
        updateSmsCounter(ta);
    } else {
        emailBody.focus();
        document.execCommand('insertText', false, tag);
        updateEmailPreview();
    }
}

function updateSmsCounter(ta) {
    const len     = ta.value.length;
    const sms     = Math.ceil(len / 160) || 1;
    const counter = document.getElementById('smsCounter');
    counter.textContent = `${len} / ${sms * 160} characters (${sms} SMS)`;
    counter.className   = 'sms-counter' + (len > 160 ? ' warn' : '') + (len > 320 ? ' over' : '');
    document.getElementById('smsPreview').textContent =
        ta.value.substring(0, 120) + (ta.value.length > 120 ? '...' : '');
}

function saveAllTemplates() {
    const payload = {
        email_subject:   document.getElementById('emailSubject').value,
        email_preheader: document.getElementById('emailPreheader').value,
        email_body:      emailBody.innerHTML,
        sms_body:        document.getElementById('smsBody').value,
        triggers: {
            remind_7d: document.querySelector('[name="remind_7d"]').checked,
            remind_3d: document.querySelector('[name="remind_3d"]').checked,
            remind_1d: document.querySelector('[name="remind_1d"]').checked,
            remind_2h: document.querySelector('[name="remind_2h"]').checked,
        },
        channels: {
            send_email: document.querySelector('[name="send_email"]').checked,
            send_sms:   document.querySelector('[name="send_sms"]').checked,
        },
        sender_name: document.querySelector('[name="sender_name"]').value,
        reply_to:    document.querySelector('[name="reply_to"]').value,
    };
    fetch('/admin/templates/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrfToken },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(() => {
        const toast = document.getElementById('saveToast');
        toast.style.display = 'flex';
        setTimeout(() => toast.style.display = 'none', 3000);
    })
    .catch(() => alert('Failed to save. Please try again.'));
}

function updateCronTimer() {
    const now  = new Date();
    const next = new Date();
    next.setHours(8, 0, 0, 0);
    if (now >= next) next.setDate(next.getDate() + 1);
    const diff = next - now;
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    document.getElementById('nextRunTimer').textContent =
        `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}
updateCronTimer();
setInterval(updateCronTimer, 1000);
@endverbatim
</script>
@endpush