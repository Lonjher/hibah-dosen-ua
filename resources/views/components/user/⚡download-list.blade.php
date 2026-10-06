<?php

use App\Models\Download;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $scope = 'dashboard';
    public string $layout = 'grouped';
    public int $limit = 0;
    public string $categoryFilter = '';
    public bool $searchable = false;
    public string $search = '';
    public bool $showHeader = true;

    public function download(int $id)
    {
        $dl = Download::find($id);

        if (! $dl) {
            Flux::toast('File not found.', variant: 'danger');
            return;
        }

        if (! $dl->is_active) {
            Flux::toast('File is currently inactive.', variant: 'danger');
            return;
        }

        $path = storage_path('app/public/' . $dl->file_path);

        if (! file_exists($path)) {
            Flux::toast('File is not available on the server.', variant: 'danger');
            return;
        }

        $dl->incrementQuietly('download_count');

        return response()->download($path, $dl->file_name);
    }

    #[Computed]
    public function downloads()
    {
        return Download::query()
            ->with('author')
            ->when($this->scope === 'dashboard', fn ($q) => $q->onDashboard())
            ->when($this->scope === 'welcome',   fn ($q) => $q->onWelcome())
            ->when($this->scope === 'all',       fn ($q) => $q->active())
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")))
            ->ordered()
            ->when($this->limit > 0, fn ($q) => $q->limit($this->limit))
            ->get();
    }

    #[Computed]
    public function groupedDownloads()
    {
        return $this->downloads->groupBy('category');
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return Download::categoryOptions();
    }
};
?>

