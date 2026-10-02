<style>
    .resumen-page { max-width:1500px; margin:0 auto; font-family:Tahoma,Arial,sans-serif; font-weight:700; }
    .resumen-tools { display:flex; flex-wrap:wrap; align-items:end; gap:.65rem; margin-bottom:1rem; padding:.85rem; border:1px solid var(--border); border-radius:12px; background:var(--surface); }
    .resumen-tools label { display:grid; gap:.3rem; min-width:180px; color:var(--text-muted); font-size:.76rem; }
    .resumen-tools select,.resumen-tools button,.resumen-tools a { min-height:38px; padding:.45rem .7rem; border:1px solid var(--border); border-radius:7px; background:var(--bg); color:var(--text); font:700 .82rem Tahoma,Arial,sans-serif; }
    .resumen-tools button,.resumen-tools a { display:inline-flex; align-items:center; justify-content:center; border:0; background:var(--accent); color:#fff; text-decoration:none; cursor:pointer; }
    .resumen-note { margin:0 0 1rem; padding:.75rem; border:1px solid #f4c982; border-radius:8px; background:#fff8e8; color:#7a4b00; font-size:.82rem; }
    .resumen-scroll { overflow-x:auto; padding:.4rem; border:1px solid var(--border); border-radius:10px; background:#fff; }
    .resumen-sheet { min-width:1320px; color:#111; font:700 8px Tahoma,Arial,sans-serif; }
    .resumen-sheet h1 { margin:0 0 .4rem; text-align:center; font-size:14px; }
    .resumen-table { width:100%; border-collapse:collapse; table-layout:fixed; color:#111; font:700 8px Tahoma,Arial,sans-serif; }
    .resumen-table th,.resumen-table td { padding:3px 2px; border:1px solid #111; text-align:center; vertical-align:middle; overflow-wrap:anywhere; }
    .resumen-table .left { text-align:left; }
    .resumen-table thead th { background:#f4f4f4; }
    .resumen-table .worker-col { width:145px; }
    .resumen-table .purok-col { width:65px; }
    .resumen-table .count-col { width:43px; }
    .resumen-table tfoot { font-weight:700; background:#f4f4f4; }
    .resumen-signatories { display:grid; grid-template-columns:repeat(3,1fr); gap:2rem; margin:2.5rem 1rem .5rem; text-align:center; color:#111; font-size:.8rem; }
    .resumen-signatory { padding-top:1.5rem; border-top:1px solid #111; }
    .resumen-signatory strong { display:block; margin-bottom:.25rem; text-transform:uppercase; }
    @media print {
        @page { size:landscape; margin:.25in; }
        body { background:#fff!important; }
        .sidebar,.topbar,.overlay,.resumen-tools,.resumen-note { display:none!important; }
        .main-area,.content { width:100%!important; margin:0!important; padding:0!important; }
        .resumen-page { max-width:none; }
        .resumen-scroll { overflow:visible; padding:0; border:0; }
        .resumen-sheet { min-width:0; }
        .resumen-table { font-size:7pt; }
        .resumen-table th,.resumen-table td { padding:2px; }
        .resumen-signatories { margin-top:1.5rem; }
    }
</style>

<div class="resumen-page">
    <div class="resumen-tools">
        <a href="{{ route('forms.monthly.resumen.download') }}">Download editable DOCX</a>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <p class="resumen-note">The starting count (A) is the previous month’s closing MS/UWP total. Additions (B, C+) are added; C-, D, DD, E, G, and H are deducted to calculate this month’s total.</p>

    <div class="resumen-scroll">
        <section class="resumen-sheet" aria-label="Monthly Resumen report">
            <h1>BILANG NG NADAGDAG/NABAWAS NA MS/TS AT UWP</h1>
            <table class="resumen-table">
                <colgroup>
                    <col class="worker-col"><col class="purok-col">
                    @for ($i = 0; $i < 20; $i++)<col class="count-col">@endfor
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="4" class="left">MAYSAKOP</th>
                        <th rowspan="4">SAKOP NA PUROK</th>
                        <th colspan="9">PARA SA BUWAN NG: {{ mb_strtoupper($report['month']->format('F Y')) }} · MADALANG SUMAMBA (MS)</th>
                        <th rowspan="4">BILANG<br>NGAYON</th>
                        <th colspan="9">PARA SA BUWAN NG: {{ mb_strtoupper($report['month']->format('F Y')) }} · UMALIS WALANG PAALAM (UWP)</th>
                        <th rowspan="4">BILANG<br>NGAYON</th>
                    </tr>
                    <tr><th colspan="1">DATISAKOP</th><th colspan="2">NADAGDAG</th><th colspan="6">NABAWAS</th><th colspan="1">DATISAKOP</th><th colspan="2">NADAGDAG</th><th colspan="6">NABAWAS</th></tr>
                    <tr>
                        @foreach (['A','B','C+','C-','D','DD','E','G','H'] as $code)<th>{{ $code }}</th>@endforeach
                        @foreach (['A','B','C+','C-','D','DD','E','G','H'] as $code)<th>{{ $code }}</th>@endforeach
                    </tr>
                    <tr>
                        @foreach (['Opening','Add','Transfer in','Transfer out','Subtract','Subtract','Subtract','Subtract','Subtract'] as $label)<th>{{ $label }}</th>@endforeach
                        @foreach (['Opening','Add','Transfer in','Transfer out','Subtract','Subtract','Subtract','Subtract','Subtract'] as $label)<th>{{ $label }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="left">{{ $row['maysakop'] }}</td>
                            <td>{{ $row['purok_label'] }}</td>
                            @foreach (['A','B','C+','C-','D','DD','E','G','H'] as $code)<td>{{ $row['ms'][$code] }}</td>@endforeach
                            <td>{{ $row['ms_total'] }}</td>
                            @foreach (['A','B','C+','C-','D','DD','E','G','H'] as $code)<td>{{ $row['uwp'][$code] }}</td>@endforeach
                            <td>{{ $row['uwp_total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="22">No Purok or movement data is available for this month.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="left">KABUUAN</td><td></td>
                        @foreach (['A','B','C+','C-','D','DD','E','G','H'] as $code)<td>{{ $report['totals']['ms'][$code] }}</td>@endforeach
                        <td>{{ $report['totals']['ms_total'] }}</td>
                        @foreach (['A','B','C+','C-','D','DD','E','G','H'] as $code)<td>{{ $report['totals']['uwp'][$code] }}</td>@endforeach
                        <td>{{ $report['totals']['uwp_total'] }}</td>
                    </tr>
                </tfoot>
            </table>
            <p>LOKAL: {{ $locale }} &nbsp; LCODE: {{ $lcode }}</p>
            <div class="resumen-signatories">
                <div class="resumen-signatory"><strong>{{ $signatories['head_lsp'] ?: ' ' }}</strong>KALIHIM SA LSP</div>
                <div class="resumen-signatory"><strong>{{ $signatories['ksp_pd'] ?: ' ' }}</strong>KATIWALA SA PAGPAPATIBAY (KSP)</div>
                <div class="resumen-signatory"><strong>{{ $signatories['pastor_destinado'] ?: ' ' }}</strong>PASTOR NG LOKAL</div>
            </div>
        </section>
    </div>
</div>
