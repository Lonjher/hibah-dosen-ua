@props([
    'title' => '',
    'leading' => '',
    'icon' => '',
])

<div class="space-y-1">
    <div class="flex items-center gap-2.5">
        <div
            class="flex items-center justify-center w-9 h-9 rounded-full bg-linear-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/20">
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
