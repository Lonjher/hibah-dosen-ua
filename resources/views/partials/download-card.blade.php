@props([
    'dl',
    'cat',
    'variant' => 'compact',
])

<button
    type="button"
    wire:click="download({{ $dl->id }})"
    wire:key="dl-{{ $dl->id }}"
    wire:loading.attr="disabled"
    wire:target="download({{ $dl->id }})"
    class="group w-full text-left flex items-center gap-2
           rounded-md
           border border-slate-200/80 dark:border-zinc-700/60
           bg-white dark:bg-zinc-800/40
           px-2 py-1.5
           hover:border-{{ $cat['color'] }}-300 dark:hover:border-{{ $cat['color'] }}-700
           hover:bg-{{ $cat['color'] }}-50/40 dark:hover:bg-{{ $cat['color'] }}-900/10
           disabled:opacity-60 disabled:cursor-wait
           transition-colors duration-150">

    {{-- Icon --}}
    <div class="shrink-0 w-6 h-6 rounded
                bg-{{ $cat['color'] }}-100 dark:bg-{{ $cat['color'] }}-900/30
                flex items-center justify-center
                group-hover:scale-105 transition-transform">
        <flux:icon :name="$cat['icon']"
            class="size-3 text-{{ $cat['color'] }}-600 dark:text-{{ $cat['color'] }}-400" />
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <p class="text-[10.5px] font-semibold leading-tight truncate
                  text-slate-900 dark:text-zinc-100
                  group-hover:text-{{ $cat['color'] }}-700 dark:group-hover:text-{{ $cat['color'] }}-300
                  transition-colors">
            {{ $dl->title }}
        </p>

        @if ($variant === 'full' && $dl->description)
            <p class="mt-0.5 text-[9.5px] leading-snug
                      text-slate-500 dark:text-zinc-400
                      line-clamp-1">
                {{ $dl->description }}
            </p>
        @endif

        {{-- Meta row --}}
        <div class="mt-0.5 flex items-center gap-1 flex-wrap
                    text-[9px] text-slate-500 dark:text-zinc-500">
            <span class="px-1 rounded
                         bg-slate-100 dark:bg-zinc-800
                         text-slate-600 dark:text-zinc-400
                         font-semibold">
                {{ $dl->file_extension }}
            </span>
            <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
            <span>{{ $dl->file_size_human }}</span>
            @if ($dl->download_count > 0)
                <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                <span>{{ $dl->download_count }}×</span>
            @endif
        </div>
    </div>

    {{-- Action --}}
    <div class="shrink-0">
        <flux:icon.arrow-down-tray
            wire:loading.remove
            wire:target="download({{ $dl->id }})"
            class="size-3 text-slate-300 dark:text-zinc-600
                   group-hover:text-{{ $cat['color'] }}-500 dark:group-hover:text-{{ $cat['color'] }}-400
                   group-hover:translate-y-0.5
                   transition-all duration-150" />

        <svg wire:loading
             wire:target="download({{ $dl->id }})"
             class="size-3 animate-spin text-{{ $cat['color'] }}-500"
             viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
        </svg>
    </div>
</button>
