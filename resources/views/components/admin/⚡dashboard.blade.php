<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="p-space-lg flex flex-col gap-space-lg w-full max-w-7xl">
    <!-- Executive Welcome & Monev Alert Card -->
    <section
        class="w-full bg-surface-container-lowest/85 backdrop-blur-md rounded-xl p-space-lg shadow-sm relative overflow-hidden dark:bg-zinc-900/85 dark:shadow-black/20">
        <div
            class="absolute top-0 right-0 w-80 h-full bg-gradient-to-l from-primary/10 via-primary/5 to-transparent pointer-events-none dark:from-emerald-900/25 dark:via-emerald-900/10">
        </div>
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md relative">
            <div class="flex flex-col gap-space-2xs">
                <div class="flex items-center gap-space-xs">
                    <span
                        class="font-label-sm text-[10px] uppercase tracking-wider text-primary bg-primary/10 px-space-xs py-space-3xs rounded font-bold dark:bg-primary/20 dark:text-emerald-400">Workspace
                        Terpadu Dosen</span>
                    <span class="font-label-sm text-[11px] text-on-surface-variant dark:text-zinc-400">Sistem
                        Manajemen Riset &amp;
                        Pengabdian Universitas
                        Annuqayah</span>
                </div>
                <h1 class="font-title-md text-[16px] text-on-surface font-bold leading-snug dark:text-zinc-100">
                    Selamat Datang, {{ auth()->user()->full_name }}
                </h1>
                <p class="font-body-sm text-[12px] text-on-surface-variant max-w-2xl leading-relaxed dark:text-zinc-400">
                    Pantau status hibah kompetitif, pelaporan monev termin 1,
                    rekapitulasi luaran jurnal internasional, serta pengesahan RAB
                    berbasis standar biaya LPPM tahun anggaran berjalan.
                </p>
            </div>
            <!-- Monev Countdown & Fast Action Buttons -->
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-space-sm">
                <div
                    class="flex items-center gap-space-xs bg-surface-container-high/90 px-space-sm py-space-2xs rounded-xl shadow-xs dark:bg-zinc-800/90">
                    <div
                        class="w-7 h-7 rounded-lg bg-primary-container text-on-primary flex items-center justify-center dark:bg-emerald-700 dark:text-white">
                        <flux:icon.clock class="size-4" />
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="font-label-sm text-[10px] text-on-surface-variant leading-none dark:text-zinc-400">Batas
                            Unggah
                            Monev</span>
                        <span
                            class="font-title-sm text-[12px] text-primary font-bold leading-tight dark:text-emerald-400">14
                            Hari
                            Tersisa</span>
                    </div>
                </div>
                <div class="flex items-center gap-space-xs flex-wrap">
                    <button
                        class="inline-flex items-center gap-space-2xs px-space-sm py-space-2xs rounded-xl bg-primary text-on-primary font-title-sm text-[11px] shadow-xs hover:bg-primary-container transition-all dark:bg-emerald-700 dark:text-white dark:hover:bg-emerald-600">
                        <flux:icon.plus-circle class="size-4" />
                        <span class="">Ajukan Usulan Baru</span>
                    </button>
                    <button
                        class="inline-flex items-center gap-space-2xs px-space-xs py-space-2xs rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-title-sm text-[11px] transition-all shadow-xs dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        <flux:icon.pencil-square class="size-4 text-primary dark:text-emerald-400" />
                        <span class="">Catat Logbook</span>
                    </button>
                    <button
                        class="inline-flex items-center gap-space-2xs px-space-xs py-space-2xs rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-title-sm text-[11px] transition-all shadow-xs dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        title="Unduh format excel RAB">
                        <flux:icon.arrow-down-tray class="size-4 text-secondary" />
                        <span class="">Template RAB UA</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- 4 KPI Metrics Grid -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
        <!-- Metric 1: Active Proposals -->
        <div
            class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-sm shadow-xs flex flex-col justify-between relative overflow-hidden group hover:bg-surface-container-lowest transition-all dark:bg-zinc-900/80 dark:hover:bg-zinc-900">
            <div class="flex items-center justify-between mb-space-xs">
                <span
                    class="font-label-md text-[10px] text-on-surface-variant uppercase tracking-wider font-bold dark:text-zinc-400">Usulan
                    Aktif</span>
                <div
                    class="w-7 h-7 rounded-md bg-primary/10 text-primary flex items-center justify-center dark:bg-primary/20 dark:text-emerald-400">
                    <flux:icon.document-text class="size-4" />
                </div>
            </div>
            <div class="flex flex-col">
                <div class="font-headline-md text-[20px] text-on-surface font-bold dark:text-zinc-100">
                    3 Proposal
                </div>
                <div class="flex items-center gap-space-2xs mt-space-2xs">
                    <span
                        class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-primary/10 text-primary font-semibold dark:bg-primary/20 dark:text-emerald-400">2
                        Riset</span>
                    <span
                        class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-secondary/10 text-secondary font-semibold dark:bg-secondary/20">1
                        PkM Kemitraan</span>
                </div>
            </div>
            <div
                class="mt-space-sm text-[11px] text-on-surface-variant font-body-sm flex items-center gap-space-3xs dark:text-zinc-400">
                <span class="text-primary font-bold dark:text-emerald-400">100%</span> lolos
                verifikasi administrasi
            </div>
        </div>
        <!-- Metric 2: Grant Funding Approved -->
        <div
            class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-sm shadow-xs flex flex-col justify-between relative overflow-hidden group hover:bg-surface-container-lowest transition-all dark:bg-zinc-900/80 dark:hover:bg-zinc-900">
            <div class="flex items-center justify-between mb-space-xs">
                <span
                    class="font-label-md text-[10px] text-on-surface-variant uppercase tracking-wider font-bold dark:text-zinc-400">Dana
                    Disetujui</span>
                <div
                    class="w-7 h-7 rounded-md bg-secondary/10 text-secondary flex items-center justify-center dark:bg-secondary/20">
                    <flux:icon.banknotes class="size-4" />
                </div>
            </div>
            <div class="flex flex-col">
                <div class="font-headline-md text-[20px] text-on-surface font-bold dark:text-zinc-100">
                    Rp 65.000.000
                </div>
                <div class="flex items-center gap-space-2xs mt-space-2xs">
                    <span
                        class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-primary-fixed text-on-primary-fixed font-bold dark:bg-emerald-900/40 dark:text-emerald-300">Termin
                        1 (70%)</span>
                </div>
            </div>
            <div class="mt-space-sm text-[11px] text-on-surface-variant font-body-sm dark:text-zinc-400">
                Termin 2 (Rp 19.500.000) menanti Monev Lapangan
            </div>
        </div>
        <!-- Metric 3: Output Achievements -->
        <div
            class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-sm shadow-xs flex flex-col justify-between relative overflow-hidden group hover:bg-surface-container-lowest transition-all dark:bg-zinc-900/80 dark:hover:bg-zinc-900">
            <div class="flex items-center justify-between mb-space-xs">
                <span
                    class="font-label-md text-[10px] text-on-surface-variant uppercase tracking-wider font-bold dark:text-zinc-400">Target
                    Luaran Wajib</span>
                <div
                    class="w-7 h-7 rounded-md bg-primary/10 text-primary flex items-center justify-center dark:bg-primary/20 dark:text-emerald-400">
                    <flux:icon.book-open class="size-4" />
                </div>
            </div>
            <div class="flex flex-col">
                <div class="font-headline-md text-[20px] text-on-surface font-bold dark:text-zinc-100">
                    2 Artikel Terverifikasi
                </div>
                <div class="flex items-center gap-space-2xs mt-space-2xs">
                    <span
                        class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-secondary-container text-on-secondary-container font-semibold dark:bg-secondary/30 dark:text-zinc-200">1
                        Scopus Q2 Under Review</span>
                </div>
            </div>
            <div
                class="mt-space-sm text-[11px] text-on-surface-variant font-body-sm flex items-center gap-space-3xs dark:text-zinc-400">
                <flux:icon.check-circle class="size-3.5 text-primary dark:text-emerald-400" />
                <span class="">1 Jurnal SINTA 2 Published</span>
            </div>
        </div>
        <!-- Metric 4: Logbook Compliance -->
        <div
            class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-sm shadow-xs flex flex-col justify-between relative overflow-hidden group hover:bg-surface-container-lowest transition-all dark:bg-zinc-900/80 dark:hover:bg-zinc-900">
            <div class="flex items-center justify-between mb-space-xs">
                <span
                    class="font-label-md text-[10px] text-on-surface-variant uppercase tracking-wider font-bold dark:text-zinc-400">Kepatuhan
                    Logbook</span>
                <div
                    class="w-7 h-7 rounded-md bg-primary-container text-on-primary flex items-center justify-center dark:bg-emerald-700 dark:text-white">
                    <flux:icon.clipboard-document-check class="size-4" />
                </div>
            </div>
            <div class="flex flex-col">
                <div class="flex items-baseline justify-between">
                    <span class="font-headline-md text-[18px] text-on-surface font-bold dark:text-zinc-100">88%
                        Terisi</span>
                    <span class="font-label-sm text-[10px] text-primary font-bold dark:text-emerald-400">18 / 20
                        Bukti</span>
                </div>
                <div class="w-full bg-surface-container h-2 rounded-full mt-space-2xs overflow-hidden dark:bg-zinc-800">
                    <div class="bg-primary h-full rounded-full dark:bg-emerald-500" style="width: 88%"></div>
                </div>
            </div>
            <div
                class="mt-space-sm text-[11px] text-on-surface-variant font-body-sm flex items-center justify-between dark:text-zinc-400">
                <span class="">Kesiapan Monev:</span>
                <span class="text-primary font-semibold dark:text-emerald-400">Sangat Baik</span>
            </div>
        </div>
    </section>

    <!-- Academic Split Layout (8 Cols Proposals & Serapan / 4 Cols Widgets) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
        <!-- Left Column (8 Cols) -->
        <div class="lg:col-span-8 flex flex-col gap-space-lg">
            <!-- Proposals List Section -->
            <section
                class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-md shadow-xs dark:bg-zinc-900/80">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm mb-space-sm border-b border-white/60 dark:border-zinc-800">
                    <div class="flex items-center gap-space-2xs">
                        <flux:icon.folder-open class="size-5 text-primary dark:text-emerald-400" />
                        <h2 class="font-title-sm text-[14px] text-on-surface font-semibold dark:text-zinc-100">
                            Daftar Usulan Riset &amp; Pengabdian
                        </h2>
                    </div>
                    <div
                        class="inline-flex bg-surface-container-low p-space-3xs rounded-lg gap-space-3xs dark:bg-zinc-800/70">
                        <button
                            class="px-space-xs py-space-3xs text-[11px] font-title-sm rounded-md bg-surface-container-lowest text-primary shadow-xs font-semibold dark:bg-zinc-900 dark:text-emerald-400">
                            Semua Usulan (3)
                        </button>
                        <button
                            class="px-space-xs py-space-3xs text-[11px] font-title-sm rounded-md text-on-surface-variant hover:text-on-surface transition-colors dark:text-zinc-400 dark:hover:text-zinc-100">
                            Riset Berjalan (2)
                        </button>
                        <button
                            class="px-space-xs py-space-3xs text-[11px] font-title-sm rounded-md text-on-surface-variant hover:text-on-surface transition-colors dark:text-zinc-400 dark:hover:text-zinc-100">
                            Menunggu Review (1)
                        </button>
                    </div>
                </div>
                <!-- Proposal Item 1 -->
                <div
                    class="bg-surface-container-lowest rounded-xl p-space-md mb-space-sm shadow-xs hover:bg-surface-container-low/50 transition-all flex flex-col gap-space-xs border border-white/60 dark:bg-zinc-900 dark:hover:bg-zinc-800/60 dark:border-zinc-800">
                    <div class="flex flex-wrap items-center justify-between gap-space-xs">
                        <div class="flex items-center gap-space-xs">
                            <span
                                class="font-label-sm text-[10px] px-space-xs py-space-3xs rounded-full bg-primary/10 text-primary font-bold dark:bg-primary/20 dark:text-emerald-400">SKEMA:
                                RISET TERAPAN TERBUKA</span>
                            <span class="text-on-surface-variant text-[11px] font-label-md dark:text-zinc-400">Nomor
                                Registrasi:
                                UA/LIT-2025/084</span>
                        </div>
                        <div
                            class="flex items-center gap-space-2xs bg-primary-fixed/60 text-on-primary-fixed-variant px-space-xs py-space-3xs rounded-md dark:bg-emerald-900/40 dark:text-emerald-300">
                            <span class="w-2 h-2 rounded-full bg-primary dark:bg-emerald-400"></span>
                            <span class="font-label-sm text-[10px] font-bold">Didanai - Pelaksanaan Lapangan</span>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <h3
                            class="font-title-sm text-[14px] text-on-surface font-semibold hover:text-primary transition-colors cursor-pointer leading-snug dark:text-zinc-100 dark:hover:text-emerald-400">
                            Model Konservasi Ekologi Pesisir Madura Timur Berbasis
                            Nilai Kearifan Pesantren Annuqayah
                        </h3>
                        <p
                            class="font-body-sm text-[12px] text-on-surface-variant mt-space-3xs line-clamp-2 leading-relaxed dark:text-zinc-400">
                            Fokus integrasi fiqh lingkungan (fiqh al-bi'ah) pada
                            restorasi bakau pesisir Dungkek bersama komunitas nelayan
                            binaan santri Annuqayah Guluk-Guluk.
                        </p>
                    </div>
                    <div
                        class="grid grid-cols-1 md:grid-cols-3 gap-space-xs py-space-2xs bg-surface-container-low/60 rounded-lg px-space-sm text-[11px] dark:bg-zinc-800/60">
                        <div class="flex items-center gap-space-2xs">
                            <flux:icon.currency-dollar class="size-3.5 text-secondary" />
                            <span class="text-on-surface-variant dark:text-zinc-400">Pagu:</span>
                            <span class="font-bold text-on-surface dark:text-zinc-100">Rp 45.000.000</span>
                        </div>
                        <div class="flex items-center gap-space-2xs">
                            <flux:icon.user-group class="size-3.5 text-primary dark:text-emerald-400" />
                            <span class="text-on-surface-variant dark:text-zinc-400">Reviewer:</span>
                            <span class="text-on-surface font-medium truncate dark:text-zinc-200">Dr. Achmad F. &amp;
                                Dr. Siti N.</span>
                        </div>
                        <div class="flex items-center gap-space-2xs">
                            <flux:icon.calendar-days class="size-3.5 text-on-surface-variant dark:text-zinc-500" />
                            <span class="text-on-surface-variant dark:text-zinc-400">Termin:</span>
                            <span class="text-primary font-bold dark:text-emerald-400">Termin I (70%)</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-space-xs pt-space-2xs">
                        <div
                            class="flex items-center gap-space-3xs text-[10px] text-on-surface-variant dark:text-zinc-400">
                            <flux:icon.clock class="size-3.5 text-primary dark:text-emerald-400" />
                            <span class="">Logbook Terakhir: 2 hari lalu (Survei Lapangan)</span>
                        </div>
                        <div class="flex items-center gap-space-2xs">
                            <button
                                class="inline-flex items-center gap-space-3xs px-space-xs py-space-3xs rounded-md bg-surface-container text-on-surface hover:text-primary text-[11px] font-title-sm transition-colors dark:bg-zinc-800 dark:text-zinc-200 dark:hover:text-emerald-400">
                                <flux:icon.document-text class="size-3.5" />
                                <span class="">SPK Digital</span>
                            </button>
                            <button
                                class="inline-flex items-center gap-space-3xs px-space-xs py-space-3xs rounded-md bg-surface-container text-on-surface hover:text-primary text-[11px] font-title-sm transition-colors dark:bg-zinc-800 dark:text-zinc-200 dark:hover:text-emerald-400">
                                <flux:icon.clipboard-document-list class="size-3.5" />
                                <span class="">Logbook (12/15)</span>
                            </button>
                            <button
                                class="inline-flex items-center gap-space-3xs px-space-xs py-space-3xs rounded-md bg-primary text-on-primary text-[11px] font-title-sm hover:bg-primary-container transition-colors dark:bg-emerald-700 dark:text-white dark:hover:bg-emerald-600">
                                <flux:icon.eye class="size-3.5" />
                                <span class="">Detail</span>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Proposal Item 2 -->
                <div
                    class="bg-surface-container-lowest rounded-xl p-space-md shadow-xs hover:bg-surface-container-low/50 transition-all flex flex-col gap-space-xs border border-white/60 dark:bg-zinc-900 dark:hover:bg-zinc-800/60 dark:border-zinc-800">
                    <div class="flex flex-wrap items-center justify-between gap-space-xs">
                        <div class="flex items-center gap-space-xs">
                            <span
                                class="font-label-sm text-[10px] px-space-xs py-space-3xs rounded-full bg-secondary/10 text-secondary font-bold dark:bg-secondary/20">SKEMA:
                                PENGABDIAN KEMITRAAN (PKM)</span>
                            <span class="text-on-surface-variant text-[11px] font-label-md dark:text-zinc-400">Nomor
                                Registrasi:
                                UA/PKM-2025/031</span>
                        </div>
                        <div
                            class="flex items-center gap-space-2xs bg-surface-container-high text-on-surface px-space-xs py-space-3xs rounded-md dark:bg-zinc-800 dark:text-zinc-200">
                            <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                            <span class="font-label-sm text-[10px] font-bold">Tahap Paparan Desk &amp; Review</span>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <h3
                            class="font-title-sm text-[14px] text-on-surface font-semibold hover:text-primary transition-colors cursor-pointer leading-snug dark:text-zinc-100 dark:hover:text-emerald-400">
                            Pemberdayaan UMKM Olahan Garam Rakyat Berbasis Pesantren
                            Preneur di Kecamatan Guluk-Guluk
                        </h3>
                        <p
                            class="font-body-sm text-[12px] text-on-surface-variant mt-space-3xs line-clamp-2 leading-relaxed dark:text-zinc-400">
                            Aplikasi teknologi filtrasi garam konsumsi beryodium serta
                            pendampingan sertifikasi halal bagi kelompok usaha santri
                            mandiri.
                        </p>
                    </div>
                    <div
                        class="grid grid-cols-1 md:grid-cols-3 gap-space-xs py-space-2xs bg-surface-container-low/60 rounded-lg px-space-sm text-[11px] dark:bg-zinc-800/60">
                        <div class="flex items-center gap-space-2xs">
                            <flux:icon.banknotes class="size-3.5 text-secondary" />
                            <span class="text-on-surface-variant dark:text-zinc-400">Usulan Dana:</span>
                            <span class="font-bold text-on-surface dark:text-zinc-100">Rp 20.000.000</span>
                        </div>
                        <div class="flex items-center gap-space-2xs">
                            <flux:icon.star class="size-3.5 text-primary dark:text-emerald-400" />
                            <span class="text-on-surface-variant dark:text-zinc-400">Skor Evaluasi:</span>
                            <span class="text-primary font-bold dark:text-emerald-400">84.50 (Layak Lolos)</span>
                        </div>
                        <div class="flex items-center gap-space-2xs">
                            <flux:icon.check-badge class="size-3.5 text-on-surface-variant dark:text-zinc-500" />
                            <span class="text-on-surface-variant dark:text-zinc-400">Mitra:</span>
                            <span class="text-on-surface font-medium truncate dark:text-zinc-200">Koperasi Garam
                                Guluk-Guluk</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-space-xs pt-space-2xs">
                        <div
                            class="flex items-center gap-space-3xs text-[10px] text-on-surface-variant dark:text-zinc-400">
                            <flux:icon.calendar-days class="size-3.5 text-secondary" />
                            <span class="">Presentasi: 12 April 2025 (Ruang Sidang LPPM UA)</span>
                        </div>
                        <div class="flex items-center gap-space-2xs">
                            <button
                                class="inline-flex items-center gap-space-3xs px-space-xs py-space-3xs rounded-md bg-surface-container text-on-surface hover:text-primary text-[11px] font-title-sm transition-colors dark:bg-zinc-800 dark:text-zinc-200 dark:hover:text-emerald-400">
                                <flux:icon.presentation-chart-bar class="size-3.5" />
                                <span class="">Slide Paparan</span>
                            </button>
                            <button
                                class="inline-flex items-center gap-space-3xs px-space-xs py-space-3xs rounded-md bg-surface-container text-on-surface hover:text-primary text-[11px] font-title-sm transition-colors dark:bg-zinc-800 dark:text-zinc-200 dark:hover:text-emerald-400">
                                <flux:icon.chat-bubble-left class="size-3.5" />
                                <span class="">Catatan (2)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Serapan Anggaran & Timeline Section -->
            <section
                class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-md shadow-xs flex flex-col gap-space-md dark:bg-zinc-900/80">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-space-2xs">
                        <flux:icon.chart-bar class="size-5 text-primary dark:text-emerald-400" />
                        <h2 class="font-title-sm text-[14px] text-on-surface font-semibold dark:text-zinc-100">
                            Realisasi Anggaran &amp; Serapan SPK 2025
                        </h2>
                    </div>
                    <span
                        class="font-label-sm text-[10px] text-primary font-semibold bg-primary/10 px-space-xs py-space-3xs rounded dark:bg-primary/20 dark:text-emerald-400">Total:
                        Rp 65.000.000</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md items-center">
                    <div
                        class="flex flex-col gap-space-xs bg-surface-container-low/70 p-space-sm rounded-lg dark:bg-zinc-800/70">
                        <div class="flex justify-between items-center text-[11px]">
                            <span class="text-on-surface-variant font-medium dark:text-zinc-400">Serapan Dana (Termin
                                1)</span>
                            <span class="text-primary font-bold dark:text-emerald-400">Rp 32.450.000 (71.3%)</span>
                        </div>
                        <div
                            class="w-full bg-surface-container h-3 rounded-full flex overflow-hidden dark:bg-zinc-700">
                            <div class="bg-primary h-full dark:bg-emerald-500" style="width: 48%"></div>
                            <div class="bg-secondary h-full" style="width: 18%"></div>
                            <div class="bg-tertiary-fixed h-full dark:bg-amber-500/70" style="width: 5%"></div>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-between text-[10px] text-on-surface-variant pt-space-3xs dark:text-zinc-400">
                            <span class="flex items-center gap-1"><span
                                    class="w-2 h-2 rounded bg-primary dark:bg-emerald-500"></span>
                                Operasional</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-secondary"></span>
                                Bahan Habis Pakai</span>
                            <span class="flex items-center gap-1"><span
                                    class="w-2 h-2 rounded bg-tertiary-fixed dark:bg-amber-500/70"></span>
                                Publikasi</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-space-xs">
                        <span
                            class="font-label-sm text-[10px] uppercase tracking-wider text-on-surface-variant font-bold dark:text-zinc-400">Siklus
                            Pelaksanaan Kontrak</span>
                        <div class="flex items-center justify-between relative">
                            <div
                                class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-surface-container -z-0 dark:bg-zinc-700">
                            </div>
                            <div class="flex flex-col items-center gap-1 relative z-10">
                                <div
                                    class="w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center text-[10px] dark:bg-emerald-600 dark:text-white">
                                    <flux:icon.check class="size-3" />
                                </div>
                                <span class="text-[9px] font-title-sm text-on-surface dark:text-zinc-200">SPK
                                    Terbit</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 relative z-10">
                                <div
                                    class="w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center text-[10px] dark:bg-emerald-600 dark:text-white">
                                    <flux:icon.check class="size-3" />
                                </div>
                                <span class="text-[9px] font-title-sm text-on-surface dark:text-zinc-200">Pencairan
                                    I</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 relative z-10">
                                <div
                                    class="w-6 h-6 rounded-full bg-primary-container text-on-primary ring-4 ring-primary/20 flex items-center justify-center text-[10px] font-bold dark:bg-emerald-700 dark:text-white dark:ring-emerald-500/30">
                                    3
                                </div>
                                <span
                                    class="text-[9px] font-title-sm text-primary font-bold dark:text-emerald-400">Monev</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 relative z-10">
                                <div
                                    class="w-6 h-6 rounded-full bg-surface-container text-on-surface-variant flex items-center justify-center text-[10px] dark:bg-zinc-800 dark:text-zinc-400">
                                    4
                                </div>
                                <span
                                    class="text-[9px] font-title-sm text-on-surface-variant dark:text-zinc-500">Laporan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column Widgets (4 Cols) -->
        <div class="lg:col-span-4 flex flex-col gap-space-lg">
            <!-- Output Checklist & Required HKI -->
            <div
                class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-md shadow-xs flex flex-col gap-space-sm dark:bg-zinc-900/80">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-space-2xs">
                        <flux:icon.check-badge class="size-5 text-primary dark:text-emerald-400" />
                        <h3 class="font-title-sm text-[14px] text-on-surface font-semibold dark:text-zinc-100">
                            Kewajiban Luaran &amp; HKI
                        </h3>
                    </div>
                    <span class="text-[11px] text-primary font-bold dark:text-emerald-400">2/4 Tercapai</span>
                </div>
                <div class="flex flex-col gap-space-xs text-[11px]">
                    <div
                        class="flex items-start gap-space-xs p-space-xs rounded-lg bg-surface-container-low/60 dark:bg-zinc-800/60">
                        <flux:icon.check-circle class="size-4 text-primary mt-0.5 shrink-0 dark:text-emerald-400" />
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface text-[11px] dark:text-zinc-100">Jurnal SINTA
                                2</span>
                            <span class="text-on-surface-variant text-[10px] dark:text-zinc-400">Al-Balagh: Jurnal
                                Dakwah</span>
                            <span class="text-primary font-bold text-[10px] dark:text-emerald-400">Status: Terbit Vol.
                                9 (2025)</span>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-space-xs p-space-xs rounded-lg bg-surface-container-low/60 dark:bg-zinc-800/60">
                        <flux:icon.clock class="size-4 text-secondary mt-0.5 shrink-0" />
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface text-[11px] dark:text-zinc-100">Scopus Q2 Under
                                Review</span>
                            <span class="text-on-surface-variant text-[10px] dark:text-zinc-400">Asian Fisheries
                                Science</span>
                            <span class="text-secondary font-medium text-[10px]">Status: Target Juni 2025</span>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-space-xs p-space-xs rounded-lg bg-surface-container-low/60 dark:bg-zinc-800/60">
                        <flux:icon.check-circle class="size-4 text-primary mt-0.5 shrink-0 dark:text-emerald-400" />
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface text-[11px] dark:text-zinc-100">Hak Cipta Modul
                                Konservasi</span>
                            <span class="text-on-surface-variant text-[10px] dark:text-zinc-400">EC00202504918</span>
                            <span class="text-primary font-bold text-[10px] dark:text-emerald-400">Status: Sertifikat
                                HKI Terbit</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Research Team & Student MBKM Collaboration -->
            <div
                class="bg-surface-container-lowest/80 backdrop-blur-md rounded-xl p-space-md shadow-xs flex flex-col gap-space-sm dark:bg-zinc-900/80">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-space-2xs">
                        <flux:icon.users class="size-5 text-primary dark:text-emerald-400" />
                        <h3 class="font-title-sm text-[14px] text-on-surface font-semibold dark:text-zinc-100">
                            Anggota &amp; Mahasiswa MBKM
                        </h3>
                    </div>
                    <button
                        class="text-[11px] text-primary hover:underline font-semibold dark:text-emerald-400">
                        + Tambah
                    </button>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <div
                        class="flex items-center justify-between p-space-xs bg-surface-container-low/50 rounded-lg dark:bg-zinc-800/50">
                        <div class="flex items-center gap-space-xs min-w-0">
                            <div
                                class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-[10px] dark:bg-primary/20 dark:text-emerald-400">
                                AF
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="font-title-sm text-[11px] text-on-surface truncate dark:text-zinc-100">Ahmad
                                    Fawaid, M.Si.</span>
                                <span class="text-on-surface-variant text-[10px] dark:text-zinc-400">Anggota
                                    Peneliti</span>
                            </div>
                        </div>
                        <span
                            class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-primary/10 text-primary font-bold dark:bg-primary/20 dark:text-emerald-400">Valid</span>
                    </div>
                    <div
                        class="flex items-center justify-between p-space-xs bg-surface-container-low/50 rounded-lg dark:bg-zinc-800/50">
                        <div class="flex items-center gap-space-xs min-w-0">
                            <div
                                class="w-7 h-7 rounded-full bg-secondary/10 text-secondary flex items-center justify-center font-bold text-[10px] dark:bg-secondary/20">
                                NM
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="font-title-sm text-[11px] text-on-surface truncate dark:text-zinc-100">Nurul
                                    Mawaddah, M.Pd.</span>
                                <span class="text-on-surface-variant text-[10px] dark:text-zinc-400">Anggota
                                    Peneliti</span>
                            </div>
                        </div>
                        <span
                            class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-primary/10 text-primary font-bold dark:bg-primary/20 dark:text-emerald-400">Valid</span>
                    </div>
                    <div
                        class="flex items-center justify-between p-space-xs bg-surface-container-low/50 rounded-lg dark:bg-zinc-800/50">
                        <div class="flex items-center gap-space-xs min-w-0">
                            <div
                                class="w-7 h-7 rounded-full bg-surface-container text-on-surface flex items-center justify-center font-bold text-[10px] dark:bg-zinc-700 dark:text-zinc-200">
                                MF
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="font-title-sm text-[11px] text-on-surface truncate dark:text-zinc-100">Moh.
                                    Farhan Ramadhani</span>
                                <span class="text-on-surface-variant text-[10px] dark:text-zinc-400">MBKM Riset</span>
                            </div>
                        </div>
                        <span
                            class="font-label-sm text-[10px] px-space-2xs py-space-3xs rounded bg-surface-container text-on-surface-variant font-semibold dark:bg-zinc-700 dark:text-zinc-300">SK
                            Dekan</span>
                    </div>
                </div>
            </div>

            <!-- LPPM Helpdesk Widget -->
            <div
                class="bg-gradient-to-br from-primary/10 via-surface-container-lowest/90 to-surface-container-low/80 backdrop-blur-md rounded-xl p-space-md shadow-xs flex flex-col gap-space-xs dark:from-emerald-900/30 dark:via-zinc-900/90 dark:to-zinc-900/80">
                <div class="flex items-center gap-space-2xs">
                    <flux:icon.lifebuoy class="size-5 text-primary dark:text-emerald-400" />
                    <h3 class="font-title-sm text-[14px] text-on-surface font-semibold dark:text-zinc-100">
                        Klinik Publikasi &amp; Monev
                    </h3>
                </div>
                <p class="text-[12px] text-on-surface-variant leading-relaxed dark:text-zinc-400">
                    Butuh konsultasi proofreading manuskrip, validasi RAB monev,
                    atau pendampingan pengurusan HKI bersama reviewer LPPM
                    Universitas Annuqayah.
                </p>
                <div class="flex items-center justify-between pt-space-2xs mt-space-3xs">
                    <span class="text-[10px] text-on-surface-variant dark:text-zinc-500">Gedung Rektorat UA</span>
                    <a class="inline-flex items-center gap-1 text-[11px] font-title-sm text-primary font-bold hover:underline dark:text-emerald-400"
                        href="#">
                        <span class="">Konsultasi WA</span>
                        <flux:icon.arrow-right class="size-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
