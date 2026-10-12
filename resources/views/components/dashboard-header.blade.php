@props([
    'title'   => '',
    'leading' => '',
    'icon'    => '',
    'color'   => 'emerald', // emerald | rose | violet | sky | amber | indigo | blue | teal
])

@php
    // Map color → gradient + shadow classes (static, biar Tailwind compile)
    $colorClasses = match ($color) {
        'rose'    => 'bg-linear-to-br from-rose-500 to-pink-600 shadow-lg shadow-rose-500/20',
        'violet'  => 'bg-linear-to-br from-violet-500 to-purple-600 shadow-lg shadow-violet-500/20',
        'sky'     => 'bg-linear-to-br from-sky-500 to-cyan-600 shadow-lg shadow-sky-500/20',
        'amber'   => 'bg-linear-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/20',
        'indigo'  => 'bg-linear-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-500/20',
        'blue'    => 'bg-linear-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/20',
        'teal'    => 'bg-linear-to-br from-teal-500 to-emerald-600 shadow-lg shadow-teal-500/20',
        default   => 'bg-linear-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/20',
    };
@endphp

<div class="space-y-1">
    <div class="flex items-center gap-2.5">
        <div class="flex items-center justify-center w-9 h-9 rounded-full {{ $colorClasses }}">
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
