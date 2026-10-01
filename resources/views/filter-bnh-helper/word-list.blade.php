<!doctype html>
<html>
<head><meta charset="utf-8"><title>{{ $title }}</title>
<style>body{font-family:Arial,sans-serif;font-size:10pt}h1{text-align:center;font-size:16pt}p{margin:4px 0 12px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #222;padding:6px;text-align:left}th{background:#e8edf5} .center{text-align:center}</style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p><strong>Purok:</strong> {{ $filters['purok'] }} &nbsp;&nbsp; <strong>Kalagayan:</strong> {{ $filters['status'] }}</p>
    <table>
        <thead><tr><th>NAME</th><th>KALAGAYAN</th><th>PUROK-GRUPO</th><th>SALAYSAY / PAKIKIPAG-USAP</th><th>PAGHAHANDA</th><th>VERDICT</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['location'] }}</td>
                    <td class="center"><span style="font-family: Wingdings">{{ $row['is_valid1'] ? 'ü' : '' }}</span></td>
                    <td class="center"><span style="font-family: Wingdings">{{ $row['is_valid2'] ? 'ü' : '' }}</span></td>
                    <td class="center"><span style="font-family: Wingdings">{{ $row['qualified'] ? 'ü' : '' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">No matching active records.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
