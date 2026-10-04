<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Setting;
use App\Models\Student;
use App\Services\WhatsAppLinkBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuesController extends Controller
{
    /**
     * Defaulters list sorted by days overdue (desc) with WhatsApp reminders.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::with(['batch', 'installments', 'notesList'])
            ->active()
            ->whereHas('installments', fn ($q) => $q->overdue());

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        $students = $query->get()
            ->sortByDesc('days_overdue')
            ->values();

        // Attach derived fields used by the view
        $defaulters = $students->map(function ($student) {
            $overdueInsts = $student->installments
                ->filter(fn ($i) => $i->status === 'overdue');

            $promise = $student->notesList
                ->first(fn ($n) => $n->type === 'promise' && $n->promise_date);

            return [
                'student' => $student,
                'overdue_amount' => $student->overdue_amount,
                'days_overdue' => $student->days_overdue,
                'overdue_count' => $overdueInsts->count(),
                'oldest_due' => $overdueInsts->min('due_date'),
                'promise_date' => $promise?->promise_date,
                'template' => WhatsAppLinkBuilder::build($student),
                'template_roman' => WhatsAppLinkBuilder::build(
                    $student,
                    Setting::get('whatsapp_template_roman', WhatsAppLinkBuilder::DEFAULT_TEMPLATE_URDU)
                ),
            ];
        });

        $totals = [
            'students' => $defaulters->count(),
            'amount' => (float) $defaulters->sum('overdue_amount'),
            'max_days' => (int) $defaulters->max('days_overdue'),
        ];

        $batches = Batch::orderBy('name')->get();

        return view('dues.index', compact('defaulters', 'totals', 'batches'));
    }
}