<div class="w-full">

    {{-- ══════════ HEADER + FILTER ══════════ --}}
    @if ($showHeader || $searchable)
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
            @if ($showHeader)
                <div class="flex items-center gap-1.5 min-w-0">
                    @if ($this->downloads->isNotEmpty())
                        <span class="inline-flex items-center justify-center
                                     min-w-[18px] h-[18px] px-1.5 rounded-full
                                     bg-violet-100 text-violet-700 text-[9px] font-bold
                                     dark:bg-violet-900/40 dark:text-violet-300 shrink-0">
                            {{ $this->downloads->count() }}
                        </span>
                    @endif
                </div>
            @endif

            @if ($searchable)
                <div class="flex items-center gap-1.5 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-44">
                        <flux:icon.magnifying-glass
                            class="absolute left-3 top-1/2 -translate-y-1/2 size-3 text-slate-400 pointer-events-none" />
                        <input type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search..."
                            class="w-full pl-8 pr-3 py-1.5 text-[10.5px] rounded-full
                                   border border-slate-200 dark:border-zinc-700
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                   hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
                                   hover:border-slate-300 dark:hover:border-zinc-600
                                   focus:outline-none
                                   focus:ring-2 focus:ring-violet-500/25 focus:border-violet-500
                                   focus:shadow-md focus:shadow-emerald-500/10
                                   transition-all duration-200" />
                    </div>
                    <select wire:model.live="categoryFilter"
                        class="text-[10.5px] font-medium rounded-full
                               border border-slate-200 dark:border-zinc-700
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               py-1.5 pl-3 pr-6 cursor-pointer
                               focus:outline-none focus:ring-2 focus:ring-violet-500/25 focus:border-violet-500
                               transition-all duration-200">
                        <option value="">All</option>
                        @foreach ($this->categoryOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════ EMPTY STATE ══════════ --}}
    @if ($this->downloads->isEmpty())
        <div class="w-full rounded-2xl border border-dashed border-slate-300 dark:border-zinc-700
                    bg-slate-50/60 dark:bg-zinc-900/40
                    px-3 py-5 text-center">
            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-zinc-800
                        flex items-center justify-center mx-auto">
                <flux:icon.arrow-down-tray class="size-5 text-slate-300 dark:text-zinc-600" />
            </div>
            <p class="mt-2 text-[10.5px] font-medium text-slate-500 dark:text-zinc-400">
                No files available yet
            </p>
            @if ($search)
                <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                    No results for "{{ $search }}"
                </p>
            @endif
        </div>
    @else

        {{-- ══════════ GROUPED (full-width rows) ══════════ --}}
        @if ($layout === 'grouped')
            <div class="w-full space-y-3">
                @foreach ($this->groupedDownloads as $category => $files)
                    @php
                        $first = $files->first();
                        $cat = $first->categoryMeta();
                    @endphp

                    <div wire:key="group-{{ $category }}" class="w-full">
                        {{-- Section header --}}
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <div class="w-5 h-5 rounded-full
                                        bg-{{ $cat['color'] }}-100 dark:bg-{{ $cat['color'] }}-900/30
                                        flex items-center justify-center shrink-0">
                                <flux:icon :name="$cat['icon']"
                                    class="size-2.5 text-{{ $cat['color'] }}-600 dark:text-{{ $cat['color'] }}-400" />
                            </div>
                            <h3 class="text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                {{ $cat['label'] }}
                            </h3>
                            <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500">
                                {{ $files->count() }}
                            </span>
                            <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-800"></div>
                        </div>

                        {{-- Items --}}
                        <div class="w-full space-y-1">
                            @foreach ($files as $dl)
                                @include('partials.download-card', [
                                    'dl' => $dl,
                                    'cat' => $cat,
                                    'variant' => 'compact',
                                ])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

        {{-- ══════════ GRID ══════════ --}}
        @elseif ($layout === 'grid')
            <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach ($this->downloads as $dl)
                    @php $cat = $dl->categoryMeta(); @endphp
                    @include('partials.download-card', [
                        'dl' => $dl,
                        'cat' => $cat,
                        'variant' => 'full',
                    ])
                @endforeach
            </div>

        {{-- ══════════ LIST ══════════ --}}
        @else
            <div class="w-full rounded-2xl border border-slate-200 dark:border-zinc-800
                        overflow-hidden
                        divide-y divide-slate-100 dark:divide-zinc-800/70">
                @foreach ($this->downloads as $dl)
                    @php $cat = $dl->categoryMeta(); @endphp
                    <button
                        type="button"
                        wire:click="download({{ $dl->id }})"
                        wire:key="dl-{{ $dl->id }}"
                        wire:loading.attr="disabled"
                        wire:target="download({{ $dl->id }})"
                        class="group w-full text-left flex items-center gap-2 pl-1.5 pr-2 py-1.5
                               bg-white dark:bg-zinc-900
                               hover:bg-slate-50 dark:hover:bg-zinc-800/50
                               disabled:opacity-60 disabled:cursor-wait
                               transition-colors">

                        <div class="w-7 h-7 rounded-full shrink-0
                                    bg-{{ $cat['color'] }}-100 dark:bg-{{ $cat['color'] }}-900/30
                                    flex items-center justify-center
                                    group-hover:scale-105 transition-transform duration-200">
                            <flux:icon :name="$cat['icon']"
                                class="size-3 text-{{ $cat['color'] }}-600 dark:text-{{ $cat['color'] }}-400" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-[10.5px] font-semibold text-slate-900 dark:text-zinc-100 truncate
                                      group-hover:text-violet-600 dark:group-hover:text-violet-400
                                      transition-colors">
                                {{ $dl->title }}
                            </p>
                            <div class="mt-0.5 flex items-center gap-1.5 text-[9px]
                                        text-slate-500 dark:text-zinc-500">
                                <span>{{ $cat['label'] }}</span>
                                <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                <span class="px-1.5 py-0.5 rounded-full
                                             bg-slate-100 dark:bg-zinc-800 font-semibold">
                                    {{ $dl->file_extension }}
                                </span>
                                <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                <span>{{ $dl->file_size_human }}</span>
                                @if ($dl->download_count > 0)
                                    <span class="w-0.5 h-0.5 rounded-full bg-current"></span>
                                    <span>{{ $dl->download_count }}×</span>
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0 w-6 h-6 rounded-full
                                    flex items-center justify-center
                                    group-hover:bg-violet-50 dark:group-hover:bg-violet-900/30
                                    transition-colors">
                            <flux:icon.arrow-down-tray
                                wire:loading.remove
                                wire:target="download({{ $dl->id }})"
                                class="size-3 text-slate-300 dark:text-zinc-600
                                       group-hover:text-violet-500 dark:group-hover:text-violet-400
                                       transition-colors" />
                            <svg wire:loading
                                 wire:target="download({{ $dl->id }})"
                                 class="size-3 animate-spin text-violet-500"
                                 viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @endif
</div>
