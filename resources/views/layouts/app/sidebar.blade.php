{{-- resources/views/layouts/sidebar.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Cegah flash warna salah: set dark/light SEBELUM CSS & Alpine dimuat --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @include('partials.head')

    {{-- Font: DM Sans untuk body, Manrope untuk heading --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --font-heading: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --font-body: 'DM Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        .font-heading,
        .font-title-sm {
            font-family: var(--font-heading);
        }

        body,
        .font-body,
        .font-label-sm {
            font-family: var(--font-body);
        }

        /* iOS safe area support untuk sidebar */
        .safe-top {
            padding-top: max(0.625rem, env(safe-area-inset-top));
        }

        .safe-bottom {
            padding-bottom: max(0.625rem, env(safe-area-inset-bottom));
        }
    </style>
</head>

<body class="min-h-screen bg-white font-body text-on-surface antialiased dark:bg-zinc-950">

    {{-- Background pattern & dekorasi --}}
    <div class="pointer-events-none fixed inset-0 -z-10 bg-pattern-dots dark:opacity-40"></div>
    <div
        class="pointer-events-none fixed -top-16 left-1/4 -z-10 h-96 w-96 rounded-full bg-primary/5 blur-3xl dark:bg-primary/10">
    </div>
    <div
        class="pointer-events-none fixed top-40 right-10 -z-10 h-80 w-80 rounded-full bg-secondary/10 blur-3xl dark:bg-secondary/15">
    </div>

    <div x-data="{ sidebarOpen: false }" class="min-h-screen w-full bg-surface relative dark:bg-zinc-950">

        {{-- ======================= OVERLAY (mobile) ======================= --}}
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-on:click="sidebarOpen = false"
            class="fixed inset-0 z-40 lg:hidden
                   bg-zinc-950/60 backdrop-blur-md"
            style="display: none;" aria-hidden="true"></div>

        {{-- ======================= SIDEBAR KIRI ======================= --}}
        <aside x-data="{ usersOpen: true }" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50
                   w-50 lg:w-48
                   flex flex-col justify-between
                   bg-white/90 lg:bg-white/70
                   border-r border-white/80 dark:border-zinc-800
                   shadow-2xl lg:shadow-xs
                   select-none
                   transition-transform duration-300 ease-out
                   lg:translate-x-0
                   dark:bg-zinc-900/85 dark:lg:bg-zinc-900/70">
            <div class="flex flex-col h-full overflow-y-auto safe-top safe-bottom">

                {{-- ─────── Header & Logo ─────── --}}
                <div
                    class="p-2.5 border-b border-white/60
                           flex items-center gap-2
                           bg-white/60 dark:border-zinc-800 dark:bg-zinc-900/60">

                    <img alt="LPPM Annuqayah Logo" class="w-7 h-7 rounded-full object-contain shrink-0"
                        src="{{ asset('images/logo.webp') }}" />

                    <div class="flex flex-col min-w-0 flex-1">
                        <span
                            class="font-heading text-[12px] font-bold text-on-surface leading-tight tracking-tight truncate dark:text-zinc-100">
                            HIBAH DOSEN
                        </span>
                        <span
                            class="font-body text-[9px] text-on-surface-variant leading-none truncate dark:text-zinc-400">
                            Universitas Annuqayah
                        </span>
                    </div>

                    {{-- Tombol tutup (mobile only) --}}
                    <button type="button" x-on:click="sidebarOpen = false" aria-label="Close menu"
                        class="p-1.5 rounded-lg
                               text-on-surface-variant
                               hover:text-emerald-500 hover:bg-surface-container-low
                               active:scale-95
                               transition-all
                               lg:hidden
                               dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>

                {{-- ─────── Navigasi ─────── --}}
                <div class="flex flex-col p-2 gap-1.5 flex-1">

                    {{-- Dashboard --}}
                    <x-sidebar-link href="dashboard" title="Dashboard" icon="squares-2x2" />

                    @can('superadminOrAdmin')
                        <x-sidebar-link href="admin.manage-information" title="Information" icon="newspaper" />
                        <x-sidebar-link href="admin.manage-download" title="Download" icon="arrow-down-on-square-stack" />

                        {{-- Group: Administrator --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">
                                Administrator
                            </span>

                            <x-sidebar-link href="admin.manage-periods" title="Periods" icon="calendar-days" />
                            <x-sidebar-link href="admin.manage-schemes" title="Schemes" icon="rectangle-group" />

                            <div class="flex flex-col">
                                <button type="button" x-on:click="usersOpen = !usersOpen"
                                    class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg
                                           text-on-surface-variant
                                           hover:text-on-surface hover:bg-surface-container-low/50
                                           font-heading text-[11px] font-medium
                                           transition-all group
                                           dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.users
                                            class="size-4 text-outline group-hover:text-emerald-500 transition-colors shrink-0 dark:group-hover:text-emerald-400" />
                                        <span>Users</span>
                                    </div>
                                    <flux:icon.chevron-down
                                        class="size-3.5 text-outline transition-transform duration-200 shrink-0 dark:text-zinc-500"
                                        x-bind:class="usersOpen && 'rotate-180'" />
                                </button>

                                <div x-show="usersOpen" x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    class="flex flex-col gap-0.5 border-l border-white/80 ml-3.5 my-0.5 dark:border-zinc-800">
                                    <x-sidebar-link href="admin.manage-admins" title="Admins" icon="shield-check" />
                                    <x-sidebar-link href="admin.manage-reviewers" title="Reviewers"
                                        icon="clipboard-document-check" />
                                    <x-sidebar-link href="admin.manage-users" title="Users" icon="user-circle" />
                                </div>
                            </div>
                        </div>

                        {{-- Group: Internal Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">
                                {{ __('Internal Data') }}
                            </span>
                            <x-sidebar-link href="admin.internal.manage-researches" title="Research" icon="beaker" />
                            <x-sidebar-link href="admin.internal.manage-dedications" title="Community Service"
                                icon="heart" />
                        </div>

                        {{-- Group: External Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">
                                External Data
                            </span>
                            <x-sidebar-link href="admin.external.manage-researches" title="Research" icon="globe-alt" />
                            <x-sidebar-link href="admin.external.manage-dedications" title="Community Service" icon="gift" />
                        </div>
                    @endcan

                    @can('reviewer')
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">
                                Internal Data
                            </span>
                            <x-sidebar-link href="reviewer.review-proposal" title="Research" icon="beaker" />
                            <x-sidebar-link href="reviewer.review-progress-report" title="Progress Report"
                                icon="beaker" />
                        </div>
                    @endcan

                    @can('user')
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">
                                {{ __("Internal Data") }}
                            </span>
                            <x-sidebar-link href="user.internal.manage-researches" title="Research" icon="beaker" />
                            <x-sidebar-link href="user.internal.manage-dedications" title="Community Service"
                                icon="hand-raised" />
                        </div>
                        {{-- External Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">
                                {{ __("External Data") }}
                            </span>
                            <x-sidebar-link href="user.external.manage-researches" title="Research"
                                icon="globe-alt" />
                            <x-sidebar-link href="user.external.manage-dedications" title="Community Service"
                                icon="gift" />
                        </div>
                    @endcan
                </div>
            </div>
        </aside>

        {{-- ======================= KANAN: HEADER + KONTEN ======================= --}}
        <div class="flex flex-col min-h-screen bg-surface transition-[margin] duration-300 lg:ml-48 dark:bg-zinc-950">

            {{-- ══════════ HEADER ══════════ --}}
            <header
                class="sticky top-0 z-30
                       border-b border-white/60 dark:border-zinc-800
                       shadow-[0_1px_8px_rgba(0,0,0,0.04)]
                       px-3 sm:px-4 py-2
                       flex items-center justify-between gap-2 sm:gap-4">

                <div class="absolute inset-0 -z-10 pointer-events-none
                            bg-white/85 dark:bg-zinc-900/85
                            backdrop-blur-xl backdrop-saturate-150"
                    aria-hidden="true"></div>

                <div class="relative flex items-center gap-2 min-w-0 shrink">
                    <button type="button" x-on:click="sidebarOpen = true" aria-label="Open menu"
                        class="lg:hidden p-1.5 shadow-lg shadow-emerald-500/20 rounded-xl cursor-pointer
                               text-on-surface-variant
                               hover:bg-green-100 hover:text-emerald-600
                               active:scale-95
                               transition-all
                               dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-emerald-400">
                        <flux:icon.bars-3 class="size-5" />
                    </button>

                    <div
                        class="header-page-title font-body text-sm sm:text-base font-semibold
                               truncate max-w-[45vw] sm:max-w-none
                               text-zinc-800 dark:text-zinc-100">
                        @isset($title)
                            {{ $title }}
                        @else
                            {{ __('Dashboard') }}
                        @endisset
                    </div>
                </div>

                {{-- RIGHT: Search + Badges + Actions --}}
                <div class="relative flex items-center gap-1 sm:gap-2 shrink-0">
                    <div class="hidden sm:block">
                        <livewire:period-badge />
                    </div>

                    <button type="button"
                        x-on:click="document.documentElement.classList.toggle('dark');
                        localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';"
                        aria-label="Change theme"
                        class="p-1.5 rounded-full text-on-surface-variant cursor-pointer
                               hover:text-emerald-500 hover:bg-surface-container-high transition-colors
                               dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800">
                        <flux:icon.sun class="size-4 hidden dark:block" />
                        <flux:icon.moon class="size-4 dark:hidden" />
                    </button>

                    <livewire:notifications.notification-bell />

                    <x-desktop-user-menu class="hidden lg:block ms-1" avatar="{{ auth()->user()->avatar }}" />
                </div>
            </header>

            {{-- Slot utama --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast / Scripts --}}
    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
