<x-app-layout>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Activity Log</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Every create, update and delete recorded with the user, IP address and changed values.</p>
            </div>
        </div>

        <!-- KPI tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <x-stat-tile title="Total Events" :value="number_format($stats['total'])" subtitle="All-time audit trail" variant="indigo" />
            <x-stat-tile title="Today" :value="number_format($stats['today'])" subtitle="Changes made today" variant="emerald" />
            <x-stat-tile title="Last 7 Days" :value="number_format($stats['week'])" subtitle="Recent activity" variant="violet" />
            <x-stat-tile title="Active Users" :value="number_format($stats['actors'])" subtitle="Distinct actors" variant="amber" />
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl md:rounded-3xl p-5 sm:p-6 shadow-sm">
            <form method="GET" action="{{ route('activity.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-2">
                    <x-input-label for="act_search" value="Search" />
                    <input id="act_search" name="search" type="text" value="{{ request('search') }}" placeholder="Action, user or IP…"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="act_user" value="User" />
                    <select id="act_user" name="user_id"
                            class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="">All users</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="act_action" value="Action" />
                    <select id="act_action" name="action"
                            class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ str_replace('_', ' ', $action) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="act_from" value="From" />
                    <input id="act_from" name="from" type="date" value="{{ request('from') }}"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="act_from" value="From" />
                    <input id="act_from" name="from" type="date" value="{{ request('from') }}"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="act_to" value="To" />
                    <input id="act_to" name="to" type="date" value="{{ request('to') }}"
                           class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div class="sm:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 px-3 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold hover:opacity-90 transition-opacity">Filter</button>
                    <a href="{{ route('activity.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Reset</a>
                </div>
            </form>
        </div>

        <!-- Audit trail -->
        <x-table>
            <x-slot:header>
                <tr>
                    <th class="px-5 py-3 text-left">When</th>
                    <th class="px-5 py-3 text-left">User</th>
                    <th class="px-5 py-3 text-left">Action</th>
                    <th class="px-5 py-3 text-left">Record</th>
                    <th class="px-5 py-3 text-left">Changes</th>
                    <th class="px-5 py-3 text-right">IP</th>
                </tr>
            </x-slot:header>

            @forelse ($logs as $log)
                @php
                    $old = collect($log->old_values ?: []);
                    $new = collect($log->new_values ?: []);
                    $diff = $old->isEmpty() && $new->isEmpty()
                        ? collect()
                        : $new->map(fn ($value, $key) => [
                            'field' => $key,
                            'before' => $old->get($key),
                            'after' => $value,
                        ])->values();
                @endphp
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 align-top">
                    <td class="px-5 py-3 whitespace-nowrap">
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $log->created_at?->format('d M Y') }}</p>
                        <p class="text-[11px] text-slate-400">{{ $log->created_at?->format('H:i:s') }}</p>
                    </td>
                    <td class="px-5 py-3">
                        @if ($log->user)
                            <div class="flex items-center gap-2">
                                <x-avatar :name="$log->user->name" size="sm" />
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-white truncate">{{ $log->user->name }}</p>
                                    <x-badge :status="$log->user->role ?? 'admin'" size="sm" class="mt-0.5" />
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-slate-400">System</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                            {{ str_replace('_', ' ', $log->action) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">
                        @if ($log->subject_type)
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ class_basename($log->subject_type) }}</span>
                            @if ($log->subject_id)
                                <span class="text-slate-400">#{{ $log->subject_id }}</span>
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if ($diff->isEmpty())
                            <span class="text-[11px] text-slate-400">No field changes</span>
                        @else
                            <div class="space-y-1 max-w-md">
                                @foreach ($diff as $change)
                                    <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                        <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $change['field'] }}</span>
                                        <span class="text-rose-500 line-through decoration-rose-300">
                                            {{ is_scalar($change['before']) || $change['before'] === null ? \Illuminate\Support\Str::limit((string) ($change['before'] ?? '—'), 28) : json_encode($change['before']) }}
                                        </span>
                                        <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            {{ is_scalar($change['after']) || $change['after'] === null ? \Illuminate\Support\Str::limit((string) ($change['after'] ?? '—'), 28) : json_encode($change['after']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right text-[11px] num-tabular text-slate-400 whitespace-nowrap">{{ $log->ip ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <p class="text-sm text-slate-400">No activity recorded yet. Every payment, edit and deletion will show up here.</p>
                    </td>
                </tr>
            @endforelse

            <x-slot:pagination>
                {{ $logs->links() }}
            </x-slot:pagination>
        </x-table>
    </div>
</x-app-layout>
