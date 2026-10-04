{{--
    Global Ctrl/Cmd+K search modal.
    Included from layouts/app.blade.php; listens for the `open-search` window event.
--}}
<div
    x-data="globalSearch()"
    @open-search.window="open()"
    @keydown.escape.window="close()"
    x-cloak
>
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] bg-slate-900/60 backdrop-blur-sm"
        style="display: none;"
        @click="close()"
    ></div>

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="fixed inset-x-3 top-[12vh] sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-full sm:max-w-xl z-[61]"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-label="Global search"
    >
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">

            <!-- Input row -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>

                <input
                    x-ref="input"
                    x-model="query"
                    @input.debounce.250ms="search()"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="go()"
                    type="text"
                    autocomplete="off"
                    placeholder="Search students by name or phone, batches, receipts…"
                    class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 p-0"
                />

                <span x-show="loading" class="text-[10px] font-semibold uppercase tracking-wider text-indigo-500 animate-pulse">searching</span>

                <kbd
                    x-show="!loading"
                    class="hidden sm:inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500"
                >ESC</kbd>
            </div>

            <!-- Results -->
            <div class="max-h-[60vh] overflow-y-auto">

                {{-- Idle --}}
                <template x-if="query.trim().length < 2 && !loading">
                    <div class="px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">Type at least 2 characters to search.</p>
                        <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-[10px] text-slate-400">
                            <span class="px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 font-mono">&#8593; &#8595; navigate</span>
                            <span class="px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 font-mono">&#8629; open</span>
                            <span class="px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 font-mono">esc close</span>
                        </div>
                    </div>
                </template>

                {{-- No matches --}}
                <template x-if="query.trim().length >= 2 && !loading && flat.length === 0">
                    <div class="px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">No matches for &ldquo;<span x-text="query"></span>&rdquo;.</p>
                    </div>
                </template>

                {{-- Grouped results --}}
                <template x-for="group in groups" :key="group.key">
                    <div x-show="group.items.length > 0" class="py-1.5">
                        <p class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="group.title"></p>
                        <template x-for="item in group.items" :key="item.url">
                            <a
                                :href="item.url"
                                :data-index="flat.indexOf(item)"
                                @mouseenter="active = flat.indexOf(item)"
                                class="flex items-center gap-3 px-4 py-2.5 transition-colors"
                                :class="active === flat.indexOf(item) ? 'bg-indigo-50 dark:bg-indigo-950/40' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                            >
                                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-white truncate" x-text="item.label"></p>
                                    <p class="text-[10px] text-slate-400 truncate" x-text="item.sub"></p>
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <p class="text-[10px] font-bold num-tabular text-slate-600 dark:text-slate-300" x-text="item.meta"></p>
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" x-text="item.status"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400 bg-slate-50/60 dark:bg-slate-900/60">
                <span>Students &middot; Batches &middot; Receipts</span>
                <span class="font-mono">Ctrl K</span>
            </div>
        </div>
    </div>
</div>

<script>
    function globalSearch() {
        return {
            isOpen: false,
            query: '',
            loading: false,
            groups: [],
            flat: [],
            active: 0,
            controller: null,

            open() {
                this.isOpen = true;
                this.$nextTick(() => this.$refs.input?.focus());
            },

            close() {
                this.isOpen = false;
                this.query = '';
                this.groups = [];
                this.flat = [];
                this.active = 0;
            },

            async search() {
                const term = this.query.trim();

                if (term.length < 2) {
                    this.groups = [];
                    this.flat = [];
                    this.loading = false;
                    return;
                }

                if (this.controller) {
                    this.controller.abort();
                }
                this.controller = new AbortController();
                this.loading = true;

                try {
                    const response = await fetch(`{{ route('search.index') }}?q=${encodeURIComponent(term)}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: this.controller.signal,
                    });
                    const data = await response.json();

                    this.groups = (data.groups || []).filter((g) => g.items && g.items.length > 0);
                    this.flat = this.groups.flatMap((g) => g.items);
                    this.active = 0;
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        this.groups = [];
                        this.flat = [];
                    }
                } finally {
                    this.loading = false;
                }
            },

            move(delta) {
                if (this.flat.length === 0) return;
                this.active = (this.active + delta + this.flat.length) % this.flat.length;
                this.$nextTick(() => {
                    this.$el.querySelector(`a[data-index="${this.active}"]`)
                        ?.scrollIntoView({ block: 'nearest' });
                });
            },

            go() {
                const item = this.flat[this.active];
                if (item) {
                    window.location.href = item.url;
                }
            },
        };
    }
</script>
