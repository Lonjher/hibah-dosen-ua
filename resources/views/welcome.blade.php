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
        content="Portal Terpadu Hibah Penelitian & Pengabdian Dosen Universitas Annuqayah (SIM-LITABMAS) — dari pengajuan proposal hingga pelaporan akhir dalam satu alur kerja.">

    <title>{{ __('Welcome') }} - {{ config('app.name', 'SIM-LITABMAS') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Font: Instrument Sans untuk body, Lora untuk heading --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|lora:400,500,600,700&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data="{ dark: document.documentElement.classList.contains('dark'), mobileOpen: false, scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 10)"
    class="bg-[#fafcfb] dark:bg-[#0a0f0e] text-slate-900 dark:text-slate-100 font-body antialiased selection:bg-emerald-100 selection:text-emerald-700 dark:selection:bg-emerald-500/30">

    {{-- Pola latar titik halus + gradient blob dekoratif --}}
    <div class="pointer-events-none fixed inset-0 -z-10 bg-pattern-dots"></div>
    <div
        class="pointer-events-none fixed -top-40 -left-20 -z-10 h-[550px] w-[550px] rounded-full bg-gradient-to-br from-emerald-100/50 to-teal-100/30 blur-3xl dark:from-emerald-900/20 dark:to-teal-900/10">
    </div>
    <div
        class="pointer-events-none fixed top-96 -right-24 -z-10 h-[600px] w-[600px] rounded-full bg-gradient-to-bl from-teal-100/40 to-emerald-100/20 blur-3xl dark:from-emerald-900/10 dark:to-transparent">
    </div>

    {{-- ==================== HEADER ==================== --}}
    <header
        class="fixed top-0 left-0 right-0 z-50 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border-b border-white/60 dark:border-white/10 shadow-[0_1px_8px_rgba(0,0,0,0.04)] transition-all"
        :class="scrolled ? 'py-2' : 'py-3'">
        <div class="h-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
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
                        class="font-semibold text-slate-900 dark:text-white text-base leading-tight tracking-tight">SIM-LITABMAS</span>
                    <span class="text-sm text-slate-500 dark:text-slate-400 leading-none">LPPM Universitas
                        Annuqayah</span>
                </div>
            </a>

            {{-- Navigasi desktop --}}
            <nav class="hidden lg:flex items-center gap-1" aria-label="Navigasi utama">
                <a href="#"
                    class="px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 font-semibold text-sm dark:bg-emerald-900/40 dark:text-emerald-300">Beranda</a>
                <a href="#skema"
                    class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-sm font-medium dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition">Skema
                    Hibah</a>
                <a href="#panduan"
                    class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-sm font-medium dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition">Panduan
                    & SOP</a>
                <a href="#statistik"
                    class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-sm font-medium dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition">Statistik</a>
                <a href="#alur"
                    class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-sm font-medium dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition">Agenda
                    & Linimasa</a>
                <a href="#faq"
                    class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-sm font-medium dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition">FAQ</a>
            </nav>

            {{-- Aksi kanan --}}
            <div class="flex items-center gap-2">
                {{-- Dark Mode Toggle --}}
                <button x-data variant="segmented" x-model="$flux.appearance"
                    class="cursor-pointer rounded-lg p-2 text-stone-500 transition-colors hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
                    :aria-label="darkMode ? 'Dark Mode' : 'Light Mode'" @click="darkMode = !darkMode">
                    <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                <a href="{{ Route::has('login') ? route('login') : '#' }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-700 text-white text-sm font-medium shadow-sm hover:bg-emerald-800 transition">
                    Masuk Portal
                </a>

                {{-- Toggle menu mobile --}}
                <button type="button" @click="mobileOpen = !mobileOpen"
                    class="grid h-9 w-9 place-items-center rounded-full text-slate-700 lg:hidden dark:text-slate-300"
                    aria-label="Buka menu" :aria-expanded="mobileOpen">
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

        {{-- Panel navigasi mobile --}}
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak
            class="mx-4 mt-2 flex flex-col gap-1 rounded-xl bg-white/90 backdrop-blur-md p-2 shadow-lg lg:hidden dark:bg-slate-800/90">
            <a href="#" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Beranda</a>
            <a href="#skema" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Skema
                Hibah</a>
            <a href="#panduan" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Panduan
                & SOP</a>
            <a href="#statistik" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Statistik</a>
            <a href="#alur" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Agenda
                & Linimasa</a>
            <a href="#faq" @click="mobileOpen=false"
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">FAQ</a>
        </div>
    </header>

    <main class="w-full pt-16">
        <div class="relative w-full overflow-hidden">
            {{-- ==================== HERO SECTION ==================== --}}
            <section class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-16">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-6 flex flex-col items-start gap-4 reveal-up">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/80 backdrop-blur-md shadow-sm border border-white/90 dark:bg-slate-800/80 dark:border-slate-700">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span
                                class="font-semibold text-emerald-700 dark:text-emerald-400 text-xs tracking-wider">TAHUN
                                ANGGARAN 2025/2026 RESMI DIBUKA</span>
                        </div>

                        <h1
                            class="font-serif text-slate-900 dark:text-white font-bold tracking-tight text-left text-lg leading-[1.4] md:text-4xl md:leading-[1.4]">
                            Portal Terpadu Hibah Penelitian &amp; Pengabdian Dosen Universitas Annuqayah
                        </h1>

                        <p
                            class="text-slate-600 dark:text-slate-300 font-normal leading-relaxed text-left max-w-xl text-sm leading-relaxed">
                            Fasilitasi pendanaan riset ilmiah, hilirisasi pengabdian masyarakat pesantren, dan
                            akselerasi publikasi bereputasi nasional maupun global bagi segenap civitas akademika
                            Universitas Annuqayah Madura secara transparan dan akuntabel.
                        </p>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <a href="#skema"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-emerald-700 text-white font-medium shadow-md hover:bg-emerald-800 transition text-sm">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path
                                        d="M7 3.5h7l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 19V5A1.5 1.5 0 0 1 7 3.5Z M14 3.5V8h4.5"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                Ajukan Proposal Sekarang
                            </a>
                            <a href="#unduhan"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-white/80 backdrop-blur-md text-slate-700 font-medium border border-white/90 shadow-sm hover:bg-white hover:text-emerald-700 transition text-sm dark:bg-slate-800/80 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-emerald-600">
                                    <path
                                        d="M4 16v3.5A1.5 1.5 0 0 0 5.5 21h13a1.5 1.5 0 0 0 1.5-1.5V16M12 3v11m0 0 4-4m-4 4L8 10"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                Unduh Panduan SIM-LITABMAS 2025
                            </a>
                        </div>

                        <div
                            class="w-full mt-3 p-3 rounded-xl bg-white/70 backdrop-blur-lg border border-white/80 shadow-sm grid grid-cols-3 gap-2 divide-x divide-slate-200/60 dark:bg-slate-800/70 dark:border-slate-700 dark:divide-slate-700">
                            <div class="flex items-center gap-2 px-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0 dark:bg-emerald-900/50">
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-4 h-4 text-emerald-700 dark:text-emerald-400">
                                        <path d="M4 6.5 12 3l8 3.5v5c0 4-2.5 7-8 9-5.5-2-8-5-8-9v-5Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-slate-900 dark:text-white font-bold truncate text-sm">14 Skema
                                        Aktif</span>
                                    <span class="text-slate-500 dark:text-slate-400 font-medium truncate text-xs">Riset
                                        &amp; Abdimas</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center shrink-0 dark:bg-teal-900/50">
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-4 h-4 text-teal-700 dark:text-teal-400">
                                        <path
                                            d="M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-slate-900 dark:text-white font-bold truncate text-sm">Rp 1.8
                                        Miliar</span>
                                    <span
                                        class="text-slate-500 dark:text-slate-400 font-medium truncate text-xs">Alokasi
                                        Hibah UA</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center shrink-0 dark:bg-cyan-900/50">
                                    <svg viewBox="0 0 24 24" fill="none"
                                        class="w-4 h-4 text-cyan-700 dark:text-cyan-400">
                                        <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-slate-900 dark:text-white font-bold truncate text-sm">120+
                                        Reviewer</span>
                                    <span
                                        class="text-slate-500 dark:text-slate-400 font-medium truncate text-xs">Tersertifikasi
                                        Nasional</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Hero: Illustration --}}
                    <div class="lg:col-span-6 relative flex items-center justify-center min-h-[380px] reveal-scale">
                        <div
                            class="absolute w-72 h-72 rounded-full bg-gradient-to-tr from-emerald-200/60 to-teal-200/40 blur-2xl dark:from-emerald-900/30 dark:to-teal-900/20">
                        </div>
                        <div
                            class="relative w-full max-w-[420px] rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90 shadow-[0_16px_36px_rgba(15,23,42,0.06)] p-4 transition-all duration-300 hover:shadow-[0_20px_42px_rgba(15,23,42,0.09)] dark:bg-slate-800/75 dark:border-slate-700">
                            <div
                                class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex p-1.5 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
                                        <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                            <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-slate-900 dark:text-white font-semibold text-sm">Simulasi
                                            Status Evaluasi Usulan</h3>
                                        <span class="text-slate-400 dark:text-slate-500 text-xs">ID:
                                            UA-LIT-2025-0892</span>
                                    </div>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3">
                                        <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Disetujui
                                </span>
                            </div>
                            <div class="py-3 flex flex-col gap-1.5">
                                <span
                                    class="text-slate-400 dark:text-slate-500 font-medium uppercase tracking-wider text-xs">Judul
                                    Penelitian Fundamental</span>
                                <p
                                    class="font-serif text-slate-800 dark:text-slate-200 font-semibold leading-snug text-sm">
                                    Model Konservasi Ekologi Pesisir Madura Timur Berbasis Nilai Kearifan Pesantren
                                    Annuqayah
                                </p>
                                <div
                                    class="flex items-center gap-3 text-slate-500 dark:text-slate-400 font-normal pt-1 text-xs">
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
                                        </svg> Riset Terapan</span>
                                </div>
                            </div>
                            <div
                                class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-100 dark:bg-slate-900/50 dark:border-slate-700 flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-slate-300 font-medium text-xs">Skor
                                        Peer-Review Gabungan:</span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-bold text-sm">88.5 <span
                                            class="text-slate-400 font-normal text-xs">/ 100</span></span>
                                </div>
                                <div
                                    class="w-full bg-slate-200/70 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-gradient-to-r from-emerald-600 to-teal-500 h-full rounded-full"
                                        style="width: 88.5%;"></div>
                                </div>
                                <div
                                    class="flex items-center justify-between text-slate-400 dark:text-slate-500 text-xs">
                                    <span>Passing Grade: 75.0</span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-semibold">Memenuhi Syarat
                                        Pendanaan</span>
                                </div>
                            </div>
                            <div
                                class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-emerald-600">
                                        <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                    <span class="font-mono text-xs text-slate-400">SHA256: 7f8a92...e01d</span>
                                </div>
                                <span class="font-medium">Reviewer 1 &amp; 2 Verified</span>
                            </div>
                        </div>

                        {{-- Floating Mini Card 1 --}}
                        <div
                            class="absolute -top-4 -right-3 md:-right-6 w-56 p-2.5 rounded-xl bg-white/90 backdrop-blur-xl border border-white shadow-lg flex items-center gap-2.5 transform hover:-translate-y-1 transition-all dark:bg-slate-800/90 dark:border-slate-700">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 dark:bg-emerald-900/50 dark:text-emerald-400">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path
                                        d="M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-slate-900 dark:text-white font-semibold truncate text-xs">Pencairan
                                    Tahap I: 70%</span>
                                <span
                                    class="text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-0.5 text-xs">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3">
                                        <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Siap Ditransfer ke VA Dosen
                                </span>
                            </div>
                        </div>
                        {{-- Floating Pill Badge --}}
                        <div
                            class="absolute bottom-2 -right-2 px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md border border-white shadow-sm flex items-center gap-1.5 dark:bg-slate-800/95 dark:border-slate-700">
                            <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3 text-emerald-600">
                                <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300 font-medium text-xs">Standar BIMA &amp;
                                BAN-PT</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ==================== STATISTIK & CAPAIAN ==================== --}}
            <section id="statistik" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div
                    class="flex flex-col md:flex-row md:items-end justify-between mb-6 pb-3 border-b border-slate-200/60 dark:border-slate-700">
                    <div>
                        <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                            <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                <path d="M4 16 8 12l4 4 6-8" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="font-bold tracking-wider uppercase text-xs">DATA &amp; METRIK KINERJA</span>
                        </div>
                        <h2 class="font-serif text-slate-900 dark:text-white font-bold text-lg leading-[1.4]">
                            Statistik &amp; Capaian Hibah Litabmas Universitas Annuqayah
                        </h2>
                        <p class="text-slate-500 dark:text-slate-400 font-normal mt-0.5 text-sm">
                            Rekapitulasi berkala usulan penelitian, capaian luaran wajib, dan serapan anggaran periode
                            2022-2025.
                        </p>
                    </div>
                    <div class="mt-3 md:mt-0">
                        <span
                            class="px-3 py-1.5 rounded-lg bg-white/80 border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 font-medium text-xs">
                            Sinkronisasi: PDDIKTI &amp; SINTA Kemdikbud
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- KPI Cards (using data-counter for animation) -->
                    <div class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm hover:border-emerald-300 dark:bg-slate-800/75 dark:border-slate-700 dark:hover:border-emerald-700 transition-all reveal-up"
                        style="transition-delay:0ms">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium text-sm">Total Usulan
                                Masuk</span>
                            <div
                                class="w-7 h-7 rounded-md bg-emerald-100 flex items-center justify-center text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path
                                        d="M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-slate-900 dark:text-white font-bold text-base"><span
                                    data-counter="342">0</span> Usulan</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs">+18.4%
                                YoY</span>
                        </div>
                        <span class="text-slate-400 dark:text-slate-500 block mt-1 text-xs">Dari 4 Fakultas &amp;
                            Pascasarjana</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm hover:border-emerald-300 dark:bg-slate-800/75 dark:border-slate-700 dark:hover:border-emerald-700 transition-all reveal-up"
                        style="transition-delay:80ms">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium text-sm">Didanai &amp; Lolos
                                Seleksi</span>
                            <div
                                class="w-7 h-7 rounded-md bg-emerald-50 flex items-center justify-center text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M5 12.5 10 17l9-10" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-slate-900 dark:text-white font-bold text-base"><span
                                    data-counter="186">0</span> Judul</span>
                            <span class="text-slate-500 dark:text-slate-400 font-medium text-xs">Rasio 54.3%</span>
                        </div>
                        <span class="text-slate-400 dark:text-slate-500 block mt-1 text-xs">Melalui Desk Review &amp;
                            Paparan</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm hover:border-emerald-300 dark:bg-slate-800/75 dark:border-slate-700 dark:hover:border-emerald-700 transition-all reveal-up"
                        style="transition-delay:160ms">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium text-sm">Publikasi SINTA &amp;
                                Scopus</span>
                            <div
                                class="w-7 h-7 rounded-md bg-teal-50 flex items-center justify-center text-teal-700 dark:bg-teal-900/50 dark:text-teal-400">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path
                                        d="M5 19.5A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5h5L14 8v10a1.5 1.5 0 0 1-1.5 1.5H5Z M15 7.5h4.5a1 1 0 0 1 1 1v9.5"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-slate-900 dark:text-white font-bold text-base"><span
                                    data-counter="114">0</span> Artikel</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs">SINTA 1-4 &amp;
                                Q1-Q4</span>
                        </div>
                        <span class="text-slate-400 dark:text-slate-500 block mt-1 text-xs">Capaian luaran wajib
                            terverifikasi</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm hover:border-emerald-300 dark:bg-slate-800/75 dark:border-slate-700 dark:hover:border-emerald-700 transition-all reveal-up"
                        style="transition-delay:240ms">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium text-sm">HKI &amp; Paten
                                Dosen</span>
                            <div
                                class="w-7 h-7 rounded-md bg-amber-50 flex items-center justify-center text-amber-700 dark:bg-amber-900/50 dark:text-amber-400">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M12 3.5 4.5 7v5c0 4.5 3 8 7.5 10 4.5-2 7.5-5.5 7.5-10V7L12 3.5Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-slate-900 dark:text-white font-bold text-base"><span
                                    data-counter="38">0</span> Sertifikat</span>
                            <span class="text-slate-500 dark:text-slate-400 font-medium text-xs">Hak Cipta &amp;
                                Desain</span>
                        </div>
                        <span class="text-slate-400 dark:text-slate-500 block mt-1 text-xs">Terkait hilirisasi
                            teknologi lokal</span>
                    </div>
                </div>

                {{-- Chart Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <div
                        class="lg:col-span-8 p-4 rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90 shadow-sm flex flex-col justify-between dark:bg-slate-800/75 dark:border-slate-700 reveal-up">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="font-serif text-slate-900 dark:text-white font-bold text-base">Distribusi
                                    Skema Pendanaan (2022 - 2025)</h3>
                                <p class="text-slate-500 dark:text-slate-400 font-normal text-xs">Perbandingan jumlah
                                    judul berdasarkan klaster hibah internal LPPM Annuqayah</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5"><span
                                        class="w-2.5 h-2.5 rounded-sm bg-emerald-700"></span><span
                                        class="text-slate-600 dark:text-slate-300 font-medium text-xs">Riset</span>
                                </div>
                                <div class="flex items-center gap-1.5"><span
                                        class="w-2.5 h-2.5 rounded-sm bg-teal-600"></span><span
                                        class="text-slate-600 dark:text-slate-300 font-medium text-xs">Pengabdian
                                        (PkM)</span></div>
                            </div>
                        </div>
                        <div class="h-56">
                            <canvas id="fundingChart" role="img"
                                aria-label="Grafik distribusi skema pendanaan 2022-2025"></canvas>
                        </div>
                        <div
                            class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs">
                            <span>* Sumber: Buku Laporan Kinerja Tahunan LPPM Universitas Annuqayah</span>
                            <span
                                class="text-emerald-600 dark:text-emerald-400 font-medium hover:underline cursor-pointer">Lihat
                                Rincian Per Prodi →</span>
                        </div>
                    </div>
                    <div
                        class="lg:col-span-4 p-4 rounded-2xl bg-white/75 backdrop-blur-xl border border-white/90 shadow-sm flex flex-col justify-between dark:bg-slate-800/75 dark:border-slate-700 reveal-up">
                        <div>
                            <h3 class="font-serif text-slate-900 dark:text-white font-bold text-base">Monitoring
                                &amp; Evaluasi (Monev)</h3>
                            <p class="text-slate-500 dark:text-slate-400 font-normal text-xs">Progres serapan target
                                riset tahun 2024/2025</p>
                        </div>
                        <div class="relative flex items-center justify-center my-3">
                            <svg class="w-36 h-36 transform -rotate-90" viewbox="0 0 100 100">
                                <circle cx="50" cy="50" fill="transparent" r="40" stroke="#f1f5f9"
                                    stroke-width="12"></circle>
                                <circle cx="50" cy="50" fill="transparent" r="40" stroke="#005d42"
                                    stroke-dasharray="88 251" stroke-dashoffset="0" stroke-width="12"></circle>
                                <circle cx="50" cy="50" fill="transparent" r="40" stroke="#00776b"
                                    stroke-dasharray="100 251" stroke-dashoffset="-88" stroke-width="12"></circle>
                                <circle cx="50" cy="50" fill="transparent" r="40" stroke="#99efe5"
                                    stroke-dasharray="63 251" stroke-dashoffset="-188" stroke-width="12"></circle>
                            </svg>
                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-slate-900 dark:text-white font-bold text-base">186</span>
                                <span class="text-slate-500 dark:text-slate-400 font-medium text-xs">Judul Riset</span>
                            </div>
                        </div>
                        <div
                            class="flex flex-col gap-1.5 pt-1 border-t border-slate-100 dark:border-slate-700 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5"><span
                                        class="w-2 h-2 rounded-full bg-emerald-700"></span><span
                                        class="text-slate-600 dark:text-slate-300">Laporan Akhir &amp; Luaran</span>
                                </div>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">35% (65 Judul)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5"><span
                                        class="w-2 h-2 rounded-full bg-teal-600"></span><span
                                        class="text-slate-600 dark:text-slate-300">Laporan Kemajuan (70%)</span></div>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">40% (74 Judul)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5"><span
                                        class="w-2 h-2 rounded-full bg-emerald-200"></span><span
                                        class="text-slate-600 dark:text-slate-300">Tahap Kontrak &amp; RAB</span></div>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">25% (47 Judul)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ==================== FITUR & LAYANAN ==================== --}}
            <section id="skema" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col mb-7">
                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                        <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                            <path
                                d="M4 5.5C4 4.67 4.67 4 5.5 4h5v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z M15 4h3.5c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5H15V4Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                        </svg>
                        <span class="font-bold tracking-wider uppercase text-xs">EKOSISTEM DIGITAL</span>
                    </div>
                    <h2 class="font-serif text-slate-900 dark:text-white font-bold text-lg leading-[1.4]">
                        Fitur &amp; Layanan Terintegrasi SIM-LITABMAS LPPM UA
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 font-normal mt-0.5 text-sm">
                        Infrastruktur digital menyeluruh dari penyusunan proposal, pengawasan etik, penilaian reviewer,
                        hingga hilirisasi luaran.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                        $features = [
                            [
                                'title' => 'Pengajuan Proposal Single Window',
                                'desc' =>
                                    'Formulir pengajuan online otomatis terintegrasi data PDDIKTI, SINTA ID, dan Google Scholar profil Dosen tanpa pengisian ulang manual.',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'footer' => 'Standar Template UA 2025',
                            ],
                            [
                                'title' => 'Blind Peer-Review System',
                                'desc' =>
                                    'Penilaian independen dengan sistem penugasan reviewer berimbang, rubrik skor terstandarisasi BIMA, dan rekap masukan perbaikan naskah.',
                                'icon' => 'M5 12.5 10 17l9-10',
                                'footer' => 'Kerahasiaan Ganda Terjamin',
                            ],
                            [
                                'title' => 'Monev & Logbook Harian',
                                'desc' =>
                                    'Pencatatan real-time progres aktivitas riset, dokumentasi kegiatan lapangan, serta persentase realisasi serapan anggaran penelitian.',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'footer' => 'Geotagging & Lampiran Bukti',
                            ],
                            [
                                'title' => 'Validasi Luaran & Publikasi',
                                'desc' =>
                                    'Verifikasi keabsahan artikel jurnal, prosiding bereputasi, buku referensi ber-ISBN, serta sertifikasi HKI bersama sentra HKI Annuqayah.',
                                'icon' => 'M5 12.5 10 17l9-10',
                                'footer' => 'Insentif Publikasi Otomatis',
                            ],
                            [
                                'title' => 'Manajemen Anggaran & SPK',
                                'desc' =>
                                    'Penandatanganan Surat Perjanjian Kontrak (SPK) digital, pelacakan termin pencairan 70% dan 30%, serta kepatuhan Standar Biaya Masukan (SBM).',
                                'icon' =>
                                    'M3.5 8h17M3.5 8a1.5 1.5 0 0 1 1.5-1.5h14A1.5 1.5 0 0 1 20.5 8v9A1.5 1.5 0 0 1 19 18.5H5A1.5 1.5 0 0 1 3.5 17V8Z M8 14h2',
                                'footer' => 'E-Sign Terdaftar & Akurat',
                            ],
                            [
                                'title' => 'Repositori Riset Pesantren & Madura',
                                'desc' =>
                                    'Pusat dokumentasi riset keislaman, manuskrip keilmuan pesantren, dan studi sosiokultural Madura yang dapat diakses publik global.',
                                'icon' =>
                                    'M5 19.5A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5h5L14 8v10a1.5 1.5 0 0 1-1.5 1.5H5Z M15 7.5h4.5a1 1 0 0 1 1 1v9.5',
                                'footer' => 'Open Access Annuqayah Press',
                            ],
                        ];
                    @endphp
                    @foreach ($features as $i => $feature)
                        <div class="p-4 rounded-2xl bg-white/70 backdrop-blur-lg border border-white/80 shadow-sm hover:shadow-md hover:bg-white/90 hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between dark:bg-slate-800/70 dark:border-slate-700 dark:hover:bg-slate-700/90 reveal-up"
                            style="transition-delay: {{ $i * 70 }}ms">
                            <div>
                                <div
                                    class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 flex items-center justify-center mb-3">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5">
                                        <path d="{{ $feature['icon'] }}" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <h3 class="text-slate-900 dark:text-white font-bold mb-1 text-base">
                                    {{ $feature['title'] }}</h3>
                                <p class="text-slate-600 dark:text-slate-300 font-normal leading-relaxed text-sm">
                                    {{ $feature['desc'] }}</p>
                            </div>
                            <div
                                class="mt-4 pt-2.5 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-medium text-sm">
                                <span>{{ $feature['footer'] }}</span>
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ==================== ALUR & TIMELINE ==================== --}}
            <section id="alur" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div
                    class="p-6 rounded-3xl bg-white/80 backdrop-blur-xl border border-white/90 shadow-md dark:bg-slate-800/80 dark:border-slate-700 reveal-up">
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-3 border-b border-slate-200/60 dark:border-slate-700">
                        <div>
                            <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path
                                        d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Z M15 4h3.5c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5H15V4Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg>
                                <span class="font-bold tracking-wider uppercase text-xs">SIKLUS TAHUNAN</span>
                            </div>
                            <h2 class="font-serif text-slate-900 dark:text-white font-bold text-lg leading-[1.4]">
                                Tahapan &amp; Alur Pengajuan Hibah Riset &amp; Pengabdian 2025</h2>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <span
                                class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-semibold text-sm">Periode
                                Aktif: Tahap 2 (Unggah Proposal)</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        @php
                            $steps = [
                                [
                                    'no' => '01',
                                    'title' => 'Akun & Verifikasi SINTA',
                                    'desc' =>
                                        'Pemutakhiran profil, jabatan fungsional, dan sinkronisasi skor SINTA 3 tahun terakhir.',
                                    'date' => '1 - 15 Jan 2025',
                                    'active' => false,
                                ],
                                [
                                    'no' => '02',
                                    'title' => 'Unggah Proposal & RAB',
                                    'desc' =>
                                        'Pengisian form online, unggah naskah sesuai template dan rincian anggaran biaya standar.',
                                    'date' => '16 Jan - 20 Feb 2025',
                                    'active' => true,
                                ],
                                [
                                    'no' => '03',
                                    'title' => 'Desk Eval & Paparan',
                                    'desc' =>
                                        'Penilaian oleh reviewer independen serta seminar pemaparan usulan terpilih.',
                                    'date' => '25 Feb - 10 Mar 2025',
                                    'active' => false,
                                ],
                                [
                                    'no' => '04',
                                    'title' => 'Penetapan & Kontrak SPK',
                                    'desc' =>
                                        'Surat Keputusan Rektor, penandatanganan SPK, serta penyaluran dana termin 70%.',
                                    'date' => '18 - 25 Mar 2025',
                                    'active' => false,
                                ],
                                [
                                    'no' => '05',
                                    'title' => 'Monev & Pelaporan Akhir',
                                    'desc' =>
                                        'Visitasi lapangan, submit luaran wajib artikel/buku, dan pelunasan termin 30%.',
                                    'date' => 'Agu - Nov 2025',
                                    'active' => false,
                                ],
                            ];
                        @endphp
                        @foreach ($steps as $i => $step)
                            <div class="p-3.5 rounded-xl border flex flex-col gap-2 relative reveal-up {{ $step['active'] ? 'bg-emerald-50 border-emerald-300 shadow-md dark:bg-emerald-900/20 dark:border-emerald-700' : 'bg-white/90 border-slate-100 shadow-sm dark:bg-slate-800 dark:border-slate-700' }}"
                                style="transition-delay: {{ $i * 90 }}ms">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="px-2.5 py-1 rounded-md {{ $step['active'] ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }} font-bold text-sm">{{ $step['no'] }}{{ $step['active'] ? ' (Aktif)' : '' }}</span>
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
                                            class="w-4 h-4 text-slate-300 dark:text-slate-600">
                                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" />
                                        </svg>
                                    @endif
                                </div>
                                <h4
                                    class="font-bold {{ $step['active'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }} text-sm leading-[1.4]">
                                    {{ $step['title'] }}</h4>
                                <p class="text-slate-600 dark:text-slate-300 font-normal text-sm leading-[1.4]">
                                    {{ $step['desc'] }}</p>
                                <span
                                    class="font-medium mt-auto pt-1 {{ $step['active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }} text-xs">{{ $step['date'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ==================== UNDUHAN DOKUMEN ==================== --}}
            <section id="unduhan" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col mb-6">
                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                        <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                            <path
                                d="M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <span class="font-bold tracking-wider uppercase text-xs">DOKUMEN MUTU RESMI</span>
                    </div>
                    <h2 class="font-serif text-slate-900 dark:text-white font-bold text-lg leading-[1.4]">Pusat
                        Unduhan Format Proposal &amp; Panduan Hibah Internal</h2>
                    <p class="text-slate-500 dark:text-slate-400 font-normal mt-0.5 text-sm">Gunakan berkas template
                        resmi yang telah diverifikasi LPPM Universitas Annuqayah untuk kelancaran administrasi usulan.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @php
                        $documents = [
                            [
                                'title' => 'Buku Panduan Hibah UA Edisi V',
                                'meta' => 'PDF (2.4 MB) • Update Jan 2025',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'color' => 'bg-red-50 text-red-600',
                            ],
                            [
                                'title' => 'Template RAB Standar SBM UA',
                                'meta' => 'XLSX (420 KB) • Sesuai PMK Riset',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'color' => 'bg-emerald-50 text-emerald-700',
                            ],
                            [
                                'title' => 'Format Logbook & Form Monev',
                                'meta' => 'DOCX (610 KB) • Format 2025',
                                'icon' =>
                                    'M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4',
                                'color' => 'bg-blue-50 text-blue-700',
                            ],
                        ];
                    @endphp
                    @foreach ($documents as $doc)
                        <div
                            class="p-3.5 rounded-xl bg-white/75 backdrop-blur-lg border border-white/80 shadow-sm hover:bg-white hover:border-emerald-300 dark:bg-slate-800/75 dark:border-slate-700 dark:hover:border-emerald-700 transition-all flex items-center justify-between reveal-up">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-lg {{ $doc['color'] }} flex items-center justify-center shrink-0">
                                    <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5">
                                        <path d="{{ $doc['icon'] }}" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <h4 class="text-slate-900 dark:text-white font-bold truncate text-sm">
                                        {{ $doc['title'] }}</h4>
                                    <span
                                        class="text-slate-400 dark:text-slate-500 font-normal truncate text-xs">{{ $doc['meta'] }}</span>
                                </div>
                            </div>
                            <button
                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-700 hover:text-white text-slate-600 dark:bg-slate-700 dark:text-slate-300 flex items-center justify-center shrink-0 transition-colors"
                                title="Unduh File">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
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
            <section id="faq" class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mb-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-7 flex flex-col gap-3">
                        <div>
                            <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 mb-1">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.3-1 .8-1 1.7" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" />
                                    <circle cx="12" cy="17" r="0.5" fill="currentColor" />
                                </svg>
                                <span class="font-bold tracking-wider uppercase text-xs">TANYA JAWAB</span>
                            </div>
                            <h2 class="font-serif text-slate-900 dark:text-white font-bold text-lg leading-[1.4]">
                                Pertanyaan yang Sering Diajukan (FAQ)</h2>
                        </div>
                        @php
                            $faqs = [
                                [
                                    'q' => 'Siapa saja yang berhak menjadi Ketua Pengusul Hibah Internal UA?',
                                    'a' =>
                                        'Dosen tetap Universitas Annuqayah ber-NIDN/NUPTK aktif, memiliki akun SINTA terverifikasi, dan tidak sedang dalam status tugas belajar atau memiliki tanggungan laporan luaran pada periode sebelumnya.',
                                ],
                                [
                                    'q' => 'Apakah anggota tim riset boleh melibatkan mahasiswa aktif?',
                                    'a' =>
                                        'Wajib. Setiap usulan penelitian dan pengabdian diwajibkan melibatkan minimal 2 (dua) mahasiswa aktif sebagai pemenuhan Indikator Kinerja Utama (IKU) integrasi riset dalam pembelajaran Merdeka Belajar.',
                                ],
                                [
                                    'q' => 'Bagaimana mekanisme pencairan dana hibah yang disetujui?',
                                    'a' =>
                                        'Pencairan dana disalurkan dalam 2 termin: Tahap I sebesar 70% setelah penandatanganan SPK, dan Tahap II sebesar 30% setelah pelaksanaan Monev kemajuan serta verifikasi draf luaran artikel jurnal.',
                                ],
                            ];
                        @endphp
                        @foreach ($faqs as $faq)
                            <div
                                class="rounded-xl bg-white/75 backdrop-blur-lg border border-white/80 p-3 shadow-sm dark:bg-slate-800/75 dark:border-slate-700 reveal-up">
                                <h4
                                    class="text-slate-900 dark:text-white font-bold flex items-center justify-between text-sm leading-[1.4]">
                                    <span>{{ $faq['q'] }}</span>
                                    <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-slate-400">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </h4>
                                <p class="text-slate-600 dark:text-slate-300 font-normal mt-1 leading-relaxed text-sm">
                                    {{ $faq['a'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div
                        class="lg:col-span-5 p-5 rounded-2xl bg-white/85 backdrop-blur-xl border border-white/90 shadow-md flex flex-col gap-3 dark:bg-slate-800/85 dark:border-slate-700 reveal-up">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400 flex items-center justify-center">
                                <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5">
                                    <path d="M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17Z M12 8v5m0 3v.01"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-slate-900 dark:text-white font-bold text-base">Layanan Bantuan &amp;
                                    Helpdesk LPPM</h3>
                                <span class="text-slate-400 dark:text-slate-500 font-normal text-xs">Gedung Rektorat
                                    Lt. 2, Sayap Barat</span>
                            </div>
                        </div>
                        <p class="text-slate-600 dark:text-slate-300 font-normal leading-relaxed text-sm">
                            Memerlukan bantuan terkait aktivasi akun, sinkronisasi ID SINTA, atau kendala unggah dokumen
                            RAB? Tim teknis kami siap melayani pada hari dan jam kerja.
                        </p>
                        <div class="flex flex-col gap-2 pt-2 border-t border-slate-100 dark:border-slate-700 text-sm">
                            <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300"><svg
                                    viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-emerald-600">
                                    <path
                                        d="M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M4 7l8 6 8-6"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg> lppm@annuqayah.ac.id</div>
                            <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300"><svg
                                    viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-emerald-600">
                                    <path
                                        d="M6 3.5h4l1.5 4-2 1.5a12 12 0 0 0 6 6l1.5-2 4 1.5v4a2 2 0 0 1-2 2A16 16 0 0 1 4 5.5a2 2 0 0 1 2-2Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                </svg> Hotline WhatsApp: +62 823-3456-7890</div>
                            <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><svg
                                    viewBox="0 0 24 24" fill="none" class="w-4 h-4 text-slate-400">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" />
                                </svg> Senin - Kamis &amp; Sabtu: 08.00 - 15.00 WIB</div>
                        </div>
                        <a class="w-full mt-2 py-2.5 rounded-lg bg-emerald-600 text-white font-medium text-center shadow-sm hover:bg-emerald-700 transition-colors flex items-center justify-center gap-1.5 text-sm"
                            href="https://wa.me/6282334567890" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                <path
                                    d="M8 11h.01M12 11h.01M16 11h.01M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            Hubungi Helpdesk via WhatsApp
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    {{-- ==================== FOOTER ==================== --}}
    <footer
        class="w-full bg-white/70 backdrop-blur-xl border-t border-white/80 py-8 mt-12 dark:bg-slate-900/70 dark:border-slate-700">
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
                    <span class="text-base font-semibold text-slate-900 dark:text-white">Lembaga Penelitian dan
                        Pengabdian kepada Masyarakat</span>
                    <span class="text-sm text-slate-500 dark:text-slate-400">© Universitas Annuqayah Guluk-Guluk
                        Sumenep. Seluruh hak cipta dilindungi.</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm text-slate-600 dark:text-slate-400">
                <a href="#skema" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Pedoman
                    Hibah</a>
                <a href="#unduhan" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Dokumen
                    Mutu</a>
                <a href="#faq" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Pusat
                    Bantuan</a>
            </div>
        </div>
    </footer>

    {{-- ==================== SCRIPTS ==================== --}}
    {{-- Chart.js --}}
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
                    textColor: isDark ? '#94a5b0' : '#475569',
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
                                label: 'Riset',
                                data: [35, 44, 53, 58],
                                backgroundColor: '#005d42',
                                borderRadius: 4,
                                maxBarThickness: 20
                            },
                            {
                                label: 'Pengabdian',
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
                                        size: 12
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
                                        size: 12
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
