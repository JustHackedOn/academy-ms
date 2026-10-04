<x-app-layout>
    <div
        class="space-y-6"
        x-data="expensePage({{ \Illuminate\Support\Js::from([
            'storeUrl' => route('expenses.store'),
            'updateBase' => url('expenses'),
            'serverErrors' => $errors->any() ? collect($errors->all())->values() : [],
            'old' => old(),
        ]) }})"
    >

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Expenses</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Track every rupee spent and compare it against collections to see real net profit.</p>
            </div>

            <button
                type="button"
                @click="openCreate()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm shadow-indigo-500/25 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Expense
            </button>
        </div>

        <!-- KPI tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <x-stat-tile
                :title="$stats['month_name'] . ' Spend'"
                :value="money($stats['month_total'], 0)"
                subtitle="Filtered month total"
                variant="rose"
            />
            <x-stat-tile
                title="Net Profit"
                :value="($stats['net_profit'] >= 0 ? '+' : '-') . money(abs($stats['net_profit']), 0)"
                subtitle="Collected minus spent"
                :variant="$stats['net_profit'] >= 0 ? 'emerald' : 'rose'"
            />
            <x-stat-tile
                title="All-time Spend"
                :value="money($stats['all_time_total'], 0)"
                :subtitle="$stats['count'] . ' entries'"
                variant="amber"
            />
            <x-stat-tile
                title="Collected"
                :value="money($stats['received'], 0)"
                :subtitle="'Receipts in ' . $stats['month_name']"
                variant="indigo"
            />
        </div>

        <!-- Category breakdown + filters -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">

            <x-card title="Where the money went" :subtitle="$stats['month_name'] . ' by category'" class="lg:col-span-4">
                @php
                    $catTotal = array_sum($byCategory);
                    $catColors = [
                        'Utilities' => 'bg-sky-500', 'Rent' => 'bg-violet-500', 'Salaries' => 'bg-indigo-500',
                        'Marketing' => 'bg-amber-500', 'Software' => 'bg-emerald-500', 'Equipment' => 'bg-rose-500',
                        'Miscellaneous' => 'bg-slate-400',
                    ];
                @endphp

                @if ($catTotal <= 0)
                    <p class="text-xs text-slate-400 py-6 text-center">No expenses recorded for this month.</p>
                @else
                    <div class="space-y-3.5">
                        @foreach ($byCategory as $cat => $amt)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="inline-flex items-center gap-2 font-medium text-slate-600 dark:text-slate-300">
                                        <span class="w-2 h-2 rounded-full {{ $catColors[$cat] ?? 'bg-slate-400' }}"></span>
                                        {{ $cat }}
                                    </span>
                                    <span class="num-tabular font-bold text-slate-800 dark:text-white">
                                        {{ money($amt, 0) }}
                                        <span class="text-slate-400 font-normal">{{ round(($amt / $catTotal) * 100) }}%</span>
                                    </span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full {{ $catColors[$cat] ?? 'bg-slate-400' }}" style="width: {{ round(($amt / $catTotal) * 100, 1) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl md:rounded-3xl p-5 sm:p-6 shadow-sm">
                <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-4">
                        <x-input-label for="exp_search" value="Search" />
                        <input id="exp_search" name="search" type="text" value="{{ request('search') }}" placeholder="Title or note…"
                               class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <div class="sm:col-span-3">
                        <x-input-label for="exp_month" value="Month" />
                        <input id="exp_month" name="month" type="month" value="{{ $stats['month'] }}"
                               class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <div class="sm:col-span-3">
                        <x-input-label for="exp_category" value="Category" />
                        <select id="exp_category" name="category"
                                class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                            <option value="">All categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit" class="flex-1 px-3 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold hover:opacity-90 transition-opacity">Filter</button>
                        <a href="{{ route('expenses.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Reset</a>
                    </div>
                </form>
            </div>
        </div>


        <!-- Expenses table -->
        <x-table>
            <x-slot:header>
                <tr>
                    <th class="px-5 py-3 text-left">Expense</th>
                    <th class="px-5 py-3 text-left">Category</th>
                    <th class="px-5 py-3 text-left">Date</th>
                    <th class="px-5 py-3 text-left">Recorded By</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </x-slot:header>

            @forelse ($expenses as $expense)
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                    <td class="px-5 py-3">
                        <p class="font-semibold text-slate-800 dark:text-white text-sm">{{ $expense->title }}</p>
                        @if ($expense->note)
                            <p class="text-[11px] text-slate-400 line-clamp-1 max-w-xs">{{ $expense->note }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                            {{ $expense->category }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $expense->spent_on?->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $expense->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-right font-bold num-tabular text-rose-600 dark:text-rose-400 whitespace-nowrap">
                        {{ money((float) $expense->amount, 0) }}
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <button
                            type="button"
                            @click="openEdit({{ \Illuminate\Support\Js::from([
                                'id' => $expense->id,
                                'title' => $expense->title,
                                'category' => $expense->category,
                                'amount' => (float) $expense->amount,
                                'spent_on' => $expense->spent_on?->format('Y-m-d'),
                                'note' => $expense->note,
                            ]) }})"
                            class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors"
                            title="Edit"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                        </button>

                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline"
                              onsubmit="return confirm('Delete this expense? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <p class="text-sm text-slate-400">No expenses match the current filters.</p>
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <tr>
                    <td colspan="4" class="px-5 py-3 text-right text-xs uppercase tracking-wider">Page total</td>
                    <td class="px-5 py-3 text-right num-tabular">{{ money($expenses->sum('amount'), 0) }}</td>
                    <td></td>
                </tr>
            </x-slot:footer>

            <x-slot:pagination>
                {{ $expenses->links() }}
            </x-slot:pagination>
        </x-table>


        {{-- Server-side validation banner --}}
        <template x-if="serverErrors.length > 0">
            <div class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 px-4 py-3">
                <p class="text-xs font-semibold text-rose-700 dark:text-rose-300 mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-[11px] text-rose-600 dark:text-rose-400 space-y-0.5">
                    <template x-for="err in serverErrors" :key="err">
                        <li x-text="err"></li>
                    </template>
                </ul>
            </div>
        </template>

        <!-- ============ ADD / EDIT MODAL ============ -->
        <x-modal name="expense-form" max-width="lg">
            <form
                :action="editing ? updateBase + '/' + editing : storeUrl"
                method="POST"
                class="p-6"
                x-ref="expenseForm"
            >
                @csrf
                <template x-if="editing">
                    <div>
                        <input type="hidden" name="_method" value="PUT" />
                    </div>
                </template>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white" x-text="editing ? 'Edit Expense' : 'Add Expense'"></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Expenses reduce collected revenue to give you true net profit.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="exp_title" value="Title" />
                        <input id="exp_title" name="title" type="text" x-model="form.title" required maxlength="120"
                               class="mt-1.5 w-full rounded-xl text-sm border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="exp_cat" value="Category" />
                            <select id="exp_cat" name="category" x-model="form.category" required
                                    class="mt-1.5 w-full rounded-xl text-sm border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="exp_amount" value="Amount ({{ currency() }})" />
                            <input id="exp_amount" name="amount" type="number" step="0.01" min="0.01" x-model="form.amount" required
                                   class="mt-1.5 w-full rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500" />
                        </div>

                        <div>
                            <x-input-label for="exp_date" value="Date" />
                            <input id="exp_date" name="spent_on" type="date" x-model="form.spent_on" required
                                   class="mt-1.5 w-full rounded-xl text-sm border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="exp_note" value="Note (optional)" />
                        <textarea id="exp_note" name="note" rows="3" x-model="form.note" maxlength="500"
                                  class="mt-1.5 w-full rounded-xl text-sm border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-500/25 transition-colors"
                            x-text="editing ? 'Save Changes' : 'Record Expense'"></button>
                </div>
            </form>
        </x-modal>

    </div>


    <script>
        function expensePage(config) {
            return {
                storeUrl: config.storeUrl,
                updateBase: config.updateBase,
                serverErrors: config.serverErrors || [],
                editing: null,
                form: {
                    title: '',
                    category: '{{ $categories[0] }}',
                    amount: '',
                    spent_on: '{{ now()->format('Y-m-d') }}',
                    note: '',
                },

                openCreate() {
                    this.editing = null;
                    this.form = {
                        title: '',
                        category: '{{ $categories[0] }}',
                        amount: '',
                        spent_on: '{{ now()->format('Y-m-d') }}',
                        note: '',
                    };
                    this.$dispatch('open-modal', 'expense-form');
                },

                openEdit(expense) {
                    this.editing = expense.id;
                    this.form = {
                        title: expense.title,
                        category: expense.category,
                        amount: expense.amount,
                        spent_on: expense.spent_on,
                        note: expense.note || '',
                    };
                    this.$dispatch('open-modal', 'expense-form');
                },

                closeModal() {
                    this.$dispatch('close-modal', 'expense-form');
                },
            };
        }
    </script>
</x-app-layout>

