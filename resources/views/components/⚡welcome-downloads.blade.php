<?php


use App\Models\Download;
use Livewire\Component;

new class extends Component
{
    /**
     * Mode tampilan: 'grid' atau 'table'.
     * Disimpan di session agar bertahan saat refresh.
     */
    public string $viewMode = 'grid';

    public function mount(): void
    {
        $this->viewMode = session()->get('welcome_downloads_view', 'grid');
    }

    /**
     * Ganti mode tampilan.
     */
    public function setView(string $mode): void
    {
        if (!in_array($mode, ['grid', 'table'], true)) {
            return;
        }

        $this->viewMode = $mode;
        session()->put('welcome_downloads_view', $mode);
    }

    /**
     * Increment download counter.
     */
    public function trackDownload(int $id): void
    {
        $download = Download::active()->find($id);

        if ($download) {
            $download->increment('download_count');
        }
    }

    /**
     * Map kategori → class warna Tailwind.
     */
    public function colorClasses(string $category): string
    {
        return match ($category) {
            'guideline' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/40 dark:text-sky-400',
            'template'  => 'bg-violet-50 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400',
            'form'      => 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
            default     => 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        };
    }

    /**
     * Map kategori → SVG path.
     */
    public function iconPath(string $category): string
    {
        return match ($category) {
            'guideline' => 'M12 6.5c-2-1.5-4.5-2-7-2v13c2.5 0 5 .5 7 2 2-1.5 4.5-2 7-2v-13c-2.5 0-5 .5-7 2Z M12 6.5v13',
            'template'  => 'M8 3.5h7l4 4V17a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 7 17V5A1.5 1.5 0 0 1 8.5 3.5Z M15 3.5V8h4.5',
            'form'      => 'M9 3.5h6a1 1 0 0 1 1 1V6h1.5A1.5 1.5 0 0 1 19 7.5v12A1.5 1.5 0 0 1 17.5 21h-11A1.5 1.5 0 0 1 5 19.5v-12A1.5 1.5 0 0 1 6.5 6H8V4.5a1 1 0 0 1 1-1Z M9 10h6 M9 14h4',
            default     => 'M7 3.5h7l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 19V5A1.5 1.5 0 0 1 7 3.5Z M14 3.5V8h4.5',
        };
    }

    public function with()
    {
        $documents = Download::query()
            ->onWelcome()
            ->ordered()
            ->get();

        return [
            'documents' => $documents,
        ];
    }
}
?>
<section id="unduhan"
    wire:key="welcome-downloads-section"
    class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 scroll-mt-24">

    {{-- ============ HEADER ROW: Section title + view toggle ============ --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
        <x-section-header
            label="Official Quality Documents"
            title="Download Center for Proposal Formats & Internal Grant Guidelines"
            description="Use official template files verified by LPPM Universitas Annuqayah to smooth your submission administration."
            icon="M4 6.5h16v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z M8 9.5l2-2 3 3 4-4"
            class="flex-1" />

        {{-- View toggle (hanya muncul kalau ada data) --}}
        @if (!$documents->isEmpty())
            <div class="shrink-0 inline-flex items-center gap-1 p-1 rounded-full
                        bg-white/75 backdrop-blur-lg border border-white/90 shadow-sm
                        dark:bg-zinc-900/75 dark:border-zinc-800">

                {{-- Grid button --}}
                <button type="button"
                    wire:click="setView('grid')"
                    title="{{ __('Grid view') }}"
                    aria-label="{{ __('Grid view') }}"
                    @class([
                        'cursor-pointer group relative flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-300',
                        'bg-emerald-700 text-white shadow-sm' => $viewMode === 'grid',
                        'text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-800' => $viewMode !== 'grid',
                    ])>
                    <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                        <rect x="4" y="4" width="7" height="7" rx="1.5"
                            stroke="currentColor" stroke-width="1.5" />
                        <rect x="13" y="4" width="7" height="7" rx="1.5"
                            stroke="currentColor" stroke-width="1.5" />
                        <rect x="4" y="13" width="7" height="7" rx="1.5"
                            stroke="currentColor" stroke-width="1.5" />
                        <rect x="13" y="13" width="7" height="7" rx="1.5"
                            stroke="currentColor" stroke-width="1.5" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Grid') }}</span>
                </button>

                {{-- Table button --}}
                <button type="button"
                    wire:click="setView('table')"
                    title="{{ __('Table view') }}"
                    aria-label="{{ __('Table view') }}"
                    @class([
                        'group relative flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-300',
                        'bg-emerald-700 text-white shadow-sm' => $viewMode === 'table',
                        'text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-800' => $viewMode !== 'table',
                    ])>
                    <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                        <rect x="4" y="5" width="16" height="3" rx="1.2"
                            stroke="currentColor" stroke-width="1.5" />
                        <rect x="4" y="10.5" width="16" height="3" rx="1.2"
                            stroke="currentColor" stroke-width="1.5" />
                        <rect x="4" y="16" width="16" height="3" rx="1.2"
                            stroke="currentColor" stroke-width="1.5" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Table') }}</span>
                </button>
            </div>
        @endif
    </div>

    {{-- ============ EMPTY STATE ============ --}}
    @if ($documents->isEmpty())
        <div class="reveal-up p-8 rounded-2xl bg-white/60 dark:bg-zinc-900/60 backdrop-blur-lg
                    border border-dashed border-slate-200 dark:border-zinc-800
                    flex flex-col items-center justify-center gap-2 text-center">
            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-zinc-800
                        flex items-center justify-center text-slate-400 dark:text-zinc-500">
                <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5">
                    <path d="M7 3.5h7l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 19V5A1.5 1.5 0 0 1 7 3.5Z M14 3.5V8h4.5"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="text-slate-500 dark:text-zinc-400 text-xs font-medium">
                {{ __('No documents published yet.') }}
            </p>
            <p class="text-slate-400 dark:text-zinc-500 text-[10px]">
                {{ __('Please check back soon or contact LPPM for assistance.') }}
            </p>
        </div>

    {{-- ============ GRID VIEW ============ --}}
    @elseif ($viewMode === 'grid')
        <div wire:key="view-grid"
             class="grid grid-cols-1 md:grid-cols-3 gap-3.5
                    transition-opacity duration-300">
            @foreach ($documents as $i => $doc)
                <div wire:key="download-grid-{{ $doc->id }}"
                    class="reveal-up group p-3.5 rounded-2xl bg-white/75 backdrop-blur-lg
                        border border-white/80 shadow-sm
                        hover:bg-white hover:border-emerald-300 hover:-translate-y-1 hover:shadow-lg
                        dark:bg-zinc-900/75 dark:border-zinc-800 dark:hover:border-emerald-700
                        transition-all duration-300 flex items-center justify-between"
                    style="transition-delay: {{ $i * 90 }}ms">

                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full {{ $this->colorClasses($doc->category) }}
                                    flex items-center justify-center shrink-0
                                    transition-transform duration-500 group-hover:scale-110 group-hover:rotate-3">
                            <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                <path d="{{ $this->iconPath($doc->category) }}"
                                    stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <h4 class="text-slate-900 dark:text-white font-bold truncate text-xs"
                                title="{{ $doc->title }}">
                                {{ $doc->title }}
                            </h4>
                            <span class="text-slate-400 dark:text-zinc-500 font-normal truncate text-[10px]">
                                {{ $doc->file_extension }} ({{ $doc->file_size_human }})
                                &bull; {{ $doc->categoryMeta()['label'] }}
                                &bull; {{ $doc->download_count }}&times; {{ __('downloaded') }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ $doc->public_url }}"
                        wire:click="trackDownload({{ $doc->id }})"
                        target="_blank" rel="noopener"
                        title="{{ __('Download') }} — {{ $doc->title }}"
                        class="w-8 h-8 rounded-full bg-slate-100 hover:bg-emerald-700 hover:text-white
                            hover:scale-110 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300
                            flex items-center justify-center shrink-0 transition-all duration-300">
                        <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                            <path d="M4 16v3.5A1.5 1.5 0 0 0 5.5 21h13a1.5 1.5 0 0 0 1.5-1.5V16M12 3v11m0 0 4-4m-4 4L8 10"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </div>
            @endforeach
        </div>

    {{-- ============ TABLE VIEW ============ --}}
    @else
        <div wire:key="view-table"
             class="reveal-up rounded-2xl bg-white/75 backdrop-blur-lg
                    border border-white/80 shadow-sm overflow-hidden
                    dark:bg-zinc-900/75 dark:border-zinc-800">

            {{-- Desktop table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-zinc-950/50
                                   border-b border-slate-100 dark:border-zinc-800">
                            <th class="text-left font-semibold text-slate-500 dark:text-zinc-400
                                       px-4 py-2.5 text-[10px] uppercase tracking-wider w-10">#</th>
                            <th class="text-left font-semibold text-slate-500 dark:text-zinc-400
                                       px-3 py-2.5 text-[10px] uppercase tracking-wider">{{ __('Document') }}</th>
                            <th class="text-left font-semibold text-slate-500 dark:text-zinc-400
                                       px-3 py-2.5 text-[10px] uppercase tracking-wider hidden lg:table-cell">{{ __('Category') }}</th>
                            <th class="text-left font-semibold text-slate-500 dark:text-zinc-400
                                       px-3 py-2.5 text-[10px] uppercase tracking-wider hidden lg:table-cell">{{ __('Format') }}</th>
                            <th class="text-left font-semibold text-slate-500 dark:text-zinc-400
                                       px-3 py-2.5 text-[10px] uppercase tracking-wider hidden xl:table-cell">{{ __('Downloads') }}</th>
                            <th class="text-right font-semibold text-slate-500 dark:text-zinc-400
                                       px-4 py-2.5 text-[10px] uppercase tracking-wider w-24">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                        @foreach ($documents as $i => $doc)
                            <tr wire:key="download-row-{{ $doc->id }}"
                                class="group transition-colors duration-200
                                       hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10">
                                <td class="px-4 py-3 text-slate-400 dark:text-zinc-500 font-mono text-[10px]">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-full {{ $this->colorClasses($doc->category) }}
                                                    flex items-center justify-center shrink-0
                                                    transition-transform duration-500
                                                    group-hover:scale-110 group-hover:rotate-3">
                                            <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                                <path d="{{ $this->iconPath($doc->category) }}"
                                                    stroke="currentColor" stroke-width="1.5"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-slate-900 dark:text-white font-semibold truncate"
                                                  title="{{ $doc->title }}">
                                                {{ $doc->title }}
                                            </span>
                                            @if ($doc->description)
                                                <span class="text-slate-400 dark:text-zinc-500 text-[10px] truncate"
                                                      title="{{ $doc->description }}">
                                                    {{ \Illuminate\Support\Str::limit($doc->description, 80) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 hidden lg:table-cell">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                                 text-[10px] font-semibold
                                                 {{ $this->colorClasses($doc->category) }}">
                                        {{ $doc->categoryMeta()['label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 hidden lg:table-cell">
                                    <span class="text-slate-600 dark:text-zinc-300 font-mono text-[10px]">
                                        {{ $doc->file_extension }}
                                    </span>
                                    <span class="text-slate-400 dark:text-zinc-500 text-[10px]">
                                        ({{ $doc->file_size_human }})
                                    </span>
                                </td>
                                <td class="px-3 py-3 hidden xl:table-cell">
                                    <span class="text-slate-600 dark:text-zinc-300 font-medium text-[11px]">
                                        {{ number_format($doc->download_count) }}&times;
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ $doc->public_url }}"
                                        wire:click="trackDownload({{ $doc->id }})"
                                        target="_blank" rel="noopener"
                                        title="{{ __('Download') }} — {{ $doc->title }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                                            bg-slate-100 hover:bg-emerald-700 hover:text-white
                                            text-slate-600 dark:bg-zinc-800 dark:text-zinc-300
                                            font-medium text-[10px]
                                            transition-all duration-300
                                            hover:-translate-y-0.5 hover:shadow-md">
                                        <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3">
                                            <path d="M4 16v3.5A1.5 1.5 0 0 0 5.5 21h13a1.5 1.5 0 0 0 1.5-1.5V16M12 3v11m0 0 4-4m-4 4L8 10"
                                                stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span class="hidden sm:inline">{{ __('Download') }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: card-style list --}}
            <div class="md:hidden divide-y divide-slate-100 dark:divide-zinc-800">
                @foreach ($documents as $i => $doc)
                    <div wire:key="download-mobile-{{ $doc->id }}"
                         class="p-3.5 flex items-center justify-between gap-3
                                hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10
                                transition-colors duration-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full {{ $this->colorClasses($doc->category) }}
                                        flex items-center justify-center shrink-0">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="{{ $this->iconPath($doc->category) }}"
                                        stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-slate-900 dark:text-white font-bold truncate text-xs">
                                    {{ $doc->title }}
                                </span>
                                <span class="text-slate-400 dark:text-zinc-500 text-[10px] truncate">
                                    {{ $doc->file_extension }} ({{ $doc->file_size_human }})
                                    &bull; {{ $doc->categoryMeta()['label'] }}
                                    &bull; {{ $doc->download_count }}&times;
                                </span>
                            </div>
                        </div>
                        <a href="{{ $doc->public_url }}"
                            wire:click="trackDownload({{ $doc->id }})"
                            target="_blank" rel="noopener"
                            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-emerald-700 hover:text-white
                                text-slate-600 dark:bg-zinc-800 dark:text-zinc-300
                                flex items-center justify-center shrink-0 transition-all duration-300">
                            <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5">
                                <path d="M4 16v3.5A1.5 1.5 0 0 0 5.5 21h13a1.5 1.5 0 0 0 1.5-1.5V16M12 3v11m0 0 4-4m-4 4L8 10"
                                    stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
