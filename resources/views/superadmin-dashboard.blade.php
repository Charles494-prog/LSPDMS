@extends('layouts.app')

@section('title', 'Superadmin Dashboard')

@section('content')
<style>
    :root {
        --sa-accent: var(--accent, #0a6bd6);
        --sa-accent-hover: var(--accent-hover, #0a4a8a);
        --sa-navy: #101828;
        --sa-ink-soft: var(--text-muted, #64748b);
        --sa-surface: var(--surface, #ffffff);
        --sa-border: var(--border, #e3e8ee);
        --sa-bg: var(--bg, #f6f8fa);
        --sa-danger: #dc2626;
        --sa-danger-bg: #fdecea;
        --sa-success: #16a34a;
        --sa-success-bg: #e6f4ea;
    }
    .sa-page {
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
    }

    .sa-header { margin-bottom: 1.4rem; }
    .sa-header h1 { font-size: 1.4rem; margin: 0 0 0.3rem; color: var(--sa-navy); }
    .sa-header p { margin: 0; color: var(--sa-ink-soft); font-size: 0.9rem; }

    /* ===== Tabs ===== */
    .sa-tabs {
        display: flex;
        gap: 0.3rem;
        border-bottom: 1px solid var(--sa-border);
        margin-bottom: 1.6rem;
        overflow-x: auto;
    }
    .sa-tab-btn {
        font-family: inherit;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--sa-ink-soft);
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.85rem 1.1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    .sa-tab-btn:hover { color: var(--sa-navy); }
    .sa-tab-btn.active { color: var(--sa-accent); border-bottom-color: var(--sa-accent); }
    .sa-tab-panel { display: none; }
    .sa-tab-panel.active { display: block; }
    #bnhResetGeneratedCode { display:inline-block; margin:.4rem 0; padding:.55rem .75rem; border:1px solid var(--sa-border); border-radius:8px; background:var(--sa-bg); font-size:1.1rem; font-weight:800; letter-spacing:.12em; user-select:none; -webkit-user-select:none; }
    .bnh-reset-grid { grid-template-columns:1fr; }
    .bnh-reset-field { width:100%; }
    .bnh-reset-state[hidden], .bnh-reset-code-panel[hidden] { display:none !important; }
    .bnh-reset-state { display:flex; width:100%; min-height:300px; box-sizing:border-box; flex-direction:column; align-items:center; justify-content:center; gap:1rem; padding:2rem; border:1px solid color-mix(in srgb,var(--sa-accent) 24%,var(--sa-border)); border-radius:12px; background:linear-gradient(145deg,color-mix(in srgb,var(--sa-accent) 7%,var(--sa-surface)),var(--sa-surface) 74%); color:var(--sa-navy); text-align:center; }
    .bnh-reset-state-copy { width:min(100%,680px); }
    .bnh-reset-state-title { margin:0 0 .4rem; color:var(--sa-navy); font-size:1.1rem; font-weight:750; line-height:1.35; }
    .bnh-reset-state p { margin:0; color:var(--sa-ink-soft); font-size:.92rem; line-height:1.6; }
    .bnh-reset-state-icon { display:grid; width:3.25rem; height:3.25rem; flex:0 0 3.25rem; place-items:center; border-radius:50%; background:color-mix(in srgb,var(--sa-accent) 13%,var(--sa-surface)); color:var(--sa-accent); }
    .bnh-reset-state-icon svg { width:1.25rem; height:1.25rem; }
    .bnh-reset-code-panel { display:flex; width:100%; min-height:300px; box-sizing:border-box; flex-direction:column; align-items:center; justify-content:center; padding:clamp(1.25rem,3vw,2rem); border:1px solid var(--sa-border); border-radius:12px; background:var(--sa-bg); text-align:center; }
    .bnh-reset-code-title { width:min(100%,680px); margin:0 auto 1.5rem; color:var(--sa-navy); font-size:1.15rem; font-weight:750; line-height:1.45; }
    .bnh-reset-code-content { display:flex; width:100%; flex-direction:column; gap:1.25rem; align-items:center; }
    .bnh-reset-code-display { width:min(100%,560px); min-width:0; }
    .bnh-reset-code-label { display:block; margin-bottom:.55rem; color:var(--sa-ink-soft); font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    #bnhResetGeneratedCode { display:block; width:100%; box-sizing:border-box; margin:0; padding:1rem 1.1rem; border:1px solid var(--sa-border); border-radius:9px; background:var(--sa-surface); color:var(--sa-navy); font-family:ui-monospace,SFMono-Regular,Consolas,monospace; font-size:clamp(1.25rem,3vw,1.75rem); font-weight:800; letter-spacing:.18em; text-align:center; user-select:none; -webkit-user-select:none; }
    .bnh-reset-countdown { height:8px; margin-top:.85rem; overflow:hidden; border-radius:999px; background:color-mix(in srgb,var(--sa-accent) 14%,var(--sa-bg)); }
    .bnh-reset-countdown > div { width:100%; height:100%; border-radius:inherit; background:var(--sa-accent); transition:width .12s linear; }
    .bnh-reset-code-timer { margin:.45rem 0 0; color:var(--sa-ink-soft); font-size:.82rem; text-align:center; }
    .bnh-reset-confirmation { width:min(100%,420px); }
    .bnh-reset-confirmation label { display:block; margin-bottom:.55rem; color:var(--sa-navy); font-size:.86rem; font-weight:700; text-align:center; }
    .bnh-reset-confirmation input { box-sizing:border-box; width:100%; min-height:48px; padding:.7rem .8rem; border:1px solid var(--sa-border); border-radius:9px; background:var(--sa-surface); color:var(--sa-navy); font:inherit; letter-spacing:.08em; text-align:center; }
    .bnh-reset-confirmation input:focus { outline:3px solid color-mix(in srgb,var(--sa-accent) 20%,transparent); border-color:var(--sa-accent); }
    .bnh-reset-confirmation input:disabled { background:var(--sa-surface); opacity:.7; }
    @media (max-width:700px) { .bnh-reset-state, .bnh-reset-code-panel { min-height:240px; padding:1.25rem; } }
    #tab-maintenance .sa-card-head { justify-content:center; text-align:center; }
    #tab-maintenance.sa-tab-panel.active { min-height:calc(100vh - 14rem); display:flex; align-items:center; justify-content:center; }
    #tab-maintenance > .sa-card { width:min(100%,760px); margin:0 auto; box-sizing:border-box; }
    .hakbang-status-tabs { display:flex; gap:.5rem; margin:0 0 1rem; }
    .hakbang-status-btn { border:1px solid var(--sa-border); border-radius:8px; padding:.55rem 1rem; color:var(--sa-ink-soft); background:var(--sa-bg); font:inherit; font-weight:700; cursor:pointer; }
    .hakbang-status-btn.active { color:#fff; background:var(--sa-accent); border-color:var(--sa-accent); }
    .hakbang-add-form { display:grid; grid-template-columns:minmax(110px, .7fr) minmax(220px, 2fr) auto; gap:.7rem; align-items:end; margin-bottom:1.2rem; }
    .hakbang-field label { display:block; margin-bottom:.35rem; color:var(--sa-ink-soft); font-size:.8rem; font-weight:600; }
    .hakbang-field input { width:100%; box-sizing:border-box; padding:.6rem .7rem; border:1px solid var(--sa-border); border-radius:8px; font:inherit; }
    .hakbang-row-input { width:100%; min-width:100px; box-sizing:border-box; padding:.5rem .6rem; border:1px solid var(--sa-border); border-radius:7px; font:inherit; }
    .hakbang-description-input { min-width:240px; }
    .hakbang-actions { display:flex; gap:.4rem; }
    @media (max-width:700px) { .hakbang-add-form { grid-template-columns:1fr; } }

    /* ===== Shared card ===== */
    .sa-card {
        background: var(--sa-surface);
        border: 1px solid var(--sa-border);
        border-radius: 14px;
        padding: 1.5rem 1.7rem;
        margin-bottom: 1.5rem;
    }
    .sa-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.2rem;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .sa-card-head h2 { font-size: 1.05rem; margin: 0; color: var(--sa-navy); }
    .sa-card-head p { margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--sa-ink-soft); }

    .sa-card-head-actions {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .sa-page-actions {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 0.9rem;
    }
    .sa-audit-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 0.8rem;
        align-items: end;
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid var(--sa-border);
        border-radius: 10px;
        background: var(--sa-bg);
    }
    .sa-audit-filters label span {
        display: block;
        margin-bottom: 0.35rem;
        color: var(--sa-ink-soft);
        font-size: 0.8rem;
        font-weight: 600;
    }
    .sa-audit-filters input {
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        padding: 0.55rem 0.65rem;
        border: 1px solid var(--sa-border);
        border-radius: 7px;
        background: var(--sa-surface);
        color: var(--sa-navy);
        font: inherit;
    }
    .sa-audit-filter-error { grid-column: 1 / -1; margin: 0; color: var(--sa-danger); font-size: 0.85rem; }

    .sa-btn {
        font-family: inherit;
        font-size: 0.87rem;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.55rem 1.1rem;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        white-space: nowrap;
    }
    .sa-btn-primary { background: var(--sa-accent); color: #fff; }
    .sa-btn-primary:hover { background: var(--sa-accent-hover); }
    .sa-btn-ghost { background: var(--sa-bg); color: var(--sa-navy); border: 1px solid var(--sa-border); }
    .sa-btn-ghost:hover { background: var(--sa-border); }
    .sa-btn-danger { background: var(--sa-danger-bg); color: var(--sa-danger); }
    .sa-btn-danger:hover { background: #f9d5d2; }
    .sa-btn-sm { padding: 0.4rem 0.75rem; font-size: 0.8rem; }

    .sa-search {
        width: 100%;
        max-width: 280px;
        padding: 0.55rem 0.8rem;
        border-radius: 8px;
        border: 1px solid var(--sa-border);
        font-size: 0.87rem;
        font-family: inherit;
        background: var(--sa-bg);
        color: var(--sa-navy);
    }
    .sa-search:focus { outline: none; border-color: var(--sa-accent); background: var(--sa-surface); }

    /* ===== Tables (shared: users + permissions) ===== */
    .sa-table-wrap { overflow-x: auto; }
    table.sa-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    table.sa-table th {
        text-align: left;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--sa-ink-soft);
        font-weight: 700;
        padding: 0.6rem 0.7rem;
        border-bottom: 1px solid var(--sa-border);
        white-space: nowrap;
    }
    table.sa-table th.center, table.sa-table td.center { text-align: center; }
    table.sa-table td {
        padding: 0.7rem;
        border-bottom: 1px solid var(--sa-border);
        color: var(--sa-navy);
        vertical-align: middle;
    }
    table.sa-table tr:last-child td { border-bottom: none; }
    .sa-user-cell { display: flex; align-items: center; gap: 0.65rem; }
    .sa-user-cell img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; background: var(--sa-border); flex-shrink: 0; }
    .sa-user-avatar { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: var(--sa-accent); color: #fff; font-weight: 700; flex-shrink: 0; }
    .sa-user-name { font-weight: 600; font-size: 0.88rem; }
    .sa-user-email { font-size: 0.78rem; color: var(--sa-ink-soft); }

    .sa-select {
        font-family: inherit;
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
        border-radius: 7px;
        border: 1px solid var(--sa-border);
        background: var(--sa-bg);
        color: var(--sa-navy);
        cursor: pointer;
    }
    .sa-select:focus { outline: none; border-color: var(--sa-accent); }

    .sa-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.76rem;
        font-weight: 700;
        padding: 0.28rem 0.6rem;
        border-radius: 999px;
    }
    .sa-status-pill.on { background: var(--sa-success-bg); color: var(--sa-success); }
    .sa-status-pill.off { background: var(--sa-danger-bg); color: var(--sa-danger); }
    .sa-status-pill svg { width: 8px; height: 8px; }

    /* Toggle switch */
    .sa-toggle {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
        flex-shrink: 0;
    }
    .sa-toggle input { opacity: 0; width: 0; height: 0; }
    .sa-toggle-track {
        position: absolute;
        inset: 0;
        background: var(--sa-border);
        border-radius: 999px;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .sa-toggle-track::before {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.15s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .sa-toggle input:checked + .sa-toggle-track { background: var(--sa-success); }
    .sa-toggle input:checked + .sa-toggle-track::before { transform: translateX(18px); }

    .sa-checkbox {
        width: 17px;
        height: 17px;
        accent-color: var(--sa-accent);
        cursor: pointer;
    }

    .sa-actions-cell { display: flex; gap: 0.5rem; align-items: center; }

    /* ===== Signatories (Purok) ===== */
    .purok-list { display: flex; flex-direction: column; gap: 1rem; }
    .purok-card {
        border: 1px solid var(--sa-border);
        border-radius: 12px;
        overflow: hidden;
    }
    .purok-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.9rem 1.1rem;
        background: var(--sa-bg);
        gap: 0.8rem;
    }
    .purok-head-left { display: flex; align-items: center; gap: 0.7rem; flex: 1; min-width: 0; }
    .purok-name-wrap { flex: 1; min-width: 0; }
    .purok-name-view { display: flex; align-items: center; gap: 0.4rem; }
    .purok-name-display {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--sa-navy);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .purok-edit-btn {
        background: none;
        border: 1px solid transparent;
        color: var(--sa-ink-soft);
        border-radius: 6px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .purok-edit-btn:hover { background: var(--sa-surface); border-color: var(--sa-border); color: var(--sa-accent); }
    .purok-name-edit { display: none; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .purok-name-edit.active { display: flex; }
    .purok-name-input {
        font-family: inherit;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--sa-navy);
        background: var(--sa-surface);
        border: 1px solid var(--sa-border);
        border-radius: 6px;
        padding: 0.35rem 0.55rem;
        width: 200px;
        max-width: 100%;
    }
    .purok-name-input:focus { outline: none; border-color: var(--sa-accent); }
    .purok-name-input.invalid { border-color: var(--sa-danger); }
    .purok-body { padding: 1.1rem; display: flex; flex-direction: column; gap: 0.9rem; }

    .signatory-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 0.7rem;
        align-items: center;
    }
    @media (max-width: 640px) {
        .signatory-row { grid-template-columns: 1fr; }
        .sakop-range-inputs { grid-template-columns: 1fr; }
    }
    .signatory-role-badge {
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--sa-accent);
        background: rgba(10,107,214,0.08);
        padding: 0.4rem 0.7rem;
        border-radius: 7px;
        text-align: center;
    }
    .signatory-input {
        font-family: inherit;
        font-size: 0.88rem;
        padding: 0.55rem 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--sa-border);
        background: var(--sa-bg);
        color: var(--sa-navy);
        width: 100%;
    }
    .signatory-input:focus { outline: none; border-color: var(--sa-accent); background: var(--sa-surface); }

    /* Sakop na Grupo range block */
    .sakop-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 0.7rem;
        align-items: start;
        transition: opacity 0.15s ease;
    }
    @media (max-width: 640px) {
        .sakop-row { grid-template-columns: 1fr; }
    }
    .sakop-row.disabled { opacity: 0.5; }
    .sakop-row.disabled .signatory-input { cursor: not-allowed; color: var(--sa-ink-soft); }
    .sakop-row.disabled .sakop-preview { color: var(--sa-ink-soft); background: var(--sa-bg); }

    /* Complete Purok checkbox row */
    .complete-row {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.8rem 0.9rem;
        margin-top: 0.2rem;
        border: 1px solid var(--sa-border);
        border-radius: 10px;
        background: var(--sa-bg);
        cursor: pointer;
    }
    .complete-row input[type="checkbox"] {
        width: 17px; height: 17px;
        margin-top: 0.1rem;
        accent-color: var(--sa-accent);
        cursor: pointer;
        flex-shrink: 0;
    }
    .complete-row-text strong { display: block; font-size: 0.88rem; color: var(--sa-navy); }
    .complete-row-text span { font-size: 0.78rem; color: var(--sa-ink-soft); }
    .sakop-range-inputs {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 0.6rem;
        align-items: center;
    }
    .sakop-range-inputs span { color: var(--sa-ink-soft); font-size: 0.85rem; text-align: center; }
    .sakop-preview {
        margin-top: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--sa-accent);
        background: rgba(10,107,214,0.06);
        padding: 0.45rem 0.7rem;
        border-radius: 7px;
        display: inline-block;
    }

    .add-purok-btn {
        width: 100%;
        border: 1.5px dashed var(--sa-border);
        background: transparent;
        color: var(--sa-ink-soft);
        border-radius: 12px;
        padding: 0.9rem;
        font-family: inherit;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .add-purok-btn:hover { border-color: var(--sa-accent); color: var(--sa-accent); background: rgba(10,107,214,0.04); }

    /* ===== Global variables ===== */
    .gv-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.1rem 1.4rem;
    }
    #tab-maintenance .bnh-reset-grid { grid-template-columns:minmax(0,1fr); }
    #tab-maintenance .bnh-reset-field { grid-column:1 / -1; }
    @media (max-width: 720px) {
        .gv-grid { grid-template-columns: 1fr; }
    }
    .gv-field label {
        display: block;
        font-size: 0.83rem;
        font-weight: 600;
        color: var(--sa-navy);
        margin-bottom: 0.4rem;
    }
    .gv-field input[type="text"],
    .gv-field input[type="date"],
    .gv-field select {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border-radius: 8px;
        border: 1px solid var(--sa-border);
        font-size: 0.88rem;
        font-family: inherit;
        color: var(--sa-navy);
        background: var(--sa-bg);
    }
    .gv-field input:focus, .gv-field select:focus {
        outline: none;
        border-color: var(--sa-accent);
        background: var(--sa-surface);
    }
    .gv-field.disabled label { color: var(--sa-ink-soft); }
    .gv-field.disabled input, .gv-field.disabled select {
        background: var(--sa-bg);
        color: var(--sa-ink-soft);
        cursor: not-allowed;
        opacity: 0.65;
    }
    .gv-hint { font-size: 0.76rem; color: var(--sa-ink-soft); margin-top: 0.35rem; }

    .gv-subsection {
        margin-top: 0.4rem;
        padding-top: 1.3rem;
        border-top: 1px dashed var(--sa-border);
    }
    .gv-subsection-title { font-size: 0.95rem; font-weight: 700; color: var(--sa-navy); margin: 0 0 0.2rem; }
    .gv-subsection-sub { font-size: 0.83rem; color: var(--sa-ink-soft); margin: 0 0 1rem; }

    .delist-checks { display: flex; flex-direction: column; gap: 0.7rem; }
    .delist-check-row {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.8rem 0.9rem;
        border: 1px solid var(--sa-border);
        border-radius: 10px;
        background: var(--sa-bg);
    }
    .delist-check-row input[type="checkbox"] {
        width: 17px; height: 17px;
        margin-top: 0.1rem;
        accent-color: var(--sa-accent);
        cursor: pointer;
        flex-shrink: 0;
    }
    .delist-check-text label { font-weight: 600; font-size: 0.88rem; color: var(--sa-navy); display: block; cursor: pointer; }
    .delist-check-text span { font-size: 0.78rem; color: var(--sa-ink-soft); }

    /* Security Settings — visually distinct from Requirements (toggles instead of checkboxes) */
    .settings-list { display: flex; flex-direction: column; gap: 0.7rem; }
    .settings-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.8rem 0.9rem;
        border: 1px solid var(--sa-border);
        border-radius: 10px;
        background: var(--sa-bg);
    }
    .settings-row-text label { font-weight: 600; font-size: 0.88rem; color: var(--sa-navy); display: block; }
    .settings-row-text span { font-size: 0.78rem; color: var(--sa-ink-soft); }

    .sa-save-bar {
        display: flex;
        justify-content: flex-end;
        gap: 0.7rem;
        margin-top: 0.2rem;
    }
    #saveSignatoriesBtn:disabled {
        background: #cbd5e1;
        color: #64748b;
        cursor: not-allowed;
        box-shadow: none;
    }

    .sa-empty {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--sa-ink-soft);
        font-size: 0.88rem;
    }

    .sa-note {
        font-size: 0.82rem;
        color: var(--sa-ink-soft);
        background: var(--sa-bg);
        border: 1px solid var(--sa-border);
        border-radius: 8px;
        padding: 0.7rem 0.9rem;
        margin-bottom: 1.2rem;
    }

    .sa-toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 100100;
        display: grid;
        gap: 0.65rem;
        width: min(420px, calc(100vw - 2rem));
        pointer-events: none;
    }
    .sa-toast {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        border: 1px solid var(--sa-border);
        border-left: 4px solid var(--sa-accent);
        border-radius: 10px;
        background: var(--sa-surface);
        color: var(--sa-navy);
        box-shadow: 0 12px 32px rgba(16, 24, 40, 0.18);
        pointer-events: auto;
        animation: sa-toast-in 220ms ease-out both;
    }
    .sa-toast[data-type="success"] { border-left-color: var(--sa-success); }
    .sa-toast[data-type="error"] { border-left-color: var(--sa-danger); }
    .sa-toast-icon {
        display: grid;
        place-items: center;
        width: 1.35rem;
        height: 1.35rem;
        flex: 0 0 1.35rem;
        border-radius: 50%;
        background: var(--sa-bg);
        color: var(--sa-accent);
        font-weight: 700;
    }
    .sa-toast[data-type="success"] .sa-toast-icon { color: var(--sa-success); }
    .sa-toast[data-type="error"] .sa-toast-icon { color: var(--sa-danger); }
    .sa-toast-message { flex: 1; font-size: 0.9rem; line-height: 1.45; overflow-wrap: anywhere; }
    .sa-toast-close {
        border: 0;
        padding: 0;
        background: transparent;
        color: var(--sa-ink-soft);
        font-size: 1.2rem;
        line-height: 1;
        cursor: pointer;
    }
    .sa-toast.sa-toast-leaving { animation: sa-toast-out 180ms ease-in both; }
    @keyframes sa-toast-in {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes sa-toast-out {
        to { opacity: 0; transform: translateY(-6px); }
    }

    /* ===== Modal (Edit Permissions of Secretaries) ===== */
    /* Rendered as a direct child of <body> by JS on load, so position:fixed is
       always relative to the viewport and can't be broken by a transformed
       or overflow:hidden ancestor elsewhere in the page layout. */
    .sa-modal-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        background: rgba(16, 24, 40, 0.55) !important;
        display: none;
        align-items: flex-start;
        justify-content: center;
        padding: 3.5rem 1rem;
        z-index: 99999 !important;
        overflow-y: auto;
        box-sizing: border-box;
    }
    .sa-modal-overlay.active { display: flex !important; }
    .sa-modal {
        background: var(--sa-surface);
        border-radius: 14px;
        width: 100%;
        max-width: 820px;
        max-height: calc(100vh - 5rem);
        overflow-y: auto;
        box-sizing: border-box;
        padding: 1.5rem 1.7rem;
        box-shadow: 0 20px 50px rgba(16,24,40,0.35);
        font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif;
    }
    .sa-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.2rem;
    }
    .sa-modal-header h2 { font-size: 1.05rem; margin: 0; color: var(--sa-navy); }
    .sa-modal-header p { margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--sa-ink-soft); }
    .sa-modal-close {
        background: var(--sa-bg);
        border: 1px solid var(--sa-border);
        color: var(--sa-ink-soft);
        border-radius: 8px;
        width: 30px;
        height: 30px;
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sa-modal-close:hover { background: var(--sa-border); color: var(--sa-navy); }
    .sa-modal-search-row { margin-bottom: 1rem; }
    .sa-modal-save-bar {
        display: flex;
        justify-content: flex-end;
        gap: 0.7rem;
        margin-top: 1.2rem;
    }
</style>

<div class="sa-page">

    <div class="sa-header">
        <h1>Superadmin Dashboard</h1>
        <p>Manage user access, congregation signatories, and system-wide settings.</p>
    </div>

    <div class="sa-tabs">
        <button type="button" class="sa-tab-btn active" data-tab="users">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="6.5" r="3"/><path d="M1.5 17c.8-3 3-5 5.5-5s4.7 2 5.5 5"/><circle cx="15" cy="7" r="2.3"/><path d="M13 11.3c2 .2 3.6 1.9 4.2 4.2"/></svg>
            User Management
        </button>
        <button type="button" class="sa-tab-btn" data-tab="signatories">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3h9l3 3v11H4z"/><path d="M13 3v3h3"/><path d="M7 10h6M7 13h6"/></svg>
            Signatories
        </button>
        <button type="button" class="sa-tab-btn" data-tab="security">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2l7 3v5c0 4.5-3 7.5-7 8-4-.5-7-3.5-7-8V5l7-3z"/><path d="M7.2 10l1.9 1.9L13 8"/></svg>
            Security Settings
        </button>
        <button type="button" class="sa-tab-btn" data-tab="globals">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7.3"/><path d="M2.7 10h14.6M10 2.7c2 2.2 3 5 3 7.3s-1 5.1-3 7.3c-2-2.2-3-5-3-7.3s1-5.1 3-7.3z"/></svg>
            Global Variables
        </button>
        <button type="button" class="sa-tab-btn" data-tab="r402-hakbangs">Mga R4-02 Hakbang</button>
        <button type="button" class="sa-tab-btn" data-tab="maintenance">BNH FORM RESET</button>
        <button type="button" class="sa-tab-btn" data-tab="record-audit">Record Activity</button>
    </div>

    <!-- ============ USER MANAGEMENT ============ -->
    <div class="sa-tab-panel active" id="tab-users">
        <div class="sa-page-actions">
            <button type="button" class="sa-btn sa-btn-ghost" id="editPermissionsBtn">
                <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2l7 3v5c0 4.5-3 7.5-7 8-4-.5-7-3.5-7-8V5l7-3z"/><path d="M7.2 10l1.9 1.9L13 8"/></svg>
                Edit Permissions of Secretaries
            </button>
        </div>
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Registered Users</h2>
                    <p>Assign roles and grant or revoke system access.</p>
                </div>
                <input type="text" class="sa-search" id="userSearch" placeholder="Search by name or email...">
            </div>

            <div class="sa-table-wrap">
                <table class="sa-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Tungkulin</th>
                            <th>Access</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        @forelse ($users as $user)
                            @php
                                $userName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->gmail;
                                $roleOptions = ['Secretary', 'Head Secretary'];
                                if ($user->tungkulin && !in_array($user->tungkulin, $roleOptions, true)) {
                                    $roleOptions[] = $user->tungkulin;
                                }
                            @endphp
                            <tr data-user-id="{{ $user->id }}" data-name="{{ strtolower($userName) }}" data-email="{{ strtolower($user->gmail) }}">
                                <td>
                                    <div class="sa-user-cell">
                                        <span class="sa-user-avatar" aria-hidden="true">{{ strtoupper(substr($userName, 0, 1)) }}</span>
                                        <div>
                                            <div class="sa-user-name">{{ $userName }}</div>
                                            <div class="sa-user-email">{{ $user->gmail }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <select class="sa-select tungkulin-select" data-previous-value="{{ $user->tungkulin }}" aria-label="Tungkulin for {{ $userName }}">
                                        @foreach ($roleOptions as $role)
                                            <option value="{{ $role }}" @selected($user->tungkulin === $role)>{{ $role }}</option>
                                        @endforeach
                                        @if (!$user->tungkulin)
                                            <option value="" selected>Not assigned</option>
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    <label class="sa-toggle">
                                        <input type="checkbox" class="access-toggle" @checked($user->is_verified)>
                                        <span class="sa-toggle-track"></span>
                                    </label>
                                </td>
                                <td>
                                    <span class="sa-status-pill {{ $user->is_verified ? 'on' : 'off' }} status-pill">
                                        <svg viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                                        {{ $user->is_verified ? 'Verified' : 'Not Verified' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="sa-empty">No registered users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="sa-empty" id="usersEmptyState" style="display:none;">No users match your search.</div>
            </div>
        </div>
    </div>

    <div class="sa-tab-panel" id="tab-record-audit">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Record Activity Log</h2>
                    <p>Record changes with the acting account, date and changed values. Newest actions appear first.</p>
                </div>
                <a class="sa-btn sa-btn-primary" href="{{ route('superadmin.record-activity.download', array_filter($auditFilters, fn ($value) => $value !== null && $value !== '')) }}">
                    Download Word
                </a>
            </div>
            <form method="GET" action="{{ route('superadmin-dashboard') }}#record-audit" class="sa-audit-filters" id="recordAuditFilters">
                <input type="hidden" name="tab" value="record-audit">
                <label class="gv-field">
                    <span>From date</span>
                    <input type="date" name="date_from" value="{{ $auditFilters['date_from'] ?? '' }}">
                </label>
                <label class="gv-field">
                    <span>To date</span>
                    <input type="date" name="date_to" value="{{ $auditFilters['date_to'] ?? '' }}">
                </label>
                <label class="gv-field">
                    <span>Record name</span>
                    <input type="search" name="record_name" value="{{ $auditFilters['record_name'] ?? '' }}" maxlength="150" placeholder="Search member name">
                </label>
                <label class="gv-field">
                    <span>Secretary email</span>
                    <input type="search" name="actor_email" value="{{ $auditFilters['actor_email'] ?? '' }}" maxlength="255" placeholder="Search account email">
                </label>
                <div class="sa-card-head-actions">
                    <button type="submit" class="sa-btn sa-btn-primary">Apply filters</button>
                    <a class="sa-btn sa-btn-ghost" href="{{ route('superadmin-dashboard') }}#record-audit">Clear</a>
                </div>
                @if ($errors->any())
                    <p class="sa-audit-filter-error" role="alert">{{ $errors->first() }}</p>
                @endif
            </form>
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th scope="col">Date of action</th>
                            <th scope="col">Account</th>
                            <th scope="col">Record</th>
                            <th scope="col">Action details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recordAuditLogs as $auditLog)
                            <tr>
                                <td>{{ $auditLog->created_at?->timezone('Asia/Manila')->format('M d, Y h:i:s A') }}</td>
                                <td>{{ $auditLog->actor_email ?: 'System / background task' }}</td>
                                <td>{{ $auditLog->record_name ?: 'Deleted record' }}{{ $auditLog->record_id ? ' (#'.$auditLog->record_id.')' : '' }}</td>
                                <td>{{ $auditLog->summary }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="sa-empty">No record changes have been logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($recordAuditLogs->hasPages())
                <div class="sa-audit-pagination">{{ $recordAuditLogs->links() }}</div>
            @endif
        </div>
    </div>

    <!-- ============ SIGNATORIES ============ -->
    <div class="sa-tab-panel" id="tab-signatories">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Purok Signatories</h2>
                    <p>Add Puroks, assign their header number, Secretary and Manggagawa, and define each Sakop na Grupo range.</p>
                </div>
            </div>

            <div class="sa-note">
                The Assigned Number is the shared Purok header, not a unique ID. Multiple Puroks may use the same number when a header is split. Set each Purok's Sakop na Grupo range to distinguish its groups; tick "Complete Purok" only when it includes every group under that header.
            </div>

            <div class="purok-list" id="purokList">
                @foreach ($puroks as $purok)
                    <div class="purok-card" data-id="{{ $purok->id }}">
                        <div class="purok-head">
                            <div class="purok-head-left">
                                <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                                <div class="purok-name-wrap">
                                    <div class="purok-name-view">
                                        <span class="purok-name-display">{{ $purok->purok_name ?: 'Purok '.$purok->purok_number }}</span>
                                        <button type="button" class="purok-edit-btn" title="Edit Purok name" aria-label="Edit Purok name"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M12 5l3 3"/></svg></button>
                                    </div>
                                    <div class="purok-name-edit">
                                        <input type="text" class="purok-name-input" value="{{ $purok->purok_name ?: 'Purok '.$purok->purok_number }}" maxlength="40">
                                        <button type="button" class="sa-btn sa-btn-primary sa-btn-sm purok-name-save">Save</button>
                                        <button type="button" class="sa-btn sa-btn-ghost sa-btn-sm purok-name-cancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                        </div>
                        <div class="purok-body">
                            <div class="signatory-row">
                                <span class="signatory-role-badge">Header Number</span>
                                <input type="number" class="signatory-input purok-number-input" placeholder="e.g. 1" min="1" value="{{ $purok->purok_number }}">
                            </div>
                            <div class="signatory-row">
                                <span class="signatory-role-badge">Secretary</span>
                                <select class="signatory-input secretary-input">
                                    <option value="">Not assigned</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($purok->secretary === $user->id)>{{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->gmail }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="signatory-row">
                                <span class="signatory-role-badge">Manggagawa</span>
                                <input type="text" class="signatory-input manggagawa-input" placeholder="Full name" maxlength="255" value="{{ $purok->manggagawa }}">
                            </div>
                            <div class="signatory-row">
                                <span class="signatory-role-badge">ID Number</span>
                                <input type="text" class="signatory-input purok-id-input" placeholder="Ministerial ID number" value="{{ $purok->ministerial_id }}">
                            </div>
                            <label class="complete-row">
                                <input type="checkbox" class="complete-purok-check" @checked($purok->full_purok)>
                                <div class="complete-row-text">
                                    <strong>Complete Purok</strong>
                                    <span>Includes every group that starts with this Purok number (e.g. 1-1, 1-2, 1-3 ...).</span>
                                </div>
                            </label>
                            <div class="sakop-row {{ $purok->full_purok ? 'disabled' : '' }}">
                                <span class="signatory-role-badge">Sakop na Grupo</span>
                                <div>
                                    <div class="sakop-range-inputs">
                                        <input type="number" class="signatory-input sakop-from" placeholder="From" min="0" value="{{ $purok->grupo_start }}" @disabled($purok->full_purok)>
                                        <span>to</span>
                                        <input type="number" class="signatory-input sakop-to" placeholder="To" min="0" value="{{ $purok->grupo_end }}" @disabled($purok->full_purok)>
                                    </div>
                                    <div class="sakop-preview"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if ($puroks->isEmpty())
                    <div class="sa-empty" id="noPuroksMessage">No Purok signatories are configured yet.</div>
                @endif
                @if (false)
                <!-- Sample Purok 1: full range 1–24 -->
                <div class="purok-card">
                    <div class="purok-head">
                        <div class="purok-head-left">
                            <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                            <div class="purok-name-wrap">
                                <div class="purok-name-view">
                                    <span class="purok-name-display">Purok 1</span>
                                    <button type="button" class="purok-edit-btn" title="Edit Purok name" aria-label="Edit Purok name"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M12 5l3 3"/></svg></button>
                                </div>
                                <div class="purok-name-edit">
                                    <input type="text" class="purok-name-input" value="Purok 1" maxlength="40">
                                    <button type="button" class="sa-btn sa-btn-primary sa-btn-sm purok-name-save">Save</button>
                                    <button type="button" class="sa-btn sa-btn-ghost sa-btn-sm purok-name-cancel">Cancel</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                    </div>
                    <div class="purok-body">
                        <div class="signatory-row">
                            <span class="signatory-role-badge">Assigned Number</span>
                            <input type="number" class="signatory-input purok-number-input" placeholder="e.g. 1" min="1" value="1">
                        </div>                        <div class="signatory-row">
                            <span class="signatory-role-badge">Secretary</span>
                            <input type="text" class="signatory-input" placeholder="Full name">
                        </div>
                        <div class="signatory-row">
                            <span class="signatory-role-badge">Manggagawa</span>
                            <input type="text" class="signatory-input" placeholder="Full name">
                        </div>
                        <div class="signatory-row">
                            <span class="signatory-role-badge">ID Number</span>
                            <input type="text" class="signatory-input purok-id-input" placeholder="Ministerial ID number">
                        </div>
                        <label class="complete-row">
                            <input type="checkbox" class="complete-purok-check" checked>
                            <div class="complete-row-text">
                                <strong>Complete Purok</strong>
                                <span>Includes every group that starts with this Purok number (e.g. 1-1, 1-2, 1-3 ...).</span>
                            </div>
                        </label>
                        <div class="sakop-row disabled">
                            <span class="signatory-role-badge">Sakop na Grupo</span>
                            <div>
                                <div class="sakop-range-inputs">
                                    <input type="number" class="signatory-input sakop-from" disabled placeholder="From" value="1" min="0">
                                    <span>to</span>
                                    <input type="number" class="signatory-input sakop-to" disabled placeholder="To" value="24" min="0">
                                </div>
                                <div class="sakop-preview">All groups: Purok 1-1, 1-2, 1-3 ...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sample Purok 2a: sub-group range 1–6 -->
                <div class="purok-card">
                    <div class="purok-head">
                        <div class="purok-head-left">
                            <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                            <div class="purok-name-wrap">
                                <div class="purok-name-view">
                                    <span class="purok-name-display">Purok 2a</span>
                                    <button type="button" class="purok-edit-btn" title="Edit Purok name" aria-label="Edit Purok name"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M12 5l3 3"/></svg></button>
                                </div>
                                <div class="purok-name-edit">
                                    <input type="text" class="purok-name-input" value="Purok 2a" maxlength="40">
                                    <button type="button" class="sa-btn sa-btn-primary sa-btn-sm purok-name-save">Save</button>
                                    <button type="button" class="sa-btn sa-btn-ghost sa-btn-sm purok-name-cancel">Cancel</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                    </div>
                    <div class="purok-body">
                        <div class="signatory-row">
                            <span class="signatory-role-badge">Assigned Number</span>
                            <input type="number" class="signatory-input purok-number-input" placeholder="e.g. 1" min="1" value="2">
                        </div>                        <div class="signatory-row">
                            <span class="signatory-role-badge">Secretary</span>
                            <input type="text" class="signatory-input" placeholder="Full name">
                        </div>
                        <div class="signatory-row">
                            <span class="signatory-role-badge">Manggagawa</span>
                            <input type="text" class="signatory-input" placeholder="Full name">
                        </div>
                        <div class="signatory-row">
                            <span class="signatory-role-badge">ID Number</span>
                            <input type="text" class="signatory-input purok-id-input" placeholder="Ministerial ID number">
                        </div>
                        <label class="complete-row">
                            <input type="checkbox" class="complete-purok-check">
                            <div class="complete-row-text">
                                <strong>Complete Purok</strong>
                                <span>Includes every group that starts with this Purok number (e.g. 1-1, 1-2, 1-3 ...).</span>
                            </div>
                        </label>
                        <div class="sakop-row">
                            <span class="signatory-role-badge">Sakop na Grupo</span>
                            <div>
                                <div class="sakop-range-inputs">
                                    <input type="number" class="signatory-input sakop-from" placeholder="From" value="1" min="0">
                                    <span>to</span>
                                    <input type="number" class="signatory-input sakop-to" placeholder="To" value="6" min="0">
                                </div>
                                <div class="sakop-preview">Range: Purok 2-1 to 2-6</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sample Purok 2b: sub-group range 7–10 -->
                <div class="purok-card">
                    <div class="purok-head">
                        <div class="purok-head-left">
                            <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                            <div class="purok-name-wrap">
                                <div class="purok-name-view">
                                    <span class="purok-name-display">Purok 2b</span>
                                    <button type="button" class="purok-edit-btn" title="Edit Purok name" aria-label="Edit Purok name"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M12 5l3 3"/></svg></button>
                                </div>
                                <div class="purok-name-edit">
                                    <input type="text" class="purok-name-input" value="Purok 2b" maxlength="40">
                                    <button type="button" class="sa-btn sa-btn-primary sa-btn-sm purok-name-save">Save</button>
                                    <button type="button" class="sa-btn sa-btn-ghost sa-btn-sm purok-name-cancel">Cancel</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                    </div>
                    <div class="purok-body">
                        <div class="signatory-row">
                            <span class="signatory-role-badge">Assigned Number</span>
                            <input type="number" class="signatory-input purok-number-input" placeholder="e.g. 1" min="1" value="2">
                        </div>                        <div class="signatory-row">
                            <span class="signatory-role-badge">Secretary</span>
                            <input type="text" class="signatory-input" placeholder="Full name">
                        </div>
                        <div class="signatory-row">
                            <span class="signatory-role-badge">Manggagawa</span>
                            <input type="text" class="signatory-input" placeholder="Full name">
                        </div>
                        <div class="signatory-row">
                            <span class="signatory-role-badge">ID Number</span>
                            <input type="text" class="signatory-input purok-id-input" placeholder="Ministerial ID number">
                        </div>
                        <label class="complete-row">
                            <input type="checkbox" class="complete-purok-check">
                            <div class="complete-row-text">
                                <strong>Complete Purok</strong>
                                <span>Includes every group that starts with this Purok number (e.g. 1-1, 1-2, 1-3 ...).</span>
                            </div>
                        </label>
                        <div class="sakop-row">
                            <span class="signatory-role-badge">Sakop na Grupo</span>
                            <div>
                                <div class="sakop-range-inputs">
                                    <input type="number" class="signatory-input sakop-from" placeholder="From" value="7" min="0">
                                    <span>to</span>
                                    <input type="number" class="signatory-input sakop-to" placeholder="To" value="10" min="0">
                                </div>
                                <div class="sakop-preview">Range: Purok 2-7 to 2-10</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <button type="button" class="add-purok-btn" id="addPurokBtn" style="margin-top:1rem;">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 4v12M4 10h12"/></svg>
                Add Purok
            </button>

            <div class="sa-save-bar">
                <button type="button" class="sa-btn sa-btn-ghost" id="discardSignatoriesBtn">Discard</button>
                <button type="button" class="sa-btn sa-btn-primary" id="saveSignatoriesBtn" disabled>Save Signatories</button>
            </div>
        </div>
    </div>

    <!-- ============ SECURITY SETTINGS ============ -->
    <div class="sa-tab-panel" id="tab-security">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Security Settings</h2>
                    <p>General on/off settings for the system. Not related to de-listment requirements — rename or add to these as needed.</p>
                </div>
            </div>

            <div class="settings-list">
                <div class="settings-row">
                    <div class="settings-row-text">
                        <label for="settingSample1">Require OTP</label>
                        <span>When enabled, every account must verify an emailed code after entering a password.</span>
                    </div>
                    <label class="sa-toggle">
                        <input type="checkbox" id="requireOtpSetting" @checked($globalVariables?->require_otp ?? true)>
                        <span class="sa-toggle-track"></span>
                    </label>
                </div>
                <div class="settings-row">
                    <div class="settings-row-text">
                        <label for="settingSample2">Allow Forgot Password</label>
                        <span>When disabled, the forgot-password link and password reset endpoints are unavailable.</span>
                    </div>
                    <label class="sa-toggle">
                        <input type="checkbox" id="allowForgotPasswordSetting" @checked($globalVariables?->allow_forgot_password ?? true)>
                        <span class="sa-toggle-track"></span>
                    </label>
                </div>
            </div>

            <div class="sa-save-bar">
                <button type="button" class="sa-btn sa-btn-ghost" id="discardSecuritySettingsBtn">Discard</button>
                <button type="button" class="sa-btn sa-btn-primary" id="saveSecuritySettingsBtn">Save Security Settings</button>
            </div>
        </div>
    </div>

    <!-- ============ GLOBAL VARIABLES ============ -->
    <div class="sa-tab-panel" id="tab-globals">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Global Variables</h2>
                    <p>These values are used across generated forms and documents.</p>
                </div>
            </div>

            <div class="gv-grid">
                <div class="gv-field">
                    <label for="gvLocale">Locale</label>
                    <input type="text" id="gvLocale" placeholder="Locale" value="{{ $globalVariables?->locale }}">
                </div>
                <div class="gv-field">
                    <label for="gvLcode">Lcode</label>
                    <input type="text" id="gvLcode" placeholder="Lcode" value="{{ $globalVariables?->lcode }}">
                </div>
                <div class="gv-field">
                    <label for="gvDistrict">District</label>
                    <input type="text" id="gvDistrict" placeholder="District" value="{{ $globalVariables?->district }}">
                </div>
                <div class="gv-field">
                    <label for="gvDcode">DCode</label>
                    <input type="text" id="gvDcode" placeholder="DCode" value="{{ $globalVariables?->dcode }}">
                </div>
                <div class="gv-field">
                    <label for="gvSNumber">S Number</label>
                    <input type="text" id="gvSNumber" placeholder="e.g. S-123" value="{{ $globalVariables?->s_number }}">
                </div>
                <div class="gv-field">
                    <label for="gvPastor">Pastor / Destinado</label>
                    <input type="text" id="gvPastor" placeholder="Full name" value="{{ $globalVariables?->pastor_destinado }}">
                </div>
                <div class="gv-field">
                    <label for="gvKsp">KSP ng Lokal / Pangulong Diakono</label>
                    <input type="text" id="gvKsp" placeholder="Full name" value="{{ $globalVariables?->ksp_pd }}">
                    <div class="gv-hint">Also used as the Pangulong Diakono in generated forms.</div>
                </div>
                <div class="gv-field">
                    <label for="gvHeadLsp">Head LSP</label>
                    <input type="text" id="gvHeadLsp" placeholder="Full name" value="{{ $globalVariables?->head_lsp }}">
                </div>
                <div class="gv-field">
                    <label for="gvKalihimMs">Kalihim ng MS</label>
                    <input type="text" id="gvKalihimMs" placeholder="Full name" value="{{ $globalVariables?->kalihim_ms }}">
                </div>
                <div class="gv-field">
                    <label for="gvKalihimUwp">Kalihim ng UWP</label>
                    <input type="text" id="gvKalihimUwp" placeholder="Full name" value="{{ $globalVariables?->kalihim_uwp }}">
                </div>

                <div class="gv-field {{ $globalVariables?->dateofbaptism_requirement_boolean ? '' : 'disabled' }}" id="gvBaptismField">
                    <label for="gvBaptismDate">Baptism cutoff date</label>
                    <input type="date" id="gvBaptismDate" value="{{ $globalVariables?->baptism_requirement?->format('Y-m-d') }}" @disabled(!$globalVariables?->dateofbaptism_requirement_boolean)>
                    <div class="gv-hint">When enabled, members must have a baptism date on or before this cutoff.</div>
                </div>

                <div class="gv-field" id="gvEntryYearsField">
                    <label for="gvEntryYears">Minimum years since date of entry</label>
                    <select id="gvEntryYears" required>
                        <option value="1" @selected(($globalVariables?->entry_years_requirement ?? 1) === 1)>1 year</option>
                        <option value="2" @selected($globalVariables?->entry_years_requirement === 2)>2 years</option>
                        <option value="3" @selected($globalVariables?->entry_years_requirement === 3)>3 years</option>
                    </select>
                    <div class="gv-hint">MS and UWP records must have been in the system for this long. MK is always eligible.</div>
                </div>
            </div>

            <!-- Requirements for De-Listment (controls the two fields above) -->
            <div class="gv-subsection">
                <p class="gv-subsection-title">Requirements for De-Listment</p>
                <p class="gv-subsection-sub">Configure whether baptism cutoff applies to R2-10 eligibility.</p>

                <div class="delist-checks">
                    <div class="delist-check-row">
                        <input type="checkbox" id="delistBaptismCheck" data-controls="gvBaptismField" @checked($globalVariables?->dateofbaptism_requirement_boolean)>
                        <div class="delist-check-text">
                            <label for="delistBaptismCheck">Date of Baptism Requirement</label>
                            <span>Require a recorded date of baptism before de-listment.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sa-save-bar">
                <button type="button" class="sa-btn sa-btn-ghost" id="discardGlobalVariablesBtn">Discard</button>
                <button type="button" class="sa-btn sa-btn-primary" id="saveGlobalVariablesBtn">Save Global Variables</button>
            </div>
        </div>
    </div>

    <div class="sa-tab-panel" id="tab-r402-hakbangs">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Mga R4-02 Hakbang</h2>
                    <p>Manage the code and description entries separately for MS and UWP. Codes are unique within each status.</p>
                </div>
            </div>
            <div class="hakbang-status-tabs" role="tablist" aria-label="R4-02 status">
                <button type="button" class="hakbang-status-btn active" data-hakbang-status="MS" role="tab" aria-selected="true">MS</button>
                <button type="button" class="hakbang-status-btn" data-hakbang-status="UWP" role="tab" aria-selected="false">UWP</button>
            </div>
            <form class="hakbang-add-form" id="addHakbangForm">
                <div class="hakbang-field">
                    <label for="newHakbangCode">Code</label>
                    <input id="newHakbangCode" name="code" type="text" placeholder="e.g. 1-7" pattern="[0-9]+-[0-9]+" maxlength="30" required>
                </div>
                <div class="hakbang-field">
                    <label for="newHakbangDescription">Description</label>
                    <input id="newHakbangDescription" name="description" type="text" maxlength="2000" placeholder="e.g. Dinalaw, Pinayuhan, Pinanalangin" required>
                </div>
                <button type="submit" class="sa-btn sa-btn-primary" id="addHakbangBtn">Add Hakbang</button>
            </form>
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead><tr><th>Code</th><th>Description</th><th>Actions</th></tr></thead>
                    <tbody id="hakbangRows">
                        @foreach ($r402Hakbangs as $hakbang)
                            <tr data-hakbang-id="{{ $hakbang->id }}" data-status="{{ $hakbang->status }}">
                                <td><input class="hakbang-row-input hakbang-code-input" type="text" value="{{ $hakbang->code }}" pattern="[0-9]+-[0-9]+" maxlength="30" aria-label="Hakbang code"></td>
                                <td><input class="hakbang-row-input hakbang-description-input" type="text" value="{{ $hakbang->description }}" maxlength="2000" aria-label="Hakbang description"></td>
                                <td>
                                    <div class="hakbang-actions">
                                        <button type="button" class="sa-btn sa-btn-primary sa-btn-sm hakbang-save-btn" disabled>Save</button>
                                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm hakbang-delete-btn">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="sa-empty" id="hakbangEmptyState" @if ($r402Hakbangs->isNotEmpty()) style="display:none;" @endif>No hakbang codes configured for this status.</div>
            </div>
        </div>
    </div>

    <div class="sa-tab-panel" id="tab-maintenance">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>BNH FORM RESET</h2>
                </div>
            </div>
            <div class="gv-grid bnh-reset-grid">
                <div class="gv-field bnh-reset-field">
                    <div class="bnh-reset-state" id="bnhResetEmptyState" role="status" hidden>
                        <span class="bnh-reset-state-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3.5 20 7v5.5c0 4.2-3.1 7.1-8 8.5-4.9-1.4-8-4.3-8-8.5V7l8-3.5Z"/>
                                <path d="M12 8v4.5M12 16h.01"/>
                            </svg>
                        </span>
                        <div class="bnh-reset-state-copy">
                            <h3 class="bnh-reset-state-title">No BNH code has been detected</h3>
                            <p>If a code becomes available, it will appear here instead.</p>
                        </div>
                    </div>
                    <div class="bnh-reset-code-panel" id="bnhResetCodePanel" hidden>
                        <h3 class="bnh-reset-code-title">Enter the code below to reset the BNH Form Helper</h3>
                        <div class="bnh-reset-code-content">
                            <div class="bnh-reset-code-display">
                                <span class="bnh-reset-code-label">Reset code</span>
                                <code id="bnhResetGeneratedCode" aria-label="Reset code"></code>
                                <div class="bnh-reset-countdown" role="progressbar" aria-label="Code time remaining" aria-valuemin="0" aria-valuemax="45" aria-valuenow="45">
                                    <div id="bnhResetCountdownBar"></div>
                                </div>
                                <p class="bnh-reset-code-timer" id="bnhResetCountdownText">45 seconds remaining</p>
                            </div>
                            <div class="bnh-reset-confirmation">
                                <label for="bnhResetCodeInput">Enter reset code</label>
                                <input id="bnhResetCodeInput" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="12" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="sa-toast-container" id="saToastContainer" aria-live="polite" aria-atomic="false"></div>

<div class="sa-modal-overlay" id="deleteHakbangModal" aria-hidden="true">
    <div class="sa-modal" role="dialog" aria-modal="true" aria-labelledby="deleteHakbangTitle">
        <div class="sa-modal-header">
            <div>
                <h2 id="deleteHakbangTitle">Delete Hakbang?</h2>
                <p id="deleteHakbangMessage">This entry will be permanently removed.</p>
            </div>
        </div>
        <div class="sa-modal-save-bar">
            <button type="button" class="sa-btn sa-btn-ghost" id="cancelDeleteHakbang">Cancel</button>
            <button type="button" class="sa-btn sa-btn-danger" id="confirmDeleteHakbang">Delete Hakbang</button>
        </div>
    </div>
</div>

<!-- ============ EDIT PERMISSIONS OF SECRETARIES (modal, launched from User Management) ============ -->
<div class="sa-modal-overlay" id="permissionsModalOverlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <div>
                <h2>Secretary Permissions</h2>
                <p>Only currently active secretaries are listed. Toggle which portals each one can access.</p>
            </div>
            <button type="button" class="sa-modal-close" id="closePermissionsModal" aria-label="Close">&times;</button>
        </div>

        <div class="sa-modal-search-row">
            <input type="text" class="sa-search" id="permSearch" placeholder="Search by name or email..." style="max-width:100%;">
        </div>

        <div class="sa-table-wrap">
            <table class="sa-table" id="permissionsTable">
                <thead>
                    <tr>
                        <th>Secretary</th>
                        <th class="center">Allow Dashboard<br>Monitoring</th>
                        <th class="center">Allow Form<br>Generator Portal</th>
                        <th class="center">Allow<br>De-Listment</th>
                        <th class="center">Allow Inquire<br>/ Modify</th>
                        <th class="center">Allow Tarheta<br>Status Checker</th>
                        <th class="center">Allow Filter &amp;<br>BNH Helper</th>
                    </tr>
                </thead>
                <tbody id="permissionsTableBody">
                    @forelse ($users->where('is_verified', true) as $user)
                        @php $userName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->gmail; @endphp
                        <tr data-user-id="{{ $user->id }}" data-name="{{ strtolower($userName) }}" data-email="{{ strtolower($user->gmail) }}">
                            <td>
                                <div class="sa-user-cell">
                                    <span class="sa-user-avatar" aria-hidden="true">{{ strtoupper(substr($userName, 0, 1)) }}</span>
                                    <div>
                                        <div class="sa-user-name">{{ $userName }}</div>
                                        <div class="sa-user-email">{{ $user->gmail }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="center"><input type="checkbox" class="sa-checkbox" data-permission="dashboard_allow" @checked($user->dashboard_allow)></td>
                            <td class="center"><input type="checkbox" class="sa-checkbox" data-permission="form_generator_allow" @checked($user->form_generator_allow)></td>
                            <td class="center"><input type="checkbox" class="sa-checkbox" data-permission="Delistment_allow" @checked($user->Delistment_allow)></td>
                            <td class="center"><input type="checkbox" class="sa-checkbox" data-permission="Inquire_Allow" @checked($user->Inquire_Allow)></td>
                            <td class="center"><input type="checkbox" class="sa-checkbox" data-permission="tarheta_status_allow" @checked($user->tarheta_status_allow)></td>
                            <td class="center"><input type="checkbox" class="sa-checkbox" data-permission="filter_bnh_helper_allow" @checked($user->filter_bnh_helper_allow)></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="sa-empty">No verified users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="sa-empty" id="permEmptyState" style="display:none;">No secretaries match your search.</div>
        </div>

        <div class="sa-modal-save-bar">
            <button type="button" class="sa-btn sa-btn-ghost" id="cancelPermissionsBtn">Cancel</button>
            <button type="button" class="sa-btn sa-btn-primary" id="savePermissionsBtn">Save Permissions</button>
        </div>
    </div>
</div>

<script>
    (function () {
        // ===== Tabs =====
        const tabBtns = document.querySelectorAll('.sa-tab-btn');
        const tabPanels = document.querySelectorAll('.sa-tab-panel');
        let ensureBnhResetCode = null;

        function activateTab(tabName) {
            const button = Array.from(tabBtns).find(tab => tab.dataset.tab === tabName);
            const panel = document.getElementById(`tab-${tabName}`);
            if (!button || !panel) return;
            tabBtns.forEach(tab => tab.classList.toggle('active', tab === button));
            tabPanels.forEach(tabPanel => tabPanel.classList.toggle('active', tabPanel === panel));
        }

        const requestedTab = window.location.hash.replace(/^#/, '')
            || new URLSearchParams(window.location.search).get('tab');
        if (requestedTab) activateTab(requestedTab);

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                activateTab(btn.dataset.tab);
                window.history.replaceState(null, '', `#${btn.dataset.tab}`);
                if (btn.dataset.tab === 'maintenance') ensureBnhResetCode?.();
            });
        });

        // ===== Generic search filter (used by Users table + Permissions modal table) =====
        function wireSearch(inputId, bodyId, emptyStateId) {
            const input = document.getElementById(inputId);
            const body = document.getElementById(bodyId);
            const emptyState = document.getElementById(emptyStateId);
            if (!input || !body) return;

            input.addEventListener('input', () => {
                const term = input.value.trim().toLowerCase();
                let visibleCount = 0;

                body.querySelectorAll('tr').forEach(row => {
                    const matches = (row.dataset.name || '').includes(term) || (row.dataset.email || '').includes(term);
                    row.style.display = matches ? '' : 'none';
                    if (matches) visibleCount++;
                });

                if (emptyState) emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            });
        }

        wireSearch('userSearch', 'usersTableBody', 'usersEmptyState');
        wireSearch('permSearch', 'permissionsTableBody', 'permEmptyState');

        const csrfToken = @json(csrf_token());
        const superadminUrls = {
            users: @json(url('/superadmin/users')),
            permissions: @json(route('superadmin.permissions.update')),
            signatories: @json(route('superadmin.signatories.update')),
            globalVariables: @json(route('superadmin.global-variables.update')),
            securitySettings: @json(route('superadmin.security-settings.update')),
            generateBnhResetCode: @json(route('superadmin.reset-bnh-helper-checks.code')),
            resetBnhChecks: @json(route('superadmin.reset-bnh-helper-checks')),
            r402Hakbangs: @json(url('/superadmin/r4-02/hakbangs')),
        };
        const toastContainer = document.getElementById('saToastContainer');
        if (toastContainer && toastContainer.parentElement !== document.body) {
            document.body.appendChild(toastContainer);
        }

        function showToast(message, type = 'success') {
            if (!toastContainer || !message) return;

            const toast = document.createElement('div');
            toast.className = 'sa-toast';
            toast.dataset.type = type;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

            const icon = document.createElement('span');
            icon.className = 'sa-toast-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = type === 'success' ? '✓' : (type === 'error' ? '!' : 'i');

            const text = document.createElement('span');
            text.className = 'sa-toast-message';
            text.textContent = message;

            const close = document.createElement('button');
            close.className = 'sa-toast-close';
            close.type = 'button';
            close.setAttribute('aria-label', 'Dismiss notification');
            close.textContent = '×';

            let removed = false;
            const dismiss = () => {
                if (removed) return;
                removed = true;
                window.clearTimeout(timer);
                toast.classList.add('sa-toast-leaving');
                window.setTimeout(() => toast.remove(), 180);
            };
            close.addEventListener('click', dismiss);
            toast.append(icon, text, close);
            toastContainer.appendChild(toast);
            const timer = window.setTimeout(dismiss, type === 'error' ? 7000 : 4500);
        }

        async function sendDestructiveReset(url, confirmation) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ confirmation }),
            });
            const body = await response.json();
            if (!response.ok) {
                const message = body.errors ? Object.values(body.errors).flat()[0] : body.message;
                throw new Error(message || 'The reset could not be completed.');
            }
            return body;
        }

        const bnhCodePanel = document.getElementById('bnhResetCodePanel');
        const bnhEmptyState = document.getElementById('bnhResetEmptyState');
        const bnhCodeDisplay = document.getElementById('bnhResetGeneratedCode');
        const bnhCodeInput = document.getElementById('bnhResetCodeInput');
        const bnhCountdownBar = document.getElementById('bnhResetCountdownBar');
        const bnhCountdownTrack = bnhCountdownBar?.parentElement;
        const bnhCountdownText = document.getElementById('bnhResetCountdownText');
        const bnhCodeLifetime = 45;
        let expectedBnhCode = '';
        let submittingBnhCode = false;
        let bnhCodeCycleActive = false;
        let bnhCodeExpiresAt = 0;
        let bnhCodeTimer = null;

        async function generateBnhResetCode() {
            window.clearInterval(bnhCodeTimer);
            bnhCodeCycleActive = false;
            expectedBnhCode = '';
            bnhCodePanel.hidden = true;
            bnhEmptyState.hidden = true;
            bnhCodeInput.disabled = true;
            bnhCodeInput.value = '';
            const requestStartedAt = Date.now();
            const response = await fetch(superadminUrls.generateBnhResetCode, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            });
            const body = await response.json();
            if (!response.ok) {
                bnhCodePanel.hidden = true;
                if (body.empty) {
                    bnhEmptyState.hidden = false;
                    return;
                }
                throw new Error(body.message || Object.values(body.errors || {}).flat()[0] || 'Could not generate the BNH reset code.');
            }

            expectedBnhCode = body.code;
            bnhCodeDisplay.textContent = expectedBnhCode;
            bnhCodePanel.hidden = false;
            bnhCodeInput.disabled = false;
            bnhCodeCycleActive = true;
            bnhCodeExpiresAt = requestStartedAt + bnhCodeLifetime * 1000;

            const updateCountdown = () => {
                const remaining = Math.max(0, bnhCodeExpiresAt - Date.now());
                const seconds = Math.ceil(remaining / 1000);
                bnhCountdownBar.style.width = `${(remaining / (bnhCodeLifetime * 1000)) * 100}%`;
                bnhCountdownTrack.setAttribute('aria-valuenow', String(seconds));
                bnhCountdownText.textContent = `${seconds} second${seconds === 1 ? '' : 's'} remaining`;
                if (remaining === 0) {
                    window.clearInterval(bnhCodeTimer);
                    generateBnhResetCode().catch(error => showToast(error.message, 'error'));
                }
            };

            updateCountdown();
            bnhCodeTimer = window.setInterval(updateCountdown, 100);
        }

        ensureBnhResetCode = () => {
            if (bnhCodeCycleActive || submittingBnhCode) return;
            generateBnhResetCode().catch(error => showToast(error.message, 'error'));
        };

        bnhCodeDisplay.addEventListener('copy', event => event.preventDefault());
        bnhCodeDisplay.addEventListener('cut', event => event.preventDefault());
        bnhCodeDisplay.addEventListener('contextmenu', event => event.preventDefault());
        bnhCodeInput.addEventListener('paste', event => {
            event.preventDefault();
            showToast('Please type the code manually; pasting is disabled.', 'error');
        });
        bnhCodeInput.addEventListener('copy', event => event.preventDefault());
        bnhCodeInput.addEventListener('cut', event => event.preventDefault());
        bnhCodeInput.addEventListener('input', async () => {
            if (!expectedBnhCode || submittingBnhCode || bnhCodeInput.value !== expectedBnhCode) return;
            submittingBnhCode = true;
            bnhCodeCycleActive = false;
            window.clearInterval(bnhCodeTimer);
            bnhCodeInput.disabled = true;
            try {
                const result = await sendDestructiveReset(superadminUrls.resetBnhChecks, bnhCodeInput.value);
                showToast(result.message);
                expectedBnhCode = '';
                bnhCodePanel.hidden = true;
                bnhEmptyState.hidden = false;
                bnhCodeInput.value = '';
            } catch (error) {
                if (error.message === 'BNH CHECKS is currently Empty.') bnhEmptyState.hidden = false;
                else showToast(error.message, 'error');
                expectedBnhCode = '';
                bnhCodePanel.hidden = true;
                bnhCodeInput.value = '';
            } finally {
                submittingBnhCode = false;
            }
        });

        if (document.getElementById('tab-maintenance')?.classList.contains('active')) {
            ensureBnhResetCode();
        }

        async function sendUpdate(url, payload) {
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const body = await response.json();
            if (!response.ok) {
                const message = body.errors ? Object.values(body.errors).flat()[0] : body.message;
                throw new Error(message || 'The update could not be saved.');
            }
            return body;
        }

        // ===== R4-02 Hakbang management =====
        const hakbangRows = document.getElementById('hakbangRows');
        const hakbangEmptyState = document.getElementById('hakbangEmptyState');
        const hakbangStatusButtons = document.querySelectorAll('[data-hakbang-status]');
        const hakbangAddForm = document.getElementById('addHakbangForm');
        const deleteHakbangModal = document.getElementById('deleteHakbangModal');
        const deleteHakbangMessage = document.getElementById('deleteHakbangMessage');
        let selectedHakbangStatus = 'MS';
        let pendingHakbangDelete = null;

        if (deleteHakbangModal && deleteHakbangModal.parentElement !== document.body) {
            document.body.appendChild(deleteHakbangModal);
        }

        function refreshHakbangRows() {
            if (!hakbangRows) return;
            const visibleRows = [...hakbangRows.querySelectorAll('tr[data-hakbang-id]')]
                .filter(row => row.dataset.status === selectedHakbangStatus);
            hakbangRows.querySelectorAll('tr[data-hakbang-id]').forEach(row => {
                row.hidden = row.dataset.status !== selectedHakbangStatus;
            });
            if (hakbangEmptyState) hakbangEmptyState.style.display = visibleRows.length ? 'none' : 'block';
        }

        function setHakbangRowBaseline(row) {
            row.querySelector('.hakbang-code-input').dataset.originalValue = row.querySelector('.hakbang-code-input').value;
            row.querySelector('.hakbang-description-input').dataset.originalValue = row.querySelector('.hakbang-description-input').value;
            row.querySelector('.hakbang-save-btn').disabled = true;
        }

        function createHakbangRow(item) {
            const row = document.createElement('tr');
            row.dataset.hakbangId = item.id;
            row.dataset.status = item.status;

            const codeCell = document.createElement('td');
            const codeInput = document.createElement('input');
            codeInput.className = 'hakbang-row-input hakbang-code-input';
            codeInput.type = 'text';
            codeInput.maxLength = 30;
            codeInput.pattern = '[0-9]+-[0-9]+';
            codeInput.setAttribute('aria-label', 'Hakbang code');
            codeInput.value = item.code;
            codeCell.appendChild(codeInput);

            const descriptionCell = document.createElement('td');
            const descriptionInput = document.createElement('input');
            descriptionInput.className = 'hakbang-row-input hakbang-description-input';
            descriptionInput.type = 'text';
            descriptionInput.maxLength = 2000;
            descriptionInput.setAttribute('aria-label', 'Hakbang description');
            descriptionInput.value = item.description;
            descriptionCell.appendChild(descriptionInput);

            const actionsCell = document.createElement('td');
            const actions = document.createElement('div');
            actions.className = 'hakbang-actions';
            const saveButton = document.createElement('button');
            saveButton.type = 'button';
            saveButton.className = 'sa-btn sa-btn-primary sa-btn-sm hakbang-save-btn';
            saveButton.textContent = 'Save';
            saveButton.disabled = true;
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'sa-btn sa-btn-danger sa-btn-sm hakbang-delete-btn';
            deleteButton.textContent = 'Delete';
            actions.append(saveButton, deleteButton);
            actionsCell.appendChild(actions);
            row.append(codeCell, descriptionCell, actionsCell);
            setHakbangRowBaseline(row);
            return row;
        }

        async function sendHakbangRequest(method, url, payload) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                ...(payload ? { body: JSON.stringify(payload) } : {}),
            });
            const body = await response.json();
            if (!response.ok) {
                const message = body.errors ? Object.values(body.errors).flat()[0] : body.message;
                throw new Error(message || 'The R4-02 hakbang could not be saved.');
            }
            return body;
        }

        hakbangRows?.querySelectorAll('tr[data-hakbang-id]').forEach(setHakbangRowBaseline);
        hakbangStatusButtons.forEach(button => {
            button.addEventListener('click', () => {
                selectedHakbangStatus = button.dataset.hakbangStatus;
                hakbangStatusButtons.forEach(item => {
                    const active = item === button;
                    item.classList.toggle('active', active);
                    item.setAttribute('aria-selected', String(active));
                });
                refreshHakbangRows();
            });
        });
        refreshHakbangRows();

        hakbangRows?.addEventListener('input', event => {
            const row = event.target.closest('tr[data-hakbang-id]');
            if (!row) return;
            const code = row.querySelector('.hakbang-code-input');
            const description = row.querySelector('.hakbang-description-input');
            row.querySelector('.hakbang-save-btn').disabled =
                code.value === code.dataset.originalValue &&
                description.value === description.dataset.originalValue;
        });

        hakbangAddForm?.addEventListener('submit', async event => {
            event.preventDefault();
            const submitButton = document.getElementById('addHakbangBtn');
            const code = document.getElementById('newHakbangCode');
            const description = document.getElementById('newHakbangDescription');
            submitButton.disabled = true;
            try {
                const { data, message } = await sendHakbangRequest('POST', superadminUrls.r402Hakbangs, {
                    status: selectedHakbangStatus,
                    code: code.value.trim(),
                    description: description.value.trim(),
                });
                hakbangRows.appendChild(createHakbangRow(data));
                hakbangAddForm.reset();
                refreshHakbangRows();
                showToast(message || 'Hakbang added.');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitButton.disabled = false;
            }
        });

        hakbangRows?.addEventListener('click', async event => {
            const row = event.target.closest('tr[data-hakbang-id]');
            if (!row) return;

            if (event.target.closest('.hakbang-save-btn')) {
                const button = event.target.closest('.hakbang-save-btn');
                const code = row.querySelector('.hakbang-code-input').value.trim();
                const description = row.querySelector('.hakbang-description-input').value.trim();
                button.disabled = true;
                try {
                    const { data, message } = await sendHakbangRequest(
                        'PUT',
                        `${superadminUrls.r402Hakbangs}/${row.dataset.hakbangId}`,
                        { status: row.dataset.status, code, description }
                    );
                    row.querySelector('.hakbang-code-input').value = data.code;
                    row.querySelector('.hakbang-description-input').value = data.description;
                    setHakbangRowBaseline(row);
                    showToast(message || 'Hakbang updated.');
                } catch (error) {
                    button.disabled = false;
                    showToast(error.message, 'error');
                }
                return;
            }

            if (event.target.closest('.hakbang-delete-btn')) {
                const code = row.querySelector('.hakbang-code-input').value;
                deleteHakbangMessage.textContent = `Delete ${row.dataset.status} hakbang ${code}? This cannot be undone.`;
                deleteHakbangModal.classList.add('active');
                deleteHakbangModal.setAttribute('aria-hidden', 'false');
                pendingHakbangDelete = row;
            }
        });

        function closeHakbangDeleteModal() {
            deleteHakbangModal.classList.remove('active');
            deleteHakbangModal.setAttribute('aria-hidden', 'true');
            pendingHakbangDelete = null;
        }

        document.getElementById('cancelDeleteHakbang')?.addEventListener('click', closeHakbangDeleteModal);
        deleteHakbangModal?.addEventListener('click', event => {
            if (event.target === deleteHakbangModal) closeHakbangDeleteModal();
        });
        document.getElementById('confirmDeleteHakbang')?.addEventListener('click', async event => {
            if (!pendingHakbangDelete) return;
            const row = pendingHakbangDelete;
            const button = event.currentTarget;
            button.disabled = true;
            try {
                const { message } = await sendHakbangRequest(
                    'DELETE',
                    `${superadminUrls.r402Hakbangs}/${row.dataset.hakbangId}`
                );
                row.remove();
                closeHakbangDeleteModal();
                refreshHakbangRows();
                showToast(message || 'Hakbang deleted.');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });

        // ===== Access and role updates =====
        const usersTableBody = document.getElementById('usersTableBody');
        usersTableBody?.addEventListener('change', (e) => {
            const control = e.target;
            const row = control.closest('tr[data-user-id]');
            if (!row) return;

            const update = control.classList.contains('access-toggle')
                ? { is_verified: control.checked ? 1 : 0 }
                : control.classList.contains('tungkulin-select')
                    ? { tungkulin: control.value || null }
                    : null;
            if (!update) return;

            const previousValue = control.type === 'checkbox' ? !control.checked : control.dataset.previousValue;
            control.disabled = true;
            sendUpdate(`${superadminUrls.users}/${row.dataset.userId}`, update)
                .then(({ data }) => {
                    if (control.classList.contains('tungkulin-select')) {
                        control.dataset.previousValue = data.tungkulin || '';
                    } else {
                        const pill = row.querySelector('.status-pill');
                        control.checked = data.is_verified;
                        pill.classList.toggle('on', data.is_verified);
                        pill.classList.toggle('off', !data.is_verified);
                        pill.lastChild.textContent = data.is_verified ? 'Verified' : 'Not Verified';
                        if (data.revoked_current_session) {
                            window.location.assign('/login?access=revoked');
                            return;
                        }
                        const permissionRow = document.querySelector(`#permissionsTableBody tr[data-user-id="${row.dataset.userId}"]`);
                        if (!data.is_verified) {
                            permissionRow?.remove();
                            document.getElementById('permEmptyState').style.display =
                                document.querySelector('#permissionsTableBody tr[data-user-id]') ? 'none' : 'block';
                        }
                        if (data.is_verified && !permissionRow) window.location.reload();
                    }
                    showToast('User settings saved.');
                })
                .catch(error => {
                    if (control.type === 'checkbox') control.checked = previousValue;
                    else control.value = previousValue ?? '';
                    showToast(error.message, 'error');
                })
                .finally(() => { control.disabled = false; });
        });

        // ===== Edit Permissions of Secretaries modal =====
        const permModalOverlay = document.getElementById('permissionsModalOverlay');
        const editPermissionsBtn = document.getElementById('editPermissionsBtn');
        const closePermissionsModal = document.getElementById('closePermissionsModal');
        const cancelPermissionsBtn = document.getElementById('cancelPermissionsBtn');

        // Move the modal to be a direct child of <body>. If left nested inside
        // the page layout, an ancestor with a CSS transform/filter/overflow
        // (common in dashboard shells) can make "position: fixed" behave like
        // "absolute" relative to that ancestor instead of the viewport, which
        // is what made the overlay render mispositioned/undimmed. Reparenting
        // guarantees it always sits above and covers the real viewport.
        if (permModalOverlay && permModalOverlay.parentElement !== document.body) {
            document.body.appendChild(permModalOverlay);
        }

        // Force the critical visual properties as inline styles. Inline
        // styles win over ANY external stylesheet rule regardless of load
        // order or !important, so this guarantees the dimmed backdrop shows
        // even if the app's global CSS has its own conflicting ".overlay"-type
        // rule (e.g. a Bootstrap/theme reset making backgrounds transparent).
        if (permModalOverlay) {
            permModalOverlay.style.position = 'fixed';
            permModalOverlay.style.top = '0';
            permModalOverlay.style.left = '0';
            permModalOverlay.style.right = '0';
            permModalOverlay.style.bottom = '0';
            permModalOverlay.style.width = '100%';
            permModalOverlay.style.height = '100%';
            permModalOverlay.style.margin = '0';
            permModalOverlay.style.zIndex = '99999';
            permModalOverlay.style.background = 'rgba(16, 24, 40, 0.55)';
            permModalOverlay.style.justifyContent = 'center';
            permModalOverlay.style.overflowY = 'auto';
            permModalOverlay.style.boxSizing = 'border-box';
        }

        function openPermissionsModal() {
            permModalOverlay.style.display = 'flex';
            permModalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closePermissionsModalFn() {
            permModalOverlay.style.display = 'none';
            permModalOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        editPermissionsBtn?.addEventListener('click', openPermissionsModal);
        closePermissionsModal?.addEventListener('click', closePermissionsModalFn);
        cancelPermissionsBtn?.addEventListener('click', closePermissionsModalFn);
        document.getElementById('savePermissionsBtn')?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            const permissions = Array.from(document.querySelectorAll('#permissionsTableBody tr[data-user-id]')).map(row => {
                const values = { id: Number(row.dataset.userId) };
                row.querySelectorAll('[data-permission]').forEach(input => {
                    values[input.dataset.permission] = input.checked ? 1 : 0;
                });
                return values;
            });

            button.disabled = true;
            try {
                await sendUpdate(superadminUrls.permissions, { permissions });
                closePermissionsModalFn();
                showToast('Permissions saved.');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });
        permModalOverlay?.addEventListener('click', (e) => {
            if (e.target === permModalOverlay) closePermissionsModalFn();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && permModalOverlay.classList.contains('active')) {
                closePermissionsModalFn();
            }
        });

        // ===== Signatories: add / remove Purok, and Sakop na Grupo preview =====
        const purokList = document.getElementById('purokList');
        const addPurokBtn = document.getElementById('addPurokBtn');
        const saveSignatoriesBtn = document.getElementById('saveSignatoriesBtn');
        let purokCounter = purokList.querySelectorAll('.purok-card').length;
        const signatoryUsers = @json($users->map(fn ($user) => [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->gmail,
        ])->values());
        const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[character]));

        function collectSignatories() {
            return Array.from(purokList.querySelectorAll('.purok-card')).map(card => {
                const id = card.dataset.id;
                return {
                    ...(id ? { id: Number(id) } : {}),
                    purok_number: Number(card.querySelector('.purok-number-input').value),
                    purok_name: normalizeName(card.querySelector('.purok-name-input').value),
                    secretary: card.querySelector('.secretary-input').value || null,
                    manggagawa: card.querySelector('.manggagawa-input').value.trim() || null,
                    ministerial_id: card.querySelector('.purok-id-input').value.trim() || null,
                    grupo_start: Number(card.querySelector('.sakop-from').value),
                    grupo_end: Number(card.querySelector('.sakop-to').value),
                    full_purok: card.querySelector('.complete-purok-check').checked ? 1 : 0,
                };
            });
        }

        let savedSignatoriesSnapshot = JSON.stringify(collectSignatories());
        function getSakopOverlap(signatories = collectSignatories()) {
            for (let i = 0; i < signatories.length; i++) {
                const current = signatories[i];
                for (let j = i + 1; j < signatories.length; j++) {
                    const other = signatories[j];
                    if (Number(current.purok_number) !== Number(other.purok_number)) continue;

                    const complete = current.full_purok || other.full_purok;
                    const overlaps = current.grupo_start <= other.grupo_end
                        && other.grupo_start <= current.grupo_end;
                    if (complete) {
                        const completeName = current.full_purok
                            ? current.purok_name
                            : other.purok_name;
                        return `${completeName || `Purok ${current.purok_number}`} cannot be marked Complete Purok while another Purok shares header ${current.purok_number}. Use non-overlapping Sakop na Grupo ranges instead.`;
                    }
                    if (overlaps) {
                        const currentName = current.purok_name || `Purok ${current.purok_number}`;
                        const otherName = other.purok_name || `Purok ${other.purok_number}`;
                        return `${currentName} and ${otherName} use overlapping Sakop na Grupo ranges under header ${current.purok_number}.`;
                    }
                }
            }
            return '';
        }

        let lastSignatoryOverlap = '';
        function updateSignatoriesSaveState(notify = false) {
            const signatories = collectSignatories();
            const overlap = getSakopOverlap(signatories);
            saveSignatoriesBtn.disabled = Boolean(overlap) || JSON.stringify(signatories) === savedSignatoriesSnapshot;
            if (notify && overlap && overlap !== lastSignatoryOverlap) {
                showToast(overlap, 'error');
            }
            lastSignatoryOverlap = overlap;
        }

        purokList.addEventListener('input', () => updateSignatoriesSaveState(true));
        purokList.addEventListener('change', () => updateSignatoriesSaveState(true));
        updateSignatoriesSaveState();

        function updateSakopPreview(card) {
            const nameInput = card.querySelector('.purok-name-input');
            const fromInput = card.querySelector('.sakop-from');
            const toInput = card.querySelector('.sakop-to');
            const previewEl = card.querySelector('.sakop-preview');
            if (!nameInput || !fromInput || !toInput || !previewEl) return;

            const numberInput = card.querySelector('.purok-number-input');
            const completeCheck = card.querySelector('.complete-purok-check');
            const sakopRow = card.querySelector('.sakop-row');
            const prefix = numberInput && numberInput.value !== '' ? numberInput.value : null;
            const from = fromInput.value;
            const to = toInput.value;
            const isComplete = completeCheck && completeCheck.checked;

            sakopRow.classList.toggle('disabled', isComplete);
            fromInput.disabled = isComplete;
            toInput.disabled = isComplete;

            if (isComplete) {
                previewEl.textContent = prefix
                    ? `All groups: Purok ${prefix}-1, ${prefix}-2, ${prefix}-3 ...`
                    : 'Enter an Assigned Number to preview.';
            } else if (prefix && from !== '' && to !== '') {
                previewEl.textContent = `Range: Purok ${prefix}-${from} to ${prefix}-${to}`;
            } else {
                previewEl.textContent = 'Enter an Assigned Number and a From/To range to preview.';
            }
        }

        function buildPurokCard(name, number) {
            const card = document.createElement('div');
            card.className = 'purok-card';
            const userOptions = signatoryUsers.map(user =>
                `<option value="${user.id}">${escapeHtml(user.name)}</option>`
            ).join('');
            const safeName = escapeHtml(name);
            card.innerHTML = `
                <div class="purok-head">
                    <div class="purok-head-left">
                        <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                        <div class="purok-name-wrap">
                            <div class="purok-name-view">
                                <span class="purok-name-display">${safeName}</span>
                                <button type="button" class="purok-edit-btn" title="Edit Purok name" aria-label="Edit Purok name"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M12 5l3 3"/></svg></button>
                            </div>
                            <div class="purok-name-edit">
                                <input type="text" class="purok-name-input" value="${safeName}" maxlength="40">
                                <button type="button" class="sa-btn sa-btn-primary sa-btn-sm purok-name-save">Save</button>
                                <button type="button" class="sa-btn sa-btn-ghost sa-btn-sm purok-name-cancel">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                </div>
                <div class="purok-body">
                    <div class="signatory-row">
                        <span class="signatory-role-badge">Assigned Number</span>
                        <input type="number" class="signatory-input purok-number-input" placeholder="e.g. 1" min="1" value="${number}">
                    </div>
                    <div class="signatory-row">
                        <span class="signatory-role-badge">Secretary</span>
                        <select class="signatory-input secretary-input">
                            <option value="">Not assigned</option>${userOptions}
                        </select>
                    </div>
                    <div class="signatory-row">
                        <span class="signatory-role-badge">Manggagawa</span>
                        <input type="text" class="signatory-input manggagawa-input" placeholder="Full name" maxlength="255">
                    </div>
                    <div class="signatory-row">
                        <span class="signatory-role-badge">ID Number</span>
                        <input type="text" class="signatory-input purok-id-input" placeholder="Ministerial ID number">
                    </div>
                    <label class="complete-row">
                        <input type="checkbox" class="complete-purok-check">
                        <div class="complete-row-text">
                            <strong>Complete Purok</strong>
                            <span>Includes every group that starts with this Purok number (e.g. 1-1, 1-2, 1-3 ...).</span>
                        </div>
                    </label>
                    <div class="sakop-row">
                        <span class="signatory-role-badge">Sakop na Grupo</span>
                        <div>
                            <div class="sakop-range-inputs">
                                <input type="number" class="signatory-input sakop-from" placeholder="From" value="1" min="0">
                                <span>to</span>
                                <input type="number" class="signatory-input sakop-to" placeholder="To" value="24" min="0">
                            </div>
                            <div class="sakop-preview">Enter an Assigned Number and a From/To range to preview.</div>
                        </div>
                    </div>
                </div>
            `;
            return card;
        }

        addPurokBtn?.addEventListener('click', () => {
            purokCounter++;
            while (nameExists(`Purok ${purokCounter}`)) purokCounter++;
            const card = buildPurokCard(`Purok ${purokCounter}`, purokCounter);
            purokList.appendChild(card);
            document.getElementById('noPuroksMessage')?.remove();
            updateSakopPreview(card);
            updateSignatoriesSaveState();
        });

        saveSignatoriesBtn?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            if (button.disabled) return;
            const puroks = collectSignatories();
            const overlap = getSakopOverlap(puroks);
            if (overlap) {
                showToast(overlap, 'error');
                return;
            }

            button.disabled = true;
            try {
                await sendUpdate(superadminUrls.signatories, { puroks });
                savedSignatoriesSnapshot = JSON.stringify(puroks);
                showToast('Purok signatories saved.');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                updateSignatoriesSaveState();
            }
        });
        document.getElementById('discardSignatoriesBtn')?.addEventListener('click', () => window.location.reload());

        purokList?.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-purok-btn')) {
                e.target.closest('.purok-card').remove();
                updateSignatoriesSaveState();
            }
        });

        purokList?.addEventListener('change', (e) => {
            if (e.target.classList.contains('complete-purok-check')) {
                updateSakopPreview(e.target.closest('.purok-card'));
            }
        });

        purokList?.addEventListener('input', (e) => {
            if (
                e.target.classList.contains('purok-number-input') ||
                e.target.classList.contains('sakop-from') ||
                e.target.classList.contains('sakop-to')
            ) {
                updateSakopPreview(e.target.closest('.purok-card'));
            }
        });
        purokList.querySelectorAll('.purok-card').forEach(updateSakopPreview);

        // ===== Purok name: display + edit icon, with unique-name validation =====
        function normalizeName(v) {
            return v.trim().replace(/\s+/g, ' ');
        }

        function nameExists(name, exceptCard) {
            const key = normalizeName(name).toLowerCase();
            return Array.from(purokList.querySelectorAll('.purok-card')).some(card => {
                if (card === exceptCard) return false;
                const display = card.querySelector('.purok-name-display');
                return display && normalizeName(display.textContent).toLowerCase() === key;
            });
        }

        function validatePurokName(card) {
            const input = card.querySelector('.purok-name-input');
            const value = normalizeName(input.value);
            let message = '';

            if (value === '') {
                message = 'Purok name can\'t be empty.';
            } else if (nameExists(value, card)) {
                message = `"${value}" is already used by another Purok. Choose a different name.`;
            }

            input.classList.toggle('invalid', message !== '');
            return message;
        }

        function openNameEditor(card) {
            const display = card.querySelector('.purok-name-display');
            const input = card.querySelector('.purok-name-input');
            input.value = display.textContent;
            card.querySelector('.purok-name-view').style.display = 'none';
            card.querySelector('.purok-name-edit').classList.add('active');
            validatePurokName(card);
            input.focus();
            input.select();
        }

        function closeNameEditor(card) {
            const input = card.querySelector('.purok-name-input');
            input.classList.remove('invalid');
            card.querySelector('.purok-name-edit').classList.remove('active');
            card.querySelector('.purok-name-view').style.display = '';
        }

        function saveName(card) {
            const validationMessage = validatePurokName(card);
            if (validationMessage) {
                showToast(validationMessage, 'error');
                return;
            }
            const input = card.querySelector('.purok-name-input');
            card.querySelector('.purok-name-display').textContent = normalizeName(input.value);
            closeNameEditor(card);
        }

        purokList?.addEventListener('click', (e) => {
            const card = e.target.closest('.purok-card');
            if (!card) return;
            if (e.target.closest('.purok-edit-btn')) openNameEditor(card);
            else if (e.target.closest('.purok-name-save')) saveName(card);
            else if (e.target.closest('.purok-name-cancel')) closeNameEditor(card);
        });

        purokList?.addEventListener('input', (e) => {
            if (e.target.classList.contains('purok-name-input')) {
                validatePurokName(e.target.closest('.purok-card'));
            }
        });

        purokList?.addEventListener('keydown', (e) => {
            if (!e.target.classList.contains('purok-name-input')) return;
            const card = e.target.closest('.purok-card');
            if (e.key === 'Enter') { e.preventDefault(); saveName(card); }
            if (e.key === 'Escape') { e.stopPropagation(); closeNameEditor(card); }
        });

        // Compute previews for the sample Puroks already on the page
        purokList?.querySelectorAll('.purok-card').forEach(updateSakopPreview);

        // ===== Global Variables: de-listment requirement gray-out logic =====
        document.querySelectorAll('[data-controls]').forEach(checkbox => {
            const targetField = document.getElementById(checkbox.dataset.controls);
            const input = targetField.querySelector('input, select');

            checkbox.addEventListener('change', () => {
                const enabled = checkbox.checked;
                targetField.classList.toggle('disabled', !enabled);
                input.disabled = !enabled;
            });
        });

        const globalVariableFields = {
            locale: 'gvLocale',
            lcode: 'gvLcode',
            district: 'gvDistrict',
            dcode: 'gvDcode',
            s_number: 'gvSNumber',
            pastor_destinado: 'gvPastor',
            ksp_pd: 'gvKsp',
            head_lsp: 'gvHeadLsp',
            kalihim_ms: 'gvKalihimMs',
            kalihim_uwp: 'gvKalihimUwp',
            baptism_requirement: 'gvBaptismDate',
            entry_years_requirement: 'gvEntryYears',
        };
        document.getElementById('saveGlobalVariablesBtn')?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            const values = Object.fromEntries(Object.entries(globalVariableFields).map(([column, id]) => {
                const value = document.getElementById(id).value;
                return [column, value === '' ? null : value];
            }));
            values.dateofbaptism_requirement_boolean = document.getElementById('delistBaptismCheck').checked ? 1 : 0;
            button.disabled = true;
            try {
                await sendUpdate(superadminUrls.globalVariables, values);
                showToast('Global variables saved.');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });
        document.getElementById('discardGlobalVariablesBtn')?.addEventListener('click', () => window.location.reload());

        const securityInputs = ['requireOtpSetting', 'allowForgotPasswordSetting'];
        const securityDefaults = Object.fromEntries(securityInputs.map(id => [id, document.getElementById(id).checked]));
        document.getElementById('saveSecuritySettingsBtn')?.addEventListener('click', async event => {
            const button = event.currentTarget;
            button.disabled = true;
            try {
                await sendUpdate(superadminUrls.securitySettings, {
                    require_otp: document.getElementById('requireOtpSetting').checked ? 1 : 0,
                    allow_forgot_password: document.getElementById('allowForgotPasswordSetting').checked ? 1 : 0,
                });
                showToast('Security settings saved.');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });
        document.getElementById('discardSecuritySettingsBtn')?.addEventListener('click', () => {
            securityInputs.forEach(id => { document.getElementById(id).checked = securityDefaults[id]; });
        });
    })();
</script>
@endsection