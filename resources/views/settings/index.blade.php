<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Academy Settings</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Branding, finance defaults, WhatsApp reminder templates and portal users.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/30 px-4 py-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 px-4 py-3 text-sm font-semibold text-rose-700 dark:text-rose-300">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 px-4 py-3">
                    <p class="text-xs font-semibold text-rose-700 dark:text-rose-300 mb-1">Please fix the following:</p>
                    <ul class="list-disc list-inside text-[11px] text-rose-600 dark:text-rose-400 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">
                <!-- Left: grouped settings -->
                <div class="lg:col-span-8 space-y-5">
                    @php
                        $grouped = collect($fields)->groupBy(fn ($meta) => $meta['group']);
                    @endphp

                    @foreach ($grouped as $group => $fieldsInGroup)
                        <x-card :title="$group" :subtitle="$group === 'Templates' ? 'Placeholders: {name}, {batch}, {amount_due}, {due_date}, {academy}' : ($group === 'Finance' ? 'Defaults used across receipts, ledgers and reminders.' : 'Shown on receipts, reports and reminders.')">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($fieldsInGroup as $key => $meta)
                                    @php
                                        $isWide = $meta['type'] === 'textarea';
                                    @endphp
                                    <div class="{{ $isWide ? 'sm:col-span-2' : '' }}">
                                        <x-input-label :for="'setting_' . $key" :value="$meta['label']" />

                                        @if ($meta['type'] === 'textarea')
                                            <textarea
                                                id="setting_{{ $key }}"
                                                name="{{ $key }}"
                                                rows="3"
                                                maxlength="1000"
                                                class="mt-1.5 w-full rounded-xl text-sm border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                            >{{ old($key, $settings[$key] ?? '') }}</textarea>
                                        @else
                                            <input
                                                id="setting_{{ $key }}"
                                                name="{{ $key }}"
                                                type="text"
                                                maxlength="300"
                                                value="{{ old($key, $settings[$key] ?? '') }}"
                                                class="mt-1.5 w-full rounded-xl text-sm border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                            />
                                        @endif
                                    </div>

                                    @if ($key === 'academy_name')
                                        <!-- Logo upload sits with the academy identity fields -->
                                        <div class="sm:col-span-2">
                                            <x-input-label for="logo" value="Academy Logo (PNG / JPG, max 2 MB)" />
                                            <div class="mt-1.5 flex items-center gap-4">
                                                <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden">
                                                    @if ($logo)
                                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="Academy logo" class="w-full h-full object-contain" />
                                                    @else
                                                        <span class="text-xs font-bold text-slate-400">LOGO</span>
                                                    @endif
                                                </div>
                                                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"
                                                       class="text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:px-3 file:py-2 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/50 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-950" />
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('settings.index') }}"
                           class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Discard</a>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-500/25 transition-colors">
                            Save Settings
                        </button>
                    </div>
                </div>

                <!-- Right rail -->
                <div class="lg:col-span-4 space-y-5">
                    <!-- Live reminder preview -->
                    <x-card title="Reminder preview" subtitle="How the English template reads today">
                        @php
                            $preview = str_replace(
                                ['{name}', '{batch}', '{amount_due}', '{due_date}', '{academy}'],
                                ['Ahmed Ali', 'Full-Stack Web Development', '5,000', now()->addDays(5)->format('d M Y'), academy_name()],
                                $settings['whatsapp_template'] ?? ''
                            );
                        @endphp
                        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 p-3.5">
                            <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ $preview }}</p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach (['{name}', '{batch}', '{amount_due}', '{due_date}', '{academy}'] as $tag)
                                <code class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-600 dark:text-slate-300">{{ $tag }}</code>
                            @endforeach
                        </div>
                    </x-card>

                    <!-- Recent backups -->
                    <x-card title="Database backups" subtitle="Newest files in storage/backups">
                        @if (empty($backups))
                            <p class="text-xs text-slate-400">No backups yet. The scheduler writes one every night via <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px]">php artisan app:backup</code>.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($backups as $backup)
                                    <li class="flex items-center justify-between gap-3 text-xs">
                                        <span class="font-semibold text-slate-700 dark:text-slate-200 truncate">{{ $backup['name'] }}</span>
                                        <span class="text-slate-400 whitespace-nowrap">{{ number_format($backup['size'] / 1024, 1) }} KB · {{ $backup['modified'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-card>
                </div>
            </div>
        </form>

        <!-- Portal users -->
        <x-card title="Portal users" subtitle="Admins manage everything; partners can record payments and follow up with students." :padding="'p-0'">
            @if (auth()->user()?->isAdmin())
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <form method="POST" action="{{ route('settings.users.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        @csrf
                        <div class="sm:col-span-3">
                            <x-input-label for="user_name" value="Name" />
                            <input id="user_name" name="name" type="text" required maxlength="120" value="{{ old('name') }}"
                                   class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="user_email" value="Email" />
                            <input id="user_email" name="email" type="email" required maxlength="120" value="{{ old('email') }}"
                                   class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="user_password" value="Password" />
                            <input id="user_password" name="password" type="password" required minlength="8" maxlength="120"
                                   class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="user_role" value="Role" />
                            <select id="user_role" name="role"
                                    class="mt-1 w-full rounded-xl text-xs py-2 px-3 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="partner" {{ old('role') === 'partner' ? 'selected' : '' }}>Partner</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="w-full px-3 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold hover:opacity-90 transition-opacity">Add user</button>
                        </div>
                    </form>
                </div>
            @endif

            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-5 py-3 text-left">Name</th>
                        <th class="px-5 py-3 text-left">Email</th>
                        <th class="px-5 py-3 text-left">Role</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </x-slot:header>

                @foreach ($users as $user)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$user->name" size="sm" />
                                <span class="text-sm font-semibold text-slate-800 dark:text-white">{{ $user->name }}</span>
                                @if ($user->id === auth()->id())
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">(you)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $user->email }}</td>
                        <td class="px-5 py-3"><x-badge :status="$user->role" size="sm" /></td>
                        <td class="px-5 py-3 text-right">
                            @if (auth()->user()?->isAdmin() && $user->id !== auth()->id())
                                <form method="POST" action="{{ route('settings.users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm('Remove {{ $user->name }} from the portal?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Remove user">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>
</x-app-layout>
