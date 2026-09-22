<?php

use Livewire\Component;
use App\Models\Period;

new class extends Component {
    public function render()
    {
        $period = Period::where('is_active', 1)->first();
        return $this->view([
            'period' => $period
        ]);
    }
};
?>

<div class="hidden md:flex items-center gap-1 px-2 py-0.5 rounded-md shadow-xs {{ $period ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-rose-100 dark:bg-rose-900/50' }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $period ? 'bg-emerald-500 dark:bg-emerald-400 animate-pulse' : 'bg-rose-500 dark:bg-rose-400 animate-pulse' }}"></span>
    <span class="font-label-sm text-[10px] {{ $period ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
        {{ $period ? 'Period ' . $period->periode : 'No Active Period' }}
    </span>
</div>
