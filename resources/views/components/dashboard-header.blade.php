@props([
    'title' => '',
    'leading' => '',
    'icon' => '',
])

{{-- <div class="inline-flex items-start gap-3
            bg-white/70 dark:bg-zinc-900/70
            backdrop-blur-xl
            rounded-2xl
            border border-white/80 dark:border-zinc-800
            shadow-sm shadow-slate-900/5 dark:shadow-black/20
            px-4 py-3
            w-full">
    <div class="shrink-0 w-9 h-9 rounded-xl
                bg-emerald-100 text-emerald-700
                dark:bg-emerald-900/40 dark:text-emerald-300
                flex items-center justify-center mt-0.5">
        <flux:icon :name="$icon" class="size-5" />
    </div>

    <div class="min-w-0 flex-1">
        <h2 class="font-heading text-base sm:text-lg font-bold leading-tight
                   text-slate-900 dark:text-zinc-100
                   truncate">
            {{ $title }}
        </h2>
        <p class="text-[11px] sm:text-xs leading-snug mt-0.5
                  text-slate-500 dark:text-zinc-400">
            {{ $leading }}
        </p>
    </div>
</div> --}}

<div class="space-y-1">
    <div class="flex items-center gap-2.5">
        <div
            class="flex items-center justify-center w-9 h-9 rounded-xl bg-linear-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/20">
            <flux:icon :name="$icon" class="size-5 text-white" />
        </div>
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
                {{ $title }}
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5">
                {{ $leading }}
            </p>
        </div>
    </div>
</div>
