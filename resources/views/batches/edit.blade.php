<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('batches.show', $batch) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>Back to {{ $batch->name }}</span>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Edit Batch</h1>
            </div>

            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('batches.destroy', $batch) }}" onsubmit="return confirm('Delete batch {{ $batch->code }} — {{ $batch->name }}? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 p-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/30">
                        Delete Batch
                    </button>
                </form>
            @endif
        </div>

        <x-card title="Edit Batch Details" subtitle="Update batch duration, fee or schedule details.">
            <form method="POST" action="{{ route('batches.update', $batch) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Batch Name -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Batch Name *
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $batch->name) }}"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <!-- Batch Code -->
                    <div>
                        <label for="code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Batch Code (Unique) *
                        </label>
                        <input
                            type="text"
                            name="code"
                            id="code"
                            value="{{ old('code', $batch->code) }}"
                            required
                            class="w-full rounded-xl text-sm font-mono uppercase border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Status *
                        </label>
                        <select
                            name="status"
                            id="status"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="upcoming" {{ old('status', $batch->status) === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="running" {{ old('status', $batch->status) === 'running' ? 'selected' : '' }}>Running</option>
                            <option value="completed" {{ old('status', $batch->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="archived" {{ old('status', $batch->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>

                    <!-- Default Fee -->
                    <div>
                        <label for="default_fee" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Default Fee ({{ currency() }}) *
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-slate-400">{{ currency() }}</span>
                            <input
                                type="number"
                                step="100"
                                name="default_fee"
                                id="default_fee"
                                value="{{ old('default_fee', $batch->default_fee) }}"
                                required
                                class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('default_fee')" class="mt-1" />
                    </div>

                    <!-- Duration in Months -->
                    <div>
                        <label for="duration_months" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Duration (Months) *
                        </label>
                        <input
                            type="number"
                            min="1"
                            max="24"
                            name="duration_months"
                            id="duration_months"
                            value="{{ old('duration_months', $batch->duration_months) }}"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('duration_months')" class="mt-1" />
                    </div>

                    <!-- Start Date -->
                    <div class="sm:col-span-2">
                        <label for="start_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Start Date *
                        </label>
                        <input
                            type="date"
                            name="start_date"
                            id="start_date"
                            value="{{ old('start_date', $batch->start_date?->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                    </div>

                    <!-- Notes -->
                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Internal Notes & Schedule
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >{{ old('notes', $batch->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <a href="{{ route('batches.show', $batch) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25">
                        Update Batch
                    </button>
                </div>
            </form>
        </x-card>

    </div>
</x-app-layout>
