@php
    $clientId = (int) request('client_id');
    $month = (int) request('month');
    $year = (int) request('year');
    $user = auth()->user();

    $reportService = app(\App\Services\MonthlyReportService::class);
    $report = $reportService->generateReport($user, $clientId, $month, $year);
    $client = \App\Models\Client::find($clientId);
    $clientName = $client?->name ?? '—';
    $monthName = date('F', mktime(0, 0, 0, $month, 1));
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report - {{ $clientName }} ({{ $monthName }} {{ $year }})</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            background-color: #ffffff;
            margin: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .header {
            border-b: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            font-weight: 800;
            color: #111827;
        }
        .header p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .meta-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
        }
        .meta-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
        }
        .metric-value {
            font-size: 32px;
            font-weight: 800;
            color: #4f46e5;
        }
        .metrics-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .metrics-list li {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #f3f4f6;
        }
        .metrics-list li:last-child {
            border-bottom: none;
        }
        .remarks-box {
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
            font-style: italic;
        }
        .remarks-box h3 {
            margin: 0 0 8px 0;
            font-style: normal;
            text-transform: uppercase;
            font-size: 12px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 11px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-open { background-color: #dbeafe; color: #1e40af; }
        .badge-progress { background-color: #ffedd5; color: #9a3412; }
        .badge-resolved { background-color: #d1fae5; color: #065f46; }
        .badge-closed { background-color: #f3f4f6; color: #374151; }
        
        .badge-low { background-color: #f3f4f6; color: #374151; }
        .badge-medium { background-color: #fef3c7; color: #92400e; }
        .badge-high { background-color: #ffe4e6; color: #9f1239; }

        @media print {
            body {
                margin: 0;
                font-size: 12px;
            }
            .no-print {
                display: none;
            }
            .remarks-box {
                background-color: transparent !important;
            }
        }
    </style>
</head>
<body>

    <div class="header" style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 24px; margin: 0 0 5px 0; text-transform: uppercase; font-weight: 800; color: #111827;">Monthly Support Report</h1>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">Client: <strong>{{ $clientName }}</strong> &nbsp;|&nbsp; Period: <strong>{{ $monthName }} {{ $year }}</strong></p>
        </div>
        <div style="text-align: right; font-size: 11px; color: #6b7280; padding-bottom: 2px;">
            Generated on: <strong>{{ now()->format('M d, Y H:i:s') }}</strong>
        </div>
    </div>

    <div class="meta-grid">
        <!-- Ticket Volume -->
        <div class="meta-box">
            <h3>Support Volume</h3>
            <div class="metric-value">{{ $report['total_count'] }}</div>
            <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 12px;">Total tickets created during this period</p>
        </div>

        <!-- Status & Priority -->
        <div class="meta-box" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <h3 style="font-size: 11px; margin-bottom: 5px;">Status Summary</h3>
                <ul class="metrics-list">
                    <li><span>Open:</span> <strong>{{ $report['status_counts']['open'] ?? 0 }}</strong></li>
                    <li><span>In Progress:</span> <strong>{{ $report['status_counts']['in_progress'] ?? 0 }}</strong></li>
                    <li><span>Resolved:</span> <strong>{{ $report['status_counts']['resolved'] ?? 0 }}</strong></li>
                    <li><span>Closed:</span> <strong>{{ $report['status_counts']['closed'] ?? 0 }}</strong></li>
                </ul>
            </div>
            <div>
                <h3 style="font-size: 11px; margin-bottom: 5px;">Priority Summary</h3>
                <ul class="metrics-list">
                    <li><span>Low:</span> <strong>{{ $report['priority_counts']['low'] ?? 0 }}</strong></li>
                    <li><span>Medium:</span> <strong>{{ $report['priority_counts']['medium'] ?? 0 }}</strong></li>
                    <li><span>High:</span> <strong>{{ $report['priority_counts']['high'] ?? 0 }}</strong></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Executive Remarks -->
    <div class="remarks-box">
        <h3>Executive Summary & Remarks</h3>
        <div>
            {!! nl2br(e($report['remark']?->remarks ?? 'No remarks or summary notes provided for this month.')) !!}
        </div>
    </div>

    <!-- Detailed Tickets List -->
    <h2>Tickets Audited</h2>
    <table>
        <thead>
            <tr>
                <th>Ticket No.</th>
                <th>Title</th>
                <th>Assigned Engineer</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Resolved At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['tickets'] as $t)
                <tr>
                    <td style="font-family: monospace; font-weight: bold;">{{ $t->ticket_number }}</td>
                    <td>{{ $t->title }}</td>
                    <td>{{ $t->assignedEngineer?->name ?? 'Unassigned' }}</td>
                    <td>
                        @if ($t->priority === \App\Enums\Priority::Low)
                            <span class="badge badge-low">Low</span>
                        @elseif ($t->priority === \App\Enums\Priority::Medium)
                            <span class="badge badge-medium">Medium</span>
                        @else
                            <span class="badge badge-high">High</span>
                        @endif
                    </td>
                    <td>
                        @if ($t->status === \App\Enums\TicketStatus::Open)
                            <span class="badge badge-open">Open</span>
                        @elseif ($t->status === \App\Enums\TicketStatus::InProgress)
                            <span class="badge badge-progress">In Progress</span>
                        @elseif ($t->status === \App\Enums\TicketStatus::Resolved)
                            <span class="badge badge-resolved">Resolved</span>
                        @else
                            <span class="badge badge-closed">Closed</span>
                        @endif
                    </td>
                    <td>{{ $t->created_at->format('M d, Y H:i') }}</td>
                    <td>{{ $t->resolved_at ? $t->resolved_at->format('M d, Y H:i') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #9ca3af; font-style: italic;">
                        No tickets created during this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
