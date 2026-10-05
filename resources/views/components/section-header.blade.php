@props([
    'label',
    'title',
    'description' => null,
    'icon' => null,
])

<div class="group mb-5">
    <div class="flex items-center gap-3 mb-3">

        {{-- Icon + Label --}}
        <div
            class="inline-flex items-center shrink-0
                   rounded-full
                   bg-emerald-50 dark:bg-emerald-500/10
                   ring-1 ring-emerald-500/10
                   shadow-sm
                   transition-all duration-300 ease-out
                   group-hover:-translate-y-0.5
                   group-hover:shadow-md
                   group-hover:ring-emerald-500/25"
        >
            @if ($icon)
                <span
                    class="flex items-center justify-center
                           w-8 h-8
                           rounded-full
                           bg-white dark:bg-zinc-900
                           text-emerald-600 dark:text-emerald-400
                           shadow-sm
                           transition-all duration-300 ease-out
                           group-hover:scale-110
                           group-hover:rotate-3
                           group-hover:text-emerald-500"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        class="w-4 h-4 transition-transform duration-300"
                    >
                        <path
                            d="{{ $icon }}"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
            @endif

            <span
                class="px-3 pr-4
                       text-[10px]
                       font-bold
                       uppercase
                       tracking-[0.12em]
                       text-emerald-700 dark:text-emerald-400
                       transition-all duration-300 ease-out
                       group-hover:tracking-[0.15em]"
            >
                {{ $label }}
            </span>
        </div>

        {{-- Decorative Line --}}
        <span
            class="h-px flex-1 min-w-8
                   bg-gradient-to-r
                   from-emerald-500/20
                   to-transparent
                   transition-all duration-500 ease-out
                   group-hover:from-emerald-500/45
                   group-hover:via-emerald-500/15"
        ></span>
    </div>

    {{-- Title --}}
    <h2
        class="font-heading
               text-slate-900 dark:text-white
               font-bold
               text-base
               leading-[1.4]
               transition-colors duration-300
               group-hover:text-emerald-800
               dark:group-hover:text-emerald-300"
    >
        {{ $title }}
    </h2>

    {{-- Description --}}
    @if ($description)
        <p
            class="text-slate-500 dark:text-zinc-400
                   font-normal
                   mt-0.5
                   text-xs
                   transition-colors duration-300
                   group-hover:text-slate-600
                   dark:group-hover:text-zinc-300"
        >
            {{ $description }}
        </p>
    @endif
</div>
