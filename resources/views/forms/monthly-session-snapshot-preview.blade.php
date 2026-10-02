<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly {{ $month->format('F Y') }} snapshot</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; padding:1.25rem; background:#f1f5f9; color:#172033; font-family:Arial,sans-serif; }
        .snapshot-preview { max-width:1600px; margin:0 auto; }
        .snapshot-preview-heading { margin:0 0 1.25rem; padding:1rem 1.25rem; border:1px solid #dce3ec; border-radius:12px; background:#fff; }
        .snapshot-preview-heading h1 { margin:0; font-size:1.25rem; }
        .snapshot-preview-section { margin-top:1.25rem; padding:1rem; border:1px solid #dce3ec; border-radius:12px; background:#fff; }
        .snapshot-preview-section h2 { margin:0 0 .85rem; font-size:1.05rem; }
        .snapshot-preview-section .resumen-scroll { overflow-x:auto; }
        .snapshot-preview-section .resumen-sheet { min-width:1200px; }
        .snapshot-preview-section .r401-pages { display:grid; gap:1rem; }
        .snapshot-preview-section .r401-page-sheet { max-width:none; overflow:auto; }
        .snapshot-preview-empty { padding:2rem; color:#64748b; text-align:center; }
    </style>
</head>
<body>
    <main class="snapshot-preview">
        <header class="snapshot-preview-heading">
            <h1>Monthly {{ $month->format('F Y') }} · Finalized snapshot</h1>
        </header>
        <section class="snapshot-preview-section" aria-labelledby="snapshotResumenHeading">
            <h2 id="snapshotResumenHeading">Resumen</h2>
            @include('forms.partials.monthly-resumen', array_merge($resumenData, ['pdf' => true]))
        </section>
        @foreach (['MS', 'UWP'] as $status)
            <section class="snapshot-preview-section" aria-labelledby="snapshot{{ $status }}Heading">
                <h2 id="snapshot{{ $status }}Heading">R4-01 · {{ $status }}</h2>
                @if ($statusReports[$status]['reportPages'] === [])
                    <p class="snapshot-preview-empty">No {{ $status }} records were present in this monthly session.</p>
                @else
                    @include('forms.partials.monthly-r4-01', array_merge($statusReports[$status], ['pdf' => true]))
                @endif
            </section>
        @endforeach
    </main>
</body>
</html>
