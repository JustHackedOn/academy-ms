<x-app-layout>
    <div class="space-y-6 max-w-3xl">
        <!-- Header -->
        <div>
            <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                <span>Back to Payments</span>
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Edit Payment</h1>
                <span class="font-mono text-sm font-bold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">{{ $payment->receipt_no }}</span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Saving recalculates all installment allocations for <strong>{{ $payment->student?->name }}</strong>.
            </p>
        </div>

        <form method="POST" action="{{ route('payments.update', $payment) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-card title="Payment Details" subtitle="Changing the amount rebuilds the student's installment allocations.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Amount ({{ currency() }}) *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-slate-400">{{ currency() }}</span>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                id="amount"
                                value="{{ old('amount', $payment->amount) }}"
                                required
                                class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <div>
                        <label for="paid_on" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Paid On *</label>
                        <input
                            type="date"
                            name="paid_on"
                            id="paid_on"
                            value="{{ old('paid_on', $payment->paid_on?->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('paid_on')" class="mt-1" />
                    </div>

                    <div>
                        <label for="method" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Method *</label>
                        <select name="method" id="method" required class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            @foreach (['cash', 'jazzcash', 'easypaisa', 'bank', 'other'] as $m)
                                <option value="{{ $m }}" {{ old('method', $payment->method) === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('method')" class="mt-1" />
                    </div>

                    <div>
                        <label for="reference" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Reference / Trx ID</label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference', $payment->reference) }}" class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="note" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Note</label>
                        <input type="text" name="note" id="note" value="{{ old('note', $payment->note) }}" class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white" />
                    </div>
                </div>

            <!-- Current allocation summary -->
            <x-card title="Current Allocation" subtitle="How this receipt was split across installments.">
                @if ($payment->allocations->isEmpty())
                    <p class="text-xs text-slate-400">No allocation rows recorded for this payment.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($payment->allocations as $alloc)
                            <li class="flex items-center justify-between text-xs px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">
                                    M{{ $alloc->installment?->number }} — due {{ $alloc->installment?->due_date?->format('d M Y') }}
                                </span>
                                <span class="num-tabular font-bold text-slate-900 dark:text-white">{{ money((float) $alloc->amount, 0) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('payments.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 transition-all">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

            </x-card>
