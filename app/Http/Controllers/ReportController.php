<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Services\MonthResolver;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Report hub: monthly summary, batch performance, and export actions.
     */
    public function index(Request $request): View
    {
        $month = MonthResolver::normalize($request->query('month'));

        $monthly = ReportService::monthlySummary($month);
        $batchReport = ReportService::batchSummary(
            $request->filled('batch_id') ? (int) $request->batch_id : null
        );

        $filters = $this->filters($request);

        $payments = ReportService::paymentsQuery($filters)->paginate(15, ['*'], 'payments_page')->withQueryString();
        $students = ReportService::studentsQuery($filters)->paginate(15, ['*'], 'students_page')->withQueryString();

        return view('reports.index', [
            'month' => $month,
            'monthly' => $monthly,
            'batchReport' => $batchReport,
            'payments' => $payments,
            'students' => $students,
            'batches' => Batch::orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $filters,
            'paymentTotal' => (float) ReportService::paymentsQuery($filters)->sum('amount'),
        ]);
    }

    /**
     * Monthly report as a print-ready PDF.
     */
    public function monthlyPdf(Request $request): Response
    {
        $month = MonthResolver::normalize($request->query('month'));
        $data = ReportService::monthlySummary($month);

        $pdf = Pdf::loadView('reports.pdf.monthly', $data)->setPaper('a4', 'portrait');

        return $pdf->download("monthly-report-{$month}.pdf");
    }

    /**
     * Batch performance report as a print-ready PDF.
     */
    public function batchPdf(Request $request): Response
    {
        $data = ReportService::batchSummary(
            $request->filled('batch_id') ? (int) $request->batch_id : null
        );

        $pdf = Pdf::loadView('reports.pdf.batches', $data)->setPaper('a4', 'portrait');

        return $pdf->download('batch-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * CSV export: students, payments or batch summary.
     */
    public function csv(Request $request, string $type): StreamedResponse
    {
        $filters = $this->filters($request);

        abort_unless(in_array($type, ['students', 'payments', 'batches'], true), 404);

        if ($type === 'students') {
            $rows = ReportService::studentRows(ReportService::studentsQuery($filters)->get());
            $headings = ['Name', 'Phone', 'Batch', 'Status', 'Fee Type', 'Net Fee', 'Paid', 'Balance', 'Overdue', 'Days Overdue', 'Fee Status', 'Join Date'];
            $filename = 'students-' . now()->format('Y-m-d') . '.csv';
        } elseif ($type === 'payments') {
            $rows = ReportService::paymentRows(ReportService::paymentsQuery($filters)->get());
            $headings = ['Receipt No', 'Date', 'Student', 'Batch', 'Amount', 'Method', 'Reference', 'Note', 'Received By'];
            $filename = 'payments-' . now()->format('Y-m-d') . '.csv';
        } else {
            $rows = ReportService::batchSummary($filters['batch_id'] ?? null)['rows'];
            $headings = ['Batch', 'Code', 'Status', 'Start', 'End', 'Students', 'Expected', 'Collected', 'Remaining', 'Overdue', 'Rate %'];
            $rows = array_map(fn ($r) => [
                $r['name'], $r['code'], $r['status'], $r['start_date'], $r['end_date'],
                $r['students'], $r['expected'], $r['collected'], $r['remaining'], $r['overdue'], $r['rate'],
            ], $rows);
            $filename = 'batches-' . now()->format('Y-m-d') . '.csv';
        }

        $csv = ReportService::toCsv($headings, $rows);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Extract and whitelist the report filter query string.
     */
    protected function filters(Request $request): array
    {
        return [
            'from' => $request->filled('from') ? $request->date('from')?->toDateString() : null,
            'to' => $request->filled('to') ? $request->date('to')?->toDateString() : null,
            'method' => $request->filled('method') ? $request->method : null,
            'batch_id' => $request->filled('batch_id') ? (int) $request->batch_id : null,
            'student_id' => $request->filled('student_id') ? (int) $request->student_id : null,
            'status' => in_array($request->status, ['active', 'paused', 'completed', 'dropped'], true) ? $request->status : null,
            'overdue' => $request->overdue === '1' ? '1' : null,
        ];
    }
}
