<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global Ctrl/Cmd+K search: students by name/phone, batches, and receipts.
     * Returns a compact JSON payload consumed by the search modal.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['query' => $term, 'groups' => []]);
        }

        $digits = preg_replace('/[^\d]/', '', $term);
        $escapedTerm = escape_like($term);

        $students = Student::with(['batch', 'installments', 'payments'])
            ->where(function ($q) use ($escapedTerm, $digits) {
                $q->where('name', 'like', "%{$escapedTerm}%")
                    ->orWhere('email', 'like', "%{$escapedTerm}%");

                if ($digits !== '') {
                    $q->orWhere('phone', 'like', "%{$digits}%")
                        ->orWhere('alt_phone', 'like', "%{$digits}%");
                }
            })
            ->orderBy('name')
            ->take(6)
            ->get()
            ->map(fn ($s) => [
                'label' => $s->name,
                'sub' => trim(($s->batch?->code ? $s->batch->code . ' · ' : '') . \App\Services\PhoneFormatter::display($s->phone)),
                'meta' => money($s->remaining_balance, 0) . ' due',
                'status' => $s->fee_status,
                'url' => route('students.show', $s),
            ]);

        $batches = Batch::where(function ($q) use ($escapedTerm) {
            $q->where('name', 'like', "%{$escapedTerm}%")
                ->orWhere('code', 'like', "%{$escapedTerm}%");
        })
            ->orderBy('name')
            ->take(4)
            ->get()
            ->map(fn ($b) => [
                'label' => $b->name,
                'sub' => $b->code,
                'meta' => $b->duration_months . ' months',
                'status' => $b->status,
                'url' => route('batches.show', $b),
            ]);

        $receipts = Payment::with('student')
            ->where(function ($q) use ($escapedTerm) {
                $q->where('receipt_no', 'like', "%{$escapedTerm}%")
                    ->orWhere('reference', 'like', "%{$escapedTerm}%");
            })
            ->latest('paid_on')
            ->take(4)
            ->get()
            ->map(fn ($p) => [
                'label' => $p->receipt_no,
                'sub' => ($p->student?->name ?? 'Unknown') . ' · ' . $p->paid_on?->format('d M Y'),
                'meta' => money($p->amount, 0),
                'status' => 'paid',
                'url' => route('payments.receipt', $p),
            ]);

        return response()->json([
            'query' => $term,
            'groups' => [
                ['title' => 'Students', 'key' => 'students', 'items' => $students],
                ['title' => 'Batches', 'key' => 'batches', 'items' => $batches],
                ['title' => 'Receipts', 'key' => 'receipts', 'items' => $receipts],
            ],
        ]);
    }
}
