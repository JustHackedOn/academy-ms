<x-app-layout>
    <div class="space-y-6" x-data="{ templateLang: localStorage.getItem('wa_lang') || 'en', defaulters: {{ $defaulters->count() }} }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Dues &amp; Defaulters</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Students with overdue installments, sorted by days overdue.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Template language toggle -->
                <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <button
                        @click="templateLang = 'en'; localStorage.setItem('wa_lang', 'en')"
                        :class="templateLang === 'en' ? 'bg-white dark:bg-slate-900 text-indigo-600 shadow-2xs font-semibold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-colors"
                    >English</button>
                    <button
                        @click="templateLang = 'roman'; localStorage.setItem('wa_lang', 'roman')"
                        :class="templateLang === 'roman' ? 'bg-white dark:bg-slate-900 text-indigo-600 shadow-2xs font-semibold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-colors"
                    >Roman Urdu</button>
                </div>

                <form method="GET" action="{{ route('dues.index') }}">
                    <select name="batch_id" onchange="this.form.submit()" class="rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200">
                        <option value="">All Batches</option>
                        @foreach ($batches as $b)
                            <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>{{ $b->code }}</option>
                        @endforeach
                    </select>
                </form>

                <button
                    type="button"
                    @click="openAllReminders()"
                    x-show="defaulters.length > 0"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/25 transition-all"
                    style="display: none;"
                >
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                    <span>Open All Reminders</span>
                </button>
            </div>
        </div>

        <!-- KPI Tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-tile title="Defaulters" :value="$totals['students']" subtitle="Students with overdue dues" variant="rose" />
            <x-stat-tile title="Overdue Amount" :value="money($totals['amount'], 0)" subtitle="Total uncollected" variant="amber" />
            <x-stat-tile title="Worst Case" :value="$totals['max_days'] . ' days'" subtitle="Max days overdue" variant="violet" />
            <x-stat-tile title="Avg. Days Overdue" :value="$totals['students'] > 0 ? round($defaulters->avg('days_overdue')) : 0" subtitle="Across defaulters" variant="sky" />
        </div>

        <!-- Defaulters Table -->
        @if ($defaulters->isEmpty())
            <x-empty-state
                title="No defaulters"
                description="Every active student is up to date with their installments."
            />
        @else
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Days Late</th>
                        <th class="px-6 py-3.5">Overdue</th>
                        <th class="px-6 py-3.5 hidden md:table-cell">Inst. Late</th>
                        <th class="px-6 py-3.5 hidden md:table-cell">Oldest Due</th>
                        <th class="px-6 py-3.5 hidden lg:table-cell">Promise</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </x-slot:header>

                @foreach ($defaulters as $d)
                    @php $s = $d['student']; @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <x-avatar :name="$s->name" :photo="$s->photo_url" size="lg" />
                                <div class="min-w-0">
                                    <a href="{{ route('students.show', $s) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 transition-colors truncate block">
                                        {{ $s->name }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                            {{ $s->batch?->code ?? '—' }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-mono">{{ \App\Services\PhoneFormatter::display($s->phone) }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-2 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-sm font-extrabold text-rose-600 dark:text-rose-400 num-tabular">{{ $d['days_overdue'] }}</span>
                        </td>
                        <td class="px-6 py-4 font-extrabold num-tabular text-rose-600 dark:text-rose-400">
                            {{ money($d['overdue_amount'], 0) }}
                        </td>
                        <td class="px-6 py-4 font-bold num-tabular hidden md:table-cell">
                            {{ $d['overdue_count'] }}
                        </td>
                        <td class="px-6 py-4 font-bold hidden md:table-cell">
                            {{ $d['oldest_due']?->format('d M') ?? '—' }}
                        </td>
                        <td class="px-6 py-4 hidden lg:table-cell">
                            @if ($d['promise_date'])
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 text-[11px] text-amber-700 dark:text-amber-300">
                                    {{ $d['promise_date']->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a
                                :href="templateLang === 'roman' ? @js($d['template_roman']) : @js($d['template'])"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors"
                            >
                                <svg class="w-3.5 h-3.5 fill-emerald-600" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                                <span>Send Reminder</span>
                            </a>

                            <a href="tel:{{ $s->phone }}" class="inline-flex items-center justify-center ml-2 p-2 rounded-xl text-slate-500 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800" title="Call">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                            </a>

                            <a href="{{ route('students.show', $s) }}" class="inline-flex items-center justify-center ml-2 p-2 rounded-xl text-slate-500 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800" title="Open profile">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>
    <script>
        function openAllReminders() {
            const links = Array.from(document.querySelectorAll('a[target="_blank"][href*="wa.me"]'));
            if (!links.length) { return; }
            // Stagger to avoid popup blockers killing the batch.
            links.forEach((a, i) => {
                setTimeout(() => window.open(a.href, '_blank', 'noopener'), i * 400);
            });
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Opening ' + links.length + ' WhatsApp reminders…', type: 'info' } }));
        }
    </script>
</x-app-layout>

