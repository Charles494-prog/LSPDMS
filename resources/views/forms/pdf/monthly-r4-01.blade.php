<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: .25in; }
        html, body { margin: 0; padding: 0; background: #fff; color: #111; }
        .r401-page { max-width: none; margin: 0; }
        .r401-tools, .r401-empty-note { display: none !important; }
        .r401-pages { display: block; }
        .r401-page-sheet { width: 100%; padding: 0; page-break-after: always; break-after: page; }
        .r401-page-sheet:last-child { page-break-after: auto; break-after: auto; }
    </style>
</head>
<body>
    @include('forms.partials.monthly-r4-01', ['pdf' => true])
</body>
</html>
