<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Batch Performance &amp; Collection Report</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            margin: 20px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 11px;
            margin: 0;
            padding: 24px;
            position: relative;
            background-color: #ffffff;
        }

        /* Subtle Security Watermark */
        .watermark {
            position: fixed;
            top: 38%;
            left: 5%;
            width: 90%;
            text-align: center;
            opacity: 0.03;
            transform: rotate(-28deg);
            font-size: 46px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 6px;
            text-transform: uppercase;
            z-index: -1000;
        }

        /* Header Block */
        .header {
            background: #0f766e;
            color: #ffffff;
            padding: 20px 24px;
            border-radius: 12px;
            overflow: hidden;
            border-bottom: 4px solid #14b8a6;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .logo-box {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #ffffff;
            text-align: center;
            line-height: 50px;
            font-size: 18px;
            font-weight: 800;
            color: #0f766e;
            display: inline-block;
            vertical-align: middle;
            overflow: hidden;
        }
        .logo-img {
            max-width: 46px;
            max-height: 46px;
            vertical-align: middle;
        }
        .header-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin: 0 0 2px 0;
        }
        .header-sub {
            font-size: 11px;
            color: #ccfbf1;
            margin: 0;
        }
        .header-meta {
            text-align: right;
        }
        .header-meta-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #99f6e4;
            font-weight: bold;
        }
        .header-meta-val {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            margin-top: 2px;
        }

        /* KPI Bento Grid */
        .kpis {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-top: 16px;
        }
        .kpis td {
            width: 20%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 8px;
            text-align: center;
        }
        .kpi-batches { border-top: 3px solid #0f766e !important; }
        .kpi-students { border-top: 3px solid #6366f1 !important; }
        .kpi-expected { border-top: 3px solid #0284c7 !important; }
        .kpi-collected { border-top: 3px solid #10b981 !important; background: #f0fdf4 !important; }
        .kpi-overdue { border-top: 3px solid #f43f5e !important; }

        .kpi-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            font-weight: bold;
        }
        .kpi-value {
            font-size: 15px;
            font-weight: 800;
            margin-top: 4px;
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
        }
        .green { color: #059669 !important; }
        .rose { color: #e11d48 !important; }
        .amber { color: #d97706 !important; }

        /* Section Title */
        .section-header {
            margin: 18px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #e2e8f0;
        }
        .section-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            font-weight: 800;
        }

        /* Tables */
        table.grid {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        table.grid thead {
            display: table-header-group;
        }
        table.grid tr {
            page-break-inside: avoid;
        }
        table.grid th {
            background: #f1f5f9;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            text-align: left;
            padding: 7px 10px;
            border-bottom: 1.5px solid #cbd5e1;
            font-weight: 800;
        }
        table.grid td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10.5px;
            color: #1e293b;
        }
        table.grid tr:nth-child(even) td {
            background: #fafafa;
        }
        table.grid td.num, table.grid th.num {
            text-align: right;
            font-family: DejaVu Sans, sans-serif;
        }
        table.grid tr.totals td {
            background: #f1f5f9;
            font-weight: 800;
            border-top: 2px solid #cbd5e1;
            color: #0f172a;
        }

        /* Status badges */
        .status {
            display: inline-block;
            font-size: 8.5px;
            font-weight: 800;
            padding: 2.5px 7px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-running { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .status-upcoming { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-completed { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .status-archived { background: #fce7f3; color: #9d174d; border: 1px solid #fbcfe8; }

        /* Footer */
        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1.5px dashed #cbd5e1;
            font-size: 9.5px;
            color: #64748b;
            page-break-inside: avoid;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    <div class="watermark">Batch Performance Report</div>

    @php
        $logoBase64 = pdf_image_base64(\App\Models\Setting::get('academy_logo'), 100, 100);
    @endphp

    <!-- Header Block -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 58px;">
                    <div class="logo-box">
                        @if ($logoBase64)
                            <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                        @else
                            {{ academy_initials() }}
                        @endif
                    </div>
                </td>
                <td style="padding-left: 12px;">
                    <h1 class="header-title">{{ academy_name() }}</h1>
                    <div class="header-sub">Batch Financial &amp; Enrollment Performance Summary</div>
                    <div style="font-size: 9px; color: #99f6e4; margin-top: 4px;">
                        Lifetime Course Collections &middot; {{ currency() }}
                    </div>
                </td>
                <td class="header-meta">
                    <div class="header-meta-label">Report Generated</div>
                    <div class="header-meta-val">{{ now()->format('d M Y') }}</div>
                    <div style="font-size: 9px; color: #ccfbf1; margin-top: 2px;">{{ now()->format('h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI Cards -->
    <table class="kpis">
        <tr>
            <td class="kpi-batches">
                <div class="kpi-label">Active Batches</div>
                <div class="kpi-value">{{ count($rows) }}</div>
            </td>
            <td class="kpi-students">
                <div class="kpi-label">Enrolled Students</div>
                <div class="kpi-value">{{ $totals['students'] }}</div>
            </td>
            <td class="kpi-expected">
                <div class="kpi-label">Expected Net Fee</div>
                <div class="kpi-value">{{ money($totals['expected'], 0) }}</div>
            </td>
            <td class="kpi-collected">
                <div class="kpi-label" style="color: #059669;">Total Collected</div>
                <div class="kpi-value green">{{ money($totals['collected'], 0) }}</div>
            </td>
            <td class="kpi-overdue">
                <div class="kpi-label">Outstanding Overdue</div>
                <div class="kpi-value rose">{{ money($totals['overdue'], 0) }}</div>
            </td>
        </tr>
    </table>

    <!-- Batch Performance Details Table -->
    <div class="section-header">
        <div class="section-title">Batch Breakdown &amp; Recovery Health</div>
    </div>

    @if (empty($rows))
        <p style="font-size: 11px; color: #94a3b8; padding: 6px 0;">No batches have been registered yet.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 24%;">Batch Name &amp; Code</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                    <th style="width: 17%;">Duration Cycle</th>
                    <th class="num" style="width: 7%;">Students</th>
                    <th class="num" style="width: 11%;">Expected</th>
                    <th class="num" style="width: 11%;">Collected</th>
                    <th class="num" style="width: 10%;">Remaining</th>
                    <th class="num" style="width: 10%;">Overdue</th>
                    <th class="num" style="width: 10%;">Recovery</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['name'] }}</strong><br>
                            <span style="color:#64748b; font-size: 9px; font-family: monospace;">{{ $row['code'] }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="status status-{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span>
                        </td>
                        <td style="font-size: 9.5px; color: #475569;">
                            {{ $row['start_date'] }} &rarr; {{ $row['end_date'] }}
                        </td>
                        <td class="num font-bold">{{ $row['students'] }}</td>
                        <td class="num">{{ money($row['expected'], 0) }}</td>
                        <td class="num green font-bold">{{ money($row['collected'], 0) }}</td>
                        <td class="num">{{ money($row['remaining'], 0) }}</td>
                        <td class="num rose">{{ money($row['overdue'], 0) }}</td>
                        <td class="num">
                            <strong class="{{ $row['rate'] >= 80 ? 'green' : ($row['rate'] >= 50 ? 'amber' : 'rose') }}">
                                {{ $row['rate'] }}%
                            </strong>
                        </td>
                    </tr>
                @endforeach
                <tr class="totals">
                    <td colspan="3">Grand Totals Across All Batches</td>
                    <td class="num">{{ $totals['students'] }}</td>
                    <td class="num">{{ money($totals['expected'], 0) }}</td>
                    <td class="num green">{{ money($totals['collected'], 0) }}</td>
                    <td class="num">{{ money($totals['remaining'], 0) }}</td>
                    <td class="num rose">{{ money($totals['overdue'], 0) }}</td>
                    <td class="num">
                        <strong>{{ $totals['expected'] > 0 ? round(($totals['collected'] / $totals['expected']) * 100, 1) : 0 }}%</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    This batch performance report was generated by {{ academy_name() }}. Confidential &middot; For official records only.
                </td>
                <td style="text-align: right; width: 220px;">
                    Generated on {{ now()->format('d M Y, h:i A') }} &middot; {{ currency() }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
