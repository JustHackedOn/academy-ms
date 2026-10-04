<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Monthly Collection Report — {{ $month_name }}</title>
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
            font-size: 48px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 6px;
            text-transform: uppercase;
            z-index: -1000;
        }

        /* Header Block */
        .header {
            background: #1e1b4b;
            color: #ffffff;
            padding: 20px 24px;
            border-radius: 12px;
            overflow: hidden;
            border-bottom: 4px solid #10b981;
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
            color: #4f46e5;
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
            color: #cbd5e1;
            margin: 0;
        }
        .header-meta {
            text-align: right;
        }
        .header-meta-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
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
        .kpi-expected { border-top: 3px solid #6366f1 !important; }
        .kpi-received { border-top: 3px solid #10b981 !important; background: #f0fdf4 !important; }
        .kpi-pending { border-top: 3px solid #f59e0b !important; }
        .kpi-expense { border-top: 3px solid #f43f5e !important; }
        .kpi-profit { border-top: 3px solid #059669 !important; }

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
        .amber { color: #d97706 !important; }
        .rose { color: #e11d48 !important; }

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

        /* Progress Bar */
        .bar-wrap {
            background: #e2e8f0;
            border-radius: 6px;
            height: 7px;
            overflow: hidden;
            margin-top: 4px;
        }
        .bar {
            background: #10b981;
            height: 7px;
        }

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
    <div class="watermark">Monthly Financial Report</div>

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
                    <div class="header-sub">Monthly Financial &amp; Collection Report &middot; {{ $month_name }}</div>
                    <div style="font-size: 9px; color: #a7f3d0; margin-top: 4px;">
                        Reporting Cycle: {{ $start }} to {{ $end }} &middot; {{ currency() }}
                    </div>
                </td>
                <td class="header-meta">
                    <div class="header-meta-label">Report Generated</div>
                    <div class="header-meta-val">{{ now()->format('d M Y') }}</div>
                    <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">{{ now()->format('h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KPIs Bar -->
    <table class="kpis">
        <tr>
            <td class="kpi-expected">
                <div class="kpi-label">Expected Due</div>
                <div class="kpi-value">{{ money($expected, 0) }}</div>
            </td>
            <td class="kpi-received">
                <div class="kpi-label" style="color:#059669;">Received</div>
                <div class="kpi-value green">{{ money($received, 0) }}</div>
            </td>
            <td class="kpi-pending">
                <div class="kpi-label">Month Pending</div>
                <div class="kpi-value amber">{{ money($pending, 0) }}</div>
            </td>
            <td class="kpi-expense">
                <div class="kpi-label">Expenses</div>
                <div class="kpi-value rose">{{ money($expenses, 0) }}</div>
            </td>
            <td class="kpi-profit">
                <div class="kpi-label">Net Profit</div>
                <div class="kpi-value {{ $net_profit >= 0 ? 'green' : 'rose' }}">{{ money($net_profit, 0) }}</div>
            </td>
        </tr>
    </table>

    <!-- Recovery Performance Summary -->
    <div class="section-header">
        <div class="section-title">Collection Rate &amp; Overdue Analysis</div>
    </div>
    <table class="grid">
        <tr>
            <td style="width: 32%;"><strong>Recovered against this month's dues</strong></td>
            <td class="num" style="width: 14%;">
                <strong class="green" style="font-size: 13px;">{{ number_format($collection_rate, 1) }}%</strong>
            </td>
            <td>
                {{ money($collected_against, 0) }} recovered of {{ money($expected, 0) }} expected
                <div class="bar-wrap">
                    <div class="bar" style="width: {{ min(100, $collection_rate) }}%;"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td><strong>Receipts recorded this month</strong></td>
            <td class="num"><strong>{{ $payments_count }}</strong></td>
            <td>Students still owing for this month: <strong>{{ $students_with_dues }}</strong></td>
        </tr>
        <tr>
            <td><strong>Overdue carried from earlier months</strong></td>
            <td class="num"></td>
            <td class="rose"><strong>{{ money($overdue_carried, 0) }}</strong> outstanding balance</td>
        </tr>
    </table>

    <!-- Batch-wise Breakdown -->
    <div class="section-header">
        <div class="section-title">Batch-wise Performance Breakdown</div>
    </div>
    @if (empty($batch_rows))
        <p style="font-size: 11px; color: #94a3b8; padding: 6px 0;">No active batch students had dues for this month.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th class="num">Students</th>
                    <th class="num">Expected</th>
                    <th class="num">Collected</th>
                    <th class="num">Received</th>
                    <th class="num">Remaining</th>
                    <th class="num">Recovery Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batch_rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['name'] }}</strong>
                            <span style="color:#64748b; font-size: 9.5px;">({{ $row['code'] }})</span>
                        </td>
                        <td class="num">{{ $row['students'] }}</td>
                        <td class="num">{{ money($row['expected'], 0) }}</td>
                        <td class="num green">{{ money($row['collected'], 0) }}</td>
                        <td class="num">{{ money($row['received'], 0) }}</td>
                        <td class="num">{{ money($row['remaining'], 0) }}</td>
                        <td class="num">
                            <strong class="{{ $row['rate'] >= 80 ? 'green' : ($row['rate'] >= 50 ? 'amber' : 'rose') }}">
                                {{ $row['rate'] }}%
                            </strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Payment Methods Distribution -->
    <div class="section-header">
        <div class="section-title">Payment Methods Distribution</div>
    </div>
    <table class="grid">
        <thead>
            <tr>
                <th>Payment Channel / Method</th>
                <th class="num">Amount Collected</th>
                <th class="num">Share %</th>
            </tr>
        </thead>
        <tbody>
            @php
                $methodTotal = array_sum($method_breakdown);
                $methodLabels = [
                    'cash' => 'Cash in Hand',
                    'jazzcash' => 'JazzCash Mobile Account',
                    'easypaisa' => 'Easypaisa Wallet',
                    'bank' => 'Direct Bank Transfer',
                    'other' => 'Other Payment Method'
                ];
            @endphp
            @foreach ($method_breakdown as $method => $amount)
                <tr>
                    <td>{{ $methodLabels[$method] ?? ucfirst($method) }}</td>
                    <td class="num">{{ money($amount, 0) }}</td>
                    <td class="num">{{ $methodTotal > 0 ? round(($amount / $methodTotal) * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td>Total Monthly Inflow</td>
                <td class="num">{{ money($received, 0) }}</td>
                <td class="num">100%</td>
            </tr>
        </tbody>
    </table>

    <!-- Transactions Ledger -->
    <div class="section-header">
        <div class="section-title">Receipts Recorded in {{ $month_name }} ({{ $payments->count() }})</div>
    </div>
    @if ($payments->isEmpty())
        <p style="font-size: 11px; color: #94a3b8; padding: 6px 0;">No receipts were logged during this period.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 18%;">Receipt No</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 27%;">Student Name</th>
                    <th style="width: 18%;">Batch</th>
                    <th style="width: 12%;">Method</th>
                    <th style="width: 10%; text-align: right;" class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td><strong>{{ $payment->receipt_no }}</strong></td>
                        <td>{{ $payment->paid_on?->format('d M Y') }}</td>
                        <td>{{ $payment->student?->name }}</td>
                        <td>{{ $payment->student?->batch?->code ?? '-' }}</td>
                        <td>{{ ucfirst($payment->method) }}</td>
                        <td class="num"><strong>{{ money((float) $payment->amount, 0) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="totals">
                    <td colspan="5">Total Receipts Amount</td>
                    <td class="num green">{{ money($received, 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    This financial report was automatically generated by {{ academy_name() }}. Confidential &middot; For internal management use only.
                </td>
                <td style="text-align: right; width: 220px;">
                    Generated on {{ now()->format('d M Y, h:i A') }} &middot; {{ currency() }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
