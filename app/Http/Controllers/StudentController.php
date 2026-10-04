<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Student;
use App\Services\FeePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::with(['batch', 'installments', 'payments']);

        // Filter: Batch
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: Fee Type
        if ($request->filled('fee_type')) {
            $query->where('fee_type', $request->fee_type);
        }

        // Search: Name, Phone, Email
        if ($request->filled('search')) {
            $search = escape_like($request->search);
            $digits = preg_replace('/[^\d]/', '', $request->search);

            $query->where(function ($q) use ($search, $digits) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if (! empty($digits)) {
                    $q->orWhere('phone', 'like', "%{$digits}%");
                }
            });
        }

        $perPage = 15;

        // Filter: Fee Status in collection (since fee_status is computed from installments & payments)
        if ($request->filled('fee_status')) {
            $allStudents = $query->latest('join_date')->get();
            $feeStatus = $request->fee_status;
            $filtered = $allStudents->filter(function ($s) use ($feeStatus) {
                return $s->fee_status === $feeStatus;
            });

            $page = (int) $request->input('page', 1);
            $total = $filtered->count();
            $paginated = new LengthAwarePaginator(
                $filtered->forPage($page, $perPage)->values(),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $paginated = $query->latest('join_date')->paginate($perPage)->withQueryString();
        }

        $batches = Batch::orderBy('name')->get();

        $stats = [
            'total' => Student::count(),
            'active' => Student::active()->count(),
            'defaulters' => Student::defaulters()->count(),
            'total_receivable' => (float) DB::table('students')
                ->selectRaw('COALESCE(SUM(CASE WHEN net_fee > COALESCE((SELECT SUM(amount) FROM payments WHERE payments.student_id = students.id AND payments.deleted_at IS NULL), 0) THEN net_fee - (SELECT SUM(amount) FROM payments WHERE payments.student_id = students.id AND payments.deleted_at IS NULL) ELSE 0 END), 0) as remaining')
                ->whereNull('deleted_at')
                ->value('remaining'),
        ];

        return view('students.index', [
            'students' => $paginated,
            'batches' => $batches,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Student::class);

        $batches = Batch::whereIn('status', ['running', 'upcoming'])->orderBy('name')->get();
        if ($batches->isEmpty()) {
            $batches = Batch::orderBy('name')->get();
        }

        $selectedBatch = null;
        if ($request->filled('batch_id')) {
            $selectedBatch = Batch::find($request->batch_id);
        }

        return view('students.create', compact('batches', 'selectedBatch'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $data = $request->validated();
        $discount = (float) ($data['discount'] ?? 0);
        $totalFee = (float) $data['total_fee'];
        $netFee = max(0, $totalFee - $discount);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students', 'public');
        }

        try {
            $student = DB::transaction(function () use ($data, $netFee, $discount, $photoPath) {
                $student = Student::create([
                    'batch_id' => $data['batch_id'],
                    'name' => $data['name'],
                    'photo' => $photoPath,
                    'phone' => $data['phone'],
                    'alt_phone' => $data['alt_phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'fee_type' => $data['fee_type'],
                    'total_fee' => $data['total_fee'],
                    'discount' => $discount,
                    'net_fee' => $netFee,
                    'join_date' => $data['join_date'],
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]);

                FeePlanService::createInstallments($student, $data['installments']);

                ActivityLog::log('created_student', $student, null, $student->toArray());

                return $student;
            });
        } catch (\Throwable $e) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $e;
        }

        return redirect()->route('students.show', $student)
            ->with('success', "Student '{$student->name}' enrolled with ".count($data['installments']).' installment(s).');
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        $student->load([
            'batch',
            'installments.allocations.payment',
            'payments.receiver',
            'notesList.user',
        ]);

        return view('students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        $batches = Batch::orderBy('name')->get();
        $hasPayments = $student->payments()->count() > 0;

        return view('students.edit', compact('student', 'batches', 'hasPayments'));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $old = $student->toArray();
        $data = $request->validated();

        $previousPhoto = $student->photo;
        $photoPath = $previousPhoto;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $photoPath = null;
        }

        try {
            DB::transaction(function () use ($student, $data, $photoPath) {
                // Check if fee numbers changed
                $totalFee = isset($data['total_fee']) ? (float) $data['total_fee'] : (float) $student->total_fee;
                $discount = isset($data['discount']) ? (float) $data['discount'] : (float) $student->discount;
                $newNetFee = max(0, $totalFee - $discount);

                $feeChanged = abs($newNetFee - (float) $student->net_fee) > 0.01;

                $student->update([
                    'batch_id' => $data['batch_id'],
                    'name' => $data['name'],
                    'photo' => $photoPath,
                    'phone' => $data['phone'],
                    'alt_phone' => $data['alt_phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'total_fee' => $totalFee,
                    'discount' => $discount,
                    'net_fee' => $newNetFee,
                ]);

                if ($feeChanged) {
                    FeePlanService::recalculateUnpaidInstallments($student, $newNetFee);
                }
            });
        } catch (\Throwable $e) {
            if ($photoPath && $photoPath !== $previousPhoto) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $e;
        }

        if ($previousPhoto && $previousPhoto !== $photoPath) {
            Storage::disk('public')->delete($previousPhoto);
        }

        ActivityLog::log('updated_student', $student, $old, $student->fresh()->toArray());

        return redirect()->route('students.show', $student)
            ->with('success', "Student '{$student->name}' updated successfully.");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $old = $student->toArray();
        $student->delete();

        ActivityLog::log('deleted_student', $student, $old, null);

        return redirect()->route('students.index')
            ->with('success', "Student '{$student->name}' archived successfully.");
    }

    /**
     * API preview for Alpine fee-plan calculation.
     */
    public function calculateFeePlan(Request $request): JsonResponse
    {
        $netFee = (float) $request->input('net_fee', 0);
        $feeType = $request->input('fee_type', 'installments');
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $count = (int) $request->input('count', 4);

        $schedule = FeePlanService::calculateSchedule($netFee, $feeType, $startDate, $count);

        return response()->json([
            'schedule' => $schedule,
            'sum' => array_sum(array_column($schedule, 'amount')),
        ]);
    }
}
