@extends('layouts.app')

@section('title', 'Filter & BNH Helper')

@push('styles')
<style>
    .fbnh-page { display: grid; gap: 1.25rem; }
    .fbnh-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem; min-width: 0; }
    .fbnh-card h2 { margin: 0; font-size: 1.12rem; }
    .fbnh-subtitle { margin: .35rem 0 1rem; color: var(--text-muted); font-size: .88rem; }
    .fbnh-filters { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 1rem; max-width: 720px; }
    .fbnh-field label { display: block; font-size: .82rem; font-weight: 700; margin-bottom: .38rem; }
    .fbnh-field select, .fbnh-field input { width: 100%; padding: .65rem .75rem; border: 1px solid var(--border); border-radius: 8px; color: var(--text); background: var(--surface); font: inherit; }
    .fbnh-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: .8rem; }
    .fbnh-actions { display: flex; flex-wrap: wrap; gap: .55rem; }
    .fbnh-button { border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text); padding: .55rem .8rem; font: inherit; font-size: .82rem; font-weight: 700; text-decoration: none; cursor: pointer; }
    .fbnh-button-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
    .fbnh-table-wrap { width: 100%; overflow: auto; border: 1px solid var(--border); border-radius: 10px; }
    .fbnh-table { width: 100%; border-collapse: collapse; min-width: 540px; }
    .fbnh-table th, .fbnh-table td { padding: .68rem .8rem; border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; }
    .fbnh-table th { background: color-mix(in srgb, var(--bg) 80%, var(--surface)); font-size: .76rem; text-transform: uppercase; letter-spacing: .035em; }
    .fbnh-table tbody tr:last-child td { border-bottom: 0; }
    .fbnh-table .number { text-align: center; }
    .fbnh-count-link { min-width: 3rem; padding: .35rem .55rem; border: 0; border-radius: 7px; background: transparent; color: var(--accent); font: inherit; font-weight: 700; cursor: pointer; }
    .fbnh-count-link:hover { background: color-mix(in srgb, var(--accent) 10%, transparent); }
    .fbnh-total-row { font-weight: 800; background: color-mix(in srgb, var(--bg) 80%, var(--surface)); }
    .fbnh-purok-total-row { font-weight: 700; background: color-mix(in srgb, var(--bg) 48%, var(--surface)); }
    .fbnh-status { min-height: 1.25rem; margin-top: .65rem; color: var(--text-muted); font-size: .83rem; }
    .fbnh-status[data-error="true"] { color: #dc2626; }
    .fbnh-check { width: 18px; height: 18px; accent-color: var(--accent); cursor: pointer; }
    .fbnh-verdict { font-size: .75rem; font-weight: 800; border-radius: 999px; padding: .32rem .55rem; display: inline-block; }
    .fbnh-verdict.qualified { color: #067647; background: #dcfae6; }
    .fbnh-verdict.not-qualified { color: #b42318; background: #fee4e2; }
    .fbnh-empty { padding: 1.2rem; text-align: center; color: var(--text-muted); }
    .fbnh-pagination { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-top: .8rem; color: var(--text-muted); font-size: .85rem; }
    .fbnh-pagination-controls { display: flex; gap: .5rem; }
    .fbnh-tabs { display: flex; flex-wrap: wrap; gap: .55rem; border-bottom: 1px solid var(--border); padding-bottom: .75rem; }
    .fbnh-tab { border: 1px solid var(--border); border-radius: 9px; background: var(--surface); color: var(--text-muted); padding: .7rem 1rem; font: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; }
    .fbnh-tab[aria-selected="true"] { background: var(--accent); border-color: var(--accent); color: #fff; }
    .fbnh-tab-panel[hidden] { display: none; }
    .fbnh-modal-overlay { position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center; padding: 1rem; background: rgba(15, 23, 42, .58); }
    .fbnh-modal-overlay[hidden] { display: none; }
    .fbnh-modal { width: min(680px, 100%); max-height: min(78vh, 760px); overflow: auto; padding: 1.25rem; border: 1px solid var(--border); border-radius: 14px; background: var(--surface); color: var(--text); box-shadow: 0 24px 70px rgba(0,0,0,.24); }
    .fbnh-modal-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
    .fbnh-modal-header h2 { margin: 0; font-size: 1.1rem; }
    .fbnh-modal-header p { margin: .3rem 0 0; color: var(--text-muted); font-size: .85rem; }
    @media (max-width: 600px) { .fbnh-filters { grid-template-columns: 1fr; } .fbnh-card { padding: 1rem; } }
</style>
@endpush

@section('content')
<div class="fbnh-page" id="fbnhApp"
    data-matrix-url="{{ route('filter-bnh-helper.matrix') }}"
    data-members-url="{{ route('filter-bnh-helper.members') }}"
    data-bnh-url="{{ route('filter-bnh-helper.bnh-records') }}"
    data-counts-url="{{ route('filter-bnh-helper.qualified-counts') }}"
    data-checks-url="{{ url('/filter-bnh-helper/records') }}"
    data-word-url="{{ route('filter-bnh-helper.bnh-word') }}"
    data-counts-word-url="{{ route('filter-bnh-helper.counts-word') }}"
    data-csrf="{{ csrf_token() }}">

    <section class="fbnh-card">
        <div class="fbnh-head">
            <div>
                <h2>Filter &amp; BNH Helper</h2>
                <p class="fbnh-subtitle">Filter active records by Purok and Kalagayan. Inactive records are excluded.</p>
            </div>
        </div>
        <div class="fbnh-filters">
            <div class="fbnh-field">
                <label for="fbnhPurok">Purok Filter</label>
                <select id="fbnhPurok">
                    <option value="all">All Puroks</option>
                    @foreach ($puroks as $purok)
                        <option value="{{ $purok->id }}">{{ $purok->purok_name ?: 'Purok '.$purok->purok_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fbnh-field">
                <label for="fbnhStatus">Kalagayan Filter</label>
                <select id="fbnhStatus">
                    <option value="ALL">All</option>
                    <option value="MS">MS</option>
                    <option value="UWP">UWP</option>
                    <option value="MK">MK</option>
                </select>
            </div>
        </div>
        <div class="fbnh-status" id="fbnhPageStatus" role="status"></div>
    </section>

    <div class="fbnh-tabs" role="tablist" aria-label="Filter and BNH tools">
        <button type="button" class="fbnh-tab" id="fbnhTabMatrix" role="tab" aria-controls="fbnhPanelMatrix" aria-selected="true">Purok Group Filter</button>
        <button type="button" class="fbnh-tab" id="fbnhTabList" role="tab" aria-controls="fbnhPanelList" aria-selected="false" tabindex="-1">BNH Form Helper</button>
        <button type="button" class="fbnh-tab" id="fbnhTabResumen" role="tab" aria-controls="fbnhPanelResumen" aria-selected="false" tabindex="-1">BNH Helper Form Resumen</button>
    </div>

    <section class="fbnh-card fbnh-tab-panel" id="fbnhPanelMatrix" role="tabpanel" aria-labelledby="fbnhTabMatrix">
        <div class="fbnh-head">
            <div>
                <h2>Purok Group Filter</h2>
                <p class="fbnh-subtitle">Select a Male, Female, or Total count to view the matching members.</p>
            </div>
        </div>
        <div class="fbnh-table-wrap">
            <table class="fbnh-table">
                <thead><tr><th>Purok-Grupo</th><th class="number">Male</th><th class="number">Female</th><th class="number">Total</th></tr></thead>
                <tbody id="fbnhMatrixBody"><tr><td colspan="4" class="fbnh-empty">Loading group counts…</td></tr></tbody>
            </table>
        </div>
        <div class="fbnh-status" id="fbnhMatrixStatus" role="status"></div>
    </section>

    <section class="fbnh-card fbnh-tab-panel" id="fbnhPanelList" role="tabpanel" aria-labelledby="fbnhTabList" hidden>
        <div class="fbnh-head">
            <div>
                <h2>BNH Form Helper</h2>
                <p class="fbnh-subtitle">Mark Salaysay / Pakikipag-usap and Paghahanda. A member is qualified only when both are checked.</p>
            </div>
            <div class="fbnh-actions">
                <a class="fbnh-button fbnh-button-primary" id="fbnhDownloadList" href="#">Download Word Form</a>
            </div>
        </div>
        <div class="fbnh-filters" style="margin-bottom:1rem;">
            <div class="fbnh-field">
                <label for="fbnhSearch">Search BNH records</label>
                <input type="search" id="fbnhSearch" maxlength="150" placeholder="Search by member name…">
            </div>
        </div>
        <div class="fbnh-table-wrap">
            <table class="fbnh-table">
                <thead><tr><th>Name</th><th>Kalagayan</th><th>Purok-Grupo</th><th>Salaysay / Pakikipag-usap</th><th>Paghahanda</th><th>Verdict</th></tr></thead>
                <tbody id="fbnhRecordsBody"><tr><td colspan="6" class="fbnh-empty">Loading BNH helper records…</td></tr></tbody>
            </table>
        </div>
        <div class="fbnh-pagination">
            <span id="fbnhPageInfo">Page 1</span>
            <div class="fbnh-pagination-controls">
                <button type="button" class="fbnh-button" id="fbnhPrevious" disabled>Previous</button>
                <button type="button" class="fbnh-button" id="fbnhNext" disabled>Next</button>
            </div>
        </div>
        <div class="fbnh-status" id="fbnhRecordsStatus" role="status"></div>
    </section>

    <section class="fbnh-card fbnh-tab-panel" id="fbnhPanelResumen" role="tabpanel" aria-labelledby="fbnhTabResumen" hidden>
        <div class="fbnh-head">
            <div>
                <h2>BNH Helper Form Resumen</h2>
                <p class="fbnh-subtitle">Autocount of qualified members, grouped by gender. Qualification requires both checks.</p>
            </div>
            <div class="fbnh-actions">
                <a class="fbnh-button fbnh-button-primary" id="fbnhDownloadCounts" href="#">Download Resumen</a>
            </div>
        </div>
        <div class="fbnh-table-wrap">
            <table class="fbnh-table">
                <thead><tr><th>Purok-Grupo</th><th class="number">L</th><th class="number">B</th><th class="number">Total</th></tr></thead>
                <tbody id="fbnhCountsBody"><tr><td colspan="4" class="fbnh-empty">Loading qualified counts…</td></tr></tbody>
            </table>
        </div>
        <div class="fbnh-status" id="fbnhCountsStatus" role="status"></div>
    </section>
</div>

<div class="fbnh-modal-overlay" id="fbnhMembersModal" role="presentation" hidden>
    <section class="fbnh-modal" role="dialog" aria-modal="true" aria-labelledby="fbnhMemberTitle">
        <div class="fbnh-modal-header">
            <div>
                <h2 id="fbnhMemberTitle">Members</h2>
                <p id="fbnhMemberCount"></p>
            </div>
            <button type="button" class="fbnh-button" id="fbnhMemberClose">Close</button>
        </div>
        <div class="fbnh-table-wrap">
            <table class="fbnh-table">
                <thead><tr><th>Name</th><th>Kalagayan</th><th>Purok-Grupo</th></tr></thead>
                <tbody id="fbnhMemberBody"></tbody>
            </table>
        </div>
    </section>
</div>

<script>
(() => {
    const app = document.getElementById('fbnhApp');
    if (!app) return;

    const purokSelect = document.getElementById('fbnhPurok');
    const statusSelect = document.getElementById('fbnhStatus');
    const searchInput = document.getElementById('fbnhSearch');
    const csrf = app.dataset.csrf;
    let currentPage = 1;
    let lastPage = 1;
    let searchTimer;

    const query = (extra = {}) => new URLSearchParams({
        purok_id: purokSelect.value,
        status: statusSelect.value,
        ...extra,
    }).toString();

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', ...(options.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf } : {}) },
            ...options,
        });
        let payload = {};
        try { payload = await response.json(); } catch (error) { throw new Error('The server returned an unreadable response.'); }
        if (!response.ok) {
            const message = payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to load the requested data.';
            throw new Error(message);
        }
        return payload;
    }

    function setStatus(id, message = '', isError = false) {
        const node = document.getElementById(id);
        node.textContent = message;
        node.dataset.error = isError ? 'true' : 'false';
    }

    function cell(row, text, className = '') {
        const node = document.createElement('td');
        node.textContent = text;
        if (className) node.className = className;
        row.append(node);
        return node;
    }

    function appendCountButton(row, item, gender, count, groupLabel) {
        const td = document.createElement('td');
        td.className = 'number';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'fbnh-count-link';
        button.textContent = String(count);
        button.setAttribute('aria-label', `Show ${gender === 'all' ? 'all' : gender} records in ${groupLabel}`);
        button.addEventListener('click', () => loadMembers(item, gender));
        td.append(button);
        row.append(td);
    }

    async function loadMatrix() {
        const body = document.getElementById('fbnhMatrixBody');
        body.replaceChildren();
        try {
            const data = await requestJson(`${app.dataset.matrixUrl}?${query()}`);
            for (const item of data.rows) {
                const row = document.createElement('tr');
                row.className = item.type === 'grand_total' ? 'fbnh-total-row' : (item.type === 'purok_total' ? 'fbnh-purok-total-row' : '');
                cell(row, item.label);
                appendCountButton(row, item, 'male', item.male, item.label);
                appendCountButton(row, item, 'female', item.female, item.label);
                appendCountButton(row, item, 'all', item.total, item.label);
                body.append(row);
            }
            if (!data.rows.length) {
                const empty = document.createElement('tr');
                const emptyCell = cell(empty, 'No configured Purok groups found.', 'fbnh-empty');
                emptyCell.colSpan = 4;
                body.prepend(empty);
            }
            setStatus('fbnhMatrixStatus');
        } catch (error) {
            setStatus('fbnhMatrixStatus', error.message, true);
            const row = document.createElement('tr');
            const message = cell(row, error.message, 'fbnh-empty');
            message.colSpan = 4;
            body.append(row);
        }
    }

    async function loadMembers(group, gender) {
        const modal = document.getElementById('fbnhMembersModal');
        const body = document.getElementById('fbnhMemberBody');
        const genderLabel = gender === 'all' ? 'All' : (gender === 'male' ? 'Male' : 'Female');
        document.getElementById('fbnhMemberTitle').textContent = `${genderLabel} records in ${group.label}`;
        body.replaceChildren();
        document.getElementById('fbnhMemberCount').textContent = 'Loading matching records…';
        modal.hidden = false;
        document.getElementById('fbnhMemberClose').focus();
        try {
            const params = query({
                purok_id: group.purok_id === null ? 'all' : String(group.purok_id),
                grupo: group.grupo ?? '',
                gender,
            });
            const data = await requestJson(`${app.dataset.membersUrl}?${params}`);
            document.getElementById('fbnhMemberCount').textContent = `${data.count} active record${data.count === 1 ? '' : 's'}`;
            if (!data.records.length) {
                const row = document.createElement('tr');
                const empty = cell(row, 'No matching records.', 'fbnh-empty');
                empty.colSpan = 3;
                body.append(row);
            } else {
                for (const member of data.records) {
                    const row = document.createElement('tr');
                    cell(row, member.name);
                    cell(row, member.status || '—');
                    cell(row, member.location || '—');
                    body.append(row);
                }
            }
        } catch (error) {
            document.getElementById('fbnhMemberCount').textContent = error.message;
            const row = document.createElement('tr');
            const message = cell(row, error.message, 'fbnh-empty');
            message.colSpan = 3;
            body.append(row);
        }
    }

    function verdict(isValid1, isValid2) {
        const qualified = isValid1 && isValid2;
        const badge = document.createElement('span');
        badge.className = `fbnh-verdict ${qualified ? 'qualified' : 'not-qualified'}`;
        badge.textContent = qualified ? 'QUALIFIED' : 'NOT QUALIFIED';
        return badge;
    }

    async function saveChecks(record, row, checkbox, previousState) {
        const checkboxes = row.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(input => { input.disabled = true; });
        const values = {
            is_valid1: row.querySelector('[data-check="is_valid1"]').checked ? 1 : 0,
            is_valid2: row.querySelector('[data-check="is_valid2"]').checked ? 1 : 0,
        };
        try {
            await requestJson(`${app.dataset.checksUrl}/${record.id}/checks`, {
                method: 'PUT',
                body: JSON.stringify(values),
            });
            row.querySelector('[data-verdict]').replaceChildren(verdict(Boolean(values.is_valid1), Boolean(values.is_valid2)));
            setStatus('fbnhRecordsStatus', 'BNH checks saved.');
            await loadQualifiedCounts();
        } catch (error) {
            checkbox.checked = previousState;
            setStatus('fbnhRecordsStatus', error.message, true);
        } finally {
            checkboxes.forEach(input => { input.disabled = false; });
        }
    }

    async function loadBnhRecords() {
        const body = document.getElementById('fbnhRecordsBody');
        body.replaceChildren();
        try {
            const data = await requestJson(`${app.dataset.bnhUrl}?${query({ page: currentPage, q: searchInput.value.trim() })}`);
            lastPage = data.last_page;
            document.getElementById('fbnhPageInfo').textContent = `Page ${data.page} of ${data.last_page} · ${data.total} records`;
            document.getElementById('fbnhPrevious').disabled = data.page <= 1;
            document.getElementById('fbnhNext').disabled = data.page >= data.last_page;
            for (const record of data.records) {
                const row = document.createElement('tr');
                cell(row, record.name);
                cell(row, record.status || '—');
                cell(row, record.location || '—');
                for (const key of ['is_valid1', 'is_valid2']) {
                    const td = document.createElement('td');
                    td.className = 'number';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'fbnh-check';
                    checkbox.dataset.check = key;
                    checkbox.checked = record[key];
                    checkbox.setAttribute('aria-label', `${key === 'is_valid1' ? 'Salaysay / Pakikipag-usap' : 'Paghahanda'} for ${record.name}`);
                    checkbox.addEventListener('change', () => saveChecks(record, row, checkbox, !checkbox.checked));
                    td.append(checkbox);
                    row.append(td);
                }
                const verdictCell = document.createElement('td');
                verdictCell.dataset.verdict = 'true';
                verdictCell.append(verdict(record.is_valid1, record.is_valid2));
                row.append(verdictCell);
                body.append(row);
            }
            if (!data.records.length) {
                const row = document.createElement('tr');
                const empty = cell(row, 'No matching active records.', 'fbnh-empty');
                empty.colSpan = 6;
                body.append(row);
            }
            setStatus('fbnhRecordsStatus');
        } catch (error) {
            setStatus('fbnhRecordsStatus', error.message, true);
            const row = document.createElement('tr');
            const message = cell(row, error.message, 'fbnh-empty');
            message.colSpan = 6;
            body.append(row);
        }
    }

    async function loadQualifiedCounts() {
        const body = document.getElementById('fbnhCountsBody');
        body.replaceChildren();
        try {
            const data = await requestJson(`${app.dataset.countsUrl}?${query()}`);
            for (const item of data.rows) {
                const row = document.createElement('tr');
                row.className = item.type === 'grand_total' ? 'fbnh-total-row' : (item.type === 'purok_total' ? 'fbnh-purok-total-row' : '');
                cell(row, item.label);
                cell(row, String(item.male), 'number');
                cell(row, String(item.female), 'number');
                cell(row, String(item.total), 'number');
                body.append(row);
            }
            if (!data.rows.length) {
                const row = document.createElement('tr');
                const empty = cell(row, 'No configured Purok groups found.', 'fbnh-empty');
                empty.colSpan = 4;
                body.prepend(row);
            }
            setStatus('fbnhCountsStatus');
        } catch (error) {
            setStatus('fbnhCountsStatus', error.message, true);
            const row = document.createElement('tr');
            const message = cell(row, error.message, 'fbnh-empty');
            message.colSpan = 4;
            body.append(row);
        }
    }

    function updateDownloadLinks() {
        document.getElementById('fbnhDownloadList').href = `${app.dataset.wordUrl}?${query({ q: searchInput.value.trim() })}`;
        document.getElementById('fbnhDownloadCounts').href = `${app.dataset.countsWordUrl}?${query()}`;
    }

    function refreshFilters() {
        currentPage = 1;
        updateDownloadLinks();
        loadMatrix();
        loadBnhRecords();
        loadQualifiedCounts();
        document.getElementById('fbnhMembersModal').hidden = true;
    }

    purokSelect.addEventListener('change', refreshFilters);
    statusSelect.addEventListener('change', refreshFilters);
    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        updateDownloadLinks();
        searchTimer = window.setTimeout(() => {
            currentPage = 1;
            loadBnhRecords();
        }, 250);
    });
    document.getElementById('fbnhMemberClose').addEventListener('click', () => { document.getElementById('fbnhMembersModal').hidden = true; });
    document.getElementById('fbnhMembersModal').addEventListener('click', event => {
        if (event.target === document.getElementById('fbnhMembersModal')) document.getElementById('fbnhMembersModal').hidden = true;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') document.getElementById('fbnhMembersModal').hidden = true;
    });
    const tabs = Array.from(document.querySelectorAll('.fbnh-tab'));
    function selectTab(tab) {
        tabs.forEach(button => {
            const selected = button === tab;
            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
            document.getElementById(button.getAttribute('aria-controls')).hidden = !selected;
        });
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const nextIndex = event.key === 'Home' ? 0
                : event.key === 'End' ? tabs.length - 1
                    : (index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length;
            tabs[nextIndex].focus();
            selectTab(tabs[nextIndex]);
        });
    });
    document.getElementById('fbnhPrevious').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadBnhRecords(); } });
    document.getElementById('fbnhNext').addEventListener('click', () => { if (currentPage < lastPage) { currentPage++; loadBnhRecords(); } });
    updateDownloadLinks();
    loadMatrix();
    loadBnhRecords();
    loadQualifiedCounts();
})();
</script>
@endsection
