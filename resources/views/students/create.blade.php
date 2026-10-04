<x-app-layout>
    <div
        class="max-w-4xl mx-auto space-y-6"
        x-data="{
            batches: {{ Js::from($batches) }},
            selectedBatchId: '{{ old('batch_id', $selectedBatch?->id ?? ($batches->first()->id ?? '')) }}',
            name: '{{ old('name') }}',
            phone: '{{ old('phone') }}',
            email: '{{ old('email') }}',
            feeType: '{{ old('fee_type', 'installments') }}',
            totalFee: {{ old('total_fee', $selectedBatch?->default_fee ?? ($batches->first()->default_fee ?? 40000)) }},
            discount: {{ old('discount', 0) }},
            joinDate: '{{ old('join_date', now()->format('Y-m-d')) }}',
            installmentCount: {{ old('installment_count', $selectedBatch?->duration_months ?? ($batches->first()->duration_months ?? 4)) }},
            installments: [],

            get netFee() {
                const total = parseFloat(this.totalFee) || 0;
                const disc = parseFloat(this.discount) || 0;
                return Math.max(0, total - disc);
            },

            get installmentsSum() {
                return this.installments.reduce((acc, curr) => acc + (parseFloat(curr.amount) || 0), 0);
            },

            get sumDifference() {
                return Math.round((this.netFee - this.installmentsSum) * 100) / 100;
            },

            get isSumBalanced() {
                return Math.abs(this.sumDifference) < 0.05;
            },

            onBatchChange() {
                const found = this.batches.find(b => b.id == this.selectedBatchId);
                if (found) {
                    this.totalFee = parseFloat(found.default_fee) || 40000;
                    this.installmentCount = parseInt(found.duration_months) || 4;
                    this.regenerateSchedule();
                }
            },

            regenerateSchedule() {
                const net = this.netFee;
                const count = this.feeType === 'full' ? 1 : Math.max(1, parseInt(this.installmentCount) || 1);
                const baseDate = new Date(this.joinDate || Date.now());

                const schedule = [];
                const equalBase = Math.floor((net / count) * 100) / 100;
                let runningSum = 0;

                for (let i = 1; i <= count; i++) {
                    const d = new Date(baseDate);
                    d.setMonth(d.getMonth() + (i - 1));
                    const dueDateStr = d.toISOString().split('T')[0];

                    let amt = equalBase;
                    if (i === count) {
                        amt = Math.round((net - runningSum) * 100) / 100;
                    } else {
                        runningSum += amt;
                    }

                    schedule.push({
                        number: i,
                        due_date: dueDateStr,
                        amount: amt
                    });
                }

                this.installments = schedule;
            },

            addInstallment() {
                const lastDate = this.installments.length > 0
                    ? new Date(this.installments[this.installments.length - 1].due_date)
                    : new Date(this.joinDate);
                lastDate.setMonth(lastDate.getMonth() + 1);

                this.installments.push({
                    number: this.installments.length + 1,
                    due_date: lastDate.toISOString().split('T')[0],
                    amount: 0
                });
                this.installmentCount = this.installments.length;
            },

            removeInstallment(index) {
                if (this.installments.length > 1) {
                    this.installments.splice(index, 1);
                    this.installments.forEach((inst, idx) => inst.number = idx + 1);
                    this.installmentCount = this.installments.length;
                }
            },

            init() {
                this.regenerateSchedule();
            }
        }"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>Back to Students</span>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Enroll Student & Configure Fee Plan</h1>
            </div>
        </div>

        <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- 1. Student Personal Information -->
            <x-card title="Student Details" subtitle="Primary contact and enrollment information.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <!-- Batch Selector -->
                    <div>
                        <label for="batch_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Assign to Batch *
                        </label>
                        <select
                            name="batch_id"
                            id="batch_id"
                            x-model="selectedBatchId"
                            @change="onBatchChange()"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            @foreach ($batches as $b)
                                <option value="{{ $b->id }}">
                                    {{ $b->code }} - {{ $b->name }} ({{ money($b->default_fee, 0) }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('batch_id')" class="mt-1" />
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Student Full Name *
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            x-model="name"
                            required
                            placeholder="e.g. Muhammad Ali"
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <!-- Photo -->
                    <div>
                        <label for="photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Photo (Optional)
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                type="file"
                                name="photo"
                                id="photo"
                                accept="image/png,image/jpeg,image/webp"
                                class="text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:px-3 file:py-2 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/50 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-950"
                            />
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">JPG, PNG or WebP — max 2 MB.</p>
                        <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                    </div>

                    <!-- Primary Phone (with Pakistan helper) -->
                    <div>
                        <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Phone / WhatsApp *
                        </label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            x-model="phone"
                            required
                            placeholder="0300-1234567 or 923001234567"
                            class="w-full rounded-xl text-sm font-mono border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Normalized to 92300xxxxxxx automatically for WhatsApp reminders.</p>
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <!-- Alt Phone -->
                    <div>
                        <label for="alt_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Alternate Phone / Guardian (Optional)
                        </label>
                        <input
                            type="text"
                            name="alt_phone"
                            id="alt_phone"
                            value="{{ old('alt_phone') }}"
                            placeholder="0321-7654321"
                            class="w-full rounded-xl text-sm font-mono border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Email Address (Optional)
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            x-model="email"
                            placeholder="student@example.com"
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Join Date -->
                    <div>
                        <label for="join_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Admission / Join Date *
                        </label>
                        <input
                            type="date"
                            name="join_date"
                            id="join_date"
                            x-model="joinDate"
                            @change="regenerateSchedule()"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Enrollment Status -->
                    <div class="sm:col-span-2">
                        <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Enrollment Status *
                        </label>
                        <select
                            name="status"
                            id="status"
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="active" selected>Active</option>
                            <option value="paused">Paused</option>
                            <option value="dropped">Dropped</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                </div>
            </x-card>

            <!-- 2. Interactive Fee-Plan & Installments Builder -->
            <x-card title="Fee Plan & Installment Generator" subtitle="Split fees into customizable installments with automatic remainder placement.">

                <!-- Plan Settings Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pb-5 border-b border-slate-100 dark:border-slate-800">

                    <!-- Fee Type: Full vs Installments -->
                    <div class="sm:col-span-4 flex items-center gap-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input
                                type="radio"
                                name="fee_type"
                                value="installments"
                                x-model="feeType"
                                @change="regenerateSchedule()"
                                class="text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="ms-2 text-sm font-semibold text-slate-800 dark:text-slate-200">Monthly Installments</span>
                        </label>

                        <label class="inline-flex items-center cursor-pointer">
                            <input
                                type="radio"
                                name="fee_type"
                                value="full"
                                x-model="feeType"
                                @change="regenerateSchedule()"
                                class="text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="ms-2 text-sm font-semibold text-slate-800 dark:text-slate-200">Full Single Payment</span>
                        </label>
                    </div>

                    <!-- Total Fee -->
                    <div>
                        <label for="total_fee" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Total Fee ({{ currency() }}) *
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-slate-400">{{ currency() }}</span>
                            <input
                                type="number"
                                step="100"
                                name="total_fee"
                                id="total_fee"
                                x-model="totalFee"
                                @input="regenerateSchedule()"
                                required
                                class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Discount -->
                    <div>
                        <label for="discount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Discount / Scholarship
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-slate-400">{{ currency() }}</span>
                            <input
                                type="number"
                                step="100"
                                name="discount"
                                id="discount"
                                x-model="discount"
                                @input="regenerateSchedule()"
                                class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Net Fee (Calculated) -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Net Fee (Payable)
                        </label>
                        <div class="px-3 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 font-extrabold text-sm num-tabular">
                            {{ currency() }} <span x-text="netFee.toLocaleString()"></span>
                        </div>
                    </div>

                    <!-- Number of Installments Spinner -->
                    <div x-show="feeType === 'installments'">
                        <label for="installment_count" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Installments Count
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                min="1"
                                max="12"
                                id="installment_count"
                                x-model="installmentCount"
                                @input="regenerateSchedule()"
                                class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                            <button
                                type="button"
                                @click="regenerateSchedule()"
                                class="p-2 rounded-xl text-slate-600 hover:text-indigo-600 bg-slate-100 dark:bg-slate-800"
                                title="Equalize Installments"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Installments Schedule Editor -->
                <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Installment Schedule (<span x-text="installments.length"></span>)
                        </h4>

                        <!-- Balance Indicator -->
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-slate-500">Scheduled: {{ currency() }} <strong class="num-tabular" x-text="installmentsSum.toLocaleString()"></strong></span>
                            <template x-if="isSumBalanced">
                                <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-md border border-emerald-200">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    Balanced
                                </span>
                            </template>
                            <template x-if="!isSumBalanced">
                                <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 font-semibold bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-md border border-rose-200">
                                    Diff: {{ currency() }} <span x-text="sumDifference"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 font-semibold text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-4 py-2.5 w-16">#</th>
                                    <th class="px-4 py-2.5">Due Date</th>
                                    <th class="px-4 py-2.5">Amount ({{ currency() }})</th>
                                    <th class="px-4 py-2.5 w-12 text-right" x-show="feeType === 'installments'"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="(inst, index) in installments" :key="index">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                        <td class="px-4 py-2.5 font-bold text-slate-500" x-text="'M' + inst.number"></td>
                                        <td class="px-4 py-2.5">
                                            <input
                                                type="date"
                                                :name="'installments[' + index + '][due_date]'"
                                                x-model="inst.due_date"
                                                required
                                                class="rounded-lg text-xs py-1 px-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                            />
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="relative w-40">
                                                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-[10px] text-slate-400">{{ currency() }}</span>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    :name="'installments[' + index + '][amount]'"
                                                    x-model.number="inst.amount"
                                                    required
                                                    class="w-full pl-6 rounded-lg text-xs py-1 px-2 font-mono num-tabular border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 text-right" x-show="feeType === 'installments'">
                                            <button
                                                type="button"
                                                @click="removeInstallment(index)"
                                                class="text-rose-500 hover:text-rose-700 p-1"
                                                title="Delete this installment"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button
                            type="button"
                            x-show="feeType === 'installments'"
                            @click="addInstallment()"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span>Add Another Installment</span>
                        </button>

                        <button
                            type="button"
                            @click="regenerateSchedule()"
                            class="text-xs font-medium text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 underline"
                        >
                            Re-split equally
                        </button>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        * Clicking Enroll will record the student profile and generate the installment schedule.
                    </span>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('students.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            :disabled="!isSumBalanced"
                            :class="!isSumBalanced ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25'"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white transition-all"
                        >
                            Confirm & Enroll Student
                        </button>
                    </div>
                </div>
            </x-card>
        </form>

    </div>
</x-app-layout>
