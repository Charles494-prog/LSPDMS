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
    .purok-name-input {
        font-family: inherit;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--sa-navy);
        background: transparent;
        border: 1px solid transparent;
        border-radius: 6px;
        padding: 0.3rem 0.5rem;
        max-width: 220px;
    }
    .purok-name-input:hover, .purok-name-input:focus {
        outline: none;
        border-color: var(--sa-border);
        background: var(--sa-surface);
    }
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
        padding-top: 0.6rem;
        margin-top: 0.2rem;
        border-top: 1px dashed var(--sa-border);
    }
    @media (max-width: 640px) {
        .sakop-row { grid-template-columns: 1fr; }
    }
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
                        <!-- Sample rows — replace with real data from login_user later -->
                        <tr data-name="charles andrei galvan" data-email="formalcharlesandreigalvan@gmail.com">
                            <td>
                                <div class="sa-user-cell">
                                    <img src="https://ui-avatars.com/api/?name=Charles+Galvan&background=0A6BD6&color=fff" alt="">
                                    <div>
                                        <div class="sa-user-name">Charles Andrei Galvan</div>
                                        <div class="sa-user-email">formalcharlesandreigalvan@gmail.com</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select class="sa-select tungkulin-select">
                                    <option value="secretary" selected>Secretary</option>
                                    <option value="secretary_head">Secretary Head</option>
                                </select>
                            </td>
                            <td>
                                <label class="sa-toggle">
                                    <input type="checkbox" class="access-toggle" checked>
                                    <span class="sa-toggle-track"></span>
                                </label>
                            </td>
                            <td>
                                <span class="sa-status-pill on status-pill">
                                    <svg viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                                    Verified
                                </span>
                            </td>
                        </tr>
                        <tr data-name="maria santos" data-email="maria.santos@example.com">
                            <td>
                                <div class="sa-user-cell">
                                    <img src="https://ui-avatars.com/api/?name=Maria+Santos&background=64748b&color=fff" alt="">
                                    <div>
                                        <div class="sa-user-name">Maria Santos</div>
                                        <div class="sa-user-email">maria.santos@example.com</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select class="sa-select tungkulin-select">
                                    <option value="secretary" selected>Secretary</option>
                                    <option value="secretary_head">Secretary Head</option>
                                </select>
                            </td>
                            <td>
                                <label class="sa-toggle">
                                    <input type="checkbox" class="access-toggle">
                                    <span class="sa-toggle-track"></span>
                                </label>
                            </td>
                            <td>
                                <span class="sa-status-pill off status-pill">
                                    <svg viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                                    Not Verified
                                </span>
                            </td>
                        </tr>
                        <tr data-name="juan dela cruz" data-email="juan.delacruz@example.com">
                            <td>
                                <div class="sa-user-cell">
                                    <img src="https://ui-avatars.com/api/?name=Juan+Dela+Cruz&background=64748b&color=fff" alt="">
                                    <div>
                                        <div class="sa-user-name">Juan Dela Cruz</div>
                                        <div class="sa-user-email">juan.delacruz@example.com</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select class="sa-select tungkulin-select">
                                    <option value="secretary">Secretary</option>
                                    <option value="secretary_head" selected>Secretary Head</option>
                                </select>
                            </td>
                            <td>
                                <label class="sa-toggle">
                                    <input type="checkbox" class="access-toggle" checked>
                                    <span class="sa-toggle-track"></span>
                                </label>
                            </td>
                            <td>
                                <span class="sa-status-pill on status-pill">
                                    <svg viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                                    Verified
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="sa-empty" id="usersEmptyState" style="display:none;">No users match your search.</div>
            </div>
        </div>
    </div>

    <!-- ============ SIGNATORIES ============ -->
    <div class="sa-tab-panel" id="tab-signatories">
        <div class="sa-card">
            <div class="sa-card-head">
                <div>
                    <h2>Purok Signatories</h2>
                    <p>Add a Purok, assign its Secretary and Manggagawa, and define its Sakop na Grupo range.</p>
                </div>
            </div>

            <div class="sa-note">
                Sakop na Grupo is a numeric range. For "Purok 2a" with range 1–6, members are 2-1 through 2-6. For "Purok 2b" with range 7–10, members are 2-7 through 2-10. The prefix number is taken automatically from the Purok name.
            </div>

            <div class="purok-list" id="purokList">
                <!-- Sample Purok 1: full range 1–24 -->
                <div class="purok-card">
                    <div class="purok-head">
                        <div class="purok-head-left">
                            <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                            <input type="text" class="purok-name-input" value="Purok 1">
                        </div>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                    </div>
                    <div class="purok-body">
                        <div class="signatory-row">
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
                        <div class="sakop-row">
                            <span class="signatory-role-badge">Sakop na Grupo</span>
                            <div>
                                <div class="sakop-range-inputs">
                                    <input type="number" class="signatory-input sakop-from" placeholder="From" value="1" min="0">
                                    <span>to</span>
                                    <input type="number" class="signatory-input sakop-to" placeholder="To" value="24" min="0">
                                </div>
                                <div class="sakop-preview">Range: Purok 1-1 to 1-24</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sample Purok 2a: sub-group range 1–6 -->
                <div class="purok-card">
                    <div class="purok-head">
                        <div class="purok-head-left">
                            <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                            <input type="text" class="purok-name-input" value="Purok 2a">
                        </div>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                    </div>
                    <div class="purok-body">
                        <div class="signatory-row">
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
                            <input type="text" class="purok-name-input" value="Purok 2b">
                        </div>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                    </div>
                    <div class="purok-body">
                        <div class="signatory-row">
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
            </div>

            <button type="button" class="add-purok-btn" id="addPurokBtn" style="margin-top:1rem;">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 4v12M4 10h12"/></svg>
                Add Purok
            </button>

            <div class="sa-save-bar">
                <button type="button" class="sa-btn sa-btn-ghost">Discard</button>
                <button type="button" class="sa-btn sa-btn-primary">Save Signatories</button>
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
                        <span>Needs OTP in Logging In.</span>
                    </div>
                    <label class="sa-toggle">
                        <input type="checkbox" id="settingSample1">
                        <span class="sa-toggle-track"></span>
                    </label>
                </div>
                <div class="settings-row">
                    <div class="settings-row-text">
                        <label for="settingSample2">Allow Forgot Password</label>
                        <span>By turning this setting off, secretary needs to consult the head secretary before resetting a password.</span>
                    </div>
                    <label class="sa-toggle">
                        <input type="checkbox" id="settingSample2" checked>
                        <span class="sa-toggle-track"></span>
                    </label>
                </div>
            </div>

            <div class="sa-save-bar">
                <button type="button" class="sa-btn sa-btn-ghost">Discard</button>
                <button type="button" class="sa-btn sa-btn-primary">Save Security Settings</button>
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
                    <label for="gvLocaleNumber">Locale S Number</label>
                    <input type="text" id="gvLocaleNumber" placeholder="e.g. S-123">
                </div>
                <div class="gv-field">
                    <label for="gvPastor">Pastor / Destinado</label>
                    <input type="text" id="gvPastor" placeholder="Full name">
                </div>
                <div class="gv-field">
                    <label for="gvKsp">KSP ng Lokal</label>
                    <input type="text" id="gvKsp" placeholder="Full name">
                </div>
                <div class="gv-field">
                    <label for="gvPdTagasubaybay">PD Tagasubaybay</label>
                    <input type="text" id="gvPdTagasubaybay" placeholder="Full name">
                </div>
                <div class="gv-field">
                    <label for="gvKalihimMs">Kalihim ng MS</label>
                    <input type="text" id="gvKalihimMs" placeholder="Full name">
                </div>
                <div class="gv-field">
                    <label for="gvKalihimUwp">Kalihim ng UWP</label>
                    <input type="text" id="gvKalihimUwp" placeholder="Full name">
                </div>

                <div class="gv-field disabled" id="gvBaptismField">
                    <label for="gvBaptismDate">Date of Baptism Requirement</label>
                    <input type="date" id="gvBaptismDate" disabled>
                    <div class="gv-hint">Enable "Date of Baptism Requirement" below to edit this field.</div>
                </div>

                <div class="gv-field disabled" id="gvEntryYearsField">
                    <label for="gvEntryYears">Years Upon Date of Entry Requirement</label>
                    <select id="gvEntryYears" disabled>
                        <option value="1">1 year</option>
                        <option value="2">2 years</option>
                        <option value="3">3 years</option>
                    </select>
                    <div class="gv-hint">Enable "Date of Entry Requirement" below to edit this field.</div>
                </div>
            </div>

            <!-- Requirements for De-Listment (controls the two fields above) -->
            <div class="gv-subsection">
                <p class="gv-subsection-title">Requirements for De-Listment</p>
                <p class="gv-subsection-sub">Check a requirement to enable its matching field above.</p>

                <div class="delist-checks">
                    <div class="delist-check-row">
                        <input type="checkbox" id="delistBaptismCheck" data-controls="gvBaptismField">
                        <div class="delist-check-text">
                            <label for="delistBaptismCheck">Date of Baptism Requirement</label>
                            <span>Require a recorded date of baptism before de-listment.</span>
                        </div>
                    </div>
                    <div class="delist-check-row">
                        <input type="checkbox" id="delistEntryCheck" data-controls="gvEntryYearsField">
                        <div class="delist-check-text">
                            <label for="delistEntryCheck">Date of Entry Requirement</label>
                            <span>Require a minimum number of years since date of entry.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sa-save-bar">
                <button type="button" class="sa-btn sa-btn-ghost">Discard</button>
                <button type="button" class="sa-btn sa-btn-primary">Save Global Variables</button>
            </div>
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
                    </tr>
                </thead>
                <tbody id="permissionsTableBody">
                    <!-- Sample rows — replace with active (is_verified = 1) secretaries from login_user later -->
                    <tr data-name="charles andrei galvan" data-email="formalcharlesandreigalvan@gmail.com">
                        <td>
                            <div class="sa-user-cell">
                                <img src="https://ui-avatars.com/api/?name=Charles+Galvan&background=0A6BD6&color=fff" alt="">
                                <div>
                                    <div class="sa-user-name">Charles Andrei Galvan</div>
                                    <div class="sa-user-email">formalcharlesandreigalvan@gmail.com</div>
                                </div>
                            </div>
                        </td>
                        <td class="center"><input type="checkbox" class="sa-checkbox" checked></td>
                        <td class="center"><input type="checkbox" class="sa-checkbox" checked></td>
                        <td class="center"><input type="checkbox" class="sa-checkbox"></td>
                        <td class="center"><input type="checkbox" class="sa-checkbox" checked></td>
                    </tr>
                    <tr data-name="juan dela cruz" data-email="juan.delacruz@example.com">
                        <td>
                            <div class="sa-user-cell">
                                <img src="https://ui-avatars.com/api/?name=Juan+Dela+Cruz&background=64748b&color=fff" alt="">
                                <div>
                                    <div class="sa-user-name">Juan Dela Cruz</div>
                                    <div class="sa-user-email">juan.delacruz@example.com</div>
                                </div>
                            </div>
                        </td>
                        <td class="center"><input type="checkbox" class="sa-checkbox" checked></td>
                        <td class="center"><input type="checkbox" class="sa-checkbox"></td>
                        <td class="center"><input type="checkbox" class="sa-checkbox" checked></td>
                        <td class="center"><input type="checkbox" class="sa-checkbox" checked></td>
                    </tr>
                </tbody>
            </table>
            <div class="sa-empty" id="permEmptyState" style="display:none;">No secretaries match your search.</div>
        </div>

        <div class="sa-modal-save-bar">
            <button type="button" class="sa-btn sa-btn-ghost" id="cancelPermissionsBtn">Cancel</button>
            <button type="button" class="sa-btn sa-btn-primary">Save Permissions</button>
        </div>
    </div>
