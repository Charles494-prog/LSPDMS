@extends('layouts.app')

@section('title', 'Inquire / Modify Member')

@section('content')
<div class="reg-root">

    <div class="reg-page">
        <header class="reg-hero">
            <div class="reg-hero-top">
                <div>
                    <h1>Member registry</h1>
                    <p>Look up a member by name or registry number to view or update their record.</p>
                </div>
                <button type="button" class="reg-remind-btn" id="remind-open">Reminders</button>
            </div>

            <div class="reg-search">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input type="text" id="reg-search-input" placeholder="Full name or registry number" autocomplete="off" aria-label="Search members">
                <button type="button" id="reg-search-btn" class="btn btn-gold">Search</button>
            </div>
            <div id="reg-search-error" class="reg-error" role="alert" hidden></div>

            {{-- Remove this block once wired to the real database --}}
            <div class="reg-demo">
                <span>Demo data, try:</span>
                <button type="button" class="reg-chip" data-q="Charles Andrei Galvan">Charles Andrei Galvan</button>
                <button type="button" class="reg-chip" data-q="REG-2019-0087">REG-2019-0087</button>
                <button type="button" class="reg-chip" data-q="Angelica">Angelica (two matches)</button>
            </div>
        </header>

        <section id="reg-results" class="reg-results" aria-live="polite" hidden></section>
    </div>

    {{-- ============ Reminder notification (right side) ============ --}}
    <aside class="reminder" id="reminder" role="region" aria-labelledby="rem-title" hidden>
        <div class="rem-head">
            <span class="rem-icon" aria-hidden="true">!</span>
            <h2 id="rem-title">Why you need to update records</h2>
            <button type="button" class="rem-x" id="rem-close" aria-label="Close reminder">&times;</button>
        </div>
        <div class="rem-body">
            <h3>Dito sa portal na ito</h3>
            <ol>
                <li><b>Manual na pag-update ng Binhi records.</b></li>
                <li><b>Paglipat ng Grupo sa loob ng iisang Purok.</b> Halimbawa, 1-1 → 1-2. Kung hinati ang Purok (1A: 1–12, 1B: 13–24), hindi puwedeng tumawid, gaya ng 1-1 → 1-13.</li>
                <li><b>Pagtatama ng maling entry:</b> Pangalan, Registry, Gender, Petsa ng Bautismo, atbp.</li>
            </ol>
            <h3>Sa Monthly portal lang</h3>
            <p class="rem-path">Form Generator › Monthly › Modify</p>
            <ul>
                <li><b>Status</b> at <b>paglipat ng Purok</b> (kasama ang 1A ↔ 1B), para mapasok sa resumen ang bawat galaw.</li>
                <li><b>Pag-alis ng double entry.</b></li>
            </ul>
            <div class="rem-warn"><b>Babala:</b> Ang double entry ay maaari lamang alisin sa Monthly portal.</div>
        </div>
        <div class="rem-foot"><button type="button" class="btn btn-ink" id="rem-ok">Naintindihan ko</button></div>
    </aside>

    {{-- ============ Record dialog ============ --}}
    <div id="reg-modal-overlay" class="reg-overlay" hidden>
        <div class="reg-modal" role="dialog" aria-modal="true" aria-labelledby="rm-name-display">

            <div class="reg-band">
                <div class="reg-avatar" id="rm-avatar" aria-hidden="true">--</div>
                <div class="reg-band-text">
                    <div class="reg-name" id="rm-name-display">--</div>
                    <div class="reg-regno" id="rm-registry-display">--</div>
                </div>
                <span id="rm-status-badge" class="reg-badge">--</span>
                <button type="button" id="reg-modal-close" class="reg-close" aria-label="Close">&times;</button>
            </div>

            <div id="rm-dup" class="reg-alert" hidden></div>

            <form id="reg-form" novalidate>
                <input type="hidden" id="rm-id" name="id">

                <div class="reg-body">
                    <h3>Edit here</h3>
                    <div class="reg-grid">
                        <div class="f span2"><label for="rm-name">Name</label><input type="text" id="rm-name"><p class="err" data-err="name"></p></div>
                        <div class="f"><label for="rm-registry">Registry number</label><input type="text" id="rm-registry" placeholder="REG-2024-0001"><p class="err" data-err="registry_number"></p></div>
                        <div class="f"><label for="rm-gender">Gender</label>
                            <select id="rm-gender"><option value="Male">Male</option><option value="Female">Female</option></select>
                            <p class="err" data-err="gender"></p></div>
                        <div class="f"><label for="rm-kapisanan">Kapisanan</label><input type="text" id="rm-kapisanan"><p class="err" data-err="kapisanan"></p></div>
                        <div class="f"><label for="rm-grupo">Grupo</label><input type="number" id="rm-grupo" step="1"><p class="hint" id="rm-grupo-hint"></p><p class="err" data-err="grupo"></p></div>
                        <div class="f"><label for="rm-baptism">Date of baptism</label><input type="date" id="rm-baptism"><p class="err" data-err="date_of_baptism"></p></div>
                        <div class="f"><label for="rm-evangelist">Evangelist</label><input type="text" id="rm-evangelist"><p class="err" data-err="evangelist"></p></div>
                    </div>

                    <h3 class="reg-locked-title">Monthly portal only <small>Form Generator › Monthly › Modify</small></h3>
                    <div class="reg-locks">
                        <div class="lock"><span class="lock-k">Purok</span><span class="lock-v" id="rm-purok-display">--</span></div>
                        <div class="lock"><span class="lock-k">Status</span><span class="lock-v" id="rm-status-text">--</span></div>
                        <div class="lock"><span class="lock-k">Date of entry</span><span class="lock-v" id="rm-entry-display">--</span></div>
                        <div class="lock"><span class="lock-k">R2-10 eligibility</span>
                            <span class="lock-v"><span id="rm-r210-display">••••••••••</span>
                            <button type="button" id="rm-r210-toggle" class="reg-eye">Show</button></span></div>
                    </div>
                </div>

                <div class="reg-review" id="reg-review">
                    <h3>Review your changes</h3>
                    <ul id="reg-review-list"></ul>
                    <p class="err" id="reg-review-err"></p>
                </div>

                <div class="reg-actions">
                    <span id="reg-save-msg" class="reg-save-msg"></span>
                    <span class="act-edit">
                        <button type="button" class="btn btn-ghost" id="reg-reset-btn">Reset</button>
                        <button type="button" class="btn btn-ghost" id="reg-cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-ink" id="reg-save-btn" disabled>Save changes</button>
                    </span>
                    <span class="act-review">
                        <button type="button" class="btn btn-ghost" id="reg-back-btn">Back</button>
                        <button type="button" class="btn btn-gold" id="reg-confirm-btn">Confirm and save</button>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <div class="snack" id="snack" role="status"></div>
