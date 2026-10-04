<x-app-layout>
    <div class="space-y-6" x-data="ledgerCell()">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Monthly Ledger</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Students on rows, batch months on columns. Click any cell to inspect installments.</p>
            </div>

            <form method="GET" action="{{ route('ledger.index') }}" class="flex items-center gap-2">
                <select name="batch_id" onchange="this.form.submit()" class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" {{ ($batch?->id ?? request('batch_id')) == $b->id ? 'selected' : '' }}>
                            {{ $b->code }} — {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if (! $batch)
            <x-empty-state title="No batches yet" description="Create a batch first to see the monthly ledger matrix." />
        @else

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
            <span class="font-semibold uppercase tracking-wider text-[10px]">Legend:</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Paid</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-400"></span> Partial</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-400"></span> Due</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-500"></span> Overdue</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-200 dark:bg-slate-700"></span> No installment</span>
        </div>

        <!-- Summary Tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-tile title="Batch Expected" :value="money($grand['due'], 0)" subtitle="Across all months" variant="indigo" />
            <x-stat-tile title="Batch Collected" :value="money($grand['paid'], 0)" :subtitle="($grand['due'] > 0 ? round(($grand['paid'] / $grand['due']) * 100, 1) : 0) . '% of expected'" variant="emerald" />
            <x-stat-tile title="Batch Pending" :value="money(max(0, $grand['due'] - $grand['paid']), 0)" subtitle="Still to collect" variant="amber" />
            <x-stat-tile title="Students" :value="count($rows)" :subtitle="$batch->code . ' · ' . $batch->duration_months . ' months'" variant="violet" />
        </div>

        <!-- Matrix -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl md:rounded-3xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-[11px] uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider">
                        <tr class="border-b border-slate-200/80 dark:border-slate-800">
                            <th class="px-5 py-3.5 sticky left-0 bg-slate-50/80 dark:bg-slate-800/90 z-10 min-w-[200px]">Student</th>
                            @foreach ($columns as $col)
                                <th class="px-3 py-3.5 text-center whitespace-nowrap" title="{{ $col['name'] }}">
                                    {{ $col['label'] }}
                                    <div class="text-[10px] font-normal text-slate-400 normal-case">{{ $col['name'] }}</div>
                                </th>
                            @endforeach
                            <th class="px-3 py-3.5 text-right">Net Fee</th>
                            <th class="px-3 py-3.5 text-right">Paid</th>
                            <th class="px-3 py-3.5 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <!-- Student (sticky) -->
                                <td class="px-5 py-3 sticky left-0 bg-white dark:bg-slate-900 z-10">
                                    <a href="{{ route('students.show', $row['student']) }}" class="font-semibold text-slate-800 dark:text-white hover:text-indigo-600 transition-colors">
                                        {{ $row['student']->name }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <x-badge :status="$row['fee_status']" size="sm" />
                                        <span class="text-[10px] text-slate-400 font-mono">{{ \App\Services\PhoneFormatter::display($row['student']->phone) }}</span>
                                    </div>
                                </td>


                                @foreach ($columns as $col)
                                    @php $cell = $row['cells'][$col['month']] ?? ['status' => 'none', 'due' => 0, 'paid' => 0, 'remaining' => 0]; @endphp
                                    @php
                                        $cellClasses = [
                                            'paid' => 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300',
                                            'partial' => 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-300',
                                            'due' => 'bg-sky-50 dark:bg-sky-950/30 border-sky-200 dark:border-sky-800/60 text-sky-700 dark:text-sky-300',
                                            'overdue' => 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300',
                                            'none' => 'bg-slate-50 dark:bg-slate-800/40 border-transparent text-slate-300 dark:text-slate-600',
                                        ];
                                    @endphp
                                    <td class="px-2 py-2 text-center align-top">
                                        @if ($cell['status'] === 'none')
                                            <span class="block py-2 text-slate-300 dark:text-slate-600">—</span>
                                        @else
                                            <button
                                                type="button"
                                                @click="openCell({{ $row['student']->id }}, '{{ $col['month'] }}')"
                                                class="w-full min-w-[74px] rounded-xl border px-2 py-2 transition-all hover:scale-[1.03] hover:shadow-sm {{ $cellClasses[$cell['status']] }}"
                                                title="Due {{ money($cell['due'], 0) }} · Paid {{ money($cell['paid'], 0) }}"
                                            >
                                                <span class="block text-[10px] font-bold uppercase tracking-wide leading-none mb-1">
                                                    {{ $cell['status'] }}
                                                </span>
                                                <span class="block text-[11px] font-bold num-tabular leading-none">
                                                    @if ($cell['status'] === 'paid')
                                                        {{ number_format($cell['paid'], 0) }}
                                                    @elseif ($cell['status'] === 'partial')
                                                        {{ number_format($cell['paid'], 0) }}/{{ number_format($cell['due'], 0) }}
                                                    @elseif ($cell['status'] === 'overdue')
                                                        {{ number_format($cell['remaining'], 0) }} left
                                                    @else
                                                        {{ number_format($cell['due'], 0) }}
                                                    @endif
                                                </span>
                                            </button>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-3 py-3 text-right num-tabular font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ money($row['net_fee'], 0) }}
                                </td>
                                <td class="px-3 py-3 text-right num-tabular font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    {{ money($row['total_paid'], 0) }}
                                </td>
                                <td class="px-3 py-3 text-right num-tabular font-bold {{ $row['remaining'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }} whitespace-nowrap">
                                    {{ money($row['remaining'], 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 4 }}" class="px-6 py-10 text-center text-slate-500">
                                    No students in this batch yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>


                    <!-- Column footers -->
                    <tfoot class="bg-slate-50 dark:bg-slate-800/50 font-semibold text-slate-700 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800">
                        <tr>
                            <td class="px-5 py-3 sticky left-0 bg-slate-50 dark:bg-slate-800/90 z-10 text-[11px] uppercase tracking-wider">Column Totals</td>
                            @foreach ($columns as $col)
                                @php $t = $columnTotals[$col['month']] ?? ['due' => 0, 'paid' => 0]; @endphp
                                <td class="px-2 py-3 text-center text-[10px] num-tabular leading-tight">
                                    <div class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($t['paid'], 0) }}</div>
                                    <div class="text-slate-400 font-normal">/{{ number_format($t['due'], 0) }}</div>
                                </td>
                            @endforeach
                            <td class="px-3 py-3 text-right num-tabular">{{ money($grand['due'], 0) }}</td>
                            <td class="px-3 py-3 text-right num-tabular text-emerald-600">{{ money($grand['paid'], 0) }}</td>
                            <td class="px-3 py-3 text-right num-tabular text-amber-600">{{ money(max(0, $grand['due'] - $grand['paid']), 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>


        <!-- Cell drill-down modal -->
        <x-modal name="ledger-cell" max-width="lg">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Cell Detail</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span x-text="cellData && cellData.student && cellData.student.name"></span> ·
                            <span x-text="cellMonth"></span>
                        </p>
                    </div>
                    <button @click="$dispatch('close-modal', 'ledger-cell')" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div x-show="!cellData" class="text-xs text-slate-400 py-6 text-center">Loading…</div>

                <div x-show="cellData" class="space-y-3" style="display: none;">
                    <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 p-4 flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wider">Remaining balance</span>
                        <span class="text-lg font-extrabold num-tabular text-amber-700 dark:text-amber-300" x-text="APP_CURRENCY + ' ' + fmt(cellData.remaining_balance)"></span>
                    </div>

                    <ul class="space-y-2">
                        <template x-for="inst in (cellData ? cellData.installments : [])" :key="inst.id">
                            <li class="flex items-center justify-between text-xs px-3 py-2.5 rounded-xl"
                                :class="inst.status === 'paid' ? 'bg-emerald-50 dark:bg-emerald-950/30' : (inst.status === 'overdue' ? 'bg-rose-50 dark:bg-rose-950/30' : 'bg-slate-50 dark:bg-slate-800/60')">
                                <div>
                                    <span class="font-bold" x-text="'M' + inst.number"></span>
                                    <span class="text-slate-500 ml-2" x-text="'due ' + inst.due_date"></span>
                                </div>
                                <div class="text-right">
                                    <span class="num-tabular font-bold" x-text="APP_CURRENCY + ' ' + fmt(inst.remaining) + ' left'"></span>
                                    <span class="block text-[10px] uppercase font-semibold" x-text="inst.status"></span>
                                </div>
                            </li>
                        </template>
                    </ul>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <a x-bind:href="cellData && cellData.student ? '{{ route('payments.create') }}?student_id=' + cellData.student.id : '#'"
                           class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-sm transition-all">
                            Record Payment
                        </a>
                    </div>
                </div>
            </div>
        </x-modal>

        @endif
    </div>

    <script>
        function ledgerCell() {
            return {
                cellData: null,
                cellMonth: null,
                async openCell(studentId, month) {
                    this.cellMonth = month;
                    this.cellData = null;
                    this.$dispatch('open-modal', 'ledger-cell');
                    try {
                        const url = @json(route('ledger.cell')) + '?student_id=' + studentId + '&month=' + month;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        this.cellData = await res.json();
                    } catch (e) {
                        console.error('cell load failed', e);
                    }
                },
                fmt(n) { return Number(n || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 }); }
            };
        }
        </script>
</x-app-layout>

