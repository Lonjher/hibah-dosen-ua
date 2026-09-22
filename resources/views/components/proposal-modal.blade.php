@props([
    'showModal'       => false,
    'step'            => 1,
    'editMode'        => false,
    'schemes'         => collect(),
    'periods'         => collect(),
    'budgetItems'     => collect(),
    'budgetLimit'     => 0,
    'budgetTotal'     => 0,
    'budgetRemaining' => 0,
    'budgetPercent'   => 0,
])

<div x-data="{ show: @entangle('showModal') }"
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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
               w-full sm:max-w-3xl
               rounded-t-2xl sm:rounded-xl
               max-h-[92vh] sm:max-h-[90vh] flex flex-col
               border border-outline-variant/50 dark:border-zinc-700">

        {{-- ── Header ── --}}
        <div
            class="shrink-0
                   bg-gradient-to-r from-primary to-primary-container
                   px-5 sm:px-6 py-4
                   rounded-t-2xl sm:rounded-t-xl">

            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="font-heading text-base sm:text-lg font-semibold text-on-primary truncate">
                        {{ $editMode ? 'Edit Research Proposal' : 'New Research Proposal' }}
                    </h3>

                    {{-- Step indicator --}}
                    <div class="flex items-center gap-2 mt-2 text-[11px] text-on-primary/90">
                        <span class="inline-flex items-center gap-1
                                     {{ $step === 1 ? 'font-semibold' : '' }}">
                            <span
                                class="inline-flex items-center justify-center
                                       w-4 h-4 rounded-full text-[9px] font-bold
                                       {{ $step >= 1
                                            ? 'bg-primary-fixed text-primary'
                                            : 'bg-on-primary/30 text-on-primary' }}">
                                @if ($step > 1)
                                    <flux:icon.check class="size-2.5" />
                                @else
                                    1
                                @endif
                            </span>
                            Metadata
                        </span>

                        <span class="w-6 h-px bg-on-primary/40"></span>

                        <span class="inline-flex items-center gap-1
                                     {{ $step === 2 ? 'font-semibold' : 'text-on-primary/60' }}">
                            <span
                                class="inline-flex items-center justify-center
                                       w-4 h-4 rounded-full text-[9px] font-bold
                                       {{ $step === 2
                                            ? 'bg-primary-fixed text-primary'
                                            : 'bg-on-primary/30 text-on-primary' }}">
                                2
                            </span>
                            Budget
                        </span>
                    </div>
                </div>

                <button type="button" wire:click="$set('showModal', false)"
                    class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                           text-on-primary/80 hover:text-on-primary hover:bg-on-primary/10
                           transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- ── Body ── --}}
        <div class="flex-1 overflow-y-auto p-5 sm:p-6">

            {{-- ═════════ STEP 1: METADATA ═════════ --}}
            @if ($step === 1)
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="scheme"
                                class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                                Scheme <span class="text-error">*</span>
                            </label>
                            <select id="scheme" wire:model.live="research_scheme_id"
                                class="mt-1 block w-full rounded-md shadow-sm
                                       border-outline-variant dark:border-zinc-600
                                       bg-surface-container-lowest dark:bg-zinc-800
                                       text-on-surface dark:text-zinc-100
                                       focus:border-primary focus:ring-primary
                                       sm:text-sm py-2 px-3">
                                <option value="">Select Scheme</option>
                                @foreach ($schemes as $scheme)
                                    <option value="{{ $scheme->id }}">
                                        {{ $scheme->name }}
                                        @if ($scheme->scheme_name)
                                            {{ $scheme->scheme_name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('research_scheme_id')
                                <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="period"
                                class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                                Period <span class="text-error">*</span>
                            </label>
                            <select id="period" wire:model="period_id"
                                class="mt-1 block w-full rounded-md shadow-sm
                                       border-outline-variant dark:border-zinc-600
                                       bg-surface-container-lowest dark:bg-zinc-800
                                       text-on-surface dark:text-zinc-100
                                       focus:border-primary focus:ring-primary
                                       sm:text-sm py-2 px-3">
                                <option value="">Select Period</option>
                                @foreach ($periods as $period)
                                    <option value="{{ $period->id }}">{{ $period->periode }}</option>
                                @endforeach
                            </select>
                            @error('period_id')
                                <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="title"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Title <span class="text-error">*</span>
                        </label>
                        <input type="text" id="title" wire:model="title"
                            placeholder="Enter your proposal title..."
                            class="mt-1 block w-full rounded-md shadow-sm
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-primary focus:ring-primary
                                   sm:text-sm py-2 px-3" />
                        @error('title')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="keywords"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Keywords <span class="text-error">*</span>
                        </label>
                        <input type="text" id="keywords" wire:model="keywords"
                            placeholder="conservation, ecology, pesantren"
                            class="mt-1 block w-full rounded-md shadow-sm
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-primary focus:ring-primary
                                   sm:text-sm py-2 px-3" />
                        <p class="text-[11px] text-outline dark:text-zinc-500 mt-1">
                            Separate keywords with commas.
                        </p>
                        @error('keywords')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="summary"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Summary <span class="text-error">*</span>
                        </label>
                        <textarea id="summary" wire:model="summary" rows="5"
                            placeholder="Brief summary of background, objectives, and methodology..."
                            class="mt-1 block w-full rounded-md shadow-sm resize-none
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   placeholder:text-outline dark:placeholder-zinc-500
                                   focus:border-primary focus:ring-primary
                                   sm:text-sm py-2 px-3"></textarea>
                        @error('summary')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div
                        class="flex items-start gap-3 p-3 rounded-lg
                               bg-primary/5 dark:bg-primary/10
                               border border-primary/20 dark:border-primary/30">
                        <flux:icon.information-circle
                            class="size-4 text-primary dark:text-primary-fixed-dim shrink-0 mt-0.5" />
                        <div class="text-[11px] text-primary dark:text-primary-fixed-dim leading-relaxed">
                            Next, you'll allocate the budget for this proposal. Make sure to select
                            the correct scheme — its budget limit will be enforced.
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═════════ STEP 2: BUDGET ═════════ --}}
            @if ($step === 2)
                <div class="space-y-5">

                    {{-- Budget summary cards --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <div
                            class="p-3 rounded-xl
                                   border border-outline-variant/60 dark:border-zinc-700
                                   bg-surface-container-low dark:bg-zinc-800/40">
                            <div
                                class="flex items-center gap-1.5 text-[10px]
                                       uppercase tracking-wider
                                       text-outline dark:text-zinc-400 font-semibold">
                                <flux:icon.banknotes class="size-3" />
                                Limit
                            </div>
                            <p class="mt-1 text-[15px] font-bold font-mono
                                      text-on-surface dark:text-zinc-100 tabular-nums">
                                Rp {{ number_format($budgetLimit, 0, ',', '.') }}
                            </p>
                        </div>

                        <div
                            class="p-3 rounded-xl
                                   border border-outline-variant/60 dark:border-zinc-700
                                   bg-surface-container-low dark:bg-zinc-800/40">
                            <div
                                class="flex items-center gap-1.5 text-[10px]
                                       uppercase tracking-wider
                                       text-outline dark:text-zinc-400 font-semibold">
                                <flux:icon.calculator class="size-3" />
                                Allocated
                            </div>
                            <p class="mt-1 text-[15px] font-bold font-mono
                                      text-on-surface dark:text-zinc-100 tabular-nums">
                                Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                            </p>
                        </div>

                        <div
                            class="p-3 rounded-xl border
                                   {{ $budgetRemaining < 0
                                        ? 'border-error/40 dark:border-rose-800/80 bg-error-container/40 dark:bg-rose-900/20'
                                        : 'border-primary/30 dark:border-emerald-800/80 bg-primary/5 dark:bg-emerald-900/20' }}">
                            <div
                                class="flex items-center gap-1.5 text-[10px]
                                       uppercase tracking-wider font-semibold
                                       {{ $budgetRemaining < 0
                                            ? 'text-error dark:text-rose-400'
                                            : 'text-primary dark:text-primary-fixed-dim' }}">
                                <flux:icon.wallet class="size-3" />
                                Remaining
                            </div>
                            <p class="mt-1 text-[15px] font-bold font-mono tabular-nums
                                      {{ $budgetRemaining < 0
                                            ? 'text-error dark:text-rose-400'
                                            : 'text-primary dark:text-primary-fixed-dim' }}">
                                Rp {{ number_format($budgetRemaining, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-1.5">
                            <span class="text-on-surface-variant dark:text-zinc-400">
                                Allocation progress
                            </span>
                            <span class="font-semibold text-on-surface dark:text-zinc-200">
                                {{ $budgetPercent }}%
                            </span>
                        </div>
                        <div
                            class="w-full h-2 rounded-full overflow-hidden
                                   bg-surface-container-high dark:bg-zinc-800">
                            <div class="h-full rounded-full transition-all duration-300
                                        bg-gradient-to-r from-primary to-primary-container"
                                style="width: {{ $budgetPercent }}%">
                            </div>
                        </div>
                    </div>

                    {{-- Inline add form --}}
                    <div
                        class="p-3 rounded-xl
                               border border-outline-variant/60 dark:border-zinc-700
                               bg-surface-container-low dark:bg-zinc-800/40">
                        <h4
                            class="font-heading text-[11px] font-semibold uppercase tracking-wider
                                   text-outline dark:text-zinc-400 mb-2">
                            Add Budget Item
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr,180px,auto] gap-2">
                            <div>
                                <input type="text" wire:model="item_name"
                                    placeholder="Item name (e.g. Field equipment)"
                                    class="block w-full rounded-md shadow-sm text-sm
                                           border-outline-variant dark:border-zinc-600
                                           bg-surface-container-lowest dark:bg-zinc-800
                                           text-on-surface dark:text-zinc-100
                                           placeholder:text-outline dark:placeholder-zinc-500
                                           focus:border-primary focus:ring-primary
                                           py-2 px-3" />
                                @error('item_name')
                                    <span class="text-error text-[11px] mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div x-data="{
                                    display: '',
                                    raw: @entangle('amount'),
                                    format(val) {
                                        let num = String(val).replace(/[^\d]/g, '');
                                        if (num === '') { this.raw = ''; return ''; }
                                        this.raw = num;
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(parseInt(num, 10));
                                    }
                                }"
                                x-init="display = raw ? format(raw) : ''">
                                <input type="text" inputmode="numeric" x-model="display"
                                    x-on:input="display = format($event.target.value)" placeholder="Rp 0"
                                    class="block w-full rounded-md shadow-sm text-sm
                                           border-outline-variant dark:border-zinc-600
                                           bg-surface-container-lowest dark:bg-zinc-800
                                           text-on-surface dark:text-zinc-100
                                           placeholder:text-outline dark:placeholder-zinc-500
                                           focus:border-primary focus:ring-primary
                                           py-2 px-3 text-right font-mono" />
                                @error('amount')
                                    <span class="text-error text-[11px] mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="button" wire:click="addBudgetItem" wire:loading.attr="disabled"
                                wire:target="addBudgetItem"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm
                                       font-medium text-on-primary rounded-md shrink-0
                                       bg-gradient-to-r from-primary to-primary-container
                                       hover:from-primary-container hover:to-primary
                                       focus:outline-none focus:ring-2 focus:ring-primary
                                       transition-all duration-200
                                       disabled:opacity-60 disabled:cursor-not-allowed">
                                <flux:icon.plus class="size-3.5" />
                                Add
                            </button>
                        </div>
                    </div>

                    {{-- Items list --}}
                    <div
                        class="rounded-xl
                               border border-outline-variant/60 dark:border-zinc-700
                               overflow-hidden">

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[480px] text-xs">
                                <thead
                                    class="bg-surface-container-low dark:bg-zinc-800/60
                                           text-left text-[10px] uppercase tracking-wider
                                           text-outline dark:text-zinc-400">
                                    <tr>
                                        <th class="px-4 py-2.5 font-semibold w-10">#</th>
                                        <th class="px-4 py-2.5 font-semibold">Item</th>
                                        <th class="px-4 py-2.5 font-semibold text-right">Amount</th>
                                        <th class="px-4 py-2.5 font-semibold text-right w-16"></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-outline-variant/40 dark:divide-zinc-800">
                                    @forelse ($budgetItems as $index => $item)
                                        <tr wire:key="bi-{{ $item->id }}"
                                            class="hover:bg-surface-container-low/60 dark:hover:bg-zinc-800/30
                                                   transition-colors">
                                            <td class="px-4 py-2.5 font-mono text-[11px]
                                                       text-outline dark:text-zinc-500">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-4 py-2.5 font-medium
                                                       text-on-surface dark:text-zinc-100">
                                                {{ $item->item_name }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-mono tabular-nums
                                                       text-on-surface dark:text-zinc-100">
                                                Rp {{ number_format($item->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <button type="button"
                                                    wire:click="removeBudgetItem({{ $item->id }})"
                                                    wire:confirm="Remove this budget item?"
                                                    title="Remove"
                                                    class="inline-flex items-center justify-center
                                                           w-6 h-6 rounded-md
                                                           text-outline hover:text-error
                                                           hover:bg-error-container/60
                                                           dark:text-zinc-500 dark:hover:text-rose-400
                                                           dark:hover:bg-rose-900/30
                                                           transition-colors">
                                                    <flux:icon.trash class="size-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center">
                                                <div class="flex flex-col items-center gap-2">
                                                    <flux:icon.receipt-percent
                                                        class="size-8 text-outline-variant dark:text-zinc-700" />
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-[12px] font-medium
                                                                     text-on-surface dark:text-zinc-300">
                                                            No items yet
                                                        </span>
                                                        <span class="text-[11px]
                                                                     text-on-surface-variant dark:text-zinc-400">
                                                            Add at least one budget item.
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>

                                @if ($budgetItems->count() > 0)
                                    <tfoot>
                                        <tr class="bg-surface-container-low dark:bg-zinc-800/60">
                                            <td colspan="2"
                                                class="px-4 py-2.5 text-right text-[10px]
                                                       uppercase tracking-wider font-semibold
                                                       text-outline dark:text-zinc-400">
                                                Total
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-mono font-bold
                                                       text-[13px] tabular-nums
                                                       text-on-surface dark:text-zinc-100">
                                                Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if ($budgetRemaining < 0)
                        <div
                            class="flex items-start gap-2 p-3 rounded-lg
                                   bg-error-container/50 dark:bg-rose-900/20
                                   border border-error/30 dark:border-rose-800">
                            <flux:icon.exclamation-triangle
                                class="size-4 text-error dark:text-rose-400 shrink-0 mt-0.5" />
                            <div class="text-[11px] text-on-error-container dark:text-rose-300 leading-relaxed">
                                Total allocation exceeds the budget limit. Please remove some
                                items before finishing.
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── Footer ── --}}
        <div
            class="shrink-0 flex items-center justify-between gap-2
                   px-5 sm:px-6 py-4
                   border-t border-outline-variant/40 dark:border-zinc-700
                   bg-surface-container-lowest dark:bg-zinc-900
                   rounded-b-2xl sm:rounded-b-xl">

            {{-- Left: Cancel or Back --}}
            <div class="flex items-center gap-2">
                @if ($step === 1)
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md
                               text-on-surface-variant dark:text-zinc-300
                               bg-surface-container-lowest dark:bg-zinc-800
                               border border-outline-variant dark:border-zinc-600
                               hover:bg-surface-container-low dark:hover:bg-zinc-700
                               focus:outline-none focus:ring-2 focus:ring-primary
                               transition-colors duration-150">
                        Cancel
                    </button>
                @else
                    <button type="button" wire:click="prevStep"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-md
                               text-on-surface-variant dark:text-zinc-300
                               bg-surface-container-lowest dark:bg-zinc-800
                               border border-outline-variant dark:border-zinc-600
                               hover:bg-surface-container-low dark:hover:bg-zinc-700
                               focus:outline-none focus:ring-2 focus:ring-primary
                               transition-colors duration-150">
                        <flux:icon.arrow-left class="size-3.5" />
                        Back
                    </button>
                @endif
            </div>

            {{-- Right: Next or Finish --}}
            <div class="flex items-center gap-2">
                @if ($step === 1)
                    <button type="button" wire:click="nextStep" wire:loading.attr="disabled"
                        wire:target="nextStep"
                        class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium
                               text-on-primary rounded-md
                               bg-gradient-to-r from-primary to-primary-container
                               hover:from-primary-container hover:to-primary
                               focus:outline-none focus:ring-2 focus:ring-primary
                               transition-all duration-200
                               disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="nextStep">
                            Next: Budget
                            <flux:icon.arrow-right class="size-3.5 inline-block ml-0.5" />
                        </span>
                        <span wire:loading.flex wire:target="nextStep" class="items-center gap-1.5">
                            <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Saving...
                        </span>
                    </button>
                @else
                    <button type="button" wire:click="finish" wire:loading.attr="disabled" wire:target="finish"
                        @disabled($budgetItems->isEmpty() || $budgetRemaining < 0)
                        class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium
                               text-on-primary rounded-md
                               bg-gradient-to-r from-primary to-primary-container
                               hover:from-primary-container hover:to-primary
                               focus:outline-none focus:ring-2 focus:ring-primary
                               transition-all duration-200
                               disabled:opacity-50 disabled:cursor-not-allowed
                               disabled:hover:from-primary disabled:hover:to-primary-container">
                        <flux:icon.check class="size-3.5" />
                        Finish
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
