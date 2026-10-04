<x-app-layout>
    <div class="space-y-6 max-w-3xl">
        <!-- Header / Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>Back to Payments</span>
                </a>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Receipt</h1>
                    <span class="font-mono text-sm font-bold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">{{ $payment->receipt_no }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096M17.28 13.829L18.66 18m-2.64-4.171a42.415 42.415 0 0110.56 0m-10.56 0L15.06 18M6.72 13.829V7.947a1.125 1.125 0 011.125-1.125h8.3a1.125 1.125 0 011.125 1.125v5.882" /></svg>
                    <span>Print</span>
                </button>

                <a href="{{ route('payments.pdf', $payment) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 shadow-md shadow-rose-600/25 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    <span>Download PDF</span>
                </a>

                <a href="{{ route('payments.whatsapp', $payment) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 fill-emerald-600" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                    <span>Share</span>
                </a>
            </div>
        </div>

        <!-- Receipt Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
            <!-- Academy Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-flex p-1 rounded-xl bg-white/95 shadow-lg flex-shrink-0">
                        <x-logo size="md" :flat="false" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-lg font-extrabold tracking-tight truncate">{{ academy_name() }}</p>
                        <p class="text-indigo-100 text-xs mt-0.5">Fee Payment Receipt</p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[11px] uppercase tracking-wider text-indigo-200 font-semibold">Receipt No</p>
                    <p class="font-mono text-lg font-bold">{{ $payment->receipt_no }}</p>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Meta Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold text-[10px]">Student</p>
                        <p class="font-bold text-slate-900 dark:text-white mt-0.5">{{ $payment->student?->name }}</p>
                        <p class="text-slate-500 font-mono">{{ \App\Services\PhoneFormatter::display($payment->student?->phone) }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold text-[10px]">Batch</p>
                        <p class="font-bold text-slate-900 dark:text-white mt-0.5">{{ $payment->student?->batch?->name ?? '—' }}</p>
                        <p class="text-slate-500 font-mono">{{ $payment->student?->batch?->code ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold text-[10px]">Paid On</p>
                        <p class="font-bold text-slate-900 dark:text-white mt-0.5">{{ $payment->paid_on?->format('d M Y') }}</p>
                        <p class="text-slate-500">for month {{ $payment->for_month }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold text-[10px]">Method</p>
                        <p class="font-bold text-slate-900 dark:text-white mt-0.5 capitalize">{{ $payment->method }}</p>
                        <p class="text-slate-500">{{ $payment->reference ? 'Ref ' . $payment->reference : 'No reference' }}</p>
                    </div>
                </div>

                <!-- Amount -->
                <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] uppercase tracking-wider font-semibold text-emerald-600 dark:text-emerald-400">Amount Received</p>
                        <p class="text-3xl font-extrabold num-tabular text-emerald-700 dark:text-emerald-300 mt-1">{{ money((float) $payment->amount, 0) }}</p>
                    </div>
                    <div class="text-right text-xs">
                        <p class="text-emerald-600 dark:text-emerald-400">Remaining balance</p>
                        <p class="font-bold num-tabular text-slate-800 dark:text-white text-base">{{ money($payment->student?->remaining_balance ?? 0, 0) }}</p>
                    </div>
                </div>


                <!-- Allocation Table -->
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Allocation Breakdown</p>
                    @if ($payment->allocations->isEmpty())
                        <p class="text-xs text-slate-400">No installment allocations recorded.</p>
                    @else
                        <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] uppercase font-semibold text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2.5">Installment</th>
                                        <th class="px-4 py-2.5">Due Date</th>
                                        <th class="px-4 py-2.5">Scheduled</th>
                                        <th class="px-4 py-2.5 text-right">Allocated</th>
                                        <th class="px-4 py-2.5">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($payment->allocations as $alloc)
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-700 dark:text-slate-300">M{{ $alloc->installment?->number }}</td>
                                            <td class="px-4 py-2.5 text-slate-500">{{ $alloc->installment?->due_date?->format('d M Y') }}</td>
                                            <td class="px-4 py-2.5 num-tabular text-slate-600 dark:text-slate-400">{{ money((float) ($alloc->installment?->amount ?? 0), 0) }}</td>
                                            <td class="px-4 py-2.5 text-right num-tabular font-bold text-slate-900 dark:text-white">{{ money((float) $alloc->amount, 0) }}</td>
                                            <td class="px-4 py-2.5"><x-badge :status="$alloc->installment?->status ?? 'pending'" size="sm" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($payment->note)
                    <div class="text-xs">
                        <p class="text-slate-400 uppercase tracking-wide font-semibold text-[10px]">Note</p>
                        <p class="text-slate-600 dark:text-slate-300 mt-0.5">{{ $payment->note }}</p>
                    </div>
                @endif

                @if ($footerNote = trim((string) \App\Models\Setting::get('receipt_footer')))
                    <p class="text-center text-xs text-slate-500 dark:text-slate-400 pb-4">{{ $footerNote }}</p>
                @endif

                <!-- Footer -->
                <div class="pt-4 border-t border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Received by: <strong class="text-slate-600 dark:text-slate-300">{{ $payment->receiver?->name ?? 'System' }}</strong></span>
                    <span>Generated {{ $payment->created_at?->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

