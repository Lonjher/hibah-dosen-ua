@props([
    'title' => '',
    'leading' => '',
    'icon' => ""
])
<div class="flex items-start gap-2">
    <flux:icon :name="$icon" class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-1" />
    <div>
        <h2 class="font-serif text-xl font-bold text-slate-900 dark:text-white">{{ $title }}</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $leading }}</p>
    </div>
</div>
