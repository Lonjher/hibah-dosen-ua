<?php

use App\Models\ProgressReport;
use App\Models\ReviewerNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Review Progress Reports')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all'; // all | research | dedication

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }

    public function isFinalized(ProgressReport $report): bool
    {
        return in_array($report->status, ['accepted', 'rejected']);
    }

    public function hasReviewerNotes(ProgressReport $report): bool
    {
        return ReviewerNote::where('noteable_id', $report->id)
            ->where('noteable_type', 'progress_report')
            ->where('reviewer_id', Auth::id())
            ->exists();
    }

    public function with(): array
    {
        $reports = ProgressReport::query()
            ->with(['proposal.researchScheme', 'proposal.author', 'proposal.period', 'reviewer'])
            ->where('reviewer_id', Auth::id())
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('keyword', 'like', "%{$this->search}%")
                  ->orWhere('summary', 'like', "%{$this->search}%")
                  ->orWhereHas('proposal', function ($q) {
                      $q->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('author', fn ($q) => $q->where('full_name', 'like', "%{$this->search}%"));
                  });
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter === 'research', fn ($q) => $q->whereHas('proposal', fn ($q) => $q->where('is_research', true)))
            ->when($this->typeFilter === 'dedication', fn ($q) => $q->whereHas('proposal', fn ($q) => $q->where('is_research', false)))
            ->latest()
            ->paginate(10);

        return ['reports' => $reports];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    <x-dashboard-header icon="document-chart-bar" title="Review Progress Reports"
        leading="Review progress reports assigned to you." />

    {{-- ══════════ Table Card ══════════ --}}
    <div class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
                border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between
                    gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400 min-w-0">
                @if ($reports->total() > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-violet-50 text-violet-700 font-semibold
                                 dark:bg-violet-900/30 dark:text-violet-300 shrink-0">
                        <flux:icon.list-bullet class="size-3" />
                        {{ $reports->total() }} {{ Str::plural('report', $reports->total()) }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-slate-100 text-slate-500 font-semibold
                                 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.list-bullet class="size-3" />
                        No reports assigned
                    </span>
                @endif
            </div>

            {{-- Filter + Search --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">

                {{-- Type Filter --}}
                <select wire:model.live="typeFilter"
                    class="text-[11px] rounded-xl border-slate-200 dark:border-zinc-700
                           bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200
                           focus:border-violet-500 focus:ring-violet-500 py-2 px-3 shrink-0">
                    <option value="all">All Types</option>
                    <option value="research">Penelitian</option>
                    <option value="dedication">Pengabdian</option>
                </select>

                {{-- Status Filter --}}
                <select wire:model.live="statusFilter"
                    class="text-[11px] rounded-xl border-slate-200 dark:border-zinc-700
                           bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200
                           focus:border-violet-500 focus:ring-violet-500 py-2 px-3 shrink-0">
                    <option value="all">All Status</option>
                    <option value="under_review">Under Review</option>
                    <option value="revised">Needs Revision</option>
                    <option value="accepted">Accepted</option>
                </select>

                <x-input-search name="search" id="search-reviewer-progress"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search keyword, proposal, or author..."
                    max-width="max-w-sm" class="!flex w-full sm:w-auto flex-1" />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-xs">
                <thead class="bg-violet-50/50 dark:bg-violet-900/20 text-left text-[10px]
                              uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Keyword & Proposal</th>
                        <th class="px-4 py-3 font-semibold">Author</th>
                        <th class="px-4 py-3 font-semibold">Submitted</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse ($reports as $report)
                        @php
                            $meta = $report->statusMeta();
                            $hasNotes = $this->hasReviewerNotes($report);
                            $isResearch = (bool) $report->proposal?->is_research;
                        @endphp
                        <tr wire:key="rpt-{{ $report->id }}"
                            class="group hover:bg-violet-50/30 dark:hover:bg-zinc-800/50 transition-colors">

                            {{-- Keyword & Proposal --}}
                            <td class="px-4 py-3 max-w-md">
                                <div class="flex flex-row gap-2 min-w-0">

                                    {{-- Icon --}}
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0
                                                group-hover:scale-105 transition-transform
                                                {{ $isResearch
                                                    ? 'bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/20'
                                                    : 'bg-gradient-to-br from-rose-100 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20' }}">
                                        <flux:icon :name="$isResearch ? 'beaker' : 'heart'"
                                            class="size-3.5 {{ $isResearch
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : 'text-rose-600 dark:text-rose-400' }}" />
                                    </div>

                                    {{-- Content --}}
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon.tag class="size-3 text-violet-600 dark:text-violet-400 shrink-0" />
                                            <span class="font-medium text-slate-900 dark:text-zinc-100 truncate"
                                                title="{{ $report->keyword }}">
                                                {{ $report->keyword }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5 min-w-0">

                                            {{-- Badge Jenis --}}
                                            @if ($isResearch)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded shrink-0
                                                             text-[9px] font-semibold uppercase tracking-wider
                                                             bg-emerald-100 text-emerald-700
                                                             dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    <flux:icon.beaker class="size-2" />
                                                    Penelitian
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded shrink-0
                                                             text-[9px] font-semibold uppercase tracking-wider
                                                             bg-rose-100 text-rose-700
                                                             dark:bg-rose-900/30 dark:text-rose-300">
                                                    <flux:icon.heart class="size-2" />
                                                    Pengabdian
                                                </span>
                                            @endif

                                            {{-- Proposal Title --}}
                                            <span class="text-[10px] text-slate-500 dark:text-zinc-400 line-clamp-1 truncate"
                                                title="{{ $report->proposal?->title }}">
                                                {{ Str::limit($report->proposal?->title ?? '—', 50) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Author --}}
                            <td class="px-4 py-3">
                                @if ($report->proposal?->author)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700
                                                    flex items-center justify-center text-[10px] font-bold shrink-0
                                                    dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ strtoupper(substr($report->proposal->author->full_name, 0, 1)) }}
                                        </div>
                                        <span class="truncate max-w-[140px] text-slate-700 dark:text-zinc-300">
                                            {{ $report->proposal->author->full_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 italic">—</span>
                                @endif
                            </td>

                            {{-- Submitted --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300 whitespace-nowrap font-mono text-[11px]">
                                {{ $report->created_at?->format('d M Y, H:i') ?? '—' }}
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                             text-[11px] font-semibold whitespace-nowrap {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100
                                               dark:text-zinc-400 dark:hover:bg-zinc-700/60" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye"
                                            x-data
                                            x-on:click="$dispatch('open-view-progress-report', { id: {{ $report->id }} })">
                                            View & Review
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 rounded-full
                                                bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                        <flux:icon.document-chart-bar
                                            class="size-5 text-slate-400 dark:text-zinc-500" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-medium text-slate-700 dark:text-zinc-300">
                                            No progress reports assigned to you
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-zinc-400">
                                            @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all')
                                                Try adjusting your search or filters.
                                            @else
                                                You'll see progress reports here once the admin assigns them.
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    {{-- ══════════ MODAL ══════════ --}}
    <livewire:reviewers.researches.modals.view-progress-report />
</div>
