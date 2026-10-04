@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between gap-2">

        {{-- ══════════ MOBILE ══════════ --}}
        <div class="flex items-center justify-between w-full sm:hidden">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full
                           text-[10.5px] font-medium
                           text-slate-400 dark:text-zinc-600
                           bg-slate-50 dark:bg-zinc-800/50
                           border border-slate-200 dark:border-zinc-700/60
                           cursor-not-allowed">
                    <flux:icon.chevron-left class="size-3" />
                    Prev
                </span>
            @else
                <button type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    aria-label="{{ __('pagination.previous') }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full
                           text-[10.5px] font-medium
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 hover:border-emerald-300 hover:text-emerald-600
                           dark:hover:bg-zinc-700 dark:hover:border-emerald-700 dark:hover:text-emerald-400
                           disabled:opacity-50 disabled:cursor-wait
                           transition-colors">
                    <flux:icon.chevron-left class="size-3" />
                    Prev
                </button>
            @endif

            {{-- Page indicator --}}
            <div class="flex items-center gap-1 text-[10.5px]">
                <span class="font-semibold text-slate-900 dark:text-zinc-100">
                    {{ $paginator->currentPage() }}
                </span>
                <span class="text-slate-400 dark:text-zinc-600">/</span>
                <span class="text-slate-500 dark:text-zinc-400">
                    {{ $paginator->lastPage() }}
                </span>
            </div>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <button type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    aria-label="{{ __('pagination.next') }}"
                    class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full
                           text-[10.5px] font-medium
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 hover:border-emerald-300 hover:text-emerald-600
                           dark:hover:bg-zinc-700 dark:hover:border-emerald-700 dark:hover:text-emerald-400
                           disabled:opacity-50 disabled:cursor-wait
                           transition-colors">
                    Next
                    <flux:icon.chevron-right class="size-3" />
                </button>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full
                           text-[10.5px] font-medium
                           text-slate-400 dark:text-zinc-600
                           bg-slate-50 dark:bg-zinc-800/50
                           border border-slate-200 dark:border-zinc-700/60
                           cursor-not-allowed">
                    Next
                    <flux:icon.chevron-right class="size-3" />
                </span>
            @endif
        </div>

        {{-- ══════════ DESKTOP ══════════ --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-3">

            {{-- Left: results info --}}
            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 whitespace-nowrap">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-semibold text-slate-700 dark:text-zinc-200">
                        {{ $paginator->firstItem() }}
                    </span>
                    {!! __('to') !!}
                    <span class="font-semibold text-slate-700 dark:text-zinc-200">
                        {{ $paginator->lastItem() }}
                    </span>
                @else
                    <span class="font-semibold text-slate-700 dark:text-zinc-200">
                        {{ $paginator->count() }}
                    </span>
                @endif
                {!! __('of') !!}
                <span class="font-semibold text-slate-700 dark:text-zinc-200">
                    {{ $paginator->total() }}
                </span>
                {!! __('results') !!}
            </p>

            {{-- Right: page links --}}
            <div class="inline-flex items-center gap-1">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                        class="inline-flex items-center justify-center
                               w-7 h-7 rounded-full
                               text-slate-300 dark:text-zinc-600
                               bg-slate-50 dark:bg-zinc-800/50
                               border border-slate-200 dark:border-zinc-700/60
                               cursor-not-allowed">
                        <flux:icon.chevron-left class="size-3" />
                    </span>
                @else
                    <button type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('pagination.previous') }}"
                        class="cursor-pointer inline-flex items-center justify-center
                               w-7 h-7 rounded-full
                               text-slate-500 dark:text-zinc-400
                               bg-white dark:bg-zinc-800
                               border border-slate-200 dark:border-zinc-700/60
                               hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-300
                               dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400
                               dark:hover:border-emerald-800/60
                               disabled:opacity-50 disabled:cursor-wait
                               transition-colors">
                        <flux:icon.chevron-left class="size-3" />
                    </button>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    {{-- "..." separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true"
                            class="inline-flex items-center justify-center
                                   w-7 h-7 rounded-full
                                   text-[10.5px] font-medium
                                   text-slate-400 dark:text-zinc-600
                                   bg-slate-50 dark:bg-zinc-800/30
                                   border border-transparent
                                   cursor-default">
                            {{ $element }}
                        </span>
                    @endif

                    {{-- Page numbers --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                    class="inline-flex items-center justify-center
                                           min-w-7 h-7 px-2 rounded-full
                                           text-[10.5px] font-bold
                                           text-white
                                           bg-emerald-600
                                           border border-emerald-600
                                           shadow-sm shadow-emerald-500/20
                                           cursor-default">
                                    {{ $page }}
                                </span>
                            @else
                                <button type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    wire:loading.attr="disabled"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    class="cursor-pointer inline-flex items-center justify-center
                                           min-w-7 h-7 px-2 rounded-full
                                           text-[10.5px] font-medium
                                           text-slate-600 dark:text-zinc-400
                                           bg-white dark:bg-zinc-800
                                           border border-slate-200 dark:border-zinc-700/60
                                           hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-300
                                           dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400
                                           dark:hover:border-emerald-800/60
                                           disabled:opacity-50 disabled:cursor-wait
                                           transition-colors">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('pagination.next') }}"
                        class="cursor-pointer inline-flex items-center justify-center
                               w-7 h-7 rounded-full
                               text-slate-500 dark:text-zinc-400
                               bg-white dark:bg-zinc-800
                               border border-slate-200 dark:border-zinc-700/60
                               hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-300
                               dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400
                               dark:hover:border-emerald-800/60
                               disabled:opacity-50 disabled:cursor-wait
                               transition-colors">
                        <flux:icon.chevron-right class="size-3" />
                    </button>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                        class="inline-flex items-center justify-center
                               w-7 h-7 rounded-full
                               text-slate-300 dark:text-zinc-600
                               bg-slate-50 dark:bg-zinc-800/50
                               border border-slate-200 dark:border-zinc-700/60
                               cursor-not-allowed">
                        <flux:icon.chevron-right class="size-3" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
