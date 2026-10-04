<x-app-layout>
    @php
        $collectionPct = $expected > 0 ? min(100, round(($received / $expected) * 100, 1)) : ($received > 0 ? 100 : 0);
        $cashInHand = $received - $expenses;
    @endphp

    <div class="space-y-5 sm:space-y-6">

        <!-- ============ BENTO ROW 1: HERO (8) + RING (4) ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

            <!-- Hero: This Month Expected vs Received -->
            <div class="lg:col-span-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-900 to-violet-900 text-white p-6 sm:p-8 shadow-xl shadow-indigo-950/20 border border-indigo-800/50">
                <div class="absolute -right-16 -top-16 w-72 h-72 bg-violet-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-10 -bottom-24 w-64 h-64 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between gap-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-semibold backdrop-blur-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            {{ $month_name }}
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wider text-indigo-300 font-semibold">Net Position</p>
                            <p class="text-sm font-bold num-tabular {{ $net_profit >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                {{ $net_profit >= 0 ? '+' : '-' }}{{ money(abs($net_profit), 0) }}
                            </p>
                        </div>
                    </div>

                    <h1 class="mt-4 text-xs font-semibold uppercase tracking-wider text-indigo-200">Expected vs Received</h1>

                    <div class="mt-2 flex flex-wrap items-end gap-x-6 gap-y-3">
                        <div>
                            <p class="text-3xl sm:text-5xl font-extrabold tracking-tight num-tabular leading-none"
                               x-data="countUp({{ (int) $received }})">
                                <span>{{ currency() }} </span><span x-text="formatted">0</span>
                            </p>
                            <p class="text-[11px] text-indigo-300 mt-1.5">Received in {{ $month_name }}</p>
                        </div>

                        <div class="sm:border-l sm:border-white/15 sm:pl-6">
                            <p class="text-lg sm:text-2xl font-bold num-tabular text-indigo-200/90 leading-none">{{ money($expected, 0) }}</p>
                            <p class="text-[11px] text-indigo-300 mt-1.5">Expected (installments due)</p>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between text-[11px] font-semibold mb-2">
                            <span class="text-indigo-200">{{ $collectionPct }}% collected</span>
                            <span class="text-indigo-300 num-tabular">{{ money(max(0, $expected - $received), 0) }} outstanding</span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-teal-300 transition-all duration-1000 ease-out"
                                 style="width: {{ $collectionPct }}%"></div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-3 sm:gap-4">
                        <div class="rounded-2xl bg-white/5 border border-white/10 px-3.5 py-3 backdrop-blur-sm">
                            <p class="text-[10px] uppercase tracking-wider text-indigo-300 font-semibold">Pending</p>
                            <p class="text-base font-bold num-tabular mt-0.5">{{ money($pending, 0) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 border border-white/10 px-3.5 py-3 backdrop-blur-sm">
                            <p class="text-[10px] uppercase tracking-wider text-indigo-300 font-semibold">Carried Overdue</p>
                            <p class="text-base font-bold num-tabular mt-0.5 text-rose-300">{{ money($carried_overdue, 0) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 border border-white/10 px-3.5 py-3 backdrop-blur-sm">
                            <p class="text-[10px] uppercase tracking-wider text-indigo-300 font-semibold">Cash In Hand</p>
                            <p class="text-base font-bold num-tabular mt-0.5 {{ $cashInHand >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ money($cashInHand, 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Collection Rate Ring (4 cols) -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-300 flex flex-col">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">Collection Rate</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Received vs expected · {{ $month_name }}</p>
                    </div>
                    <x-badge :status="$collectionPct >= 90 ? 'paid' : ($collectionPct >= 60 ? 'partial' : 'overdue')" size="sm" />
                </div>

                <div class="flex-1 flex items-center justify-center py-4">
                    <div class="relative w-44 h-44">
                        <canvas id="collectionRing"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-4xl font-extrabold num-tabular text-slate-900 dark:text-white leading-none">{{ round($collectionPct) }}%</span>
                            <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mt-1">collected</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Received</p>
                        <p class="text-sm font-bold num-tabular text-emerald-600 dark:text-emerald-400">{{ money($received, 0) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Gap</p>
                        <p class="text-sm font-bold num-tabular text-amber-600 dark:text-amber-400">{{ money(max(0, $expected - $received), 0) }}</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- ============ BENTO ROW 2: KPI TILES ============ -->
        @php
            $tiles = [
                ['label' => 'Pending',         'value' => $pending,         'hint' => currency() . ' due this month',   'tint' => 'amber',   'icon' => 'clock',  'href' => null],
                ['label' => 'Overdue',         'value' => $total_overdue,   'hint' => 'Total uncollected',   'tint' => 'rose',    'icon' => 'warn',   'href' => null],
                ['label' => 'Defaulters',      'value' => $defaulters_count,'hint' => 'Students behind',     'tint' => 'rose',    'icon' => 'users',  'href' => route('dues.index')],
                ['label' => 'Active Students', 'value' => $active_students, 'hint' => 'Enrolled & active',   'tint' => 'indigo',  'icon' => 'user',   'href' => route('students.index')],
                ['label' => 'Running Batches', 'value' => $running_batches, 'hint' => 'In progress',         'tint' => 'violet',  'icon' => 'folder', 'href' => route('batches.index')],
                ['label' => 'New Joins',       'value' => $new_joins,       'hint' => 'Joined this month',   'tint' => 'emerald', 'icon' => 'plus',   'href' => null],
            ];
            $tintMap = [
                'amber'   => 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
                'rose'    => 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
                'indigo'  => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400',
                'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400',
                'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
            ];
            $iconPaths = [
                'clock'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                'warn'   => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
                'users'  => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'user'   => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
                'folder' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
                'plus'   => 'M12 4.5v15m7.5-7.5h-15',
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
            @foreach ($tiles as $tile)
                @php $tag = $tile['href'] ? 'a' : 'div'; @endphp
                <{{ $tag }}
                    @if ($tile['href']) href="{{ $tile['href'] }}" @endif
                    class="group bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 block"
                >
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $tile['label'] }}</span>
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center {{ $tintMap[$tile['tint']] }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPaths[$tile['icon']] }}" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-lg font-extrabold num-tabular text-slate-900 dark:text-white leading-tight"
                       x-data="countUp({{ (int) $tile['value'] }})">
                        <span x-text="formatted">0</span>
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                        {{ $tile['hint'] }}
                        @if ($tile['href'])
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                        @endif
                    </p>
                </{{ $tag }}>
            @endforeach
        </div>


        <!-- ============ BENTO ROW 3: 12-MONTH TREND (8) + METHODS DONUT (4) ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

            <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">12-Month Revenue Trend</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Expected installments vs received payments</p>
                    </div>
                    <div class="flex items-center gap-4 text-[11px] font-semibold">
                        <span class="inline-flex items-center gap-1.5 text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Expected
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Received
                        </span>
                    </div>
                </div>
                <div class="h-64 sm:h-72">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">Payment Methods</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $month_name }} breakdown</p>
                </div>

                @php $methodTotal = array_sum($method_breakdown); @endphp

                @if ($methodTotal <= 0)
                    <div class="h-56 flex items-center justify-center text-center">
                        <div>
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                            </div>
                            <p class="text-xs text-slate-400">No payments recorded in this month.</p>
                        </div>
                    </div>
                @else
                    <div class="relative h-44">
                        <canvas id="methodsChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-lg font-extrabold num-tabular text-slate-900 dark:text-white leading-none">{{ number_format($methodTotal / 1000, 0) }}k</span>
                            <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400 mt-0.5">total</span>
                        </div>
                    </div>

                    <ul class="mt-5 space-y-2">
                        @php
                            $methodColors = ['cash' => 'bg-emerald-500', 'jazzcash' => 'bg-rose-500', 'easypaisa' => 'bg-amber-500', 'bank' => 'bg-indigo-500', 'other' => 'bg-slate-400'];
                        @endphp
                        @foreach ($method_breakdown as $method => $amount)
                            @continue($amount <= 0)
                            <li class="flex items-center justify-between text-xs">
                                <span class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300 capitalize">
                                    <span class="w-2 h-2 rounded-full {{ $methodColors[$method] ?? 'bg-slate-400' }}"></span>
                                    {{ $method }}
                                </span>
                                <span class="num-tabular font-bold text-slate-800 dark:text-white">
                                    {{ money($amount, 0) }}
                                    <span class="text-slate-400 font-normal">· {{ round(($amount / $methodTotal) * 100) }}%</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>


        <!-- ============ BENTO ROW 4: BATCH COLLECTION (7) + UPCOMING DUES (5) ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
                <div class="flex items-start justify-between gap-3 px-5 sm:px-6 pt-5 sm:pt-6 pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">Batch-wise Collection</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Collected vs remaining per active batch</p>
                    </div>
                    <a href="{{ route('batches.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">View all →</a>
                </div>

                <div class="overflow-x-auto border-t border-slate-200/80 dark:border-slate-800">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3">Batch</th>
                                <th class="px-5 py-3 text-right hidden sm:table-cell">Students</th>
                                <th class="px-5 py-3 text-right">Collected</th>
                                <th class="px-5 py-3 text-right">Expected</th>
                                <th class="px-5 py-3 hidden md:table-cell">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse ($batches_progress as $b)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('batches.show', $b['id']) }}" class="text-sm font-semibold text-slate-800 dark:text-white hover:text-indigo-600 transition-colors">
                                            {{ $b['name'] }}
                                        </a>
                                        <p class="text-[11px] text-slate-400 font-mono">{{ $b['code'] }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold num-tabular hidden sm:table-cell">{{ $b['students_count'] }}</td>
                                    <td class="px-5 py-3 text-right font-bold num-tabular text-emerald-600 dark:text-emerald-400">{{ money($b['collected'], 0) }}</td>
                                    <td class="px-5 py-3 text-right text-xs num-tabular text-slate-400">{{ money($b['expected'], 0) }}</td>
                                    <td class="px-5 py-3 hidden md:table-cell">
                                        <div class="w-36">
                                            <div class="flex justify-between text-[10px] font-semibold mb-1">
                                                <span class="text-emerald-600 dark:text-emerald-400">{{ $b['percentage'] }}% collected</span>
                                                <span class="text-slate-400 num-tabular">{{ money($b['remaining'], 0) }} left</span>
                                            </div>
                                            <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-700"
                                                     style="width: {{ $b['percentage'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6">
                                        <x-empty-state title="No active batches" description="Create a batch to track collection progress." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
                <div class="flex items-start justify-between gap-3 px-5 sm:px-6 pt-5 sm:pt-6 pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">Upcoming Dues</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Next 7 days</p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/40 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                        {{ $upcoming_dues_count }} due
                    </span>
                </div>

                <div class="overflow-x-auto border-t border-slate-200/80 dark:border-slate-800">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-right">When</th>
                                <th class="px-5 py-3 text-right">Remind</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse ($upcoming_dues as $inst)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <x-avatar :name="$inst['student_name']" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('students.show', $inst['student_id']) }}" class="text-xs font-semibold text-slate-800 dark:text-white hover:text-indigo-600 truncate block">
                                                    {{ $inst['student_name'] }}
                                                </a>
                                                <p class="text-[10px] text-slate-400">
                                                    M{{ $inst['number'] }} · due {{ $inst['due_date_label'] }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-bold num-tabular text-slate-800 dark:text-white">
                                        {{ money($inst['remaining'], 0) }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-[9px] font-semibold uppercase {{ $inst['is_today'] ? 'text-rose-500' : 'text-amber-500' }}">
                                        {{ $inst['is_today'] ? 'today' : 'in ' . $inst['days_left'] . 'd' }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if ($inst['student_id'])
                                            <a href="{{ $inst['whatsapp'] }}" target="_blank" rel="noopener"
                                               class="inline-flex p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 transition-colors"
                                               title="WhatsApp reminder">
                                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-xs text-slate-400">
                                        Nothing due in the next 7 days.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- ============ BENTO ROW 5: OVERDUE (5) + RECENT PAYMENTS (4) + EXPENSES (3) ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

            <!-- Top overdue students -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
                <div class="flex items-start justify-between gap-3 px-5 sm:px-6 pt-5 sm:pt-6 pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">Top Overdue Students</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Highest outstanding first</p>
                    </div>
                    <a href="{{ route('dues.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">All dues →</a>
                </div>

                <div class="overflow-x-auto border-t border-slate-200/80 dark:border-slate-800">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3 text-right">Overdue</th>
                                <th class="px-5 py-3 text-right hidden sm:table-cell">Days Late</th>
                                <th class="px-5 py-3 text-right">Remind</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse ($top_defaulters as $d)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <x-avatar :name="$d['name']" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('students.show', $d['id']) }}" class="text-xs font-semibold text-slate-800 dark:text-white hover:text-indigo-600 truncate block">
                                                    {{ $d['name'] }}
                                                </a>
                                                <p class="text-[10px] text-slate-400">{{ $d['batch_code'] ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-bold num-tabular text-rose-600 dark:text-rose-400">
                                        {{ money($d['overdue_amount'], 0) }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-[9px] font-semibold uppercase text-rose-400 hidden sm:table-cell">
                                        {{ $d['days_overdue'] }} days
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ $d['whatsapp'] }}" target="_blank" rel="noopener"
                                           class="inline-flex p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 transition-colors" title="WhatsApp reminder">
                                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-xs text-slate-400">
                                        No overdue students. Great work!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Recent payments -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
                <div class="flex items-start justify-between gap-3 px-5 sm:px-6 pt-5 sm:pt-6 pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">Recent Payments</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Latest receipts recorded</p>
                    </div>
                    <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">All →</a>
                </div>

                @php
                    $methodIcons = [
                        'cash' => ['Cash', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'],
                        'jazzcash' => ['JazzCash', 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400'],
                        'easypaisa' => ['Easypaisa', 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'],
                        'bank' => ['Bank', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400'],
                        'other' => ['Other', 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
                    ];
                @endphp

                <div class="overflow-x-auto border-t border-slate-200/80 dark:border-slate-800">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3">Method</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse ($recent_payments as $p)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <x-avatar :name="$p['student_name']" size="sm" />
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-slate-800 dark:text-white truncate">{{ $p['student_name'] }}</p>
                                                <p class="text-[10px] text-slate-400 font-mono">{{ $p['receipt_no'] }} · {{ $p['paid_on_label'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide {{ ($methodIcons[$p['method']] ?? $methodIcons['other'])[1] }}">
                                            {{ ($methodIcons[$p['method']] ?? $methodIcons['other'])[0] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-bold num-tabular text-emerald-600 dark:text-emerald-400">
                                        +{{ money($p['amount'], 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-8 text-center text-xs text-slate-400">
                                        No payments recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Expenses + net profit -->
            <div class="lg:col-span-3 flex flex-col gap-4 sm:gap-5">
                <div class="bg-gradient-to-br from-rose-50 via-white to-white dark:from-rose-950/20 dark:via-slate-900 dark:to-slate-900 border border-rose-100 dark:border-rose-900/40 rounded-3xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Expenses</span>
                        <span class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" /></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold num-tabular text-rose-600 dark:text-rose-400 leading-none" x-data="countUp({{ (int) $expenses }})"><span x-text="formatted">0</span></p>
                    <p class="text-[11px] text-slate-400 mt-1">Spent in {{ $month_name }}</p>
                </div>

                <div class="flex-1 bg-gradient-to-br {{ $net_profit >= 0 ? 'from-emerald-50 dark:from-emerald-950/20' : 'from-rose-50 dark:from-rose-950/20' }} via-white to-white dark:via-slate-900 dark:to-slate-900 border {{ $net_profit >= 0 ? 'border-emerald-100 dark:border-emerald-900/40' : 'border-rose-100 dark:border-rose-900/40' }} rounded-3xl p-5 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $net_profit >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">Net Profit</span>
                        <span class="w-7 h-7 rounded-lg {{ $net_profit >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' }} flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $net_profit >= 0 ? 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941' : 'M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181' }}" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold num-tabular {{ $net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} leading-none">
                        {{ $net_profit >= 0 ? '+' : '-' }}{{ money(abs($net_profit), 0) }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">Received − expenses</p>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Chart === 'undefined') { return; }

            const isDark = () => document.documentElement.classList.contains('dark');
            const gridColor = () => isDark() ? 'rgba(148,163,184,0.12)' : 'rgba(100,116,139,0.12)';
            const tickColor = () => isDark() ? '#94a3b8' : '#64748b';
            const money = (v) => window.APP_CURRENCY + ' ' + Number(v || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 });

            const charts = [];

            // ---- 12-month trend (area/line: expected vs received) ----
            const trendEl = document.getElementById('trendChart');
            if (trendEl) {
                const ctx = trendEl.getContext('2d');
                const gradExpected = ctx.createLinearGradient(0, 0, 0, 280);
                gradExpected.addColorStop(0, 'rgba(99,102,241,0.28)');
                gradExpected.addColorStop(1, 'rgba(99,102,241,0.01)');
                const gradReceived = ctx.createLinearGradient(0, 0, 0, 280);
                gradReceived.addColorStop(0, 'rgba(16,185,129,0.30)');
                gradReceived.addColorStop(1, 'rgba(16,185,129,0.01)');

                charts.push(new window.Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($trend['labels']),
                        datasets: [
                            {
                                label: 'Expected',
                                data: @json($trend['expected']),
                                borderColor: '#6366f1',
                                backgroundColor: gradExpected,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.38,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: '#6366f1',
                                pointHoverBorderColor: '#fff',
                                pointHoverBorderWidth: 2,
                            },
                            {
                                label: 'Received',
                                data: @json($trend['received']),
                                borderColor: '#10b981',
                                backgroundColor: gradReceived,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.38,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: '#10b981',
                                pointHoverBorderColor: '#fff',
                                pointHoverBorderWidth: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 12,
                                cornerRadius: 12,
                                titleFont: { size: 11, weight: '600' },
                                bodyFont: { size: 12 },
                                callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + money(c.parsed.y) },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: tickColor(), font: { size: 10 }, maxRotation: 0, autoSkipPadding: 16 },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor() },
                                border: { display: false },
                                ticks: {
                                    color: tickColor(),
                                    font: { size: 10 },
                                    maxTicksLimit: 5,
                                    callback: (v) => (v >= 1000 ? (v / 1000) + 'k' : v),
                                },
                            },
                        },
                    },
                }));
            }

            // ---- Payment methods donut ----
            const methodsEl = document.getElementById('methodsChart');
            if (methodsEl) {
                charts.push(new window.Chart(methodsEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: @json(collect($method_breakdown)->filter(fn ($v) => $v > 0)->keys()->map(fn ($k) => ucfirst($k))->values()),
                        datasets: [{
                            data: @json(collect($method_breakdown)->filter(fn ($v) => $v > 0)->values()),
                            backgroundColor: ['#10b981', '#f43f5e', '#f59e0b', '#6366f1', '#94a3b8'],
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: { label: (c) => ' ' + c.label + ': ' + money(c.parsed) },
                            },
                        },
                    },
                }));
            }

            // ---- Collection rate ring ----
            const ringEl = document.getElementById('collectionRing');
            if (ringEl) {
                const pct = {{ (float) round($collectionPct, 1) }};
                charts.push(new window.Chart(ringEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Collected', 'Remaining'],
                        datasets: [{
                            data: [pct, Math.max(0, 100 - pct)],
                            backgroundColor: [
                                pct >= 90 ? '#10b981' : (pct >= 60 ? '#f59e0b' : '#f43f5e'),
                                isDark() ? 'rgba(148,163,184,0.14)' : 'rgba(100,116,139,0.10)',
                            ],
                            borderWidth: 0,
                            hoverOffset: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '76%',
                        rotation: -90,
                        circumference: 360,
                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    },
                }));
            }

            // Re-theme chart axes when dark mode toggles (class change on <html>)
            new MutationObserver(function () {
                charts.forEach(function (chart) {
                    Object.values(chart.options.scales || {}).forEach(function (scale) {
                        if (scale.grid) { scale.grid.color = gridColor(); }
                        if (scale.ticks) { scale.ticks.color = tickColor(); }
                    });
                    chart.update('none');
                });
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        });
    </script>
</x-app-layout>

