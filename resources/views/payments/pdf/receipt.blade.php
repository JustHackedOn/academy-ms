<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payment->receipt_no }} — Fee Receipt</title>
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
            top: 35%;
            left: 5%;
            width: 90%;
            text-align: center;
            opacity: 0.035;
            transform: rotate(-30deg);
            font-size: 52px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 6px;
            text-transform: uppercase;
            z-index: -1000;
        }

        /* Header block */
        .header {
            background: #1e1b4b;
            color: #ffffff;
            padding: 22px 24px;
            border-radius: 12px;
            overflow: hidden;
            border-bottom: 4px solid #6366f1;
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
            width: 54px;
            height: 54px;
            border-radius: 12px;
            background: #ffffff;
            text-align: center;
            line-height: 54px;
            font-size: 20px;
            font-weight: 800;
            color: #4f46e5;
            display: inline-block;
            vertical-align: middle;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .logo-img {
            max-width: 50px;
            max-height: 50px;
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
            color: #c7d2fe;
            margin: 0;
        }
        .receipt-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #e0e7ff;
            margin-top: 6px;
        }
        .receipt-badge-box {
            text-align: right;
        }
        .receipt-no-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #a5b4fc;
            font-weight: bold;
        }
        .receipt-no-val {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            font-family: monospace;
            margin-top: 2px;
        }

        /* Metadata Grid */
        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
            margin-top: 16px;
        }
        table.meta {
            width: 100%;
            border-collapse: collapse;
        }
        table.meta td {
            vertical-align: top;
            padding: 4px 12px 4px 0;
        }
        .meta-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            font-weight: bold;
        }
        .meta-value {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 3px;
        }
        .meta-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Student Photo thumbnail if available */
        .student-photo-box {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
            text-align: center;
            background: #e2e8f0;
        }
        .student-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Hero Amount Box */
        .amount-box {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 10px;
            padding: 16px 20px;
            margin-top: 16px;
        }
        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }
        .amount-table td {
            vertical-align: middle;
        }
        .amount-hero-label {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #15803d;
            font-weight: bold;
        }
        .amount-hero-value {
            font-size: 28px;
            font-weight: 800;
            color: #166534;
            margin-top: 2px;
            font-family: DejaVu Sans, sans-serif;
        }
        .balance-box {
            text-align: right;
            padding-left: 20px;
            border-left: 1px dashed #bbf7d0;
        }
        .balance-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            font-weight: bold;
        }
        .balance-val {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }

        /* Section Headings */
        .section-header {
            margin: 20px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #e2e8f0;
        }
        .section-title {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            font-weight: 800;
        }

        /* Table */
        table.alloc {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        table.alloc thead {
            display: table-header-group;
        }
        table.alloc tr {
            page-break-inside: avoid;
        }
        table.alloc th {
            background: #f1f5f9;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #475569;
            text-align: left;
            padding: 8px 12px;
            border-bottom: 1.5px solid #cbd5e1;
            font-weight: 800;
        }
        table.alloc td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            color: #1e293b;
        }
        table.alloc tr:nth-child(even) td {
            background-color: #fafafa;
        }
        table.alloc td.num {
            text-align: right;
            font-weight: 700;
            font-family: DejaVu Sans, sans-serif;
        }

        /* Status badges */
        .status {
            display: inline-block;
            font-size: 8.5px;
            font-weight: 800;
            padding: 2.5px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-partial { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-pending { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .status-overdue { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }

        /* Notes Card */
        .note-card {
            margin-top: 14px;
            font-size: 10.5px;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3.5px solid #6366f1;
            border-radius: 6px;
            padding: 9px 14px;
            page-break-inside: avoid;
        }

        /* Footer & Stamp */
        .footer-wrap {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1.5px dashed #cbd5e1;
            page-break-inside: avoid;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            padding: 0;
        }
        .footer-info {
            font-size: 9.5px;
            color: #64748b;
            line-height: 1.5;
        }
        .verification-badge {
            display: inline-block;
            border: 1.5px solid #059669;
            border-radius: 8px;
            padding: 6px 12px;
            text-align: center;
            background: #f0fdf4;
        }
        .verification-title {
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #047857;
        }
        .verification-text {
            font-size: 10px;
            font-weight: 700;
            color: #065f46;
            margin-top: 1px;
        }
    </style>
</head>
<body>
    <div class="watermark">Official Receipt</div>

    <!-- Header Block -->
    @php
        $logoBase64 = pdf_image_base64(\App\Models\Setting::get('academy_logo'), 108, 108);
        $studentPhotoBase64 = pdf_image_base64($payment->student?->photo, 88, 88);
    @endphp
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 64px;">
                    <div class="logo-box">
                        @if ($logoBase64)
                            <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                        @else
                            {{ academy_initials() }}
                        @endif
                    </div>
                </td>
                <td style="padding-left: 14px;">
                    <h1 class="header-title">{{ academy_name() }}</h1>
                    <div class="header-sub">Official Student Fee Payment Receipt</div>
                    <div class="receipt-pill">{{ currency() }} &middot; Verified Digital Copy</div>
                </td>
                <td class="receipt-badge-box">
                    <div class="receipt-no-label">Receipt Number</div>
                    <div class="receipt-no-val">{{ $payment->receipt_no }}</div>
                    <div style="font-size: 9px; color: #c7d2fe; margin-top: 3px;">
                        {{ $payment->paid_on?->format('d M Y') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Student & Payment Info Card -->
    <div class="meta-card">
        <table class="meta">
            <tr>
                <td style="width: 32%;">
                    <div class="meta-label">Student Information</div>
                    <div style="margin-top: 4px;">
                        @if ($studentPhotoBase64)
                            <div class="student-photo-box">
                                <img src="{{ $studentPhotoBase64 }}" alt="Student">
                            </div>
                        @endif
                        <div style="display: inline-block; vertical-align: middle;">
                            <div class="meta-value">{{ $payment->student?->name }}</div>
                            <div class="meta-sub">{{ \App\Services\PhoneFormatter::display($payment->student?->phone) }}</div>
                        </div>
                    </div>
                </td>
                <td style="width: 26%;">
                    <div class="meta-label">Enrolled Batch</div>
                    <div class="meta-value">{{ $payment->student?->batch?->name ?? 'Unassigned' }}</div>
                    <div class="meta-sub">{{ $payment->student?->batch?->code ?? '' }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="meta-label">Billing Month</div>
                    <div class="meta-value">{{ $payment->for_month }}</div>
                    <div class="meta-sub">Paid on {{ $payment->paid_on?->format('d M Y') }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="meta-label">Payment Method</div>
                    <div class="meta-value">{{ ucfirst($payment->method) }}</div>
                    <div class="meta-sub">{{ $payment->reference ? 'Ref: ' . $payment->reference : 'No reference' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Amount Hero Card -->
    <div class="amount-box">
        <table class="amount-table">
            <tr>
                <td>
                    <div class="amount-hero-label">Amount Received</div>
                    <div class="amount-hero-value">{{ money((float) $payment->amount, 0) }}</div>
                </td>
                <td style="width: 40%;" class="balance-box">
                    <div class="balance-label">Remaining Student Balance</div>
                    <div class="balance-val">{{ money($payment->student?->remaining_balance ?? 0, 0) }}</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">
                        Net Fee: {{ money($payment->student?->net_fee ?? 0, 0) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Allocation Breakdown Section -->
    <div class="section-header">
        <div class="section-title">Installment Allocation Breakdown</div>
    </div>

    @if ($payment->allocations->isEmpty())
        <div class="note-card" style="border-left-color: #94a3b8;">
            No installment allocations recorded for this receipt.
        </div>
    @else
        <table class="alloc">
            <thead>
                <tr>
                    <th style="width: 25%;">Installment</th>
                    <th style="width: 22%;">Due Date</th>
                    <th style="width: 20%; text-align: right;">Scheduled</th>
                    <th style="width: 20%; text-align: right;">Allocated</th>
                    <th style="width: 13%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payment->allocations as $alloc)
                    <tr>
                        <td><strong>Month {{ $alloc->installment?->number }}</strong></td>
                        <td>{{ $alloc->installment?->due_date?->format('d M Y') }}</td>
                        <td class="num" style="font-weight: normal; color: #64748b;">
                            {{ money((float) ($alloc->installment?->amount ?? 0), 0) }}
                        </td>
                        <td class="num" style="color: #047857;">
                            {{ money((float) $alloc->amount, 0) }}
                        </td>
                        <td style="text-align: center;">
                            <span class="status status-{{ $alloc->installment?->status ?? 'pending' }}">
                                {{ ucfirst($alloc->installment?->status ?? 'pending') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($payment->note)
        <div class="note-card">
            <strong>Remark / Note:</strong> {{ $payment->note }}
        </div>
    @endif

    <!-- Footer & Official Verification -->
    <div class="footer-wrap">
        <table class="footer-table">
            <tr>
                <td class="footer-info">
                    <strong>Issued by:</strong> {{ $payment->receiver?->name ?? 'System' }} &middot; Generated on {{ now()->format('d M Y, h:i A') }}<br>
                    @if ($footerNote = trim((string) \App\Models\Setting::get('receipt_footer')))
                        {{ $footerNote }}<br>
                    @endif
                    This is a computer-generated receipt issued by {{ academy_name() }}. No physical signature is required.
                </td>
                <td style="width: 170px; text-align: right;">
                    <div class="verification-badge">
                        <div class="verification-title">Official Receipt</div>
                        <div class="verification-text">&#10003; PAYMENT VERIFIED</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
