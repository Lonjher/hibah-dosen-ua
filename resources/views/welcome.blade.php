<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
    darkMode: localStorage.getItem('darkMode') ?
        localStorage.getItem('darkMode') === 'true' : window.matchMedia('(prefers-color-scheme: dark)').matches
}" x-init="$watch('darkMode', val => {
    localStorage.setItem('darkMode', val);
    document.documentElement.classList.toggle('dark', val);
})"
    :class="{ 'dark': darkMode }" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Integrated Grant Portal for Research & Community Service at Universitas Annuqayah (SIM-LITABMAS) — from proposal submission to final reporting in one workflow.">

    <title>{{ __('Welcome') }} - {{ config('app.name', 'SIM-LITABMAS') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-heading: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --font-body: 'DM Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        .font-heading {
            font-family: var(--font-heading);
        }

        body,
        .font-body {
            font-family: var(--font-body);
        }
    </style>
</head>

<body x-data="{ mobileOpen: false, scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 10)"
    class="bg-stone-50 dark:bg-zinc-950 text-slate-900 dark:text-zinc-100 font-body antialiased selection:bg-emerald-100 selection:text-emerald-700 dark:selection:bg-emerald-500/30">

    {{-- Background pattern + decorative gradient blobs --}}
    <div class="pointer-events-none fixed inset-0 -z-10 bg-pattern-dots"></div>
    <div
        class="pointer-events-none fixed -top-40 -left-20 -z-10 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-emerald-100/50 to-teal-100/30 blur-3xl dark:from-emerald-900/20 dark:to-teal-900/10">
    </div>
    <div
        class="pointer-events-none fixed top-96 -right-24 -z-10 h-[550px] w-[550px] rounded-full bg-gradient-to-bl from-teal-100/40 to-emerald-100/20 blur-3xl dark:from-emerald-900/10 dark:to-transparent">
    </div>

    {{-- ==================== HEADER ==================== --}}
    <header
        class="fixed top-0 left-0 right-0 z-50 bg-white/70 dark:bg-zinc-950/70 backdrop-blur-md border-b border-slate-200/60 dark:border-zinc-800/60 shadow-[0_1px_8px_rgba(0,0,0,0.04)] transition-all"
        :class="scrolled ? 'py-1.5' : 'py-2.5'">
        <div class="h-14 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            {{-- Logo --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 shrink-0">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-700 text-white dark:bg-emerald-500">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                        <path d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z" stroke="currentColor"
                            stroke-width="1.5" stroke-linejoin="round" />
                        <path d="M20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5c.83 0 1.5-.67 1.5-1.5v-13Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                </span>
                <div class="flex flex-col justify-center">
                    <span
                        class="font-heading font-semibold text-slate-900 dark:text-white text-sm leading-tight tracking-tight">SIM-LITABMAS</span>
                    <span class="text-[11px] text-slate-500 dark:text-zinc-400 leading-none">LPPM Universitas
                        Annuqayah</span>
                </div>
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden lg:flex items-center gap-1" aria-label="Main navigation">
                <a href="#"
                    class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold text-xs dark:bg-emerald-900/40 dark:text-emerald-300">Home</a>
                <a href="#skema"
                    class="px-3 py-1.5 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-medium dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-800 transition">Grant
                    Schemes</a>
                <a href="#panduan"
                    class="px-3 py-1.5 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-medium dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-800 transition">Guides
                    & SOPs</a>
                <a href="#statistik"
                    class="px-3 py-1.5 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-medium dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-800 transition">Statistics</a>
                <a href="#alur"
                    class="px-3 py-1.5 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-medium dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-800 transition">Agenda
                    & Timeline</a>
                <a href="#faq"
                    class="px-3 py-1.5 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-medium dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-800 transition">FAQ</a>
            </nav>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">
                {{-- Dark Mode Toggle --}}
                <button x-data variant="segmented" x-model="$flux.appearance"
                    class="cursor-pointer rounded-lg p-2 text-stone-500 transition-colors hover:bg-stone-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                    :aria-label="darkMode ? 'Dark Mode' : 'Light Mode'" @click="darkMode = !darkMode">
                    <svg x-show="!darkMode" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="darkMode" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                <a href="{{ Route::has('login') ? route('login') : '#' }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-700 text-white text-xs font-medium shadow-sm hover:bg-emerald-800 transition">
                    Sign In
                </a>

                {{-- Mobile menu toggle --}}
                <button type="button" @click="mobileOpen = !mobileOpen"
                    class="grid h-8 w-8 place-items-center rounded-full text-slate-700 lg:hidden dark:text-zinc-300"
                    aria-label="Open menu" :aria-expanded="mobileOpen">
                    <svg x-show="!mobileOpen" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" />
                    </svg>
                    <svg x-show="mobileOpen" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile navigation panel --}}
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak
            class="mx-4 mt-2 flex flex-col gap-1 rounded-xl bg-white/90 backdrop-blur-md p-2 shadow-lg lg:hidden dark:bg-zinc-900/90">
            <a href="#" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Home</a>
            <a href="#skema" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Grant
                Schemes</a>
            <a href="#panduan" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Guides
                & SOPs</a>
            <a href="#statistik" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Statistics</a>
            <a href="#alur" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Agenda
                & Timeline</a>
            <a href="#faq" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-zinc-200 dark:hover:bg-zinc-800">FAQ</a>
        </div>
    </header>

    <main class="w-full pt-16">
        <div class="relative w-full overflow-hidden">
            {{-- ==================== HERO SECTION ==================== --}}
            <section class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-6 flex flex-col items-start gap-3.5 reveal-up">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/80 backdrop-blur-md shadow-sm border border-white/90 dark:bg-zinc-900/80 dark:border-zinc-800">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span
                                class="font-semibold text-emerald-700 dark:text-emerald-400 text-[11px] tracking-wider">FISCAL
                                YEAR 2025/2026 NOW OPEN</span>
                        </div>

                        <h1
                            class="font-heading text-slate-900 dark:text-white font-bold tracking-tight text-left text-xl leading-[1.35] md:text-3xl md:leading-[1.35]">
                            Integrated Grant Portal for Research &amp; Community Service at Universitas Annuqayah
                        </h1>

                        <p
                            class="text-slate-600 dark:text-zinc-300 font-normal leading-relaxed text-left max-w-xl text-sm">
                            Facilitating scientific research funding, downstreaming of pesantren community service, and
                            accelerating reputable national and global publications for the entire academic community of
                            Universitas Annuqayah Madura — transparently and accountably.
                        </p>

                        <div class="flex flex-wrap items-center gap-2.5 pt-1">
                            {{-- Primary: Submit Proposal --}}
                            <a href="#skema"
                                class="group relative inline-flex items-center gap-2 px-4 py-2 rounded-full
                                bg-emerald-700 text-white font-medium text-xs
                                shadow-md shadow-emerald-900/20
                                transition-all duration-300 ease-out
                                hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-lg hover:shadow-emerald-900/30
                                focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2
                                dark:focus:ring-offset-zinc-950
                                active:translate-y-0 active:shadow-md overflow-hidden">

                                {{-- Shine sweep --}}
                                <span
                                    class="absolute inset-0 -translate-x-full bg-gradient-to-r
                                    from-transparent via-white/25 to-transparent
                                    group-hover:translate-x-full
                                    transition-transform duration-700 ease-out"></span>

                                <svg viewBox="0 0 24 24" fill="none"
                                    class="relative w-3.5 h-3.5 transition-transform duration-300 group-hover:rotate-[-6deg] group-hover:scale-110">
                                    <path
                                        d="M7 3.5h7l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 19V5A1.5 1.5 0 0 1 7 3.5Z M14 3.5V8h4.5"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>

                                <span class="relative">Submit Proposal Now</span>
                            </a>

                            {{-- Secondary: Download Guide --}}
                            <a href="#unduhan"
                                class="group relative inline-flex items-center gap-2 px-4 py-2 rounded-full
                                    bg-white/80 backdrop-blur-md text-slate-700 font-medium text-xs
                                    border border-white/90 shadow-sm
                                    transition-all duration-300 ease-out
                                    hover:-translate-y-0.5 hover:bg-white hover:text-emerald-700
                                    hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-900/10
                                    focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2
                                    dark:bg-zinc-900/80 dark:text-zinc-200 dark:border-zinc-800
                                    dark:hover:bg-zinc-800 dark:hover:border-emerald-700/50
                                    dark:focus:ring-offset-zinc-950
                                    active:translate-y-0 active:shadow-sm overflow-hidden">

                                {{-- Subtle background glow --}}
                                <span
                                    class="absolute inset-0 opacity-0 group-hover:opacity-100
                     bg-gradient-to-br from-emerald-50 to-teal-50
                     dark:from-emerald-950/40 dark:to-teal-950/30
                     transition-opacity duration-300 rounded-full"></span>

                                <svg viewBox="0 0 24 24" fill="none"
                                    class="relative w-3.5 h-3.5 text-emerald-600 transition-transform duration-300
                   group-hover:translate-y-0.5">
                                    <path
                                        d="M4 16v3.5A1.5 1.5 0 0 0 5.5 21h13a1.5 1.5 0 0 0 1.5-1.5V16M12 3v11m0 0 4-4m-4 4L8 10"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>

                                <span class="relative">Download 2025 Guide</span>
                            </a>
                        </div>

                        <div
                            class="w-full mt-2.5 p-3 rounded-xl bg-white/70 backdrop-blur-lg border border-white/80 shadow-sm grid grid-cols-3 gap-2 divide-x divide-slate-200/60 dark:bg-zinc-900/70 dark:border-zinc-800 dark:divide-zinc-800">
                            <div class="flex items-center gap-2 px-2">
                                <div
                                    class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0 dark:bg-emerald-900/50">
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-3.5 h-3.5 text-emerald-700 dark:text-emerald-400">
                                        <path d="M4 6.5 12 3l8 3.5v5c0 4-2.5 7-8 9-5.5-2-8-5-8-9v-5Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-slate-900 dark:text-white font-bold truncate text-xs">14 Active
                                        Schemes</span>
                                    <span
                                        class="text-slate-500 dark:text-zinc-400 font-medium truncate text-[10px]">Research
                                        &amp; Community Service</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-2">
                                <div
                                    class="w-7 h-7 rounded-lg bg-teal-100 flex items-center justify-center shrink-0 dark:bg-teal-900/50">
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-3.5 h-3.5 text-teal-700 dark:text-teal-400">
                                        <path
                                            d="M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-slate-900 dark:text-white font-bold truncate text-xs">IDR 1.8
                                        Billion</span>
                                    <span
                                        class="text-slate-500 dark:text-zinc-400 font-medium truncate text-[10px]">UA
                                        Grant Allocation</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-2">
                                <div
                                    class="w-7 h-7 rounded-lg bg-cyan-100 flex items-center justify-center shrink-0 dark:bg-cyan-900/50">
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-3.5 h-3.5 text-cyan-700 dark:text-cyan-400">
                                        <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-slate-900 dark:text-white font-bold truncate text-xs">120+
                                        Reviewers</span>
                                    <span
                                        class="text-slate-500 dark:text-zinc-400 font-medium truncate text-[10px]">Nationally
                                        Certified</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Hero: Illustration --}}
                    <div class="lg:col-span-6 relative flex items-center justify-center min-h-[340px] reveal-scale">
                        {{-- Ambient glow --}}
                        <div
                            class="animate-float-slow absolute w-64 h-64 rounded-full bg-gradient-to-tr from-emerald-200/60 to-teal-200/40 blur-2xl dark:from-emerald-900/30 dark:to-teal-900/20">
                        </div>

                        {{-- Main simulation card --}}
                        <div
                            class="animate-float relative w-full max-w-[400px] rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90 shadow-[0_16px_36px_rgba(15,23,42,0.06)] p-4 transition-all duration-300 hover:shadow-[0_20px_42px_rgba(15,23,42,0.09)] dark:bg-zinc-900/75 dark:border-zinc-800">
                            <div
                                class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-zinc-800">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex p-1.5 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
                                        <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                            <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-slate-900 dark:text-white font-semibold text-xs">Proposal
                                            Evaluation Status Simulation</h3>
                                        <span class="text-slate-400 dark:text-zinc-500 text-[10px]">ID:
                                            UA-LIT-2025-0892</span>
                                    </div>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                                        <span
                                            class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>
                                    Approved
                                </span>
                            </div>
                            <div class="py-3 flex flex-col gap-1.5">
                                <span
                                    class="text-slate-400 dark:text-zinc-500 font-medium uppercase tracking-wider text-[10px]">Fundamental
                                    Research Title</span>
                                <p
                                    class="font-heading text-slate-800 dark:text-zinc-200 font-semibold leading-snug text-xs">
                                    Ecological Conservation Model of East Madura Coastal Areas Based on the Local
                                    Wisdom of Pesantren Annuqayah
                                </p>
                                <div
                                    class="flex items-center gap-3 text-slate-500 dark:text-zinc-400 font-normal pt-1 text-[10px]">
                                    <span class="flex items-center gap-1"><svg viewBox="0 0 24 24" fill="none"
                                            class="w-3 h-3 text-slate-400">
                                            <path
                                                d="M12 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z M5 20.5c0-3.5 3-6 7-6s7 2.5 7 6"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg> Dr. M. Kholilurrahman, M.Pd.</span>
                                    <span class="flex items-center gap-1"><svg viewBox="0 0 24 24" fill="none"
                                            class="w-3 h-3 text-slate-400">
                                            <path
                                                d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z M20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5c.83 0 1.5-.67 1.5-1.5v-13Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                        </svg> Applied Research</span>
                                </div>
                            </div>
                            <div
                                class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-100 dark:bg-zinc-950/50 dark:border-zinc-800 flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-zinc-300 font-medium text-[11px]">Combined
                                        Peer-Review Score:</span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-bold text-xs">88.5 <span
                                            class="text-slate-400 font-normal text-[10px]">/ 100</span></span>
                                </div>
                                <div
                                    class="w-full bg-slate-200/70 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="animate-progress bg-gradient-to-r from-emerald-600 to-teal-500 h-full rounded-full"
                                        style="width: 88.5%;"></div>
                                </div>
                                <div
                                    class="flex items-center justify-between text-slate-400 dark:text-zinc-500 text-[10px]">
                                    <span>Passing Grade: 75.0</span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-semibold">Eligible for
                                        Funding</span>
                                </div>
                            </div>
                            <div
                                class="mt-3 pt-2.5 border-t border-slate-100 dark:border-zinc-800 flex items-center justify-between text-slate-500 dark:text-zinc-400 text-[10px]">
                                <div class="flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5 text-emerald-600">
                                        <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                    <span class="font-mono text-[10px] text-slate-400">SHA256: 7f8a92...e01d</span>
                                </div>
                                <span class="font-medium">Reviewer 1 &amp; 2 Verified</span>
                            </div>
                        </div>

                        {{-- Floating Mini Card 1 --}}
                        <div class="animate-float-slow absolute -top-4 -right-3 md:-right-6 w-52 p-2.5 rounded-xl bg-white/90 backdrop-blur-xl border border-white shadow-lg flex items-center gap-2.5 transform hover:-translate-y-1 transition-all dark:bg-zinc-900/90 dark:border-zinc-800"
                            style="animation-delay: -1.5s;">
                            <div
                                class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 dark:bg-emerald-900/50 dark:text-emerald-400">
                                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                    <path
                                        d="M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="text-slate-900 dark:text-white font-semibold truncate text-[11px]">Disbursement
                                    Phase I: 70%</span>
                                <span
                                    class="text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-0.5 text-[10px]">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3">
                                        <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Ready to Transfer
                                </span>
                            </div>
                        </div>

                        {{-- Floating Pill Badge --}}
                        <div class="animate-float absolute bottom-2 -right-2 px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md border border-white shadow-sm flex items-center gap-1.5 dark:bg-zinc-900/95 dark:border-zinc-800"
                            style="animation-delay: -3s;">
                            <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3 text-emerald-600">
                                <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                            </svg>
                            <span class="text-slate-700 dark:text-zinc-300 font-medium text-[10px]">BIMA &amp; BAN-PT
                                Standards</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ==================== STATISTICS & ACHIEVEMENTS ==================== --}}
            <livewire:welcome-stats/>

            {{-- ==================== FEATURES & SERVICES ==================== --}}
            <section id="skema" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col mb-6">
                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                        <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                            <path
                                d="M4 5.5C4 4.67 4.67 4 5.5 4h5v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z M15 4h3.5c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5H15V4Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                        </svg>
                        <span class="font-bold tracking-wider uppercase text-[10px]">DIGITAL ECOSYSTEM</span>
                    </div>
                    <h2 class="font-heading text-slate-900 dark:text-white font-bold text-base leading-[1.4]">
                        Integrated Features &amp; Services of SIM-LITABMAS LPPM UA
                    </h2>
                    <p class="text-slate-500 dark:text-zinc-400 font-normal mt-0.5 text-xs">
                        Comprehensive digital infrastructure — from proposal drafting, ethical oversight, reviewer
                        assessment, to downstreaming of outputs.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    @php
                        $features = [
                            [
                                'title' => 'Single Window Proposal Submission',
                                'desc' =>
                                    'Online submission form automatically integrated with PDDIKTI, SINTA ID, and Google Scholar lecturer profiles — no manual re-entry.',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'footer' => 'UA 2025 Template Standard',
                            ],
                            [
                                'title' => 'Blind Peer-Review System',
                                'desc' =>
                                    'Independent assessment with balanced reviewer assignment, BIMA-standardized scoring rubric, and per-manuscript feedback compilation.',
                                'icon' => 'M5 12.5 10 17l9-10',
                                'footer' => 'Double-Blind Confidentiality Guaranteed',
                            ],
                            [
                                'title' => 'M&E & Daily Logbook',
                                'desc' =>
                                    'Real-time recording of research progress, field activity documentation, and percentage of research budget absorption.',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'footer' => 'Geotagging & Evidence Attachments',
                            ],
                            [
                                'title' => 'Output & Publication Validation',
                                'desc' =>
                                    'Verification of journal article validity, reputable proceedings, ISBN books, and HKI certification in collaboration with the Annuqayah HKI center.',
                                'icon' => 'M5 12.5 10 17l9-10',
                                'footer' => 'Automatic Publication Incentives',
                            ],
                            [
                                'title' => 'Budget & Contract Management',
                                'desc' =>
                                    'Digital signing of Grant Agreements (SPK), tracking of 70% and 30% disbursement phases, and compliance with Standard Input Costs (SBM).',
                                'icon' =>
                                    'M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2',
                                'footer' => 'Registered & Accurate E-Sign',
                            ],
                            [
                                'title' => 'Pesantren & Madura Research Repository',
                                'desc' =>
                                    'Central documentation of Islamic research, pesantren scholarly manuscripts, and Madurese sociocultural studies accessible to the global public.',
                                'icon' =>
                                    'M5 19.5A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5h5L14 8v10a1.5 1.5 0 0 1-1.5 1.5H5Z M15 7.5h4.5a1 1 0 0 1 1 1v9.5',
                                'footer' => 'Open Access Annuqayah Press',
                            ],
                        ];
                    @endphp
                    @foreach ($features as $i => $feature)
                        <div class="p-4 rounded-2xl bg-white/70 backdrop-blur-lg border border-white/80 shadow-sm hover:shadow-md hover:bg-white/90 hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between dark:bg-zinc-900/70 dark:border-zinc-800 dark:hover:bg-zinc-900/90 reveal-up"
                            style="transition-delay: {{ $i * 70 }}ms">
                            <div>
                                <div
                                    class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 flex items-center justify-center mb-3">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                        <path d="{{ $feature['icon'] }}" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <h3 class="text-slate-900 dark:text-white font-bold mb-1 text-sm">
                                    {{ $feature['title'] }}</h3>
                                <p class="text-slate-600 dark:text-zinc-300 font-normal leading-relaxed text-xs">
                                    {{ $feature['desc'] }}</p>
                            </div>
                            <div
                                class="mt-4 pt-2.5 border-t border-slate-100 dark:border-zinc-800 flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-medium text-[11px]">
                                <span>{{ $feature['footer'] }}</span>
                                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                    <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ==================== WORKFLOW & TIMELINE ==================== --}}
            <section id="alur" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div
                    class="p-5 rounded-3xl bg-white/80 backdrop-blur-xl border border-white/90 shadow-md dark:bg-zinc-900/80 dark:border-zinc-800 reveal-up">
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between mb-6 pb-3 border-b border-slate-200/60 dark:border-zinc-800">
                        <div>
                            <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                    <path
                                        d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z M15 4h3.5c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5H15V4Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg>
                                <span class="font-bold tracking-wider uppercase text-[10px]">ANNUAL CYCLE</span>
                            </div>
                            <h2 class="font-heading text-slate-900 dark:text-white font-bold text-base leading-[1.4]">
                                Stages &amp; Workflow of Research &amp; Community Service Grant Submission 2025</h2>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <span
                                class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-semibold text-xs">Active
                                Period: Stage 2 (Proposal Upload)</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        @php
                            $steps = [
                                [
                                    'no' => '01',
                                    'title' => 'Account & SINTA Verification',
                                    'desc' =>
                                        'Profile update, functional position, and SINTA score synchronization for the last 3 years.',
                                    'date' => 'Jan 1 - 15, 2025',
                                    'active' => false,
                                ],
                                [
                                    'no' => '02',
                                    'title' => 'Upload Proposal & Budget',
                                    'desc' =>
                                        'Online form completion, manuscript upload per template, and standardized budget details.',
                                    'date' => 'Jan 16 - Feb 20, 2025',
                                    'active' => true,
                                ],
                                [
                                    'no' => '03',
                                    'title' => 'Desk Evaluation & Presentation',
                                    'desc' =>
                                        'Independent reviewer assessment and presentation seminar for selected proposals.',
                                    'date' => 'Feb 25 - Mar 10, 2025',
                                    'active' => false,
                                ],
                                [
                                    'no' => '04',
                                    'title' => 'Determination & SPK Contract',
                                    'desc' =>
                                        'Rector Decree, Grant Agreement (SPK) signing, and disbursement of 70% first phase.',
                                    'date' => 'Mar 18 - 25, 2025',
                                    'active' => false,
                                ],
                                [
                                    'no' => '05',
                                    'title' => 'M&E & Final Reporting',
                                    'desc' =>
                                        'Field visitation, mandatory output submission (article/book), and 30% final phase payment.',
                                    'date' => 'Aug - Nov 2025',
                                    'active' => false,
                                ],
                            ];
                        @endphp
                        @foreach ($steps as $i => $step)
                            <div class="p-3.5 rounded-xl border flex flex-col gap-2 relative reveal-up {{ $step['active'] ? 'bg-emerald-50 border-emerald-300 shadow-md dark:bg-emerald-900/20 dark:border-emerald-700' : 'bg-white/90 border-slate-100 shadow-sm dark:bg-zinc-900 dark:border-zinc-800' }}"
                                style="transition-delay: {{ $i * 90 }}ms">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="px-2 py-0.5 rounded-md {{ $step['active'] ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300' }} font-bold text-xs">{{ $step['no'] }}{{ $step['active'] ? ' (Active)' : '' }}</span>
                                    @if ($step['active'])
                                        <span class="relative flex h-2 w-2"><span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span><span
                                                class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                                    @elseif($i < 1)
                                        <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-emerald-600">
                                            <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none"
                                            class="w-4 h-4 text-slate-300 dark:text-zinc-600">
                                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" />
                                        </svg>
                                    @endif
                                </div>
                                <h4
                                    class="font-bold {{ $step['active'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }} text-xs leading-[1.4]">
                                    {{ $step['title'] }}</h4>
                                <p class="text-slate-600 dark:text-zinc-300 font-normal text-[11px] leading-[1.4]">
                                    {{ $step['desc'] }}</p>
                                <span
                                    class="font-medium mt-auto pt-1 {{ $step['active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-zinc-500' }} text-[10px]">{{ $step['date'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ==================== DOCUMENT DOWNLOADS ==================== --}}
            <section id="unduhan" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col mb-6">
                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                        <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                            <path
                                d="M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <span class="font-bold tracking-wider uppercase text-[10px]">OFFICIAL QUALITY DOCUMENTS</span>
                    </div>
                    <h2 class="font-heading text-slate-900 dark:text-white font-bold text-base leading-[1.4]">Download
                        Center for Proposal Formats &amp; Internal Grant Guidelines</h2>
                    <p class="text-slate-500 dark:text-zinc-400 font-normal mt-0.5 text-xs">Use official template
                        files verified by LPPM Universitas Annuqayah to smooth your submission administration.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    @php
                        $documents = [
                            [
                                'title' => 'UA Grant Guide Book 5th Edition',
                                'meta' => 'PDF (2.4 MB) • Updated Jan 2025',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'color' => 'bg-red-50 text-red-600',
                            ],
                            [
                                'title' => 'Standard UA SBM Budget Template',
                                'meta' => 'XLSX (420 KB) • Compliant with Research PMK',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'color' => 'bg-emerald-50 text-emerald-700',
                            ],
                            [
                                'title' => 'Logbook Format & M&E Forms',
                                'meta' => 'DOCX (610 KB) • 2025 Format',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'color' => 'bg-blue-50 text-blue-700',
                            ],
                        ];
                    @endphp
                    @foreach ($documents as $doc)
                        <div
                            class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/80 shadow-sm hover:bg-white hover:border-emerald-300 dark:bg-zinc-900/75 dark:border-zinc-800 dark:hover:border-emerald-700 transition-all flex items-center justify-between reveal-up">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-lg {{ $doc['color'] }} flex items-center justify-center shrink-0">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                        <path d="{{ $doc['icon'] }}" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <h4 class="text-slate-900 dark:text-white font-bold truncate text-xs">
                                        {{ $doc['title'] }}</h4>
                                    <span
                                        class="text-slate-400 dark:text-zinc-500 font-normal truncate text-[10px]">{{ $doc['meta'] }}</span>
                                </div>
                            </div>
                            <button
                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-700 hover:text-white text-slate-600 dark:bg-zinc-800 dark:text-zinc-300 flex items-center justify-center shrink-0 transition-colors"
                                title="Download File">
                                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                    <path
                                        d="M4 16v3.5A1.5 1.5 0 0 0 5.5 21h13a1.5 1.5 0 0 0 1.5-1.5V16M12 3v11m0 0 4-4m-4 4L8 10"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ==================== FAQ & HELPDESK ==================== --}}
            <section id="faq" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    <div class="lg:col-span-7 flex flex-col gap-3">
                        <div>
                            <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                                <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.3-1 .8-1 1.7" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" />
                                    <circle cx="12" cy="17" r="0.5" fill="currentColor" />
                                </svg>
                                <span class="font-bold tracking-wider uppercase text-[10px]">Q&amp;A</span>
                            </div>
                            <h2 class="font-heading text-slate-900 dark:text-white font-bold text-base leading-[1.4]">
                                Frequently Asked Questions (FAQ)</h2>
                        </div>
                        @php
                            $faqs = [
                                [
                                    'q' => 'Who is eligible to become the Principal Investigator of an Internal UA Grant?',
                                    'a' =>
                                        'Full-time lecturers of Universitas Annuqayah with an active NIDN/NUPTK, a verified SINTA account, and not currently on study leave or holding outstanding output obligations from the previous period.',
                                ],
                                [
                                    'q' => 'May the research team involve active students?',
                                    'a' =>
                                        'Required. Every research and community service proposal must involve at least 2 (two) active students to fulfill the Main Performance Indicators (IKU) for research integration in Merdeka Belajar learning.',
                                ],
                                [
                                    'q' => 'What is the disbursement mechanism for an approved grant?',
                                    'a' =>
                                        'Funds are disbursed in 2 phases: Phase I of 70% after signing the Grant Agreement (SPK), and Phase II of 30% after the progress M&E and verification of the journal article output draft.',
                                ],
                            ];
                        @endphp
                        @foreach ($faqs as $faq)
                            <div
                                class="rounded-xl bg-white/75 backdrop-blur-lg border border-white/80 p-3.5 shadow-sm dark:bg-zinc-900/75 dark:border-zinc-800 reveal-up">
                                <h4
                                    class="text-slate-900 dark:text-white font-bold flex items-center justify-between text-xs leading-[1.4]">
                                    <span>{{ $faq['q'] }}</span>
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-2">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </h4>
                                <p
                                    class="text-slate-600 dark:text-zinc-300 font-normal mt-1 leading-relaxed text-[11px]">
                                    {{ $faq['a'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div
                        class="lg:col-span-5 p-5 rounded-2xl bg-white/85 backdrop-blur-xl border border-white/90 shadow-md flex flex-col gap-3 dark:bg-zinc-900/85 dark:border-zinc-800 reveal-up">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 flex items-center justify-center">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17Z M12 8v5m0 3v.01"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-slate-900 dark:text-white font-bold text-sm">Help &amp; Support
                                    Center LPPM</h3>
                                <span class="text-slate-400 dark:text-zinc-500 font-normal text-[10px]">Rectorate
                                    Building, 2nd Floor, West Wing</span>
                            </div>
                        </div>
                        <p class="text-slate-600 dark:text-zinc-300 font-normal leading-relaxed text-xs">
                            Need help with account activation, SINTA ID synchronization, or budget document upload
                            issues? Our technical team is ready to assist during working days and hours.
                        </p>
                        <div class="flex flex-col gap-2 pt-2 border-t border-slate-100 dark:border-zinc-800 text-xs">
                            <div class="flex items-center gap-2 text-slate-700 dark:text-zinc-300"><svg
                                    viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5 text-emerald-600">
                                    <path
                                        d="M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M4 7l8 6 8-6"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg> lppm@annuqayah.ac.id</div>
                            <div class="flex items-center gap-2 text-slate-700 dark:text-zinc-300"><svg
                                    viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5 text-emerald-600">
                                    <path
                                        d="M6 3.5h4l1.5 4-2 1.5a12 12 0 0 0 6 6l1.5-2 4 1.5v4a2 2 0 0 1-2 2A16 16 0 0 1 4 5.5a2 2 0 0 1 2-2Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg> WhatsApp Hotline: +62 823-3456-7890</div>
                            <div class="flex items-center gap-2 text-slate-500 dark:text-zinc-400"><svg
                                    viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5 text-slate-400">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" />
                                </svg> Monday - Thursday &amp; Saturday: 08.00 - 15.00 WIB</div>
                        </div>
                        <a class="w-full mt-2 py-2 rounded-lg bg-emerald-600 text-white font-medium text-center shadow-sm hover:bg-emerald-700 transition-colors flex items-center justify-center gap-1.5 text-xs"
                            href="https://wa.me/6282334567890" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                <path
                                    d="M8 11h.01M12 11h.01M16 11h.01M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            Contact Helpdesk via WhatsApp
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    {{-- ==================== FOOTER ==================== --}}
    <footer
        class="w-full bg-white/70 backdrop-blur-xl border-t border-white/80 py-6 mt-10 dark:bg-zinc-950/70 dark:border-zinc-800">
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-700 text-white dark:bg-emerald-500">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                        <path
                            d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z M15 4h3.5c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5H15V4Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                </span>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">Institute for Research and
                        Community Service</span>
                    <span class="text-xs text-slate-500 dark:text-zinc-400">© Universitas Annuqayah Guluk-Guluk
                        Sumenep. All rights reserved.</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-600 dark:text-zinc-400">
                <a href="#skema" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Grant
                    Guidelines</a>
                <a href="#unduhan" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Quality
                    Documents</a>
                <a href="#faq" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Help
                    Center</a>
            </div>
        </div>
    </footer>

    {{-- ==================== SCRIPTS ==================== --}}
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Reveal on scroll
            var revealTargets = document.querySelectorAll('.reveal-up, .reveal-scale');
            if ('IntersectionObserver' in window && !prefersReducedMotion) {
                var revealObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.15,
                    rootMargin: '0px 0px -40px 0px'
                });
                revealTargets.forEach(el => revealObserver.observe(el));
            } else {
                revealTargets.forEach(el => el.classList.add('is-visible'));
            }

            // Counter animation
            var counters = document.querySelectorAll('[data-counter]');
            var countersAnimated = false;

            function animateCounters() {
                if (countersAnimated) return;
                countersAnimated = true;
                counters.forEach(el => {
                    var target = parseFloat(el.getAttribute('data-counter'));
                    var duration = 1000;
                    var start = null;

                    function step(ts) {
                        if (!start) start = ts;
                        var progress = Math.min((ts - start) / duration, 1);
                        var eased = 1 - Math.pow(1 - progress, 3);
                        el.textContent = Math.round(target * eased);
                        if (progress < 1) requestAnimationFrame(step);
                    }
                    if (prefersReducedMotion) {
                        el.textContent = target;
                    } else {
                        requestAnimationFrame(step);
                    }
                });
            }
            var statsSection = document.getElementById('statistik');
            if (statsSection && 'IntersectionObserver' in window) {
                var statsObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animateCounters();
                            statsObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.3
                });
                statsObserver.observe(statsSection);
            } else {
                animateCounters();
            }

            // Chart
            var fundingChartInstance = null;

            function chartThemeColors() {
                var isDark = document.documentElement.classList.contains('dark');
                return {
                    textColor: isDark ? '#a1a1aa' : '#475569',
                    gridColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
                };
            }

            function initChart() {
                var canvas = document.getElementById('fundingChart');
                if (!canvas || typeof Chart === 'undefined') return;
                var colors = chartThemeColors();
                fundingChartInstance = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['2022', '2023', '2024', '2025'],
                        datasets: [{
                                label: 'Research',
                                data: [35, 44, 53, 58],
                                backgroundColor: '#005d42',
                                borderRadius: 4,
                                maxBarThickness: 20
                            },
                            {
                                label: 'Community Service',
                                data: [22, 31, 42, 50],
                                backgroundColor: '#00776b',
                                borderRadius: 4,
                                maxBarThickness: 20
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: colors.textColor,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    color: colors.gridColor
                                },
                                ticks: {
                                    color: colors.textColor,
                                    font: {
                                        size: 11
                                    }
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            if (statsSection && 'IntersectionObserver' in window) {
                var chartObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            setTimeout(initChart, typeof Chart === 'undefined' ? 250 : 0);
                            chartObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.3
                });
                chartObserver.observe(statsSection);
            }
            // Theme change update chart colors
            if ('MutationObserver' in window) {
                var themeObserver = new MutationObserver(function(mutations) {
                    var classChanged = mutations.some(m => m.attributeName === 'class');
                    if (!classChanged || !fundingChartInstance) return;
                    var colors = chartThemeColors();
                    fundingChartInstance.options.scales.x.ticks.color = colors.textColor;
                    fundingChartInstance.options.scales.y.ticks.color = colors.textColor;
                    fundingChartInstance.options.scales.y.grid.color = colors.gridColor;
                    fundingChartInstance.update();
                });
                themeObserver.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            // Hero parallax (optional)
            var illustration = document.querySelector('.lg\\:col-span-6.relative');
            if (illustration && !prefersReducedMotion && window.matchMedia('(pointer: fine)').matches) {
                illustration.addEventListener('mousemove', function(e) {
                    var rect = illustration.getBoundingClientRect();
                    var x = (e.clientX - rect.left) / rect.width - 0.5;
                    var y = (e.clientY - rect.top) / rect.height - 0.5;
                    illustration.style.transform = 'rotateX(' + (y * -3) + 'deg) rotateY(' + (x * 3) +
                        'deg)';
                });
                illustration.addEventListener('mouseleave', function() {
                    illustration.style.transform = 'rotateX(0deg) rotateY(0deg)';
                });
                illustration.style.transition = 'transform .3s ease';
                illustration.style.transformStyle = 'preserve-3d';
            }
        });
    </script>
</body>

</html>
