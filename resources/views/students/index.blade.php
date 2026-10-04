<x-app-layout>
    <div class="space-y-6">

        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Students</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage student enrollments, fee plans, collections, and dues.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('students.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>Enroll New Student</span>
                </a>
            </div>
        </div>

        <!-- Bento KPI Stat Tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-tile title="Total Students" :value="$stats['total']" variant="indigo" />
            <x-stat-tile title="Active Students" :value="$stats['active']" variant="emerald" />
            <x-stat-tile title="Overdue Defaulters" :value="$stats['defaulters']" variant="rose" />
            <x-stat-tile title="Total Receivable" :value="money($stats['total_receivable'], 0)" variant="amber" />
        </div>

        <!-- Filters & Search Bar -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
            <form method="GET" action="{{ route('students.index') }}" class="flex flex-wrap items-center gap-3">

                <!-- Search Input -->
                <div class="flex-1 min-w-[220px] relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search name, phone, email..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    </div>
                </div>

                <!-- Batch Select -->
                <select
                    name="batch_id"
                    onchange="this.form.submit()"
                    class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">All Batches</option>
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->code }} - {{ $b->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Fee Status Select -->
                <select
                    name="fee_status"
                    onchange="this.form.submit()"
                    class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">All Fee Statuses</option>
                    <option value="cleared" {{ request('fee_status') === 'cleared' ? 'selected' : '' }}>Cleared (Paid Off)</option>
                    <option value="partial" {{ request('fee_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="pending" {{ request('fee_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="overdue" {{ request('fee_status') === 'overdue' ? 'selected' : '' }}>Overdue (Defaulter)</option>
                </select>

                <!-- Fee Type Select -->
                <select
                    name="fee_type"
                    onchange="this.form.submit()"
                    class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">All Plans</option>
                    <option value="installments" {{ request('fee_type') === 'installments' ? 'selected' : '' }}>Installments</option>
                    <option value="full" {{ request('fee_type') === 'full' ? 'selected' : '' }}>Full Payment</option>
                </select>

                <!-- Status Select -->
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="dropped" {{ request('status') === 'dropped' ? 'selected' : '' }}>Dropped</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                </select>

                @if(request()->hasAny(['search', 'batch_id', 'fee_status', 'fee_type', 'status']))
                    <a href="{{ route('students.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                        Reset Filters
                    </a>
                @endif
            </form>
        </div>

        <!-- Students Table -->
        <x-table>
            <x-slot:header>
                <tr>
                    <th class="px-6 py-3.5">Student</th>
                    <th class="px-6 py-3.5 hidden md:table-cell">Batch</th>
                    <th class="px-6 py-3.5">Phone / WhatsApp</th>
                    <th class="px-6 py-3.5 hidden md:table-cell">Net Fee</th>
                    <th class="px-6 py-3.5">Paid</th>
                    <th class="px-6 py-3.5">Balance</th>
                    <th class="px-6 py-3.5 hidden lg:table-cell">Fee Status</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </x-slot:header>

            @forelse ($students as $student)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$student->name" :photo="$student->photo_url" size="sm" />
                            <div>
                                <a href="{{ route('students.show', $student) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 transition-colors">
                                    {{ $student->name }}
                                </a>
                                <div class="text-xs text-slate-400 capitalize">{{ $student->fee_type }} &middot; {{ $student->status }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 hidden md:table-cell">
                        @if ($student->batch)
                            <a href="{{ route('batches.show', $student->batch) }}" class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $student->batch->code }}
                            </a>
                        @else
                            <span class="text-xs text-slate-400">None</span>
                        @endif
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
                                class="inline-flex items-center justify-center p-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                title="Open WhatsApp reminder chat"
                            >
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-4 num-tabular font-semibold text-slate-800 dark:text-white hidden md:table-cell">
                        {{ money($student->net_fee, 0) }}
                    </td>
                    <td class="px-6 py-4 num-tabular font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ money($student->total_paid, 0) }}
                    </td>
                    <td class="px-6 py-4 num-tabular font-semibold {{ $student->remaining_balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                        {{ money($student->remaining_balance, 0) }}
                    </td>
                    <td class="px-6 py-4 hidden lg:table-cell">
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
                    <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                        <x-empty-state title="No students found" description="Try adjusting your filters or enroll a new student.">
                            <x-slot:action>
                                <a href="{{ route('students.create') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-sm">
                                    Enroll Student
                                </a>
                            </x-slot:action>
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse

            @if ($students->hasPages())
                <x-slot:pagination>
                    {{ $students->links() }}
                </x-slot:pagination>
            @endif
        </x-table>

    </div>
</x-app-layout>