</div>
@endsection

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
.reg-root {
    --ink: #10233a; --muted: #5b6b7c; --bg: #eef1f4; --card: #fff; --line: #dde3ea;
    --gold: #c8a24a; --gold-deep: #8a6a1f; --danger: #b3261e; --ok: #2a7d55; --warn: #a5690b;
    --ease: cubic-bezier(.2, .8, .2, 1);
    font-family: 'IBM Plex Sans', system-ui, sans-serif; color: var(--ink);
}
.reg-root h1, .reg-root h2, .reg-root h3, .reg-root p, .reg-root ul, .reg-root ol { margin: 0; padding: 0; }
.reg-root [hidden] { display: none !important; }
.reg-page { max-width: 860px; margin: 0 auto; }

/* Buttons */
.btn { padding: .65rem 1.15rem; border: 0; border-radius: 9px; font: inherit; font-size: .9rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: background .2s, transform .2s var(--ease), opacity .2s; }
.btn:hover:not(:disabled) { transform: translateY(-1px); }
.btn:disabled { opacity: .45; cursor: not-allowed; }
.btn-ink { background: var(--ink); color: #fff; } .btn-ink:hover:not(:disabled) { background: #1b3a5c; }
.btn-gold { background: var(--gold); color: var(--ink); } .btn-gold:hover:not(:disabled) { background: #dcbc6a; }
.btn-ghost { background: var(--bg); color: var(--ink); } .btn-ghost:hover:not(:disabled) { background: var(--line); }

/* Hero + search */
.reg-hero { position: relative; overflow: hidden; padding: 1.8rem clamp(1.2rem, 4vw, 2.2rem) 1.4rem; border-radius: 16px; color: #fff; background: linear-gradient(155deg, #0a1324, #14264a); }
.reg-hero::before { content: ""; position: absolute; inset: -40%; background: radial-gradient(circle at 80% 20%, rgba(200,162,74,.25), transparent 40%); animation: reg-drift 16s ease-in-out infinite alternate; pointer-events: none; }
@keyframes reg-drift { to { transform: translate3d(-4%, 4%, 0) scale(1.1); } }
.reg-hero > * { position: relative; }
.reg-hero-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.3rem; }
.reg-hero h1 { font-family: 'Fraunces', Georgia, serif; font-weight: 700; font-size: clamp(1.8rem, 4vw, 2.4rem); letter-spacing: -.015em; }
.reg-hero p { margin-top: .35rem; max-width: 50ch; color: #c1cde0; font-size: .95rem; line-height: 1.6; }
.reg-remind-btn { flex: none; padding: .5rem .9rem; border: 1px solid rgba(255,255,255,.35); border-radius: 999px; background: transparent; color: #fff; font: inherit; font-size: .82rem; font-weight: 600; cursor: pointer; transition: border-color .2s, color .2s; }
.reg-remind-btn:hover { border-color: var(--gold); color: #e6cf94; }
.reg-search { display: flex; align-items: center; gap: .7rem; padding: .45rem .5rem .45rem 1rem; border-radius: 13px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
.reg-search svg { width: 20px; height: 20px; flex: none; color: var(--muted); }
.reg-search input { flex: 1; min-width: 0; padding: .6rem .1rem; border: 0; background: transparent; color: var(--ink); font: inherit; font-size: 1.05rem; }
.reg-search input:focus { outline: none; }
.reg-search:focus-within { box-shadow: 0 0 0 3px rgba(200,162,74,.6), 0 10px 30px rgba(0,0,0,.25); }
.reg-error { margin-top: .7rem; padding: .55rem .8rem; border-radius: 8px; background: rgba(179,38,30,.18); color: #ffd7d3; font-size: .88rem; }
.reg-demo { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: 1rem; color: #9fb0c8; font-size: .8rem; }
.reg-chip { padding: .25rem .75rem; border: 1px solid rgba(255,255,255,.25); border-radius: 999px; background: transparent; color: #e3eaf5; font: inherit; font-size: .8rem; cursor: pointer; }
.reg-chip:hover { border-color: var(--gold); color: #e6cf94; }

/* Results */
.reg-results { margin-top: 1rem; padding: 1rem; border: 1px solid var(--line); border-radius: 14px; background: var(--card); animation: reg-rise .5s var(--ease); }
@keyframes reg-rise { from { opacity: 0; transform: translateY(10px); } }
.reg-results h2 { margin: 0 0 .7rem; font-size: .9rem; font-weight: 600; color: var(--muted); }
.reg-result { display: flex; align-items: center; gap: .9rem; width: 100%; padding: .75rem .8rem; border: 1px solid transparent; border-radius: 10px; background: none; font: inherit; color: inherit; text-align: left; cursor: pointer; transition: background .2s, border-color .2s, transform .25s var(--ease); }
.reg-result:hover { background: var(--bg); border-color: var(--line); transform: translateX(3px); }
.reg-result .who { flex: 1; min-width: 0; }
.reg-result .who b { display: block; font-weight: 600; }
.reg-result .who small { color: var(--muted); font-size: .82rem; }
.reg-dupchip { padding: .15rem .55rem; border-radius: 999px; background: #fbf0de; color: var(--warn); font-size: .72rem; font-weight: 600; }

.reg-avatar { display: grid; place-items: center; flex: none; width: 42px; height: 42px; border-radius: 50%; background: var(--ink); color: #e6cf94; font-weight: 600; font-size: .9rem; }
.reg-badge { display: inline-block; padding: .2rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 600; white-space: nowrap; }
.reg-badge.is-active { background: #e2f3ea; color: var(--ok); }
.reg-badge.is-inactive { background: #e8ecef; color: var(--muted); }
.reg-badge.is-transferred { background: #fbf0de; color: var(--warn); }

/* Reminder notification */
.reminder { position: fixed; z-index: 900; right: 1.2rem; top: 5rem; width: min(390px, calc(100vw - 2rem)); max-height: calc(100vh - 7rem); display: flex; flex-direction: column; border-radius: 14px; border-left: 5px solid var(--gold); background: var(--card); box-shadow: 0 20px 50px rgba(16,35,58,.28); transform: translateX(115%); opacity: 0; transition: transform .55s var(--ease), opacity .4s; }
.reminder.is-in { transform: none; opacity: 1; }
.rem-head { display: flex; align-items: center; gap: .7rem; padding: 1rem 1rem .6rem 1.1rem; }
.rem-icon { display: grid; place-items: center; flex: none; width: 28px; height: 28px; border-radius: 50%; background: var(--gold); color: var(--ink); font-weight: 700; }
.rem-head h2 { flex: 1; font-size: 1rem; font-weight: 600; line-height: 1.25; }
.rem-x { width: 30px; height: 30px; border: 0; border-radius: 8px; background: var(--bg); color: var(--ink); font-size: 1.3rem; line-height: 1; cursor: pointer; }
.rem-body { padding: 0 1.1rem .6rem; overflow: auto; font-size: .88rem; line-height: 1.55; }
.rem-body h3 { margin: .7rem 0 .35rem; color: var(--gold-deep); font-size: .78rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
.rem-body ol, .rem-body ul { padding-left: 1.2rem; }
.rem-body li { margin-bottom: .4rem; }
.rem-path { display: inline-block; margin-bottom: .45rem; padding: .2rem .55rem; border-radius: 6px; background: var(--ink); color: #e6cf94; font-size: .78rem; font-weight: 600; }
.rem-warn { margin-top: .7rem; padding: .6rem .75rem; border-radius: 9px; background: #fdebe9; color: #8e1d16; }
.rem-foot { padding: .6rem 1.1rem 1rem; }
.rem-foot .btn { width: 100%; }

/* Dialog */
.reg-overlay { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 1.2rem; background: rgba(10,19,36,.6); animation: reg-fade .2s; }
@keyframes reg-fade { from { opacity: 0; } }
.reg-modal { width: 100%; max-width: 660px; max-height: 92vh; overflow-y: auto; border-radius: 16px; background: var(--card); box-shadow: 0 30px 70px rgba(0,0,0,.35); animation: reg-pop .35s var(--ease); }
@keyframes reg-pop { from { opacity: 0; transform: translateY(14px) scale(.98); } }
.reg-band { display: flex; align-items: center; gap: .9rem; padding: 1.2rem 1.4rem; background: linear-gradient(155deg, #0a1324, #14264a); color: #fff; }
.reg-band .reg-avatar { background: var(--gold); color: var(--ink); width: 48px; height: 48px; font-size: 1rem; }
.reg-band-text { flex: 1; min-width: 0; }
.reg-name { font-family: 'Fraunces', Georgia, serif; font-size: 1.35rem; font-weight: 700; }
.reg-regno { margin-top: .1rem; color: #b9c6da; font-size: .85rem; letter-spacing: .02em; }
.reg-close { align-self: flex-start; width: 32px; height: 32px; border: 0; border-radius: 8px; background: rgba(255,255,255,.12); color: #fff; font-size: 1.4rem; line-height: 1; cursor: pointer; }
.reg-close:hover { background: rgba(255,255,255,.25); }
.reg-alert { margin: 1rem 1.4rem 0; padding: .7rem .9rem; border-radius: 10px; background: #fbf0de; color: #7a4a05; font-size: .86rem; line-height: 1.5; }

.reg-body, .reg-review { padding: 1.1rem 1.4rem .4rem; }
.reg-body h3, .reg-review h3 { margin-bottom: .8rem; font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
.reg-locked-title { margin-top: 1.4rem !important; }
.reg-locked-title small { margin-left: .5rem; letter-spacing: 0; text-transform: none; font-weight: 500; color: var(--gold-deep); }
.reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem 1rem; }
.span2 { grid-column: 1 / -1; }
.f label { display: block; margin-bottom: .3rem; color: var(--muted); font-size: .78rem; font-weight: 600; }
.f input, .f select { width: 100%; padding: .6rem .7rem; border: 1.5px solid var(--line); border-radius: 9px; background: #fff; color: var(--ink); font: inherit; font-size: .95rem; transition: border-color .2s, box-shadow .2s, background .2s; }
.f input:hover, .f select:hover { border-color: #bcc6d1; }
.f input:focus, .f select:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(200,162,74,.25); }
.f.is-changed input, .f.is-changed select { border-color: var(--gold); background: #fffaf0; }
.f.has-error input, .f.has-error select { border-color: var(--danger); }
.hint { margin-top: .25rem; color: var(--muted); font-size: .76rem; line-height: 1.4; }
.err { margin-top: .25rem; color: var(--danger); font-size: .78rem; line-height: 1.4; }
.err:empty { display: none; }

.reg-locks { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.lock { display: grid; gap: .15rem; padding: .7rem .85rem; border: 1px dashed #c5cdd6; border-radius: 10px; background: var(--bg); }
.lock-k { display: flex; align-items: center; gap: .35rem; color: var(--muted); font-size: .76rem; font-weight: 600; }
.lock-k::before { content: ""; width: 11px; height: 11px; background: currentColor; -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4'%3E%3Crect x='5' y='10' width='14' height='10' rx='2'/%3E%3Cpath d='M8 10V7a4 4 0 0 1 8 0v3'/%3E%3C/svg%3E") center/contain no-repeat; mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4'%3E%3Crect x='5' y='10' width='14' height='10' rx='2'/%3E%3Cpath d='M8 10V7a4 4 0 0 1 8 0v3'/%3E%3C/svg%3E") center/contain no-repeat; }
.lock-v { display: flex; align-items: center; gap: .5rem; font-size: .95rem; font-weight: 500; }
.reg-eye { margin-left: auto; padding: .15rem .55rem; border: 1px solid var(--line); border-radius: 6px; background: #fff; font: inherit; font-size: .76rem; font-weight: 600; cursor: pointer; }

/* Review step */
.reg-review { display: none; }
.reg-modal.is-review .reg-body { display: none; }
.reg-modal.is-review .reg-review { display: block; }
.reg-review ul { list-style: none; display: grid; gap: .5rem; }
.reg-review li { display: grid; gap: .1rem; padding: .65rem .8rem; border: 1px solid var(--line); border-left: 4px solid var(--gold); border-radius: 9px; font-size: .9rem; }
.reg-review .k { color: var(--muted); font-size: .76rem; font-weight: 600; }
.reg-review .from { color: var(--muted); text-decoration: line-through; }
.reg-review .to { font-weight: 600; }

.reg-actions { display: flex; align-items: center; gap: .6rem; padding: 1rem 1.4rem 1.3rem; }
.reg-actions .act-edit, .reg-actions .act-review { display: flex; gap: .5rem; margin-left: auto; }
.reg-modal:not(.is-review) .act-review, .reg-modal.is-review .act-edit { display: none; }
.reg-save-msg { color: var(--ok); font-size: .85rem; }
.snack { position: fixed; z-index: 1200; left: 50%; bottom: 1.4rem; padding: .7rem 1.2rem; border-radius: 999px; background: var(--ink); color: #fff; font-size: .9rem; box-shadow: 0 10px 30px rgba(0,0,0,.3); transform: translate(-50%, 140%); transition: transform .45s var(--ease); pointer-events: none; }
.snack.show { transform: translate(-50%, 0); }

.reg-root :focus-visible { outline: 3px solid #5b9dff; outline-offset: 2px; }
@media (max-width: 640px) {
    .reg-grid, .reg-locks { grid-template-columns: 1fr; }
    .reg-search { flex-wrap: wrap; } .reg-search .btn { width: 100%; }
    .reminder { left: 1rem; right: 1rem; top: auto; bottom: 1rem; width: auto; max-height: 75vh; transform: translateY(115%); }
    .reg-band { flex-wrap: wrap; }
}
@media (prefers-reduced-motion: reduce) { .reg-root *, .reg-root *::before { animation: none !important; transition: none !important; } }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = id => document.getElementById(id);
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- Rules (edit to match your registry) ---------------- */
    const GRUPO_MAX = 24;                                   // grupos per purok
    const PUROK_SPLITS = { 1: [['1A', 1, 12], ['1B', 13, 24]] }; // split puroks: [label, first grupo, last grupo]
    const zone = (purok, grupo) => {
        const s = PUROK_SPLITS[purok];
        if (!s) return { label: String(purok), min: 1, max: GRUPO_MAX, split: false };
        const z = s.find(([, a, b]) => grupo >= a && grupo <= b) || s[0];
        return { label: z[0], min: z[1], max: z[2], split: true };
    };

    /* ---------------- DEMO DATA: delete once wired to /members endpoints ---------------- */
    const DEMO_MEMBERS = [
        { id: 1, name: 'Charles Andrei Galvan', registry_number: 'REG-2024-0113', gender: 'Male', kapisanan: 'Kapisanang Bayan', purok: 3, grupo: 7, date_of_baptism: '2010-06-12', evangelist: 'Ramon Dela Cruz', date_of_entry: '2010-06-15', r2_10_eligibility: 'Eligible - Tier 2', status: 'Active' },
        { id: 2, name: 'Maria Fe Santos', registry_number: 'REG-2019-0087', gender: 'Female', kapisanan: 'Kapisanang Kabataan', purok: 1, grupo: 15, date_of_baptism: '2005-09-03', evangelist: 'Isagani Ramos', date_of_entry: '2005-09-10', r2_10_eligibility: 'Not Eligible', status: 'Active' },
        { id: 3, name: 'Juan Miguel Tolentino', registry_number: 'REG-2015-0042', gender: 'Male', kapisanan: 'Kapisanang Bayan', purok: 1, grupo: 4, date_of_baptism: '1998-02-20', evangelist: 'Ramon Dela Cruz', date_of_entry: '1998-02-25', r2_10_eligibility: 'Eligible - Tier 1', status: 'Inactive' },
        { id: 4, name: 'Angelica Reyes', registry_number: 'REG-2022-0159', gender: 'Female', kapisanan: 'Kapisanang Kabataan', purok: 5, grupo: 10, date_of_baptism: '2015-11-08', evangelist: 'Isagani Ramos', date_of_entry: '2015-11-12', r2_10_eligibility: 'Under Review', status: 'Active' },
        { id: 5, name: 'Angelica Reyes', registry_number: 'REG-2022-0160', gender: 'Female', kapisanan: 'Kapisanang Kabataan', purok: 5, grupo: 11, date_of_baptism: '2015-11-08', evangelist: 'Isagani Ramos', date_of_entry: '2015-11-12', r2_10_eligibility: 'Under Review', status: 'Active' },
        { id: 6, name: 'Renato Bautista', registry_number: 'REG-2008-0021', gender: 'Male', kapisanan: 'Kapisanang Matanda', purok: 2, grupo: 3, date_of_baptism: '1990-04-17', evangelist: 'Domingo Aquino', date_of_entry: '1990-04-20', r2_10_eligibility: 'Eligible - Tier 3', status: 'Transferred' },
    ];

    // Swap the body of this for the real request; keep it returning a promise.
    async function saveMember(id, changes) {
        // const res = await fetch(`/members/${id}`, { method: 'PUT',
        //     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        //     body: JSON.stringify(changes) });
        // if (!res.ok) throw new Error('Save failed');
        await new Promise(r => setTimeout(r, 400));
        Object.assign(DEMO_MEMBERS.find(m => m.id === id), changes);
    }

    /* ---------------- Elements + helpers ---------------- */
    const FIELDS = {
        name: ['rm-name', 'Name'], registry_number: ['rm-registry', 'Registry number'], gender: ['rm-gender', 'Gender'],
        kapisanan: ['rm-kapisanan', 'Kapisanan'], grupo: ['rm-grupo', 'Grupo'], date_of_baptism: ['rm-baptism', 'Date of baptism'], evangelist: ['rm-evangelist', 'Evangelist'],
    };
    const input = k => $(FIELDS[k][0]);
    const overlay = $('reg-modal-overlay'), modal = overlay.querySelector('.reg-modal'), form = $('reg-form');
    let current = null, original = null, lastTrigger = null, r210 = '', r210Shown = false;

    const initials = n => n.split(/\s+/).filter(Boolean).map((w, i, a) => (i === 0 || i === a.length - 1) ? w[0] : '').join('').toUpperCase();
    const fmtDate = iso => iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '--';
    const statusClass = s => ({ active: 'is-active', transferred: 'is-transferred' }[(s || '').toLowerCase()] || 'is-inactive');
    const norm = s => s.toLowerCase().replace(/\s+/g, ' ').trim();
    const place = m => `Purok ${zone(m.purok, m.grupo).label}, Grupo ${m.grupo}`;
    const readVal = k => String(input(k).value).trim();
    function el(tag, cls, text) { const n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
    let snackTimer;
    function snack(text) { const s = $('snack'); s.textContent = text; s.classList.add('show'); clearTimeout(snackTimer); snackTimer = setTimeout(() => s.classList.remove('show'), 3600); }

    /* ---------------- Reminder notification ---------------- */
    const rem = $('reminder');
    function showRem() { rem.hidden = false; requestAnimationFrame(() => requestAnimationFrame(() => rem.classList.add('is-in'))); }
    function hideRem() { rem.classList.remove('is-in'); setTimeout(() => { if (!rem.classList.contains('is-in')) rem.hidden = true; }, reduce ? 0 : 450); }
    $('rem-close').addEventListener('click', hideRem);
    $('rem-ok').addEventListener('click', hideRem);
    $('remind-open').addEventListener('click', showRem);
    setTimeout(showRem, reduce ? 0 : 700);

    /* ---------------- Search ---------------- */
    const errBox = $('reg-search-error'), results = $('reg-results'), searchInput = $('reg-search-input');
    function searchFail(msg) { errBox.textContent = msg; errBox.hidden = false; }

    function runSearch(raw) {
        const q = norm(raw ?? searchInput.value);
        errBox.hidden = true; results.hidden = true;
        if (!q) return searchFail('Please enter a name or registry number.');
        const tokens = q.split(' ');
        const found = DEMO_MEMBERS.filter(m => tokens.every(t => norm(m.name).includes(t)) || norm(m.registry_number).includes(q));
        if (!found.length) return searchFail('No matching record found. Check the spelling or try the registry number.');
        if (found.length === 1) return openMember(found[0]);
        renderResults(found, q);
    }
    function renderResults(list, q) {
        const dupNames = new Set(list.filter((m, i) => list.findIndex(x => norm(x.name) === norm(m.name)) !== i).map(m => norm(m.name)));
        results.replaceChildren(el('h2', '', `${list.length} records match "${q}". Choose one to open.`),
            ...list.map(m => {
                const b = el('button', 'reg-result'); b.type = 'button';
                const who = el('span', 'who'); who.append(el('b', '', m.name), el('small', '', `${m.registry_number}, ${place(m)}`));
                b.append(el('span', 'reg-avatar', initials(m.name)), who);
                if (dupNames.has(norm(m.name))) b.append(el('span', 'reg-dupchip', 'Same name'));
                b.append(el('span', 'reg-badge ' + statusClass(m.status), m.status));
                b.addEventListener('click', () => openMember(m));
                return b;
            }));
        results.hidden = false;
    }
    $('reg-search-btn').addEventListener('click', () => runSearch());
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); runSearch(); } });
    document.querySelectorAll('.reg-chip').forEach(c => c.addEventListener('click', () => { searchInput.value = c.dataset.q; runSearch(c.dataset.q); }));

    /* ---------------- Open / close ---------------- */
    function openMember(m) {
        lastTrigger = document.activeElement;
        current = m; original = { ...m };
        Object.keys(FIELDS).forEach(k => { input(k).value = m[k] ?? ''; });
        $('rm-id').value = m.id;
        $('rm-avatar').textContent = initials(m.name);
        $('rm-name-display').textContent = m.name;
        $('rm-registry-display').textContent = m.registry_number;
        const badge = $('rm-status-badge'); badge.textContent = m.status; badge.className = 'reg-badge ' + statusClass(m.status);

        const z = zone(m.purok, m.grupo);
        $('rm-purok-display').textContent = `Purok ${z.label}`;
        $('rm-status-text').textContent = m.status;
        $('rm-entry-display').textContent = fmtDate(m.date_of_entry);
        r210 = m.r2_10_eligibility || '--'; r210Shown = false;
        $('rm-r210-display').textContent = '••••••••••'; $('rm-r210-toggle').textContent = 'Show';
        input('grupo').min = z.min; input('grupo').max = z.max;
        $('rm-grupo-hint').textContent = z.split
            ? `Purok ${z.label} allows Grupo ${z.min} to ${z.max}. Moving between 1A and 1B is done in Monthly.`
            : `Stays inside Purok ${m.purok} (Grupo ${z.min} to ${z.max}).`;

        const twins = DEMO_MEMBERS.filter(x => x.id !== m.id && norm(x.name) === norm(m.name));
        const dup = $('rm-dup');
        dup.hidden = !twins.length;
        if (twins.length) dup.textContent = `Another record has this same name (${twins.map(t => t.registry_number).join(', ')}). If it is a double entry, it can only be removed in the Monthly portal.`;

        clearErrors(); modal.classList.remove('is-review'); $('reg-save-msg').textContent = '';
        updateDirty();
        overlay.hidden = false;
        if (window.innerWidth < 1100) hideRem();
        setTimeout(() => input('name').focus(), 60);
    }
    function closeModal(force) {
        if (!force && changes().length && !confirm('Discard your unsaved changes?')) return;
        overlay.hidden = true; current = null;
        if (lastTrigger && lastTrigger.focus) lastTrigger.focus();
    }
    $('reg-modal-close').addEventListener('click', () => closeModal());
    $('reg-cancel-btn').addEventListener('click', () => closeModal());
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !overlay.hidden) closeModal(); });
    $('rm-r210-toggle').addEventListener('click', () => {
        r210Shown = !r210Shown;
        $('rm-r210-display').textContent = r210Shown ? r210 : '••••••••••';
        $('rm-r210-toggle').textContent = r210Shown ? 'Hide' : 'Show';
    });

    /* ---------------- Change tracking ---------------- */
    function changes() {
        if (!original) return [];
        return Object.keys(FIELDS).filter(k => readVal(k) !== String(original[k] ?? ''))
            .map(k => ({ key: k, label: FIELDS[k][1], from: String(original[k] ?? '') || '(empty)', to: readVal(k) || '(empty)' }));
    }
    function updateDirty() {
        const list = changes(), keys = new Set(list.map(c => c.key));
        Object.keys(FIELDS).forEach(k => input(k).closest('.f').classList.toggle('is-changed', keys.has(k)));
        $('reg-save-btn').disabled = !list.length;
        $('reg-reset-btn').disabled = !list.length;
        $('reg-save-btn').textContent = list.length ? `Save changes (${list.length})` : 'Save changes';
    }
    form.addEventListener('input', () => { clearErrors(); updateDirty(); });
    $('reg-reset-btn').addEventListener('click', () => { Object.keys(FIELDS).forEach(k => { input(k).value = original[k] ?? ''; }); clearErrors(); updateDirty(); });

    /* ---------------- Validation ---------------- */
    function clearErrors() { form.querySelectorAll('[data-err]').forEach(p => { p.textContent = ''; }); form.querySelectorAll('.f').forEach(f => f.classList.remove('has-error')); }
    function validate() {
        const errs = {};
        if (readVal('name').length < 2) errs.name = 'Enter the full name.';
        const reg = readVal('registry_number').toUpperCase();
        if (!/^REG-\d{4}-\d{4}$/.test(reg)) errs.registry_number = 'Use the format REG-YYYY-NNNN.';
        else {
            const clash = DEMO_MEMBERS.find(m => m.id !== current.id && m.registry_number.toUpperCase() === reg);
            if (clash) errs.registry_number = `Already used by ${clash.name}.`;
        }
        if (!readVal('gender')) errs.gender = 'Choose a gender.';
        const g = Number(readVal('grupo')), z = zone(current.purok, original.grupo);
        if (!readVal('grupo') || !Number.isInteger(g) || g < z.min || g > z.max)
            errs.grupo = `Grupo must be ${z.min} to ${z.max}.` + (z.split ? ' Crossing 1A and 1B is done in Monthly.' : '');
        const d = readVal('date_of_baptism');
        if (!d) errs.date_of_baptism = 'Enter the date of baptism.';
        else if (new Date(d + 'T00:00:00') > new Date()) errs.date_of_baptism = 'The date cannot be in the future.';
        Object.entries(errs).forEach(([k, msg]) => {
            const p = form.querySelector(`[data-err="${k}"]`); p.textContent = msg; p.closest('.f').classList.add('has-error');
        });
        const first = Object.keys(errs)[0];
        if (first) input(first).focus();
        return !first;
    }

    /* ---------------- Save: review, then confirm ---------------- */
    form.addEventListener('submit', e => {
        e.preventDefault();
        if (!changes().length || !validate()) return;
        $('reg-review-list').replaceChildren(...changes().map(c => {
            const li = el('li'), row = el('span');
            row.append(el('span', 'from', c.from), ' → ', el('span', 'to', c.to));
            li.append(el('span', 'k', c.label), row);
            return li;
        }));
        $('reg-review-err').textContent = '';
        modal.classList.add('is-review');
    });
    $('reg-back-btn').addEventListener('click', () => modal.classList.remove('is-review'));
    $('reg-confirm-btn').addEventListener('click', async () => {
        const list = changes(), payload = {};
        list.forEach(c => { payload[c.key] = c.key === 'registry_number' ? readVal(c.key).toUpperCase() : c.key === 'grupo' ? Number(readVal(c.key)) : readVal(c.key); });
        const btn = $('reg-confirm-btn'); btn.disabled = true; btn.textContent = 'Saving...';
        try {
            await saveMember(current.id, payload);
            const name = payload.name || current.name;
            closeModal(true);
            snack(`Record updated: ${name} (${list.length} ${list.length === 1 ? 'change' : 'changes'})`);
            results.hidden = true;
        } catch (err) {
            console.error(err);
            $('reg-review-err').textContent = 'Could not save the changes. Please try again.';
        } finally { btn.disabled = false; btn.textContent = 'Confirm and save'; }
    });
});
</script>
@endpush