</div>

<script>
    (function () {
        // ===== Tabs =====
        const tabBtns = document.querySelectorAll('.sa-tab-btn');
        const tabPanels = document.querySelectorAll('.sa-tab-panel');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
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
                    const matches = row.dataset.name.includes(term) || row.dataset.email.includes(term);
                    row.style.display = matches ? '' : 'none';
                    if (matches) visibleCount++;
                });

                if (emptyState) emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            });
        }

        wireSearch('userSearch', 'usersTableBody', 'usersEmptyState');
        wireSearch('permSearch', 'permissionsTableBody', 'permEmptyState');

        // ===== Access toggle <-> status pill sync (Users tab) =====
        const usersTableBody = document.getElementById('usersTableBody');
        usersTableBody?.addEventListener('change', (e) => {
            if (!e.target.classList.contains('access-toggle')) return;

            const row = e.target.closest('tr');
            const pill = row.querySelector('.status-pill');
            const isOn = e.target.checked;

            pill.classList.toggle('on', isOn);
            pill.classList.toggle('off', !isOn);
            pill.innerHTML = `
                <svg viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                ${isOn ? 'Verified' : 'Not Verified'}
            `;
            // NOTE: frontend-only for now.
            // Later: send an AJAX/PUT request here to update is_verified in login_user.
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
        let purokCounter = purokList.querySelectorAll('.purok-card').length;

        function updateSakopPreview(card) {
            const nameInput = card.querySelector('.purok-name-input');
            const fromInput = card.querySelector('.sakop-from');
            const toInput = card.querySelector('.sakop-to');
            const previewEl = card.querySelector('.sakop-preview');
            if (!nameInput || !fromInput || !toInput || !previewEl) return;

            const match = nameInput.value.match(/(\d+)/);
            const prefix = match ? match[1] : null;
            const from = fromInput.value;
            const to = toInput.value;

            if (prefix && from !== '' && to !== '') {
                previewEl.textContent = `Range: Purok ${prefix}-${from} to ${prefix}-${to}`;
            } else {
                previewEl.textContent = 'Enter a Purok number and a From/To range to preview.';
            }
        }

        function buildPurokCard(name) {
            const card = document.createElement('div');
            card.className = 'purok-card';
            card.innerHTML = `
                <div class="purok-head">
                    <div class="purok-head-left">
                        <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="color:var(--sa-ink-soft); flex-shrink:0;"><path d="M3 8l7-5 7 5v9H3z"/><path d="M8 17v-5h4v5"/></svg>
                        <input type="text" class="purok-name-input" value="${name}">
                    </div>
                    <button type="button" class="sa-btn sa-btn-danger sa-btn-sm remove-purok-btn">Remove</button>
                </div>
                <div class="purok-body">
                    <div class="signatory-row">
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
                    <div class="sakop-row">
                        <span class="signatory-role-badge">Sakop na Grupo</span>
                        <div>
                            <div class="sakop-range-inputs">
                                <input type="number" class="signatory-input sakop-from" placeholder="From" min="0">
                                <span>to</span>
                                <input type="number" class="signatory-input sakop-to" placeholder="To" min="0">
                            </div>
                            <div class="sakop-preview">Enter a Purok number and a From/To range to preview.</div>
                        </div>
                    </div>
                </div>
            `;
            return card;
        }

        addPurokBtn?.addEventListener('click', () => {
            purokCounter++;
            const card = buildPurokCard(`Purok ${purokCounter}`);
            purokList.appendChild(card);
        });

        purokList?.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-purok-btn')) {
                e.target.closest('.purok-card').remove();
            }
        });

        purokList?.addEventListener('input', (e) => {
            if (
                e.target.classList.contains('purok-name-input') ||
                e.target.classList.contains('sakop-from') ||
                e.target.classList.contains('sakop-to')
            ) {
                updateSakopPreview(e.target.closest('.purok-card'));
            }
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
    })();
</script>
@endsection