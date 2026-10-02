<style>
    .monthly-session-widget { position:fixed; z-index:1200; top:5.25rem; right:1rem; display:flex; align-items:center; gap:.55rem; max-width:calc(100vw - 2rem); padding:.55rem .65rem; border:1px solid var(--border); border-radius:14px; background:var(--surface); box-shadow:0 8px 30px rgba(15,23,42,.2); font-size:.84rem; }
    .monthly-session-widget button,.monthly-session-dialog button { min-height:38px; padding:.5rem .75rem; border:0; border-radius:9px; background:var(--accent); color:#fff; font:inherit; font-weight:750; cursor:pointer; }
    .monthly-session-widget button:disabled,.monthly-session-dialog button:disabled { opacity:.6; cursor:wait; }
    .monthly-session-widget [hidden] { display:none; }
    .monthly-session-widget-minimize { width:34px; min-height:34px!important; padding:.25rem!important; background:var(--bg)!important; color:var(--text)!important; font-size:1.05rem!important; }
    .monthly-session-widget[data-minimized="true"] { gap:.4rem; padding:.35rem .45rem; }
    .monthly-session-widget[data-minimized="true"] #monthlySessionStatus,.monthly-session-widget[data-minimized="true"] #monthlySessionStart,.monthly-session-widget[data-minimized="true"] #monthlySessionOpen { display:none; }
    .monthly-session-minimized-label { display:none; color:var(--text); font-size:.78rem; font-weight:750; }
    .monthly-session-widget[data-minimized="true"] .monthly-session-minimized-label { display:inline; }
    .monthly-session-dialog { width:min(900px,calc(100% - 1.5rem)); max-height:85vh; padding:0; overflow:auto; border:1px solid var(--border); border-radius:18px; background:var(--surface); color:var(--text); box-shadow:0 24px 70px rgba(15,23,42,.3); }
    .monthly-session-dialog::backdrop { background:rgba(15,23,42,.55); backdrop-filter:blur(2px); }
    .monthly-session-dialog header { position:sticky; top:0; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem 1.2rem; border-bottom:1px solid var(--border); background:var(--surface); }
    .monthly-session-dialog h2 { margin:0; font-size:1.15rem; }
    .monthly-session-dialog p { margin:.3rem 0 0; color:var(--text-muted); font-size:.84rem; }
    .monthly-session-dialog header button { min-height:34px; padding:.35rem .6rem; background:var(--bg); color:var(--text); }
    .monthly-session-dialog-content { padding:.75rem 1rem 1rem; }
    .monthly-session-list { display:grid; gap:.6rem; max-height:55vh; overflow:auto; }
    .monthly-session-movement { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:.8rem; padding:.8rem; border:1px solid var(--border); border-radius:11px; background:var(--bg); }
    .monthly-session-movement small { display:block; margin-top:.2rem; color:var(--text-muted); line-height:1.4; }
    .monthly-session-dialog .monthly-undo { background:#b42318; }
    .monthly-session-empty { padding:1.5rem; color:var(--text-muted); text-align:center; }
    .monthly-session-footer { display:flex; justify-content:flex-end; gap:.55rem; margin-top:.8rem; padding-top:.8rem; border-top:1px solid var(--border); }
    .monthly-session-finish-notice { align-self:center; margin:0!important; color:var(--text-muted); font-size:.78rem!important; }
    .monthly-session-footer .secondary { background:var(--bg); color:var(--text); }
    .monthly-session-prompt { width:min(520px,calc(100% - 2rem)); padding:0; overflow:hidden; border:1px solid var(--border); border-radius:16px; background:var(--surface); color:var(--text); box-shadow:0 24px 70px rgba(15,23,42,.3); }
    .monthly-session-prompt::backdrop { background:rgba(15,23,42,.58); backdrop-filter:blur(3px); }
    .monthly-session-prompt-content { padding:1.35rem; }
    .monthly-session-prompt h2 { margin:0 0 .65rem; font-size:1.2rem; }
    .monthly-session-prompt p { margin:0; color:var(--text-muted); line-height:1.55; white-space:pre-line; }
    .monthly-session-prompt input { width:100%; min-height:44px; margin-top:1rem; padding:.65rem .75rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; }
    .monthly-session-prompt-actions { display:flex; justify-content:flex-end; gap:.55rem; margin-top:1.25rem; }
    .monthly-session-prompt-actions button { min-height:40px; padding:.55rem .9rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; font-weight:700; cursor:pointer; }
    .monthly-session-prompt-actions button.primary { border-color:var(--accent); background:var(--accent); color:#fff; }
    .monthly-session-prompt-actions button.danger { border-color:#b42318; background:#b42318; color:#fff; }
    @media(max-width:520px) { .monthly-session-widget { top:4.5rem; right:.5rem; max-width:calc(100vw - 1rem); } .monthly-session-widget button { padding:.4rem .55rem; font-size:.78rem; } .monthly-session-movement { grid-template-columns:1fr; } .monthly-session-movement button { justify-self:end; } }
</style>
@php($currentMonthlySession = $monthlySession ?? null)
@php($monthlySessionRole = mb_strtolower(trim((string) request()->session()->get('login_user.tungkulin', ''))))
@php($canFinishMonthlySession = in_array($monthlySessionRole, ['head secretary', 'secretary head'], true))
<aside class="monthly-session-widget" id="monthlySessionWidget" aria-live="polite" data-minimized="false">
    <strong id="monthlySessionStatus">{{ $currentMonthlySession ? 'Session active · '.$currentMonthlySession->period_month?->format('F Y') : 'Monthly session' }}</strong>
    <span class="monthly-session-minimized-label">Monthly Session</span>
    <button type="button" id="monthlySessionStart" @if ($currentMonthlySession) hidden @endif>Start session</button>
    <button type="button" id="monthlySessionOpen" @if (! $currentMonthlySession) hidden @endif>
        Movements (<span id="monthlySessionCount">{{ $currentMonthlySession?->movements()->count() ?? 0 }}</span>)
    </button>
    <button type="button" class="monthly-session-widget-minimize" id="monthlySessionMinimize" aria-label="Minimize monthly session bubble" title="Minimize">−</button>
</aside>
<dialog class="monthly-session-dialog" id="monthlySessionDialog" aria-labelledby="monthlySessionDialogTitle">
    <header>
        <div>
            <h2 id="monthlySessionDialogTitle">Monthly session movements</h2>
            <p id="monthlySessionDialogInfo">Temporary changes can be undone here.</p>
        </div>
        <button type="button" id="monthlySessionClose" aria-label="Close">Close</button>
    </header>
    <div class="monthly-session-dialog-content">
        <div class="monthly-session-list" id="monthlySessionMovements" aria-live="polite">
            <div class="monthly-session-empty">Loading movements…</div>
        </div>
        <div class="monthly-session-footer">
            <button type="button" class="secondary" id="monthlySessionRefresh">Refresh</button>
            @if ($canFinishMonthlySession)
                <button type="button" id="monthlySessionFinish">Finish monthly session</button>
            @else
                <button type="button" id="monthlySessionFinish" hidden disabled>Finish monthly session</button>
                <p class="monthly-session-finish-notice" id="monthlySessionFinishNotice">Only the Head Secretary can close the monthly session.</p>
            @endif
        </div>
    </div>
</dialog>
<dialog class="monthly-session-prompt" id="monthlySessionPrompt" aria-labelledby="monthlySessionPromptTitle" aria-describedby="monthlySessionPromptMessage">
    <div class="monthly-session-prompt-content">
        <h2 id="monthlySessionPromptTitle"></h2>
        <p id="monthlySessionPromptMessage"></p>
        <input id="monthlySessionPromptInput" type="text" autocomplete="off" hidden>
        <div class="monthly-session-prompt-actions">
            <button type="button" id="monthlySessionPromptCancel">Cancel</button>
            <button type="button" class="primary" id="monthlySessionPromptConfirm">Continue</button>
        </div>
    </div>
</dialog>
<script>
(() => {
    const statusUrl = @json(route('forms.monthly.session.status'));
    const startUrl = @json(route('forms.monthly.session.start'));
    const finishUrl = @json(route('forms.monthly.session.finish'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const status = document.getElementById('monthlySessionStatus');
    const start = document.getElementById('monthlySessionStart');
    const open = document.getElementById('monthlySessionOpen');
    const count = document.getElementById('monthlySessionCount');
    const dialog = document.getElementById('monthlySessionDialog');
    const info = document.getElementById('monthlySessionDialogInfo');
    const movements = document.getElementById('monthlySessionMovements');
    const finish = document.getElementById('monthlySessionFinish');
    const canFinish = @json($canFinishMonthlySession);
    const widget = document.getElementById('monthlySessionWidget');
    const minimize = document.getElementById('monthlySessionMinimize');
    const promptDialog = document.getElementById('monthlySessionPrompt');
    const promptTitle = document.getElementById('monthlySessionPromptTitle');
    const promptMessage = document.getElementById('monthlySessionPromptMessage');
    const promptInput = document.getElementById('monthlySessionPromptInput');
    const promptCancel = document.getElementById('monthlySessionPromptCancel');
    const promptConfirm = document.getElementById('monthlySessionPromptConfirm');

    const ask = ({ title, message, confirmText = 'Continue', cancelText = 'Cancel', requiredText = null, kind = 'confirm' }) => new Promise(resolve => {
        promptTitle.textContent = title;
        promptMessage.textContent = message;
        promptInput.hidden = requiredText === null;
        promptInput.value = '';
        promptInput.placeholder = requiredText === null ? '' : `Type ${requiredText}`;
        promptInput.setAttribute('aria-label', requiredText === null ? '' : `Type ${requiredText}`);
        promptConfirm.textContent = confirmText;
        promptConfirm.className = kind === 'danger' ? 'danger' : 'primary';
        promptCancel.textContent = cancelText;
        promptConfirm.disabled = false;

        const cleanup = () => {
            promptConfirm.removeEventListener('click', onConfirm);
            promptCancel.removeEventListener('click', onCancel);
            promptDialog.removeEventListener('cancel', onCancelEvent);
            promptDialog.removeEventListener('close', onClose);
            promptInput.removeEventListener('input', updateConfirm);
        };
        const updateConfirm = () => {
            promptConfirm.disabled = requiredText !== null && promptInput.value !== requiredText;
        };
        const onConfirm = () => {
            if (requiredText !== null && promptInput.value !== requiredText) return;
            promptDialog.close('confirm');
        };
        const onCancel = () => promptDialog.close('cancel');
        const onCancelEvent = event => {
            event.preventDefault();
            promptDialog.close('cancel');
        };
        const onClose = () => {
            const confirmed = promptDialog.returnValue === 'confirm';
            const entered = promptInput.value;
            cleanup();
            resolve(confirmed ? (requiredText === null ? true : entered) : null);
        };

        promptConfirm.addEventListener('click', onConfirm);
        promptCancel.addEventListener('click', onCancel);
        promptDialog.addEventListener('cancel', onCancelEvent);
        promptDialog.addEventListener('close', onClose);
        promptInput.addEventListener('input', updateConfirm);
        promptDialog.showModal();
        (requiredText === null ? promptConfirm : promptInput).focus();
    });

    const notify = (title, message, kind = 'confirm') => ask({
        title,
        message,
        confirmText: 'OK',
        cancelText: 'Close',
        kind,
    });

    const send = async (url, method = 'POST', body = null) => {
        const response = await fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            ...(body ? { body: JSON.stringify(body) } : {})
        });
        const result = await response.json();
        if (!response.ok) {
            const errors = Object.values(result.errors || {}).flat();
            throw new Error(errors.join(' ') || result.message || 'Could not update the monthly session.');
        }
        return result;
    };
    const showAvailability = availability => {
        start.disabled = !availability?.allowed;
        start.title = availability?.message || '';
        start.textContent = availability?.allowed
            ? 'Monthly Session available right now!'
            : 'Monthly session already used';
        status.textContent = availability?.message || 'No active monthly session';
    };
    const renderMovements = rows => {
        movements.replaceChildren();
        if (!rows.length) {
            const empty = document.createElement('div');
            empty.className = 'monthly-session-empty';
            empty.textContent = 'No movements recorded yet.';
            movements.append(empty);
            return;
        }
        rows.forEach(movement => {
            const item = document.createElement('article');
            item.className = 'monthly-session-movement';
            const details = document.createElement('div');
            const member = document.createElement('strong');
            member.textContent = movement.name || 'Unnamed record';
            const movementInfo = document.createElement('small');
            movementInfo.textContent = `${movement.action} · ${movement.destination_description || ''} · ${movement.monthly_code || 'No code'}`;
            const personal = document.createElement('small');
            personal.textContent = `${movement.registry || 'No registry'} · ${movement.gender || 'Gender not set'} · ${movement.kapisanan || 'Kapisanan not set'} · ${movement.changed_by || 'Unknown'} · ${movement.changed_at || ''}${movement.description ? ` · ${movement.description}` : ''}`;
            details.append(member, movementInfo, personal);
            const undo = document.createElement('button');
            undo.type = 'button';
            undo.className = 'monthly-undo';
            undo.textContent = 'Undo';
            undo.addEventListener('click', async () => {
                const confirmed = await ask({
                    title: 'Undo monthly movement?',
                    message: `Undo ${movement.action.toLowerCase()} for ${movement.name}? This restores the member and deletes this temporary log entry.`,
                    confirmText: 'Undo movement',
                    kind: 'danger',
                });
                if (!confirmed) return;
                undo.disabled = true;
                try {
                    await send(movement.undo_url, 'DELETE');
                    await refresh();
                } catch (error) {
                    await notify('Could not undo movement', error.message, 'danger');
                    undo.disabled = false;
                }
            });
            item.append(details, undo);
            movements.append(item);
        });
    };
    const update = result => {
        const session = result.session;
        window.dispatchEvent(new CustomEvent('monthly-session-state', {
            detail: { active: Boolean(session) }
        }));
        if (!session) {
            start.hidden = false;
            open.hidden = true;
            finish.disabled = true;
            showAvailability(result.start_availability);
            return;
        }
        status.textContent = `Active · ${session.period_month} · ${session.movement_count} movements`;
        count.textContent = session.movement_count;
        start.hidden = true;
        open.hidden = false;
        finish.disabled = !canFinish;
        info.textContent = canFinish
            ? `For ${session.period_month}. Changes remain temporary until you finish this session.`
            : `For ${session.period_month}. Changes remain temporary; only the Head Secretary can close this session.`;
    };
    async function refresh() {
        const response = await fetch(`${statusUrl}?include_movements=1`, { headers: { 'Accept': 'application/json' } });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Could not load monthly session.');
        update(result);
        if (dialog.open) renderMovements(result.movements || []);
    }

    document.getElementById('monthlySessionClose').addEventListener('click', () => dialog.close());
    document.getElementById('monthlySessionRefresh').addEventListener('click', () => refresh().catch(error => notify('Could not refresh session', error.message, 'danger')));
    open.addEventListener('click', async () => {
        dialog.showModal();
        try { await refresh(); } catch (error) { await notify('Could not load session', error.message, 'danger'); }
    });
    finish.addEventListener('click', async () => {
        const warnings = [
            ['Before you finish', 'Strict rule: finish the monthly session only after you are completely done. Print R4-01 and the Resumen first. Do not proceed if any movement, pahabol, or late submission is still expected.'],
            ['Confirm reports and submissions', 'Confirm that R4-01 and the Resumen are printed, and there are no remaining or late submissions to encode?'],
            ['Final warning', 'Finishing permanently saves this session and clears monthly codes from every record. This cannot be undone. Continue?'],
        ];
        for (const [title, message] of warnings) {
            const confirmed = await ask({ title, message, confirmText: 'Continue', kind: 'danger' });
            if (!confirmed) return;
        }
        const confirmation = await ask({
            title: 'Confirm monthly session finish',
            message: 'Type the exact phrase below to permanently finish this monthly session.',
            confirmText: 'Finish session',
            requiredText: 'FINISH MONTHLY SESSION',
            kind: 'danger',
        });
        if (!confirmation) return;
        finish.disabled = true;
        try {
            const result = await send(finishUrl);
            dialog.close();
            await notify('Monthly session finished', result.message);
            await refresh();
        } catch (error) {
            await notify('Could not finish monthly session', error.message, 'danger');
            finish.disabled = false;
        }
    });
    start.addEventListener('click', async () => {
        const confirmed = await ask({
            title: 'Start monthly session?',
            message: 'Monthly Session is available right now! It is available only once per month and is generally done at the end of the month. Do not start until you are ready to record the monthly movements.',
            confirmText: 'Start session',
        });
        if (!confirmed) return;
        start.disabled = true;
        try {
            await send(startUrl);
            await refresh();
        } catch (error) {
            await notify('Could not start monthly session', error.message, 'danger');
            try { await refresh(); } catch (_) {}
        }
    });
    minimize.addEventListener('click', () => {
        const minimized = widget.dataset.minimized !== 'true';
        widget.dataset.minimized = String(minimized);
        minimize.textContent = minimized ? '+' : '−';
        minimize.setAttribute('aria-label', minimized ? 'Expand monthly session bubble' : 'Minimize monthly session bubble');
        minimize.title = minimized ? 'Expand' : 'Minimize';
        window.sessionStorage.setItem('monthlySessionWidgetMinimized', String(minimized));
    });
    if (window.sessionStorage.getItem('monthlySessionWidgetMinimized') === 'true') {
        widget.dataset.minimized = 'true';
        minimize.textContent = '+';
        minimize.setAttribute('aria-label', 'Expand monthly session bubble');
    }
    refresh().catch(error => { status.textContent = error.message; });
    window.setInterval(() => refresh().catch(() => {}), 15000);
})();
</script>
