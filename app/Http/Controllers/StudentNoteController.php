<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentNoteController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('create', StudentNote::class);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:note,call,follow_up,promise'],
            'promise_date' => ['nullable', 'date'],
        ]);

        $note = StudentNote::create([
            'student_id' => $student->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'type' => $validated['type'],
            'promise_date' => $validated['promise_date'] ?? null,
        ]);

        ActivityLog::log('added_student_note', $student, null, [
            'type' => $note->type,
            'promise_date' => $note->promise_date?->format('Y-m-d'),
        ]);

        return back()->with('success', 'Student timeline entry recorded.');
    }

    public function destroy(StudentNote $studentNote): RedirectResponse
    {
        $this->authorize('delete', $studentNote);

        $studentNote->delete();
        return back()->with('success', 'Note removed.');
    }
}
