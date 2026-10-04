<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        sidebarOpen: false,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        toggleSidebar() {
            const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar_collapsed', collapsed);
        }
    }"
    :class="{ 'dark': darkMode }"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ academy_name() }} @hasSection('title') - @yield('title') @endif</title>

    <!-- Pre-init dark mode & sidebar state to prevent FOUC -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    <!-- Currency symbol used by Alpine/x-text and inline money formatters -->
    <script>window.APP_CURRENCY = @json(currency());</script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen selection:bg-indigo-500 selection:text-white">

    <div class="min-h-screen flex">

        <!-- Mobile Sidebar Backdrop -->
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden"
            style="display: none;"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="{
                'translate-x-0': sidebarOpen,
                '-translate-x-full lg:translate-x-0': !sidebarOpen
            }"
            class="sidebar-shell fixed inset-y-0 left-0 z-50 flex flex-col bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800/80 lg:static"
        >
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center gap-2 px-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
                <div class="sidebar-hide flex items-center justify-between gap-2 w-full min-w-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                        <x-logo size="md" class="flex-shrink-0" />
                        <div class="flex flex-col truncate">
                            <span class="font-bold text-base tracking-tight text-slate-900 dark:text-white leading-tight">{{ academy_name() }}</span>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Portal</span>
                        </div>
                    </a>

                    <div class="flex items-center gap-1 shrink-0">
                        <!-- Collapse Toggle (Desktop) -->
                        <button
                            @click="toggleSidebar()"
                            type="button"
                            class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition-colors"
                            title="Collapse Sidebar"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                            </svg>
                        </button>

                        <!-- Mobile Close -->
                        <button
                            @click="sidebarOpen = false"
                            type="button"
                            class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-200 dark:hover:bg-slate-800"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Collapsed state: single button expands the sidebar -->
                <div class="sidebar-expand items-center justify-center w-full">
                    <button
                        @click="toggleSidebar()"
                        type="button"
                        title="Expand Sidebar"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1.5">
                <!-- Dashboard -->
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    <x-slot:title>Dashboard</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25zM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25z" /></svg>
                </x-sidebar-link>

                <!-- Batches -->
                <x-sidebar-link :href="Route::has('batches.index') ? route('batches.index') : '#'" :active="request()->routeIs('batches.*')">
                    <x-slot:title>Batches</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" /></svg>
                </x-sidebar-link>

                <!-- Students -->
                <x-sidebar-link :href="Route::has('students.index') ? route('students.index') : '#'" :active="request()->routeIs('students.*')">
                    <x-slot:title>Students</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z" /></svg>
                </x-sidebar-link>

                <!-- Payments -->
                <x-sidebar-link :href="Route::has('payments.index') ? route('payments.index') : '#'" :active="request()->routeIs('payments.*')">
                    <x-slot:title>Payments</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 2.25 19.5z" /></svg>
                </x-sidebar-link>

                <!-- Monthly Ledger -->
                <x-sidebar-link :href="Route::has('ledger.index') ? route('ledger.index') : '#'" :active="request()->routeIs('ledger.*')">
                    <x-slot:title>Monthly Ledger</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.25 2.25L17.25 6" /></svg>
                </x-sidebar-link>

                <!-- Dues & Defaulters -->
                <x-sidebar-link :href="Route::has('dues.index') ? route('dues.index') : '#'" :active="request()->routeIs('dues.*')">
                    <x-slot:title>Dues & Defaulters</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </x-sidebar-link>

                <!-- Reports & Exports -->
                <x-sidebar-link :href="Route::has('reports.index') ? route('reports.index') : '#'" :active="request()->routeIs('reports.*')">
                    <x-slot:title>Reports</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z" /></svg>
                </x-sidebar-link>

                <!-- Expenses -->
                <x-sidebar-link :href="Route::has('expenses.index') ? route('expenses.index') : '#'" :active="request()->routeIs('expenses.*')">
                    <x-slot:title>Expenses</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" /></svg>
                </x-sidebar-link>

                @if (auth()->user()?->isAdmin())
                <div class="pt-3 pb-1">
                    <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                    <p class="sidebar-hide px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">System</p>
                </div>

                <!-- Activity Log -->
                <x-sidebar-link :href="Route::has('activity.index') ? route('activity.index') : '#'" :active="request()->routeIs('activity.*')">
                    <x-slot:title>Activity Log</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" /></svg>
                </x-sidebar-link>

                <!-- Settings -->
                <x-sidebar-link :href="Route::has('settings.index') ? route('settings.index') : '#'" :active="request()->routeIs('settings.*')">
                    <x-slot:title>Settings</x-slot:title>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" /></svg>
                </x-sidebar-link>
                @endif
            </nav>

            <!-- User Info in Sidebar Bottom -->
            <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                <div class="sidebar-user flex items-center gap-3 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                    <x-avatar :name="auth()->user()->name ?? 'Admin'" size="sm" />
                    <div class="sidebar-hide truncate flex-1">
                        <p class="text-xs font-semibold text-slate-800 dark:text-white truncate">
                            {{ auth()->user()->name ?? 'User' }}
                        </p>
                        <p class="text-[11px] text-slate-400 capitalize">
                            {{ auth()->user()->role ?? 'admin' }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Topbar -->
            <header class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Left: Mobile Menu Toggle & Search Trigger -->
                <div class="flex items-center gap-3">
                    <button
                        @click="sidebarOpen = true"
                        type="button"
                        class="lg:hidden p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Global Search Button (Ctrl+K trigger) -->
                    <button
                        type="button"
                        @click="$dispatch('open-search')"
                        class="flex items-center gap-2.5 px-2 sm:px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700 text-xs font-medium transition-all shadow-2xs"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <span class="hidden sm:inline">Search students, batches, receipts...</span>
                        <kbd class="hidden md:inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-mono bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-500 shadow-2xs">Ctrl K</kbd>
                    </button>
                </div>

                <!-- Right: Month Picker, Dark Toggle, User Dropdown -->
                <div class="flex items-center gap-3">

                    <!-- Month Picker (Default Current Month) -->
                    <div class="relative" x-data="{ open: false }">
                        <form method="GET" action="{{ url()->current() }}" id="monthFilterForm" class="flex items-center">
                            <input
                                type="month"
                                name="month"
                                value="{{ request('month', now()->format('Y-m')) }}"
                                onchange="this.form.submit()"
                                class="text-xs font-medium py-1.5 px-2 sm:px-3 rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs max-sm:w-[7.5rem]"
                            />
                        </form>
                    </div>

                    <!-- Dark Mode Toggle Button -->
                    <button
                        @click="toggleTheme()"
                        type="button"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
                        :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                    >
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>

                    <!-- User Menu Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            type="button"
                            class="flex items-center gap-2 p-1 pl-2 rounded-xl border border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                        >
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 hidden sm:inline">
                                {{ auth()->user()->name ?? 'User' }}
                            </span>
                            <x-avatar :name="auth()->user()->name ?? 'Admin'" size="sm" />
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-slate-200 dark:border-slate-800 py-1.5 z-50 divide-y divide-slate-100 dark:divide-slate-800"
                            style="display: none;"
                        >
                            <div class="px-4 py-2.5">
                                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                    {{ auth()->user()->name }}
                                </p>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ auth()->user()->email }}
                                </p>
                                <div class="mt-2">
                                    <x-badge :status="auth()->user()->role ?? 'admin'" size="sm" />
                                </div>
                            </div>

                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                    Profile Settings
                                </a>
                            </div>

                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Page Heading (Optional) -->
            @isset($header)
                <div class="bg-white/50 dark:bg-slate-900/50 border-b border-slate-200/60 dark:border-slate-800/60 py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            @endisset

            <!-- Page Main Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>
        </div>

    </div>

    <!-- Global Ctrl/Cmd+K Search Modal -->
    @include('layouts.partials.global-search')

    <!-- Toast Notification System -->
    <x-toast />

    <!-- Keyboard Shortcuts Handler (Ctrl+K) -->
    <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('open-search'));
            }
        });
    </script>
</body>
</html>
