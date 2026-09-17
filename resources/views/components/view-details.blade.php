@props([
    'width' => 'sm:max-w-2xl',
])

<div
    x-data="{
        show: false,
        data: {
            title: '',
            scheme: '',
            period: '',
            status: '',
            statusClass: '',
            is_research: true,
            keywords: [],
            summary: '',
            reviewer: null,
            owner: null,
            budgetItems: [],
            budgetLimit: 0,
            budgetTotal: 0,
            budgetRemaining: 0,
            budgetPercent: 0,
            createdAt: '',
            updatedAt: '',
        },

        open(detail = {}) {
            this.data = Object.assign(this.data, detail);
            this.show = true;
        },

        close() {
            this.show = false;
        },

        formatRupiah(val) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(val || 0);
        }
    }"
    x-on:view-details.window="open($event.detail)"
    x-on:keydown.escape.window="close()"
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
           p-0 sm:p-4 bg-inverse-surface/40 backdrop-blur-sm">

    <div
        x-on:click.away="close()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
               w-full {{ $width }}
               rounded-t-2xl sm:rounded-xl
               max-h-[92vh] sm:max-h-[90vh] flex flex-col
               border border-outline-variant/50 dark:border-zinc-700">

        {{-- ── Header ── --}}
        <div class="shrink-0 px-5 sm:px-6 py-4
                    border-b border-outline-variant/40 dark:border-zinc-800
                    rounded-t-2xl sm:rounded-t-xl">

            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                   text-[10px] font-bold uppercase tracking-wider"
                            :class="data.is_research
                                ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-fixed-dim'
                                : 'bg-secondary/10 text-secondary dark:bg-secondary/20 dark:text-secondary-fixed-dim'">
                            <flux:icon.beaker x-show="data.is_research" class="size-3" />
                            <flux:icon.hand-raised x-show="!data.is_research" class="size-3" />
                            <span x-text="data.is_research ? 'Research' : 'Dedication'"></span>
                        </span>

                        {{-- Status badge --}}
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                   text-[10px] font-semibold whitespace-nowrap"
                            :class="data.statusClass"
                            x-text="data.status"></span>
                    </div>

                    <h3 class="font-heading text-base sm:text-lg font-semibold
                               text-on-surface dark:text-zinc-100 leading-snug"
                        x-text="data.title"></h3>
                </div>

                <button type="button" x-on:click="close()"
                    class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                           text-outline hover:text-on-surface hover:bg-surface-container-low
                           dark:text-zinc-500 dark:hover:text-zinc-200 dark:hover:bg-zinc-800
                           transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- ── Body ── --}}
        <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">

            {{-- Meta grid: Scheme, Period, Reviewer, Owner --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                <div class="p-3 rounded-xl
                            border border-outline-variant/60 dark:border-zinc-700
                            bg-surface-container-low dark:bg-zinc-800/40">
                    <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wider
                                text-outline dark:text-zinc-400 font-semibold">
                        <flux:icon.rectangle-group class="size-3" />
                        Scheme
                    </div>
                    <p class="mt-1 text-sm font-medium
                              text-on-surface dark:text-zinc-100"
                        x-text="data.scheme || '—'"></p>
                </div>

                <div class="p-3 rounded-xl
                            border border-outline-variant/60 dark:border-zinc-700
                            bg-surface-container-low dark:bg-zinc-800/40">
                    <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wider
                                text-outline dark:text-zinc-400 font-semibold">
                        <flux:icon.calendar-days class="size-3" />
                        Period
                    </div>
                    <p class="mt-1 text-sm font-medium
                              text-on-surface dark:text-zinc-100"
                        x-text="data.period || '—'"></p>
                </div>

                <div class="p-3 rounded-xl
                            border border-outline-variant/60 dark:border-zinc-700
                            bg-surface-container-low dark:bg-zinc-800/40">
                    <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wider
                                text-outline dark:text-zinc-400 font-semibold">
                        <flux:icon.user-circle class="size-3" />
                        Owner
                    </div>
                    <p class="mt-1 text-sm font-medium
                              text-on-surface dark:text-zinc-100"
                        x-text="data.owner || '—'"></p>
                </div>

                <div class="p-3 rounded-xl
                            border border-outline-variant/60 dark:border-zinc-700
                            bg-surface-container-low dark:bg-zinc-800/40">
                    <div class="flex items-center gap-1.5 text-[10px] uppercase tracking-wider
                                text-outline dark:text-zinc-400 font-semibold">
                        <flux:icon.user-group class="size-3" />
                        Reviewer
                    </div>
                    <template x-if="data.reviewer">
                        <div class="mt-1 flex items-center gap-2">
                            <div class="w-5 h-5 rounded-full
                                        bg-violet-100 text-violet-700
                                        flex items-center justify-center text-[9px] font-bold
                                        dark:bg-violet-900/40 dark:text-violet-300"
                                x-text="data.reviewer.charAt(0).toUpperCase()"></div>
                            <p class="text-sm font-medium truncate
                                      text-on-surface dark:text-zinc-100"
                                x-text="data.reviewer"></p>
                        </div>
                    </template>
                    <template x-if="!data.reviewer">
                        <p class="mt-1 text-sm italic
                                  text-outline dark:text-zinc-500">
                            Not assigned
                        </p>
                    </template>
                </div>
            </div>

            {{-- Keywords --}}
            <div>
                <h4 class="font-heading text-[11px] uppercase tracking-wider font-semibold
                           text-outline dark:text-zinc-400 mb-2">
                    Keywords
                </h4>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="(kw, i) in data.keywords" :key="i">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-md
                                   bg-surface-container-low dark:bg-zinc-800
                                   text-[11px] font-medium
                                   text-on-surface-variant dark:text-zinc-300
                                   border border-outline-variant/60 dark:border-zinc-700"
                            x-text="kw.trim()"></span>
                    </template>
                    <template x-if="!data.keywords.length">
                        <span class="text-[11px] text-outline dark:text-zinc-500 italic">
                            No keywords
                        </span>
                    </template>
                </div>
            </div>

            {{-- Summary --}}
            <div>
                <h4 class="font-heading text-[11px] uppercase tracking-wider font-semibold
                           text-outline dark:text-zinc-400 mb-2">
                    Summary
                </h4>
                <div class="p-3 rounded-xl
                            bg-surface-container-low dark:bg-zinc-800/40
                            border border-outline-variant/60 dark:border-zinc-700">
                    <p class="text-sm leading-relaxed whitespace-pre-line
                              text-on-surface-variant dark:text-zinc-300"
                        x-text="data.summary || '—'"></p>
                </div>
            </div>

            {{-- Budget section --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-heading text-[11px] uppercase tracking-wider font-semibold
                               text-outline dark:text-zinc-400">
                        Budget Allocation
                    </h4>
                    <span class="text-[10px] font-mono
                                 text-on-surface-variant dark:text-zinc-400">
                        <span x-text="data.budgetItems.length"></span>
                        <span x-text="data.budgetItems.length === 1 ? 'item' : 'items'"></span>
                    </span>
                </div>

                {{-- Summary strip --}}
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="p-2.5 rounded-lg
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <div class="text-[9px] uppercase tracking-wider font-semibold
                                    text-outline dark:text-zinc-500">
                            Limit
                        </div>
                        <p class="mt-0.5 text-[12px] font-bold font-mono tabular-nums
                                  text-on-surface dark:text-zinc-100"
                            x-text="formatRupiah(data.budgetLimit)"></p>
                    </div>
                    <div class="p-2.5 rounded-lg
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <div class="text-[9px] uppercase tracking-wider font-semibold
                                    text-outline dark:text-zinc-500">
                            Allocated
                        </div>
                        <p class="mt-0.5 text-[12px] font-bold font-mono tabular-nums
                                  text-on-surface dark:text-zinc-100"
                            x-text="formatRupiah(data.budgetTotal)"></p>
                    </div>
                    <div class="p-2.5 rounded-lg border"
                        :class="data.budgetRemaining < 0
                            ? 'bg-error-container/40 dark:bg-rose-900/20 border-error/30 dark:border-rose-800'
                            : 'bg-primary/5 dark:bg-emerald-900/20 border-primary/30 dark:border-emerald-800'">
                        <div class="text-[9px] uppercase tracking-wider font-semibold"
                            :class="data.budgetRemaining < 0
                                ? 'text-error dark:text-rose-400'
                                : 'text-primary dark:text-primary-fixed-dim'">
                            Remaining
                        </div>
                        <p class="mt-0.5 text-[12px] font-bold font-mono tabular-nums"
                            :class="data.budgetRemaining < 0
                                ? 'text-error dark:text-rose-400'
                                : 'text-primary dark:text-primary-fixed-dim'"
                            x-text="formatRupiah(data.budgetRemaining)"></p>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="mb-3">
                    <div class="w-full h-1.5 rounded-full overflow-hidden
                                bg-surface-container-high dark:bg-zinc-800">
                        <div class="h-full rounded-full transition-all duration-300
                                    bg-gradient-to-r from-primary to-primary-container"
                            :style="`width: ${Math.min(data.budgetPercent, 100)}%`"></div>
                    </div>
                </div>

                {{-- Items list --}}
                <div class="rounded-xl
                            border border-outline-variant/60 dark:border-zinc-700
                            overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[400px] text-xs">
                            <thead
                                class="bg-surface-container-low dark:bg-zinc-800/60
                                       text-left text-[10px] uppercase tracking-wider
                                       text-outline dark:text-zinc-400">
                                <tr>
                                    <th class="px-3 py-2 font-semibold w-8">#</th>
                                    <th class="px-3 py-2 font-semibold">Item</th>
                                    <th class="px-3 py-2 font-semibold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40 dark:divide-zinc-800">
                                <template x-for="(item, i) in data.budgetItems" :key="i">
                                    <tr class="hover:bg-surface-container-low/60 dark:hover:bg-zinc-800/30
                                               transition-colors">
                                        <td class="px-3 py-2 font-mono text-[11px]
                                                   text-outline dark:text-zinc-500"
                                            x-text="i + 1"></td>
                                        <td class="px-3 py-2 font-medium
                                                   text-on-surface dark:text-zinc-100"
                                            x-text="item.item_name"></td>
                                        <td class="px-3 py-2 text-right font-mono tabular-nums
                                                   text-on-surface dark:text-zinc-100"
                                            x-text="formatRupiah(item.amount)"></td>
                                    </tr>
                                </template>

                                <template x-if="!data.budgetItems.length">
                                    <tr>
                                        <td colspan="3" class="px-3 py-6 text-center">
                                            <div class="flex flex-col items-center gap-1">
                                                <flux:icon.receipt-percent
                                                    class="size-6 text-outline-variant dark:text-zinc-700" />
                                                <span class="text-[11px]
                                                             text-outline dark:text-zinc-500">
                                                    No budget items
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>

                            <template x-if="data.budgetItems.length">
                                <tfoot>
                                    <tr class="bg-surface-container-low dark:bg-zinc-800/60">
                                        <td colspan="2"
                                            class="px-3 py-2 text-right text-[10px]
                                                   uppercase tracking-wider font-semibold
                                                   text-outline dark:text-zinc-400">
                                            Total
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold
                                                   text-[12px] tabular-nums
                                                   text-on-surface dark:text-zinc-100"
                                            x-text="formatRupiah(data.budgetTotal)"></td>
                                    </tr>
                                </tfoot>
                            </template>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Timeline footer --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5
                        pt-3 border-t border-outline-variant/40 dark:border-zinc-800
                        text-[11px] text-outline dark:text-zinc-500">
                <div class="flex items-center gap-1.5">
                    <flux:icon.clock class="size-3" />
                    <span>Created</span>
                    <span class="font-medium text-on-surface-variant dark:text-zinc-400"
                        x-text="data.createdAt"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <flux:icon.arrow-path class="size-3" />
                    <span>Updated</span>
                    <span class="font-medium text-on-surface-variant dark:text-zinc-400"
                        x-text="data.updatedAt"></span>
                </div>
            </div>
        </div>

        {{-- ── Footer ── --}}
        <div class="shrink-0 flex justify-end gap-2
                    px-5 sm:px-6 py-4
                    border-t border-outline-variant/40 dark:border-zinc-700
                    bg-surface-container-lowest dark:bg-zinc-900
                    rounded-b-2xl sm:rounded-b-xl">
            <button type="button" x-on:click="close()"
                class="px-4 py-2 text-sm font-medium rounded-md
                       text-on-surface-variant dark:text-zinc-300
                       bg-surface-container-lowest dark:bg-zinc-800
                       border border-outline-variant dark:border-zinc-600
                       hover:bg-surface-container-low dark:hover:bg-zinc-700
                       focus:outline-none focus:ring-2 focus:ring-primary
                       transition-colors duration-150">
                Close
            </button>
        </div>
    </div>
</div>
