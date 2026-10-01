<!doctype html>
<html>
<head><meta charset="utf-8"><title>{{ $title }}</title>
<style>body{font-family:Arial,sans-serif;font-size:10pt}h1{text-align:center;font-size:16pt}p{margin:4px 0 12px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #222;padding:6px;text-align:left}th{background:#e8edf5}.number{text-align:center}.total{font-weight:bold;background:#f0f3f8}.purok-total{font-weight:bold;background:#f6f8fb}</style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p><strong>Purok:</strong> {{ $filters['purok'] }} &nbsp;&nbsp; <strong>Kalagayan:</strong> {{ $filters['status'] }}</p>
    <table>
        <thead><tr><th>PUROK-GRUPO</th><th class="number">L</th><th class="number">B</th><th class="number">TOTAL</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $row['type'] === 'grand_total' ? 'total' : ($row['type'] === 'purok_total' ? 'purok-total' : '') }}"><td>{{ $row['label'] }}</td><td class="number">{{ $row['male'] }}</td><td class="number">{{ $row['female'] }}</td><td class="number">{{ $row['total'] }}</td></tr>
            @empty
                <tr><td colspan="4">No configured Purok groups found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
