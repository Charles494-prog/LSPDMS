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
    .tarheta-row-fading td { opacity:0; transition:opacity .35s ease; }
    .tarheta-empty { padding:1.5rem; text-align:center; color:var(--text-muted); }
    @media(max-width:680px) {
        .tarheta-table th:nth-child(2),.tarheta-table td:nth-child(2) { display:none; }
        .tarheta-table th,.tarheta-table td { padding:.55rem .35rem; }
    }
</style>
<main class="tarheta-page">
    <header class="tarheta-heading">
        <h1>Tarheta Status Checker</h1>
        <p>Records appear here when their current status differs from their Tarheta status. Update Tarheta status to match; the record will leave this list once they match.</p>
        <p class="tarheta-note"><strong id="tarhetaCount">{{ $records->count() }}</strong> record{{ $records->count() === 1 ? '' : 's' }} need review.</p>
    </header>
    <section class="tarheta-panel">
        <div class="table-scroll">
            <table class="tarheta-table">
                <thead><tr><th>Name</th><th>Status</th><th>Tarheta status</th></tr></thead>
                <tbody id="tarhetaRows">
                    @forelse ($records as $record)
                        <tr data-record-id="{{ $record['id'] }}">
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
                        <tr class="tarheta-empty-row"><td colspan="3" class="tarheta-empty">All Tarheta statuses match the current record statuses.</td></tr>
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
            if (result.matches) {
                row.classList.add('tarheta-row-fading');
                await new Promise(resolve => window.setTimeout(resolve, 350));
                row.remove();
                const count = document.getElementById('tarhetaCount');
                const remaining = rows.querySelectorAll('tr[data-record-id]').length;
                count.textContent = remaining;
                count.parentElement.lastChild.textContent = ` record${remaining === 1 ? '' : 's'} need review.`;
                if (!rows.querySelector('tr[data-record-id]')) {
                    const empty = document.createElement('tr');
                    empty.className = 'tarheta-empty-row';
                    const cell = document.createElement('td');
                    cell.colSpan = 3;
                    cell.className = 'tarheta-empty';
                    cell.textContent = 'All Tarheta statuses match the current record statuses.';
                    empty.append(cell);
                    rows.append(empty);
                }
            } else {
                message.textContent = result.message;
            }
        } catch (error) {
            message.textContent = error.message || 'Could not save Tarheta status.';
        } finally {
            if (row.isConnected) select.disabled = false;
        }
    });
})();
</script>
@endsection
