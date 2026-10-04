<x-app-layout>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Payments</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Record receipts, review history, and share PDF receipts on WhatsApp.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>Record Payment</span>
                </a>
            </div>
        </div>

        <!-- KPI Tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-tile title="This Month" :value="money($stats['total'], 0)" :subtitle="$stats['count'] . ' receipts'" variant="indigo" />
            <x-stat-tile title="Today" :value="money($stats['today'], 0)" subtitle="Received today" variant="emerald" />
            <x-stat-tile title="Receipts (Month)" :value="$stats['count']" subtitle="Transactions logged" variant="sky" />
            <x-stat-tile title="All-Time Collected" :value="money($stats['all_time'], 0)" subtitle="Since inception" variant="violet" />
        </div>

        <!-- Filters -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <form method="GET" action="{{ route('payments.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px] relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search receipt no, reference, student, phone..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    </div>
                </div>

                <label class="text-xs font-semibold text-slate-500">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200" />

                <label class="text-xs font-semibold text-slate-500">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200" />

                <select name="method" onchange="this.form.submit()" class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200">
                    <option value="">All Methods</option>
                    @foreach (['cash', 'jazzcash', 'easypaisa', 'bank', 'other'] as $m)
                        <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>

                <select name="batch_id" onchange="this.form.submit()" class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200">
                    <option value="">All Batches</option>
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>{{ $b->code }}</option>
                    @endforeach
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-sm transition-all">Filter</button>

                @if (request()->hasAny(['search', 'from', 'to', 'method', 'batch_id']))
                    <a href="{{ route('payments.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">Reset</a>
                @endif
            </form>
        </div>


        <!-- Payments Table -->
        <x-table>
            <x-slot:header>
                <tr>
                    <th class="px-6 py-3">Receipt</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Student</th>
                    <th class="px-6 py-3 hidden md:table-cell">Batch</th>
                    <th class="px-6 py-3">Method</th>
                    <th class="px-6 py-3 hidden lg:table-cell">Allocated To</th>
                    <th class="px-6 py-3 text-right">Amount</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </x-slot:header>

            @forelse ($payments as $payment)
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $payment->receipt_no }}</span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                        {{ $payment->paid_on?->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('students.show', $payment->student) }}" class="text-xs font-semibold text-slate-800 dark:text-white hover:text-indigo-600 transition-colors">
                            {{ $payment->student?->name }}
                        </a>
                        @if ($payment->student?->phone)
                            <div class="text-[11px] text-slate-400 font-mono">{{ \App\Services\PhoneFormatter::display($payment->student->phone) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs font-mono text-slate-500 hidden md:table-cell">{{ $payment->student?->batch?->code ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-semibold uppercase tracking-wide bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $payment->method }}</span>
                    </td>
                    <td class="px-6 py-4 text-[11px] text-slate-500 dark:text-slate-400 hidden lg:table-cell">
                        @if ($payment->allocations->isEmpty())
                            —
                        @else
                            @foreach ($payment->allocations as $alloc)
                                <div>M{{ $alloc->installment?->number }} &rarr; {{ money((float) $alloc->amount, 0) }}</div>
                            @endforeach
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right num-tabular font-bold text-slate-900 dark:text-white whitespace-nowrap">
                        {{ money((float) $payment->amount, 0) }}
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="{{ route('payments.receipt', $payment) }}" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">View</a>
                            <a href="{{ route('payments.pdf', $payment) }}" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-white" title="Download PDF">PDF</a>
                            <a href="{{ route('payments.whatsapp', $payment) }}" target="_blank" rel="noopener" class="text-[11px] font-semibold text-emerald-600 hover:underline" title="Share on WhatsApp">WA</a>
                            <a href="{{ route('payments.edit', $payment) }}" class="text-[11px] font-semibold text-amber-600 hover:underline">Edit</a>
                            @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete receipt {{ $payment->receipt_no }}? Installments will be recalculated.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-rose-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-10 text-center">
                        <x-empty-state title="No payments found" description="Try adjusting the filters, or record your first payment.">
                            <x-slot:action>
                                <a href="{{ route('payments.create') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-sm">Record Payment</a>
                            </x-slot:action>
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse

            @if ($payments->hasPages())
                <x-slot:pagination>
                    {{ $payments->links() }}
                </x-slot:pagination>
            @endif
        </x-table>

    </div>
</x-app-layout>
