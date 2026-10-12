@props([
    'variant' => 'research',
    'code'   => null,
    'full'   => null,
    'statusClass' => null,
])

@php
    $isResearch = $variant === 'research';

    $theme = $isResearch
        ? ['icon' => 'beaker', 'color' => 'emerald', 'label' => 'Research']
        : ['icon' => 'heart', 'color' => 'rose', 'label' => 'Community Service'];

    $statuses = [
        ['SBM', 'Submitted',         'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',             'Uploaded by Leader, awaiting Admin verification.'],
        ['ADR', 'Admin Revision',    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',          'Admin requested revision. Leader can edit & re-upload.'],
        ['ADA', 'Admin Approved',    'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',              'Approved by Admin. Waiting for Reviewer assignment.'],
        ['REV', 'Under Review',      'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',      'Currently being reviewed by the Reviewer.'],
        ['RVR', 'Reviewer Revision', 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',      'Reviewer requested revision. Leader can edit & re-upload.'],
        ['RVA', 'Reviewer Approved', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',              'Approved by Reviewer. Awaiting Admin final decision.'],
        ['ACC', 'Accepted',          'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',  'Proposal accepted. Final status.'],
        ['REJ', 'Rejected',          'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',              'Proposal rejected. Final status.'],
    ];

    $instanceId = 'sg-' . $variant . '-' . \Illuminate\Support\Str::random(6);
@endphp

<div
    id="{{ $instanceId }}-root"
    x-data="{ open: false }"
    x-cloak
>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TRIGGER — badge status yang bisa diklik                       --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <button
        type="button"
        @click="open = true"
        :aria-expanded="open"
        @class([
            'group inline-flex items-center gap-1 px-1.5 sm:px-2 py-0.5 rounded-full
             text-[9.5px] font-semibold whitespace-nowrap
             hover:ring-2 hover:ring-offset-1
             hover:ring-offset-white dark:hover:ring-offset-zinc-900
             hover:scale-[1.03] active:scale-[0.97]
             transition-all duration-150 cursor-pointer',

            'hover:ring-emerald-400 dark:hover:ring-emerald-500' => $isResearch,
            'hover:ring-rose-400 dark:hover:ring-rose-500'       => ! $isResearch,

            $statusClass,
        ])"
        title="Click to view status guide"
    >
        <span>{{ $code ?? $full ?? '—' }}</span>
        <svg xmlns="http://www.w3.org/2000/svg"
             width="10" height="10"
             viewBox="0 0 24 24"
             fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
             class="size-2.5 shrink-0 opacity-60
                    group-hover:opacity-100
                    group-hover:rotate-12
                    transition-all duration-150">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M12 9h.01" />
            <path d="M11 12h1v4h1" />
            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
        </svg>
    </button>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- MODAL                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="open = false"
            @click.self="open = false"
            class="fixed inset-0 z-[90] flex items-center justify-center p-4"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div
                @click="open = false"
                class="absolute inset-0 bg-slate-900/40 dark:bg-black/55"
            ></div>

            {{-- Modal Panel --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="relative w-full max-w-sm
                       bg-white dark:bg-zinc-900
                       rounded-2xl
                       shadow-2xl shadow-slate-900/25 dark:shadow-black/60
                       border border-slate-200 dark:border-zinc-700
                       overflow-hidden
                       flex flex-col
                       max-h-[82vh]"
            >

                {{-- HEADER — pakai @class conditional --}}
                <div @class([
                    'shrink-0 px-4 py-3 flex items-center gap-2.5',
                    'bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700' => $isResearch,
                    'bg-gradient-to-r from-rose-500 to-rose-600 dark:from-rose-600 dark:to-rose-700'             => ! $isResearch,
                ])>

                    <span class="w-7 h-7 rounded-full bg-white/20 backdrop-blur-sm
                                 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="16" height="16"
                             viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle
                                    w-4 h-4 text-white">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                            <path d="M12 9h.01" />
                            <path d="M11 12h1v4h1" />
                        </svg>
                    </span>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-[12.5px] font-semibold text-white leading-tight">
                            Status Guide
                        </h3>
                        <p class="text-[9.5px] text-white/75 mt-0.5 truncate">
                            {{ $theme['label'] }} proposal statuses
                        </p>
                    </div>

                    <button type="button"
                        @click="open = false"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-full
                               flex items-center justify-center
                               text-white/80 hover:text-white
                               hover:bg-white/15
                               transition-colors duration-150
                               cursor-pointer">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>

                {{-- BODY --}}
                <div class="flex-1 overflow-y-auto
                            divide-y divide-slate-100 dark:divide-zinc-800/70
                            [scrollbar-width:thin]
                            [&::-webkit-scrollbar]:w-1.5
                            [&::-webkit-scrollbar-thumb]:bg-slate-300
                            [&::-webkit-scrollbar-thumb]:rounded-full
                            dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700">

                    @foreach ($statuses as [$sCode, $sFull, $sClass, $sDesc])
                        @php
                            $isCurrent = $code && strtoupper($code) === strtoupper($sCode);
                        @endphp
                        <div @class([
                            'flex items-start gap-2.5 px-4 py-2 transition-colors duration-150',
                            'bg-slate-50 dark:bg-zinc-800/40' => $isCurrent,
                            'hover:bg-slate-50 dark:hover:bg-zinc-800/40' => ! $isCurrent,
                        ])>
                            <span class="shrink-0 w-[42px] inline-flex items-center justify-center
                                         px-1.5 py-0.5 rounded-full
                                         text-[9px] font-bold tracking-wider
                                         {{ $sClass }}"
                                  title="{{ $sFull }}">
                                {{ $sCode }}
                            </span>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-[11px] font-semibold leading-tight
                                              text-slate-900 dark:text-zinc-100">
                                        {{ $sFull }}
                                    </p>
                                    @if ($isCurrent)
                                        <span @class([
                                            'inline-flex items-center gap-0.5 px-1.5 py-px rounded-full
                                             text-[8.5px] font-bold text-white shrink-0',
                                            'bg-emerald-500' => $isResearch,
                                            'bg-rose-500'    => ! $isResearch,
                                        ])>
                                            <flux:icon.check class="size-2" />
                                            Current
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[10px] leading-snug
                                          text-slate-500 dark:text-zinc-400">
                                    {{ $sDesc }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- FOOTER --}}
                <div class="shrink-0 px-4 py-2.5
                            border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50
                            flex items-center justify-between gap-2">

                    <div class="flex items-center gap-1.5 min-w-0">
                        <flux:icon.light-bulb @class([
                            'size-3 shrink-0',
                            'text-emerald-600 dark:text-emerald-400' => $isResearch,
                            'text-rose-600 dark:text-rose-400'       => ! $isResearch,
                        ]) />
                        <p class="text-[9.5px] leading-tight
                                  text-slate-500 dark:text-zinc-400 truncate">
                            Edit only when status is
                            <strong class="text-slate-700 dark:text-zinc-200">ADR</strong> or
                            <strong class="text-slate-700 dark:text-zinc-200">RVR</strong>.
                        </p>
                    </div>

                    <button type="button"
                        @click="open = false"
                        class="shrink-0 inline-flex items-center
                               px-2.5 py-1 rounded-full
                               text-[10px] font-medium
                               text-slate-700 dark:text-zinc-300
                               bg-white dark:bg-zinc-800
                               border border-slate-300 dark:border-zinc-600
                               hover:bg-slate-50 dark:hover:bg-zinc-700
                               hover:scale-[1.02] active:scale-[0.97]
                               transition-all duration-150 cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
