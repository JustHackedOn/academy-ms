<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('students.show', $student) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    <span>Back to {{ $student->name }}</span>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Edit Student</h1>
            </div>

            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Are you sure you want to archive this student? Financial records will remain preserved.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 p-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/30">
                        Archive Student
                    </button>
                </form>
            @endif
        </div>

        @if($hasPayments)
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <div>
                    <strong class="font-bold block">Notice: Payments exist for this student!</strong>
                    <span>Modifying total fee or discount will keep all already-paid installments intact and automatically re-distribute the remaining balance across unpaid installments only.</span>
                </div>
            </div>
        @endif

        <x-card title="Student Information" subtitle="Update contact information and fee details.">
            <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Batch -->
                    <div>
                        <label for="batch_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Batch *
                        </label>
                        <select
                            name="batch_id"
                            id="batch_id"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            @foreach ($batches as $b)
                                <option value="{{ $b->id }}" {{ old('batch_id', $student->batch_id) == $b->id ? 'selected' : '' }}>
                                    {{ $b->code }} - {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('batch_id')" class="mt-1" />
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Student Name *
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $student->name) }}"
                            required
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <!-- Photo -->
                    <div>
                        <label for="photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Photo
                        </label>
                        <div class="flex items-center gap-3">
                            @if ($student->photo_url)
                                <img
                                    src="{{ $student->photo_url }}"
                                    alt="{{ $student->name }}"
                                    class="w-14 h-14 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-xs flex-shrink-0"
                                />
                            @endif
                            <div class="min-w-0">
                                <input
                                    type="file"
                                    name="photo"
                                    id="photo"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:px-3 file:py-2 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/50 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-950"
                                />
                                <p class="mt-1 text-[11px] text-slate-400">JPG, PNG or WebP — max 2 MB.</p>
                                @if ($student->photo_url)
                                    <label class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-semibold text-rose-600 dark:text-rose-400 cursor-pointer select-none">
                                        <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 dark:border-slate-600 text-rose-600 focus:ring-rose-500" />
                                        Remove current photo
                                    </label>
                                @endif
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Phone / WhatsApp *
                        </label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone', $student->phone) }}"
                            required
                            class="w-full rounded-xl text-sm font-mono border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <!-- Alt Phone -->
                    <div>
                        <label for="alt_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Alt Phone
                        </label>
                        <input
                            type="text"
                            name="alt_phone"
                            id="alt_phone"
                            value="{{ old('alt_phone', $student->alt_phone) }}"
                            class="w-full rounded-xl text-sm font-mono border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Email Address
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $student->email) }}"
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
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
                            <option value="active" {{ old('status', $student->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ old('status', $student->status) === 'paused' ? 'selected' : '' }}>Paused</option>
                            <option value="dropped" {{ old('status', $student->status) === 'dropped' ? 'selected' : '' }}>Dropped</option>
                            <option value="completed" {{ old('status', $student->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
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
                                value="{{ old('total_fee', $student->total_fee) }}"
                                required
                                class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Discount -->
                    <div>
                        <label for="discount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Discount ({{ currency() }})
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-slate-400">{{ currency() }}</span>
                            <input
                                type="number"
                                step="100"
                                name="discount"
                                id="discount"
                                value="{{ old('discount', $student->discount) }}"
                                class="w-full pl-9 rounded-xl text-sm num-tabular border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="2"
                            class="w-full rounded-xl text-sm border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >{{ old('notes', $student->notes) }}</textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <a href="{{ route('students.show', $student) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/25">
                        Save Changes
                    </button>
                </div>
            </form>
        </x-card>

    </div>
</x-app-layout>
