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
    .monthly-session-entries { display:grid; gap:.35rem; }
    .monthly-session-entry { display:grid; gap:.08rem; padding:.35rem .5rem; border-left:3px solid var(--accent); border-radius:4px; background:var(--surface); }
    .monthly-session-entry strong { color:var(--text); font-size:.9rem; }
    .monthly-session-entry small { margin:0; font-weight:700; }
    .monthly-session-personal { margin-top:.4rem!important; }
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
    .monthly-session-prompt-hint { margin-top:.55rem!important; color:var(--text-muted)!important; font-size:.82rem; }
    .monthly-session-prompt-error { margin-top:.35rem!important; color:#b42318!important; font-size:.82rem; font-weight:700; }
    .monthly-session-prompt [hidden] { display:none; }
    .monthly-session-prompt-actions { display:flex; justify-content:flex-end; gap:.55rem; margin-top:1.25rem; }
    .monthly-session-prompt-actions button { min-height:40px; padding:.55rem .9rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; font-weight:700; cursor:pointer; }
    .monthly-session-prompt-actions button.primary { border-color:var(--accent); background:var(--accent); color:#fff; }
    .monthly-session-prompt-actions button.danger { border-color:#b42318; background:#b42318; color:#fff; }
    .monthly-session-blocker { position:fixed; z-index:100000; inset:0; display:grid; place-items:center; padding:1rem; background:rgba(15,23,42,.76); color:#fff; text-align:center; }
    .monthly-session-blocker[hidden] { display:none; }
    .monthly-session-blocker-card { width:min(400px,100%); padding:2rem; border:1px solid rgba(255,255,255,.2); border-radius:20px; background:#172033; box-shadow:0 25px 80px rgba(0,0,0,.38); }
    .monthly-session-success-button { min-height:42px; margin-top:1.25rem; padding:.65rem 1rem; border:0; border-radius:10px; background:#74df9b; color:#10251a; font:inherit; font-weight:800; cursor:pointer; }
    .monthly-session-success-button[hidden] { display:none; }
    .monthly-session-spinner { width:52px; height:52px; margin:0 auto 1.25rem; border:5px solid rgba(255,255,255,.25); border-top-color:#fff; border-radius:50%; animation:monthly-session-spin .8s linear infinite; }
    .monthly-session-success-check { display:grid; width:68px; height:68px; margin:0 auto 1.1rem; place-items:center; border:3px solid #74df9b; border-radius:50%; color:#74df9b; font-size:2.8rem; font-weight:800; line-height:1; animation:monthly-session-check .45s ease-out both; }
    .monthly-session-blocker h2 { margin:0; font-size:1.55rem; }
    .monthly-session-blocker p { margin:.55rem 0 0; color:#d6deeb; }
    .monthly-session-blocker[data-state="success"] .monthly-session-spinner { display:none; }
    .monthly-session-blocker[data-state="loading"] .monthly-session-success-check { display:none; }
    @keyframes monthly-session-spin { to { transform:rotate(360deg); } }
    @keyframes monthly-session-check { from { transform:scale(.3); opacity:0; } to { transform:scale(1); opacity:1; } }
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
        Activity (<span id="monthlySessionCount">{{ $currentMonthlySession?->activities_count ?? 0 }}</span>)
    </button>
    <button type="button" class="monthly-session-widget-minimize" id="monthlySessionMinimize" aria-label="Minimize monthly session bubble" title="Minimize">−</button>
</aside>
<dialog class="monthly-session-dialog" id="monthlySessionDialog" aria-labelledby="monthlySessionDialogTitle">
    <header>
        <div>
            <h2 id="monthlySessionDialogTitle">Monthly session activity</h2>
            <p id="monthlySessionDialogInfo">Review each monthly code by Purok and Grupo.</p>
        </div>
        <button type="button" id="monthlySessionClose" aria-label="Close">Close</button>
    </header>
    <div class="monthly-session-dialog-content">
        <div class="monthly-session-list" id="monthlySessionMovements" aria-live="polite">
            <div class="monthly-session-empty">Loading monthly activity…</div>
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
        <input id="monthlySessionPromptInput" type="text" autocomplete="off" aria-describedby="monthlySessionPromptHint monthlySessionPromptError" hidden>
        <p class="monthly-session-prompt-hint" id="monthlySessionPromptHint" hidden></p>
        <p class="monthly-session-prompt-error" id="monthlySessionPromptError" role="alert" hidden></p>
        <div class="monthly-session-prompt-actions">
            <button type="button" id="monthlySessionPromptCancel">Cancel</button>
            <button type="button" class="primary" id="monthlySessionPromptConfirm">Continue</button>
        </div>
    </div>
</dialog>
<div class="monthly-session-blocker" id="monthlySessionBlocker" data-state="loading" role="status" aria-live="assertive" aria-modal="true" hidden>
    <div class="monthly-session-blocker-card">
        <div class="monthly-session-spinner" aria-hidden="true"></div>
        <div class="monthly-session-success-check" aria-hidden="true">✓</div>
        <h2 id="monthlySessionBlockerTitle">Finishing monthly session…</h2>
        <p id="monthlySessionBlockerMessage">Please wait. Do not close or leave this page.</p>
        <button type="button" class="monthly-session-success-button" id="monthlySessionSuccessAcknowledge" hidden>Yay, I understand there's no redos!</button>
    </div>
</div>
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
    const promptHint = document.getElementById('monthlySessionPromptHint');
    const promptError = document.getElementById('monthlySessionPromptError');
    const promptCancel = document.getElementById('monthlySessionPromptCancel');
    const promptConfirm = document.getElementById('monthlySessionPromptConfirm');
    const blocker = document.getElementById('monthlySessionBlocker');
    const blockerTitle = document.getElementById('monthlySessionBlockerTitle');
    const blockerMessage = document.getElementById('monthlySessionBlockerMessage');
    const successAcknowledge = document.getElementById('monthlySessionSuccessAcknowledge');
    let blockedElements = [];

    const setBlocked = blocked => {
        if (blocked) {
            document.body.append(blocker);
            blockedElements = [...document.body.children]
                .filter(element => element !== blocker)
                .map(element => ({ element, wasInert: element.inert }));
            blockedElements.forEach(({ element }) => { element.inert = true; });
            blocker.hidden = false;
            document.body.style.overflow = 'hidden';
            return;
        }
        blocker.hidden = true;
        blockedElements.forEach(({ element, wasInert }) => { element.inert = wasInert; });
        blockedElements = [];
        document.body.style.overflow = '';
    };
    const showFinishing = () => {
        blocker.dataset.state = 'loading';
        blockerTitle.textContent = 'Finishing monthly session…';
        blockerMessage.textContent = 'Please wait. Do not close or leave this page.';
        successAcknowledge.hidden = true;
        setBlocked(true);
    };
    const showFinished = periodLabel => {
        blocker.dataset.state = 'success';
        blockerTitle.textContent = `Monthly ${periodLabel} done`;
        blockerMessage.textContent = 'Session finalized. No changes can be made to this session.';
        successAcknowledge.hidden = false;
        successAcknowledge.focus();
    };

    const ask = ({ title, message, confirmText = 'Continue', cancelText = 'Cancel', requiredText = null, kind = 'confirm' }) => new Promise(resolve => {
        promptTitle.textContent = title;
        promptMessage.textContent = message;
        promptInput.hidden = requiredText === null;
        promptInput.value = '';
        promptInput.placeholder = requiredText === null ? '' : `Type ${requiredText}`;
        promptInput.setAttribute('aria-label', requiredText === null ? '' : `Type ${requiredText}`);
        promptHint.hidden = requiredText === null;
        promptHint.textContent = requiredText === null ? '' : `Please type the following exactly. It is case-sensitive:\n${requiredText}`;
        promptError.hidden = true;
        promptError.textContent = '';
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
            const matches = requiredText === null || promptInput.value === requiredText;
            promptConfirm.disabled = !matches;
            promptError.hidden = matches || promptInput.value.length === 0;
            promptError.textContent = matches || promptInput.value.length === 0
                ? ''
                : 'Please match the entire sentence exactly, including capitalization.';
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
            empty.textContent = 'No monthly activity recorded yet.';
            movements.append(empty);
            return;
        }
        rows.forEach(activity => {
            const item = document.createElement('article');
            item.className = 'monthly-session-movement';
            const details = document.createElement('div');
            const entries = document.createElement('div');
            entries.className = 'monthly-session-entries';
            (activity.entries || []).forEach(entry => {
                const row = document.createElement('div');
                row.className = 'monthly-session-entry';
                const member = document.createElement('strong');
                member.textContent = `${entry.name || activity.name || 'Unnamed record'} · ${entry.location || 'Location not set'}`;
                const code = document.createElement('small');
                code.textContent = `${entry.monthly_code || 'No code'}${entry.direction ? ` (${entry.direction})` : ''}`;
                row.append(member, code);
                entries.append(row);
            });
            const personal = document.createElement('small');
            personal.className = 'monthly-session-personal';
            personal.textContent = `${activity.registry || 'No registry'} · ${activity.gender || 'Gender not set'} · ${activity.kapisanan || 'Kapisanan not set'} · ${activity.changed_by || 'Unknown'} · ${activity.changed_at || ''}${activity.description ? ` · ${activity.description}` : ''}`;
            details.append(entries, personal);
            if (activity.undo_url) {
                const undo = document.createElement('button');
                undo.type = 'button';
                undo.className = 'monthly-undo';
                undo.textContent = `Undo ${activity.action}`;
                undo.addEventListener('click', async () => {
                    const confirmed = await ask({
                        title: `Undo monthly ${activity.action.toLowerCase()}?`,
                        message: `Undo ${activity.action.toLowerCase()} for ${activity.name}? This restores the prior monthly record state${(activity.entries || []).length > 1 ? ' and removes both paired activity entries' : ''}.`,
                        confirmText: `Undo ${activity.action}`,
                        kind: 'danger',
                    });
                    if (!confirmed) return;
                    undo.disabled = true;
                    try {
                        await send(activity.undo_url, 'DELETE');
                        await refresh();
                    } catch (error) {
                        await notify(`Could not undo ${activity.action.toLowerCase()}`, error.message, 'danger');
                        undo.disabled = false;
                    }
                });
                item.append(details, undo);
            } else {
                item.append(details);
            }
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
        status.textContent = `Active · ${session.period_month} · ${session.activity_count} activities`;
        count.textContent = session.activity_count;
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
        if (dialog.open) renderMovements(result.activities || []);
    }

    document.getElementById('monthlySessionClose').addEventListener('click', () => dialog.close());
    document.getElementById('monthlySessionRefresh').addEventListener('click', () => refresh().catch(error => notify('Could not refresh session', error.message, 'danger')));
    successAcknowledge.addEventListener('click', async () => {
        setBlocked(false);
        try {
            await refresh();
        } catch (error) {
            await notify('Session finished, but the activity view could not refresh', error.message, 'danger');
        }
    });
    open.addEventListener('click', async () => {
        dialog.showModal();
        try { await refresh(); } catch (error) { await notify('Could not load session', error.message, 'danger'); }
    });
    finish.addEventListener('click', async () => {
        dialog.close();
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
            message: 'To permanently finish this monthly session, type the exact phrase shown below.',
            confirmText: 'Finish session',
            requiredText: 'FINISH MONTHLY SESSION',
            kind: 'danger',
        });
        if (!confirmation) return;
        finish.disabled = true;
        showFinishing();
        let result;
        try {
            result = await send(finishUrl);
        } catch (error) {
            setBlocked(false);
            await notify('Could not finish monthly session', error.message, 'danger');
            finish.disabled = false;
            return;
        }
        showFinished(result.period_label || 'session');
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
