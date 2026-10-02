@extends('layouts.app')

@section('title', 'Tarheta Status Checker')

@section('content')
<style>
    .tarheta-page { max-width:1000px; margin:0 auto; }
    .tarheta-heading,.tarheta-panel { padding:clamp(1rem,3vw,1.6rem); border:1px solid var(--border); border-radius:16px; background:var(--surface); }
    .tarheta-heading { margin-bottom:1rem; }
    .tarheta-heading h1 { margin:.2rem 0 .45rem; }
    .tarheta-heading p,.tarheta-note { color:var(--text-muted); line-height:1.5; }
    .tarheta-table { width:100%; border-collapse:collapse; }
    .tarheta-table th,.tarheta-table td { padding:.8rem; border-bottom:1px solid var(--border); text-align:left; vertical-align:middle; }
    .tarheta-table select { min-height:40px; padding:.5rem .7rem; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font:inherit; }
    .tarheta-table select:disabled { opacity:.65; cursor:wait; }
    .tarheta-status { min-width:100px; }
    .tarheta-message { margin-top:.35rem; color:var(--danger); font-size:.8rem; }
    .tarheta-row[hidden] { display:none; }
    .tarheta-empty { padding:1.5rem; text-align:center; color:var(--text-muted); }
    .tarheta-controls { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.8rem; margin:1rem 0; }
    .tarheta-search { min-height:42px; flex:1 1 240px; padding:.6rem .75rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; }
    .tarheta-toggle { display:flex; align-items:center; gap:.45rem; color:var(--text-muted); font-size:.85rem; }
    .tarheta-progress { height:12px; overflow:hidden; border-radius:99px; background:var(--border); }
    .tarheta-progress > span { display:block; width:{{ $percentage }}%; height:100%; border-radius:inherit; background:#16a34a; transition:width .25s ease; }
    .tarheta-summary { margin:.6rem 0 1rem; color:var(--text-muted); font-size:.86rem; }
    @media(max-width:680px) {
        .tarheta-table th:nth-child(2),.tarheta-table td:nth-child(2) { display:none; }
        .tarheta-table th,.tarheta-table td { padding:.55rem .35rem; }
    }
</style>
<main class="tarheta-page">
    <header class="tarheta-heading">
        <h1>Tarheta Status Checker</h1>
        <p>Compare current record status with Tarheta status, search by name/status, and update Tarheta values. Matching records stay available with the toggle enabled.</p>
        <div class="tarheta-controls">
            <input id="tarhetaSearch" class="tarheta-search" type="search" placeholder="Search by member name or status" aria-label="Search Tarheta records">
            <label class="tarheta-toggle"><input id="showMatched" type="checkbox"> Show already-matched records</label>
        </div>
        <div class="tarheta-summary"><strong id="tarhetaMatched">{{ $matched }}</strong> of <strong id="tarhetaTotal">{{ $total }}</strong> evaluated records matched (<strong id="tarhetaPercent">{{ $percentage }}%</strong>).</div>
        <div class="tarheta-progress" role="progressbar" aria-label="Records matching Tarheta status" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $matched }}">
            <span id="tarhetaProgress" style="width:{{ $percentage }}%"></span>
        </div>
        <p class="tarheta-note"><strong id="tarhetaCount">{{ $total - $matched }}</strong> record{{ $total - $matched === 1 ? '' : 's' }} need review.</p>
    </header>
    <section class="tarheta-panel">
        <div class="table-scroll">
            <table class="tarheta-table">
                <thead><tr><th>Name</th><th>Status</th><th>Tarheta status</th></tr></thead>
                <tbody id="tarhetaRows">
                    @forelse ($records as $record)
                        <tr class="tarheta-row" data-record-id="{{ $record['id'] }}" data-matched="{{ $record['matched'] ? '1' : '0' }}" @if ($record['matched']) hidden @endif>
                            <td class="tarheta-name">{{ $record['name'] }}</td>
                            <td class="tarheta-status">{{ $record['status'] ?: 'Not set' }}</td>
                            <td>
                                <select class="tarheta-choice" aria-label="Tarheta status for {{ $record['name'] }}">
                                    <option value="">Select status</option>
                                    @foreach (['MS', 'UWP', 'MK', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected($record['tarheta_status'] === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <div class="tarheta-message" role="status" aria-live="polite"></div>
                            </td>
                        </tr>
                    @empty
                        <tr class="tarheta-empty-row"><td colspan="3" class="tarheta-empty">No records are available to evaluate.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
(() => {
    const rows = document.getElementById('tarhetaRows');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const allRows = () => [...rows.querySelectorAll('tr[data-record-id]')];
    const showMatched = document.getElementById('showMatched');
    const search = document.getElementById('tarhetaSearch');
    const render = () => {
        const term = search.value.trim().toLocaleLowerCase();
        allRows().forEach(row => {
            const matchesSearch = !term || row.textContent.toLocaleLowerCase().includes(term);
            row.hidden = !matchesSearch || (!showMatched.checked && row.dataset.matched === '1');
        });
        const currentRows = allRows();
        const matched = currentRows.filter(row => row.dataset.matched === '1').length;
        const total = currentRows.length;
        const percentage = total === 0 ? 0 : Math.round((matched / total) * 1000) / 10;
        document.getElementById('tarhetaMatched').textContent = matched;
        document.getElementById('tarhetaTotal').textContent = total;
        document.getElementById('tarhetaPercent').textContent = `${percentage}%`;
        document.getElementById('tarhetaProgress').style.width = `${percentage}%`;
        const progress = document.querySelector('.tarheta-progress');
        progress.setAttribute('aria-valuemax', total);
        progress.setAttribute('aria-valuenow', matched);
        const remaining = total - matched;
        const review = document.getElementById('tarhetaCount');
        review.textContent = remaining;
        review.parentElement.lastChild.textContent = ` record${remaining === 1 ? '' : 's'} need review.`;
    };
    showMatched.addEventListener('change', render);
    search.addEventListener('input', render);
    rows.addEventListener('change', async event => {
        const select = event.target.closest('.tarheta-choice');
        if (!select) return;
        const row = select.closest('tr[data-record-id]');
        const message = row.querySelector('.tarheta-message');
        if (!select.value) {
            return;
        }
        select.disabled = true;
        try {
            const response = await fetch(`{{ route('tarheta-status.update', ['record' => '__RECORD__']) }}`.replace('__RECORD__', encodeURIComponent(row.dataset.recordId)), {
                method: 'PUT',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ tarheta_status: select.value })
            });
            const result = await response.json();
            if (!response.ok) {
                const errors = Object.values(result.errors || {}).flat();
                throw new Error(errors.join(' ') || result.message || 'Could not save Tarheta status.');
            }
            row.dataset.matched = result.matches ? '1' : '0';
            message.textContent = result.message;
            render();
        } catch (error) {
            message.textContent = error.message || 'Could not save Tarheta status.';
        } finally {
            if (row.isConnected) select.disabled = false;
        }
    });
    render();
})();
</script>
@endsection
