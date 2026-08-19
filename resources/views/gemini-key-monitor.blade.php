<!DOCTYPE html>
<html>
<head>
    <title>Gemini Key Monitor</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f5f5f5; }
        .bad { color: #b91c1c; font-weight: bold; }
        .ok { color: #15803d; }
    </style>
</head>
<body>
    <h1>Gemini Key Performance (last {{ $hours }}h)</h1>
    <table>
        <thead>
            <tr>
                <th>Key</th><th>Total</th><th>Success</th><th>Failed</th><th>Skipped</th>
                <th>Success Rate</th><th>Avg Latency</th><th>Last Seen</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($stats as $row)
            <tr>
                <td>{{ $row['key_label'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['successes'] }}</td>
                <td>{{ $row['failures'] }}</td>
                <td>{{ $row['skipped'] }}</td>
                <td class="{{ $row['success_rate'] < 80 ? 'bad' : 'ok' }}">{{ $row['success_rate'] ?? '—' }}%</td>
                <td>{{ $row['avg_latency_ms'] ? round($row['avg_latency_ms']).'ms' : '—' }}</td>
                <td>{{ $row['last_seen'] }}</td>
                <td>{!! $row['currently_disabled'] ? '<span class="bad">DISABLED</span>' : '<span class="ok">Active</span>' !!}</td>
            </tr>
        @empty
            <tr><td colspan="9">No requests logged yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>