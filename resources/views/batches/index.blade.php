<x-app-layout>
    <div class="space-y-6">

        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Batches</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage academy courses, duration, schedules, and collections.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('batches.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>Create Batch</span>
                </a>
            </div>
        </div>

        <!-- Bento KPI Stat Tiles -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-stat-tile title="Total Batches" :value="$stats['total']" variant="indigo" />
            <x-stat-tile title="Running" :value="$stats['running']" variant="emerald" />
            <x-stat-tile title="Upcoming" :value="$stats['upcoming']" variant="sky" />
            <x-stat-tile title="Completed" :value="$stats['completed']" variant="violet" />
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <!-- Status Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                <a href="{{ route('batches.index') }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap {{ !request('status') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    All
                </a>
                <a href="{{ route('batches.index', ['status' => 'running']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap {{ request('status') === 'running' ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Running
                </a>
                <a href="{{ route('batches.index', ['status' => 'upcoming']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap {{ request('status') === 'upcoming' ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Upcoming
                </a>
                <a href="{{ route('batches.index', ['status' => 'completed']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap {{ request('status') === 'completed' ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Completed
                </a>
            </div>

            <!-- Search Field -->
            <form method="GET" action="{{ route('batches.index') }}" class="w-full sm:w-72 relative">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search name or code..."
                    class="w-full pl-9 pr-4 py-1.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>
            </form>
        </div>

        <!-- Batches Table -->
        <div>
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3.5">Code & Name</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Default Fee</th>
                        <th class="px-6 py-3.5 hidden md:table-cell">Duration</th>
                        <th class="px-6 py-3.5">Students</th>
                        <th class="px-6 py-3.5 hidden lg:table-cell">Collection Rate</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </x-slot:header>

                @forelse ($batches as $batch)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('batches.show', $batch) }}" class="hover:text-indigo-600">
                                    {{ $batch->name }}
                                </a>
                            </div>
                            <span class="text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ $batch->code }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :status="$batch->status" />
                        </td>
                        <td class="px-6 py-4 num-tabular font-semibold text-slate-800 dark:text-white">
                            {{ money($batch->default_fee, 0) }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500 hidden md:table-cell">
                            {{ $batch->duration_months }} Months ({{ $batch->start_date?->format('M Y') }})
                        </td>
                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">
                            {{ $batch->students_count }}
                        </td>
                        <td class="px-6 py-4 hidden lg:table-cell">
                            <div class="w-32">
                                <div class="flex justify-between text-xs mb-1 num-tabular">
                                    <span>{{ money($batch->total_collected, 0) }}</span>
                                    <span class="font-bold text-indigo-600">{{ $batch->collection_rate }}%</span>
                                </div>
                                <x-progress :value="$batch->collection_rate" size="sm" />
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('batches.show', $batch) }}" class="text-xs font-semibold text-indigo-600 hover:underline">View</a>
                            <a href="{{ route('batches.edit', $batch) }}" class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:underline">Edit</a>
                            @if (auth()->user()?->isAdmin())
                                <form method="POST" action="{{ route('batches.destroy', $batch) }}" class="inline"
                                      onsubmit="return confirm('Delete batch {{ $batch->code }} — {{ $batch->name }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                            No batches found.
                        </td>
                    </tr>
                @endforelse

                @if ($batches->hasPages())
                    <x-slot:pagination>
                        {{ $batches->links() }}
                    </x-slot:pagination>
                @endif
            </x-table>
        </div>

    </div>
</x-app-layout>
