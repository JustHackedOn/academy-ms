<div
    x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-init="
        @if (session()->has('success'))
            add('{{ addslashes(session('success')) }}', 'success');
        @endif
        @if (session()->has('error'))
            add('{{ addslashes(session('error')) }}', 'error');
        @endif
        @if (session()->has('status'))
            add('{{ addslashes(session('status')) }}', 'info');
        @endif
    "
    @toast.window="add($event.detail.message, $event.detail.type || 'success')"
    class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            :class="{
                'bg-emerald-600 text-white': toast.type === 'success',
                'bg-rose-600 text-white': toast.type === 'error',
                'bg-indigo-600 text-white': toast.type === 'info',
                'bg-amber-600 text-white': toast.type === 'warning'
            }"
            class="pointer-events-auto flex items-center justify-between gap-3 px-4 py-3 rounded-2xl shadow-xl border border-white/10"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <template x-if="toast.type === 'success'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                </template>
                <span class="text-sm font-medium leading-snug" x-text="toast.message"></span>
            </div>

            <button @click="remove(toast.id)" class="text-white/80 hover:text-white flex-shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </template>
</div>
