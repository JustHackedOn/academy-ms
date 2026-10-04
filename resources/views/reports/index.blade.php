<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Reports &amp; Exports</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Monthly collections, batch performance and printable / spreadsheet exports.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.monthly.pdf', ['month' => $month] + request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    Monthly PDF
                </a>
                <a href="{{ route('reports.batches.pdf', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    Batch PDF
                </a>
                <div class="flex flex-wrap items-center gap-2" x-data>
                    <a href="{{ route('reports.export', array_merge(['type' => 'students'], request()->query())) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold hover:opacity-90 transition-opacity">CSV · Students</a>
                    <a href="{{ route('reports.export', array_merge(['type' => 'payments'], request()->query())) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-500/25 transition-colors">CSV · Payments</a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.index') }}"
              class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl md:rounded-3xl p-5 sm:p-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                <div class="lg:col-span-2">
                    <x-input-label for="rep_month" value="Month" />
                    <input id="rep_month" name="month" type="month" value="{{ $month }}"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="rep_batch" value="Batch" />
                    <select id="rep_batch" name="batch_id"
                            class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="">All batches</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ (string) ($filters['batch_id'] ?? '') === (string) $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }} ({{ $batch->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="rep_from" value="From" />
                    <input id="rep_from" name="from" type="date" value="{{ $filters['from'] ?? '' }}"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="rep_to" value="To" />
                    <input id="rep_to" name="to" type="date" value="{{ $filters['to'] ?? '' }}"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="rep_method" value="Method" />
                    <select id="rep_method" name="method"
                            class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="">All methods</option>
                        @foreach (['cash' => 'Cash', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'bank' => 'Bank', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['method'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 px-3 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold hover:opacity-90 transition-opacity">Apply</button>
                    <a href="{{ route('reports.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Reset</a>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-4 text-[11px] text-slate-500 dark:text-slate-400">
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" name="overdue" value="1" {{ ($filters['overdue'] ?? null) === '1' ? 'checked' : '' }}
                           class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                    Overdue students only
                </label>

                <label class="inline-flex items-center gap-1.5">
                    <span>Student status</span>
                    <select name="status" class="rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-[11px] py-1 px-2 text-slate-700 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Any</option>
                        @foreach (['active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <span>Tables below honour these filters. Monthly cards always follow the selected month.</span>
            </div>
        </form>

        <!-- Monthly KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <x-stat-tile title="Expected" :value="money($monthly['expected'], 0)" :subtitle="$monthly['month_name'] . ' installments'" variant="indigo" />
            <x-stat-tile title="Received" :value="money($monthly['received'], 0)" :subtitle="$monthly['payments_count'] . ' receipts'" variant="emerald" />
            <x-stat-tile title="Still Pending" :value="money($monthly['pending'], 0)" :subtitle="$monthly['students_with_dues'] . ' students with dues'" variant="amber" />
            <x-stat-tile title="Expenses" :value="money($monthly['expenses'], 0)" subtitle="Logged this month" variant="rose" />
            <x-stat-tile title="Net Profit" :value="($monthly['net_profit'] >= 0 ? '+' : '-') . money(abs($monthly['net_profit']), 0)" subtitle="Received minus expenses" :variant="$monthly['net_profit'] >= 0 ? 'violet' : 'rose'" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">
            <!-- Collection rate + method split -->
            <x-card title="Collection rate" :subtitle="$monthly['month_name']" class="lg:col-span-5">
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold num-tabular text-slate-900 dark:text-white">{{ number_format($monthly['collection_rate'], 1) }}%</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">of expected recovered</span>
                </div>

                <div class="mt-3">
                    <x-progress :value="$monthly['collection_rate']" :variant="$monthly['collection_rate'] >= 90 ? 'emerald' : ($monthly['collection_rate'] >= 60 ? 'amber' : 'rose')" size="lg" />
                </div>

                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Collected against dues: <strong class="num-tabular text-slate-700 dark:text-slate-200">{{ money($monthly['collected_against'], 0) }}</strong></span>
                    <span>Carried overdue: <strong class="num-tabular text-rose-600 dark:text-rose-400">{{ money($monthly['overdue_carried'], 0) }}</strong></span>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Payment methods</p>
                    @php
                        $methodTotal = array_sum($monthly['method_breakdown']);
                        $methodLabels = ['cash' => 'Cash', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'bank' => 'Bank', 'other' => 'Other'];
                        $methodColors = ['cash' => 'bg-emerald-500', 'jazzcash' => 'bg-rose-500', 'easypaisa' => 'bg-teal-500', 'bank' => 'bg-indigo-500', 'other' => 'bg-slate-400'];
                    @endphp

                    @if ($methodTotal <= 0)
                        <p class="text-xs text-slate-400">No receipts recorded in {{ $monthly['month_name'] }}.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($monthly['method_breakdown'] as $method => $amount)
                                @continue($amount <= 0)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="inline-flex items-center gap-2 font-medium text-slate-600 dark:text-slate-300">
                                            <span class="w-2 h-2 rounded-full {{ $methodColors[$method] ?? 'bg-slate-400' }}"></span>
                                            {{ $methodLabels[$method] ?? ucfirst($method) }}
                                        </span>
                                        <span class="num-tabular font-bold text-slate-800 dark:text-white">
                                            {{ money($amount, 0) }}
                                            <span class="text-slate-400 font-normal">{{ round(($amount / $methodTotal) * 100) }}%</span>
                                        </span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                        <div class="h-full rounded-full {{ $methodColors[$method] ?? 'bg-slate-400' }}" style="width: {{ round(($amount / $methodTotal) * 100, 1) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Batch-wise month performance -->
            <x-card title="Batch performance" :subtitle="'Expected vs collected for ' . $monthly['month_name']" class="lg:col-span-7">
                @if (empty($monthly['batch_rows']))
                    <p class="text-xs text-slate-400 py-6 text-center">No batch had students with dues this month.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($monthly['batch_rows'] as $row)
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-1.5">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ $row['name'] }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $row['code'] }} · {{ $row['students'] }} students · {{ money($row['collected'], 0) }} of {{ money($row['expected'], 0) }}</p>
                                    </div>
                                    <span class="text-xs font-bold num-tabular {{ $row['rate'] >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($row['rate'] >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                        {{ $row['rate'] }}%
                                    </span>
                                </div>
                                <x-progress :value="$row['rate']" :variant="$row['rate'] >= 90 ? 'emerald' : ($row['rate'] >= 60 ? 'amber' : 'rose')" size="md" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- All-time batch summary -->
        <x-card title="Batch summary" subtitle="Lifetime figures across every batch" :padding="'p-0'">
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-5 py-3 text-left">Batch</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Duration</th>
                        <th class="px-5 py-3 text-right">Students</th>
                        <th class="px-5 py-3 text-right">Expected</th>
                        <th class="px-5 py-3 text-right">Collected</th>
                        <th class="px-5 py-3 text-right">Remaining</th>
                        <th class="px-5 py-3 text-right">Overdue</th>
                        <th class="px-5 py-3 text-right">Rate</th>
                    </tr>
                </x-slot:header>

                @forelse ($batchReport['rows'] as $row)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-3">
                            <a href="{{ route('batches.show', $row['id']) }}" class="block">
                                <p class="font-semibold text-slate-800 dark:text-white text-sm hover:text-indigo-600 dark:hover:text-indigo-400">{{ $row['name'] }}</p>
                                <p class="text-[11px] text-slate-400">{{ $row['code'] }}</p>
                            </a>
                        </td>
                        <td class="px-5 py-3"><x-badge :status="$row['status']" size="sm" /></td>
                        <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $row['start_date'] }} &rarr; {{ $row['end_date'] }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm">{{ $row['students'] }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm">{{ money($row['expected'], 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ money($row['collected'], 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm">{{ money($row['remaining'], 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm text-rose-600 dark:text-rose-400">{{ money($row['overdue'], 0) }}</td>
                        <td class="px-5 py-3 text-right">
                            <span class="text-xs font-bold num-tabular {{ $row['rate'] >= 90 ? 'text-emerald-600' : ($row['rate'] >= 60 ? 'text-amber-600' : 'text-rose-600') }}">{{ $row['rate'] }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-400">No batches yet.</td>
                    </tr>
                @endforelse

                <x-slot:footer>
                    <tr>
                        <td class="px-5 py-3 text-xs uppercase tracking-wider">Totals</td>
                        <td></td>
                        <td></td>
                        <td class="px-5 py-3 text-right num-tabular">{{ $batchReport['totals']['students'] }}</td>
                        <td class="px-5 py-3 text-right num-tabular">{{ money($batchReport['totals']['expected'], 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular">{{ money($batchReport['totals']['collected'], 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular">{{ money($batchReport['totals']['remaining'], 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular">{{ money($batchReport['totals']['overdue'], 0) }}</td>
                        <td></td>
                    </tr>
                </x-slot:footer>
            </x-table>
        </x-card>

        <!-- Payments ledger -->
        <x-card title="Receipts" :subtitle="'Filtered payments · ' . money($paymentTotal, 0) . ' total'" :padding="'p-0'">
            <x-slot:action>
                <a href="{{ route('reports.export', array_merge(['type' => 'payments'], request()->query())) }}"
                   class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Export CSV</a>
            </x-slot:action>

            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-5 py-3 text-left">Receipt</th>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Student</th>
                        <th class="px-5 py-3 text-left">Batch</th>
                        <th class="px-5 py-3 text-left">Method</th>
                        <th class="px-5 py-3 text-left">Received By</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </x-slot:header>

                @forelse ($payments as $payment)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-3">
                            <a href="{{ route('payments.receipt', $payment) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $payment->receipt_no }}</a>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $payment->paid_on?->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800 dark:text-white">{{ $payment->student?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $payment->student?->batch?->code ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ $payment->method }}</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $payment->receiver?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-bold num-tabular text-slate-800 dark:text-white whitespace-nowrap">{{ money((float) $payment->amount, 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">No receipts match these filters.</td>
                    </tr>
                @endforelse

                <x-slot:pagination>
                    {{ $payments->links() }}
                </x-slot:pagination>
            </x-table>
        </x-card>

        <!-- Student balances -->
        <x-card title="Student fee status" subtitle="Balances under the current filters" :padding="'p-0'">
            <x-slot:action>
                <a href="{{ route('reports.export', array_merge(['type' => 'students'], request()->query())) }}"
                   class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Export CSV</a>
            </x-slot:action>

            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-5 py-3 text-left">Student</th>
                        <th class="px-5 py-3 text-left">Batch</th>
                        <th class="px-5 py-3 text-left">Fee Status</th>
                        <th class="px-5 py-3 text-right">Net Fee</th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                        <th class="px-5 py-3 text-right">Overdue</th>
                    </tr>
                </x-slot:header>

                @forelse ($students as $student)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-3">
                            <a href="{{ route('students.show', $student) }}" class="block">
                                <p class="font-semibold text-slate-800 dark:text-white text-sm hover:text-indigo-600 dark:hover:text-indigo-400">{{ $student->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ \App\Services\PhoneFormatter::display($student->phone) }}</p>
                            </a>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $student->batch?->code ?? '—' }}</td>
                        <td class="px-5 py-3"><x-badge :status="$student->fee_status" size="sm" /></td>
                        <td class="px-5 py-3 text-right num-tabular text-sm">{{ money((float) $student->net_fee, 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm text-emerald-600 dark:text-emerald-400">{{ money($student->total_paid, 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm font-semibold">{{ money($student->remaining_balance, 0) }}</td>
                        <td class="px-5 py-3 text-right num-tabular text-sm {{ $student->overdue_amount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                            {{ money($student->overdue_amount, 0) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">No students match these filters.</td>
                    </tr>
                @endforelse

                <x-slot:pagination>
                    {{ $students->links() }}
                </x-slot:pagination>
            </x-table>
        </x-card>
    </div>
</x-app-layout>
