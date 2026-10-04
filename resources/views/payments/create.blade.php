<x-app-layout>
    <div
        class="space-y-6"
        x-data="paymentForm()"
        x-init="init()"
    >
        <!-- Header -->
        <div>
            <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                <span>Back to Payments</span>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Record Payment</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Smart auto-allocation pays the oldest unpaid installments first. Partial splits across multiple installments are handled automatically.</p>
        </div>

        <!-- Overpayment guard banner -->
        <div
            x-show="snapshot && amount > snapshot.remaining_balance"
            x-transition
            class="rounded-2xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 p-4 text-sm text-rose-700 dark:text-rose-300 flex items-start gap-3"
            style="display: none;"
        >
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <div>
                <p class="font-bold">Overpayment blocked</p>
                <p class="text-xs mt-0.5">Amount exceeds remaining balance of {{ currency() }} <span class="num-tabular font-bold" x-text="format(snapshot.remaining_balance)"></span>. The server will reject this submission.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            @csrf

            <!-- Left: Payment Details -->
            <div class="lg:col-span-8 space-y-6">
                <x-card title="Payment Details" subtitle="Choose the student, amount and method.">
                    <div class="space-y-4">
                        <!-- Student Picker -->
                        <div>
                            <label for="student_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Student *</label>
                            <select
                                name="student_id"
                                id="student_id"
                                x-model="studentId"
                                @change="loadSnapshot()"
                                required
                                class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">— Select student —</option>
                                @foreach ($students as $s)
                                    <option
                                        value="{{ $s->id }}"
                                        {{ old('student_id', $selectedStudent?->id) == $s->id ? 'selected' : '' }}
                                    >
                                        {{ $s->name }} — {{ $s->batch?->code }} — Bal {{ money($s->remaining_balance, 0) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('student_id')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Amount -->
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
                                        x-model.number="amount"
                                        value="{{ old('amount') }}"
                                        required
                                        class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                            </div>

                            <!-- Paid On -->
                            <div>
                                <label for="paid_on" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Paid On *</label>
                                <input
                                    type="date"
                                    name="paid_on"
                                    id="paid_on"
                                    value="{{ old('paid_on', now()->format('Y-m-d')) }}"
                                    required
                                    class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <x-input-error :messages="$errors->get('paid_on')" class="mt-1" />
                            </div>

                            <!-- Method -->
                            <div>
                                <label for="method" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Method *</label>
                                <select
                                    name="method"
                                    id="method"
                                    required
                                    class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="jazzcash" {{ old('method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                                    <option value="easypaisa" {{ old('method') === 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
                                    <option value="bank" {{ old('method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="other" {{ old('method') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <x-input-error :messages="$errors->get('method')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="reference" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Reference / Trx ID</label>
                                <input type="text" name="reference" id="reference" value="{{ old('reference') }}" placeholder="e.g. TID-987654" class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white" />
                            </div>
                            <div>
                                <label for="note" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Note</label>
                                <input type="text" name="note" id="note" value="{{ old('note') }}" placeholder="Optional remarks" class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white" />
                            </div>
                        </div>
                    </div>
                </x-card>


                <!-- Installment Override -->
                <x-card title="Target Installment (Optional)" subtitle="Leave on auto-allocate to pay the oldest unpaid installments first.">
                    <select
                        name="installment_id"
                        x-model="installmentId"
                        class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Auto-allocate to oldest unpaid (Recommended)</option>
                        <template x-for="inst in snapshot ? snapshot.installments : []" :key="inst.id">
                            <option
                                :value="inst.id"
                                x-text="'M' + inst.number + ' (Due ' + inst.due_date + ' — Remaining ' + APP_CURRENCY + ' ' + format(inst.remaining) + ' — ' + inst.status.toUpperCase() + ')'"
                            ></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-2">If you pick a specific installment, any excess amount spills over to the next oldest unpaid installment automatically.</p>
                    <x-input-error :messages="$errors->get('installment_id')" class="mt-1" />
                </x-card>

                <!-- Submit Bar -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('payments.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</a>
                    <button
                        type="submit"
                        :disabled="!studentId || !amount || (snapshot && amount > snapshot.remaining_balance)"
                        class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Record &amp; Generate Receipt
                    </button>
                </div>
            </div>


            <!-- Right: Live Fee Snapshot -->
            <div class="lg:col-span-4 space-y-4">
                <x-card title="Fee Snapshot" subtitle="Live balance for the selected student.">
                    <div x-show="!snapshot" class="text-xs text-slate-400 py-6 text-center">Select a student to load their fee status…</div>

                    <div x-show="snapshot" x-transition class="space-y-4" style="display: none;">
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-sm font-bold shadow-xs select-none"
                                x-text="initials(snapshot ? snapshot.name : '')"
                                title="Student initials"
                            >--</span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate" x-text="snapshot && snapshot.name"></p>
                                <p class="text-[11px] text-slate-400 truncate" x-text="snapshot && snapshot.batch"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                <p class="text-slate-400 uppercase tracking-wide text-[10px] font-semibold">Net Fee</p>
                                <p class="font-bold num-tabular text-slate-800 dark:text-white" x-text="APP_CURRENCY + ' ' + format(snapshot && snapshot.net_fee)"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30">
                                <p class="text-emerald-600 uppercase tracking-wide text-[10px] font-semibold">Paid</p>
                                <p class="font-bold num-tabular text-emerald-700 dark:text-emerald-300" x-text="APP_CURRENCY + ' ' + format(snapshot && snapshot.total_paid)"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 col-span-2">
                                <p class="text-amber-600 uppercase tracking-wide text-[10px] font-semibold">Remaining Balance</p>
                                <p class="font-extrabold num-tabular text-lg text-amber-700 dark:text-amber-300" x-text="APP_CURRENCY + ' ' + format(snapshot && snapshot.remaining_balance)"></p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Installment Plan</p>
                            <ul class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                                <template x-for="inst in (snapshot ? snapshot.installments : [])" :key="inst.id">
                                    <li class="flex items-center justify-between text-[11px] px-2.5 py-1.5 rounded-lg"
                                        :class="inst.status === 'paid' ? 'bg-emerald-50 dark:bg-emerald-950/30' : (inst.status === 'overdue' ? 'bg-rose-50 dark:bg-rose-950/30' : 'bg-slate-50 dark:bg-slate-800/60')">
                                        <span class="font-semibold" x-text="'M' + inst.number + ' · ' + inst.due_date"></span>
                                        <span class="num-tabular font-bold" x-text="APP_CURRENCY + ' ' + format(inst.remaining)"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </x-card>
            </div>
        </form>
    </div>


    <script>
        function paymentForm() {
            return {
                studentId: @json(old('student_id', $selectedStudent?->id ?? '')),
                installmentId: @json(old('installment_id', '')),
                amount: {{ (float) old('amount', 0) }},
                snapshot: null,
                init() {
                    if (this.studentId) this.loadSnapshot();
                },
                async loadSnapshot() {
                    if (!this.studentId) { this.snapshot = null; return; }
                    try {
                        const res = await fetch(@json(route('payments.snapshot')) + '?student_id=' + this.studentId, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        this.snapshot = await res.json();
                        this.installmentId = '';
                    } catch (e) {
                        console.error('Failed to load student snapshot', e);
                    }
                },
                format(n) {
                    return Number(n || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 });
                },
                initials(name) {
                    const words = String(name || '').trim().split(/\s+/).slice(0, 2);
                    const letters = words.map((w) => w.charAt(0).toUpperCase()).join('');
                    return letters || '?';
                }
            };
        }
    </script>
</x-app-layout>
