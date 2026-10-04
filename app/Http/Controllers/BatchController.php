<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Models\ActivityLog;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::withCount(['students'])
            ->with(['students.payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = escape_like($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $batches = $query->latest('start_date')->paginate(12)->withQueryString();

        $stats = [
            'total' => Batch::count(),
            'running' => Batch::running()->count(),
            'upcoming' => Batch::upcoming()->count(),
            'completed' => Batch::completed()->count(),
        ];

        return view('batches.index', compact('batches', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Batch::class);

        return view('batches.create');
    }

    public function store(StoreBatchRequest $request): RedirectResponse
    {
        $this->authorize('create', Batch::class);

        $data = $request->validated();
        $startDate = Carbon::parse($data['start_date']);
        $data['end_date'] = $startDate->copy()->addMonthsNoOverflow((int) $data['duration_months'])->format('Y-m-d');

        $batch = Batch::create($data);

        ActivityLog::log('created_batch', $batch, null, $batch->toArray());

        return redirect()->route('batches.show', $batch)
            ->with('success', "Batch '{$batch->name}' created successfully.");
    }

    public function show(Batch $batch, Request $request): View
    {
        $this->authorize('view', $batch);

        $batch->load(['students' => function ($q) use ($request) {
            if ($request->filled('student_search')) {
                $s = escape_like($request->student_search);
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }
            if ($request->filled('student_status')) {
                $q->where('status', $request->student_status);
            }
            $q->with(['installments', 'payments']);
        }]);

        $totalExpected = $batch->total_expected;
        $totalCollected = $batch->total_collected;
        $totalPending = max(0, $totalExpected - $totalCollected);
        $collectionRate = $batch->collection_rate;

        return view('batches.show', compact('batch', 'totalExpected', 'totalCollected', 'totalPending', 'collectionRate'));
    }

    public function edit(Batch $batch): View
    {
        $this->authorize('update', $batch);

        return view('batches.edit', compact('batch'));
    }

    public function update(UpdateBatchRequest $request, Batch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);

        $old = $batch->toArray();
        $data = $request->validated();
        $startDate = Carbon::parse($data['start_date']);
        $data['end_date'] = $startDate->copy()->addMonthsNoOverflow((int) $data['duration_months'])->format('Y-m-d');

        $batch->update($data);

        ActivityLog::log('updated_batch', $batch, $old, $batch->toArray());

        return redirect()->route('batches.show', $batch)
            ->with('success', "Batch '{$batch->name}' updated successfully.");
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        $this->authorize('delete', $batch);

        $studentsCount = $batch->students()->count();
        if ($studentsCount > 0) {
            return redirect()->route('batches.index')
                ->with('error', "Cannot delete '{$batch->name}': {$studentsCount} student(s) are still enrolled. Reassign or archive them first.");
        }

        $old = $batch->toArray();
        $batch->delete();

        ActivityLog::log('deleted_batch', $batch, $old, null);

        return redirect()->route('batches.index')
            ->with('success', "Batch '{$batch->name}' deleted successfully.");
    }
}
