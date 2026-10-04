<x-app-layout>
    <div class="space-y-6" x-data="{ paymentModalOpen: false, selectedInstallmentId: null, paymentAmount: {{ $student->remaining_balance }} }">

        <!-- Back Link & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>Back to Students</span>
                </a>
                <div class="flex items-center gap-3">
                    <x-avatar :name="$student->name" :photo="$student->photo_url" size="xl" />
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $student->name }}</h1>
                        @if ($student->batch)
                            <a href="{{ route('batches.show', $student->batch) }}" class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:underline">
                                {{ $student->batch->code }}
                            </a>
                        @endif
                        <x-badge :status="$student->fee_status" />
                        <x-badge :status="$student->status" />
                    </div>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- WhatsApp Button -->
                <a
                    href="{{ $student->getWhatsAppUrl() }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 fill-emerald-600" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    <span>WhatsApp</span>
                </a>

                <!-- Call Button -->
                <a
                    href="tel:{{ $student->phone }}"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25z" /></svg>
                    <span>Call</span>
                </a>

                <!-- Edit Profile -->
                <a
                    href="{{ route('students.edit', $student) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    <span>Edit</span>
                </a>

                <!-- Record Payment Modal Button -->
                @if ($student->remaining_balance > 0)
                    <button
                        type="button"
                        @click="paymentModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        <span>Record Payment</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Bento Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <x-stat-tile title="Net Fee" :value="money($student->net_fee, 0)" subtitle="Total: {{ money($student->total_fee, 0) }}" variant="indigo" />
            <x-stat-tile title="Total Paid" :value="money($student->total_paid, 0)" :subtitle="$student->payment_progress . '% cleared'" variant="emerald" />
            <x-stat-tile title="Remaining" :value="money($student->remaining_balance, 0)" subtitle="{{ $student->is_cleared ? 'All dues cleared!' : 'Balance payable' }}" variant="amber" />
            <x-stat-tile title="Next Due Date" :value="$student->next_due_date ? date('M d, Y', strtotime($student->next_due_date)) : 'Cleared'" subtitle="{{ $student->days_overdue > 0 ? $student->days_overdue . ' days overdue' : 'Upcoming' }}" variant="{{ $student->days_overdue > 0 ? 'rose' : 'sky' }}" />
            <x-stat-tile title="Plan" :value="ucfirst($student->fee_type)" subtitle="{{ $student->installments->count() }} installments" variant="violet" />
        </div>

        <!-- Main Content 2-Column Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Left Column: Installment Schedule & Payments (8 cols) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Installment Schedule Table -->
                <x-card title="Installment Schedule" subtitle="Monthly milestones, due dates and coverage breakdown.">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">Due Date</th>
                                    <th class="px-4 py-3">Scheduled</th>
                                    <th class="px-4 py-3">Paid Amount</th>
                                    <th class="px-4 py-3">Remaining</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Quick Pay</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($student->installments as $inst)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                        <td class="px-4 py-3.5 font-bold text-slate-700 dark:text-slate-300">
                                            M{{ $inst->number }}
                                        </td>
                                        <td class="px-4 py-3.5 font-medium text-slate-600 dark:text-slate-400">
                                            {{ $inst->due_date?->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3.5 num-tabular font-semibold text-slate-800 dark:text-white">
                                            {{ money($inst->amount, 0) }}
                                        </td>
                                        <td class="px-4 py-3.5 num-tabular font-semibold text-emerald-600 dark:text-emerald-400">
                                            {{ money($inst->paid_amount, 0) }}
                                        </td>
                                        <td class="px-4 py-3.5 num-tabular font-semibold {{ $inst->remaining > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                            {{ money($inst->remaining, 0) }}
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <x-badge :status="$inst->status" />
                                        </td>
                                        <td class="px-4 py-3.5 text-right">
                                            @if ($inst->remaining > 0)
                                                <button
                                                    type="button"
                                                    @click="selectedInstallmentId = {{ $inst->id }}; paymentAmount = {{ $inst->remaining }}; paymentModalOpen = true"
                                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100"
                                                >
                                                    Pay M{{ $inst->number }}
                                                </button>
                                            @else
                                                <span class="text-emerald-600 text-xs font-bold">✓ Clear</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <!-- Payment History -->
                <x-card title="Payment History" subtitle="Recorded receipts, transactions, and audit records.">
                    @if ($student->payments->isEmpty())
                        <x-empty-state title="No payments recorded yet" description="No transactions have been recorded for this student so far." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-4 py-3">Receipt No</th>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Amount</th>
                                        <th class="px-4 py-3">Method</th>
                                        <th class="px-4 py-3">Receiver</th>
                                        <th class="px-4 py-3 text-right">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($student->payments as $payment)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                            <td class="px-4 py-3.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                                {{ $payment->receipt_no }}
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-600 dark:text-slate-400">
                                                {{ $payment->paid_on?->format('M d, Y') }}
                                            </td>
                                            <td class="px-4 py-3.5 num-tabular font-bold text-emerald-600 dark:text-emerald-400">
                                                {{ money($payment->amount, 0) }}
                                            </td>
                                            <td class="px-4 py-3.5 uppercase text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                {{ $payment->method }}
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-500">
                                                {{ $payment->receiver->name ?? 'System' }}
                                            </td>
                                            <td class="px-4 py-3.5 text-right space-x-2">
                                                <a
                                                    href="{{ route('payments.receipt', $payment) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:underline"
                                                    title="Download PDF Receipt"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                                    PDF
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>

            </div>

            <!-- Right Column: Student Details & Notes Timeline (4 cols) -->
            <div class="lg:col-span-4 space-y-6">

                <!-- Student Contact & Profile Card -->
                <x-card title="Contact Info">
                    <div class="space-y-3 text-xs">
                        @if ($student->photo_url)
                            <div class="flex justify-center pb-1">
                                <img
                                    src="{{ $student->photo_url }}"
                                    alt="{{ $student->name }}"
                                    class="w-28 h-28 rounded-3xl object-cover border border-slate-200 dark:border-slate-700 shadow-md"
                                />
                            </div>
                        @endif
                        <div>
                            <span class="text-slate-400 block font-medium">WhatsApp / Phone</span>
                            <span class="font-mono text-slate-800 dark:text-white font-semibold">
                                {{ \App\Services\PhoneFormatter::display($student->phone) }}
                            </span>
                        </div>
                        @if ($student->alt_phone)
                            <div>
                                <span class="text-slate-400 block font-medium">Alternate Phone</span>
                                <span class="font-mono text-slate-800 dark:text-white font-semibold">
                                    {{ \App\Services\PhoneFormatter::display($student->alt_phone) }}
                                </span>
                            </div>
                        @endif
                        @if ($student->email)
                            <div>
                                <span class="text-slate-400 block font-medium">Email</span>
                                <span class="text-slate-800 dark:text-white truncate block">
                                    {{ $student->email }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <span class="text-slate-400 block font-medium">Admission Date</span>
                            <span class="text-slate-800 dark:text-white">
                                {{ $student->join_date?->format('F d, Y') }}
                            </span>
                        </div>
                        @if ($student->notes)
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-slate-400 block font-medium">Admission Notes</span>
                                <p class="text-slate-600 dark:text-slate-300 italic mt-0.5">
                                    "{{ $student->notes }}"
                                </p>
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Communication & Notes Timeline -->
                <x-card title="Follow-up & Notes" subtitle="Track calls, promises, and internal logs.">

                    <!-- Add Note Form -->
                    <form method="POST" action="{{ route('students.notes.store', $student) }}" class="space-y-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        @csrf
                        <div>
                            <textarea
                                name="body"
                                rows="2"
                                required
                                placeholder="Add follow-up notes, call outcome, student commitment..."
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <select
                                    name="type"
                                    class="w-full text-xs rounded-xl py-1.5 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="note">General Note</option>
                                    <option value="call">Call Log</option>
                                    <option value="promise">Fee Promise</option>
                                    <option value="follow_up">Follow Up</option>
                                </select>
                            </div>
                            <div>
                                <input
                                    type="date"
                                    name="promise_date"
                                    placeholder="Promise date"
                                    title="Promise Date (if any)"
                                    class="w-full text-xs rounded-xl py-1.5 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-xs">
                                Post Note
                            </button>
                        </div>
                    </form>

                    <!-- Historical Timeline -->
                    <div class="pt-4 space-y-3.5 max-h-96 overflow-y-auto pr-1">
                        @forelse ($student->notesList as $note)
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800 text-xs space-y-1">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span @class([
                                        'font-bold uppercase tracking-wider px-1.5 py-0.5 rounded text-[10px]',
                                        'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300' => $note->type === 'note',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $note->type === 'call',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' => $note->type === 'promise',
                                        'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' => $note->type === 'follow_up',
                                    ])>
                                        {{ str_replace('_', ' ', $note->type) }}
                                    </span>
                                    <span class="text-slate-400">
                                        {{ $note->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="text-slate-700 dark:text-slate-200 mt-1">
                                    {{ $note->body }}
                                </p>

                                @if ($note->promise_date)
                                    <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-semibold text-[11px] pt-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
                                        <span>Promised payment by: {{ $note->promise_date?->format('M d, Y') }}</span>
                                    </div>
                                @endif

                                <div class="text-[10px] text-slate-400 pt-0.5">
                                    By: {{ $note->user->name ?? 'Admin' }}
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-xs text-slate-400 py-4">No follow-up notes logged yet.</p>
                        @endforelse
                    </div>

                </x-card>

            </div>

        </div>

        <!-- Quick "Record Payment" Modal (Phase 5 Connected) -->
        <div
            x-show="paymentModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;"
        >
            <div
                @click.away="paymentModalOpen = false"
                class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Fee Payment</h3>
                        <p class="text-xs text-slate-500">Student: <strong class="text-slate-800 dark:text-slate-200">{{ $student->name }}</strong></p>
                    </div>
                    <button @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $student->id }}">

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Payment Amount ({{ currency() }}) *
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-slate-400">{{ currency() }}</span>
                            <input
                                type="number"
                                step="0.01"
                                max="{{ $student->remaining_balance }}"
                                name="amount"
                                x-model.number="paymentAmount"
                                required
                                class="w-full pl-9 rounded-xl text-sm font-mono font-bold num-tabular border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Remaining balance: {{ money($student->remaining_balance, 0) }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Payment Date *
                            </label>
                            <input
                                type="date"
                                name="paid_on"
                                value="{{ now()->format('Y-m-d') }}"
                                required
                                class="w-full rounded-xl text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Payment Method *
                            </label>
                            <select
                                name="method"
                                required
                                class="w-full rounded-xl text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                                <option value="cash">Cash</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="easypaisa">Easypaisa</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Target Installment (Optional)
                        </label>
                        <select
                            name="installment_id"
                            x-model="selectedInstallmentId"
                            class="w-full rounded-xl text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                            <option value="">Auto-allocate to oldest unpaid (Recommended)</option>
                            @foreach ($student->installments as $inst)
                                <option value="{{ $inst->id }}">
                                    M{{ $inst->number }} (Due {{ $inst->due_date?->format('M d') }} - Remaining {{ money($inst->remaining, 0) }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">If left blank, system automatically pays oldest unpaid installments first.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Reference / Trx ID
                            </label>
                            <input
                                type="text"
                                name="reference"
                                placeholder="e.g. TID-987654"
                                class="w-full rounded-xl text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Note
                            </label>
                            <input
                                type="text"
                                name="note"
                                placeholder="Optional remarks"
                                class="w-full rounded-xl text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="paymentModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25"
                        >
                            Generate Receipt & Post
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
