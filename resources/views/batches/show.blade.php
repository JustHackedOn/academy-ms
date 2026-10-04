<x-app-layout>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('batches.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>All Batches</span>
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $batch->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-lg font-mono text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        {{ $batch->code }}
                    </span>
                    <x-badge :status="$batch->status" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('batches.edit', $batch) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    <span>Edit Batch</span>
                </a>

                @if (auth()->user()?->isAdmin())
                    <form method="POST" action="{{ route('batches.destroy', $batch) }}" class="inline"
                          onsubmit="return confirm('Delete batch {{ $batch->code }} — {{ $batch->name }}? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-950/70 transition-colors shadow-2xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            <span>Delete</span>
                        </button>
                    </form>
                @endif

                <a href="{{ route('students.create', ['batch_id' => $batch->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>Enroll Student</span>
                </a>
            </div>
        </div>

        <!-- Bento Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-tile title="Total Expected" :value="money($totalExpected, 0)" subtitle="Combined net fees" variant="indigo" />
            <x-stat-tile title="Total Collected" :value="money($totalCollected, 0)" :subtitle="$collectionRate . '% collected'" variant="emerald" />
            <x-stat-tile title="Remaining Pending" :value="money($totalPending, 0)" subtitle="To be collected" variant="amber" />
            <x-stat-tile title="Enrolled Students" :value="$batch->students->count()" subtitle="Duration: {$batch->duration_months} Months" variant="violet" />
        </div>

        <!-- Collection Progress Card -->
        <x-card>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700 dark:text-slate-300">Overall Batch Collection Completion</span>
                    <span class="font-extrabold text-indigo-600 dark:text-indigo-400 num-tabular">{{ $collectionRate }}%</span>
                </div>
                <x-progress :value="$collectionRate" variant="indigo" size="md" />
                <div class="flex justify-between text-xs text-slate-500 pt-1">
                    <span>Timeline: {{ $batch->start_date?->format('M d, Y') }} — {{ $batch->end_date?->format('M d, Y') }}</span>
                    <span>Default Fee: {{ money($batch->default_fee, 0) }}</span>
                </div>
            </div>
        </x-card>

        <!-- Enrolled Students Table -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Enrolled Students ({{ $batch->students->count() }})</h2>
                    <p class="text-xs text-slate-500">Student fee profiles, payment statuses, and reminders for this batch.</p>
                </div>

                <!-- Search inside batch -->
                <form method="GET" action="{{ route('batches.show', $batch) }}" class="flex items-center gap-2">
                    <input
                        type="text"
                        name="student_search"
                        value="{{ request('student_search') }}"
                        placeholder="Search student or phone..."
                        class="px-3 py-1.5 rounded-xl text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    />
                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">
                        Filter
                    </button>
                </form>
            </div>

            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Phone / WhatsApp</th>
                        <th class="px-6 py-3.5">Fee Type</th>
                        <th class="px-6 py-3.5">Net Fee</th>
                        <th class="px-6 py-3.5">Paid</th>
                        <th class="px-6 py-3.5">Remaining</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </x-slot:header>

                @forelse ($batch->students as $student)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$student->name" size="sm" />
                                <div>
                                    <a href="{{ route('students.show', $student) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 transition-colors">
                                        {{ $student->name }}
                                    </a>
                                    <div class="text-xs text-slate-400">Joined {{ $student->join_date?->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-slate-700 dark:text-slate-300">
                                    {{ \App\Services\PhoneFormatter::display($student->phone) }}
                                </span>
                                <a
                                    href="{{ $student->getWhatsAppUrl() }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center justify-center p-1 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                    title="Open WhatsApp chat"
                                >
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 capitalize text-xs font-medium text-slate-600 dark:text-slate-400">
                            {{ $student->fee_type }}
                        </td>
                        <td class="px-6 py-4 num-tabular font-semibold text-slate-800 dark:text-white">
                            {{ money($student->net_fee, 0) }}
                        </td>
                        <td class="px-6 py-4 num-tabular font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ money($student->total_paid, 0) }}
                        </td>
                        <td class="px-6 py-4 num-tabular font-semibold {{ $student->remaining_balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                            {{ money($student->remaining_balance, 0) }}
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :status="$student->fee_status" />
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('students.show', $student) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Profile
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                            No students enrolled in this batch yet.
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

    </div>
</x-app-layout>
