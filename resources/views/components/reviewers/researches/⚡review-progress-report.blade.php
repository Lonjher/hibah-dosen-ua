<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProgressReport;
use App\Models\ProgressReportNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    // ── List ──
    public string $search = '';
    public string $statusFilter = 'under_review'; // all | under_review | needs_revision | approved

    // ── Review modal ──
    public bool $showReviewModal = false;
    public ?int $reviewingId = null;

    // ── Review form ──
    public string $decision = 'approve'; // approve | revise
    public string $reviewerComment = '';
    public string $reviewerRecommendation = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ═══════════════ Review modal ═══════════════
    public function openReview(int $id): void
    {
        $report = ProgressReport::with(['proposal.researchScheme', 'proposal.author', 'notes.reviewer'])
            ->where('reviewer_id', Auth::id())
            ->findOrFail($id);

        $this->reviewingId = $report->id;

        // Default ke approve; kalau terakhir rejected, default revise
        $this->decision = $report->isRejected() ? 'revise' : 'approve';

        // Kosongkan — reviewer selalu tulis catatan baru (bukan edit yang lama)
        $this->reviewerComment = '';
        $this->reviewerRecommendation = '';

        $this->resetErrorBag();
        $this->resetValidation();
        $this->showReviewModal = true;
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->reset([
            'reviewingId', 'reviewerComment', 'reviewerRecommendation',
        ]);
        $this->decision = 'approve';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function submitReview(): void
    {
        $this->validate([
            'decision' => ['required', 'in:approve,revise'],
            'reviewerComment' => ['required', 'string', 'min:10', 'max:2000'],
            'reviewerRecommendation' => ['nullable', 'string', 'max:2000'],
        ], [
            'decision.required' => 'Please choose a decision.',
            'reviewerComment.required' => 'Please write a review comment.',
            'reviewerComment.min' => 'Comment must be at least 10 characters.',
        ]);

        if (!$this->reviewingId) {
            return;
        }

        $report = ProgressReport::where('reviewer_id', Auth::id())
            ->findOrFail($this->reviewingId);

        // 1. Simpan catatan ke history
        ProgressReportNote::create([
            'progress_report_id' => $report->id,
            'reviewer_id'        => Auth::id(),
            'decision'           => $this->decision,
            'comment'            => $this->reviewerComment,
            'recommendation'     => $this->reviewerRecommendation ?: null,
        ]);

        // 2. Update state ringkas
        $report->update([
            'is_approved' => $this->decision === 'approve',
            'reviewed_at' => now(),
        ]);

        Flux::toast(
            text: $this->decision === 'approve'
                ? 'Progress report approved.'
                : 'Revision requested. The author has been notified.',
            variant: 'success'
        );

        $this->closeReview();
    }

    public function stats(): array
    {
        $base = ProgressReport::where('reviewer_id', Auth::id());

        return [
            'under_review' => (clone $base)->whereNull('reviewed_at')->count(),
            'needs_revision' => (clone $base)->whereNotNull('reviewed_at')->where('is_approved', false)->count(),
            'approved' => (clone $base)->where('is_approved', true)->count(),
        ];
    }

    // ═══════════════ Render ═══════════════
    public function with(): array
    {
        $query = ProgressReport::query()
            ->with([
                'proposal.researchScheme',
                'proposal.author',
                'reviewer',
                'notes.reviewer',
            ])
            ->where('reviewer_id', Auth::id())
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('keyword', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%")
                        ->orWhereHas('proposal', function ($q) {
                            $q->where('title', 'like', "%{$this->search}%")
                                ->orWhereHas('author', fn($q) => $q->where('full_name', 'like', "%{$this->search}%"));
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                match ($this->statusFilter) {
                    'under_review'   => $q->whereNull('reviewed_at'),
                    'needs_revision' => $q->whereNotNull('reviewed_at')->where('is_approved', false),
                    'approved'       => $q->where('is_approved', true),
                    default          => $q,
                };
            })
            ->latest();

        return [
            'reports' => $query->paginate(10),
            'stats'   => $this->stats(),
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    {{-- ══════════ Header ══════════ --}}
    <x-dashboard-header icon="document-chart-bar" title="Progress Reports Review"
        leading="Review and evaluate progress reports assigned to you." />

    {{-- ══════════ Stats ══════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach ([
            ['key' => 'under_review',   'label' => 'Under Review',   'icon' => 'eye',
             'color' => 'violet',  'value' => $stats['under_review']],
            ['key' => 'needs_revision', 'label' => 'Needs Revision', 'icon' => 'arrow-path',
             'color' => 'amber',   'value' => $stats['needs_revision']],
            ['key' => 'approved',       'label' => 'Approved',       'icon' => 'check-circle',
             'color' => 'emerald', 'value' => $stats['approved']],
        ] as $s)
            <button type="button"
                wire:click="$set('statusFilter', '{{ $s['key'] }}')"
                class="text-left p-4 rounded-2xl border transition-all
                       bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl
                       {{ $statusFilter === $s['key']
                           ? 'border-' . $s['color'] . '-400 ring-2 ring-' . $s['color'] . '-400/20'
                           : 'border-white/80 dark:border-zinc-800 hover:border-' . $s['color'] . '-300' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg
                                    bg-{{ $s['color'] }}-100 text-{{ $s['color'] }}-700
                                    dark:bg-{{ $s['color'] }}-900/40 dark:text-{{ $s['color'] }}-300
                                    flex items-center justify-center">
                            <flux:icon :name="$s['icon']" class="size-4" />
                        </div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider
                                     text-slate-500 dark:text-zinc-400">
                            {{ $s['label'] }}
                        </span>
                    </div>
                    <span class="text-2xl font-heading font-bold
                                 text-{{ $s['color'] }}-600 dark:text-{{ $s['color'] }}-400">
                        {{ $s['value'] }}
                    </span>
                </div>
            </button>
        @endforeach
    </div>

    {{-- ══════════ Table Card ══════════ --}}
    <div class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
                border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                    gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            <div class="flex items-center gap-2 flex-wrap">
                @foreach ([
                    'all'            => 'All',
                    'under_review'   => 'Under Review',
                    'needs_revision' => 'Needs Revision',
                    'approved'       => 'Approved',
                ] as $key => $label)
                    <button type="button"
                        wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors
                               {{ $statusFilter === $key
                                   ? 'bg-violet-600 text-white shadow-sm shadow-violet-600/20'
                                   : 'bg-slate-100 text-slate-600 hover:bg-slate-200
                                      dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <x-input-search name="search" id="search-reviewer-progress"
                wire:model.live.debounce.300ms="search"
                placeholder="Search keyword, proposal, or author..."
                max-width="max-w-sm" class="w-full sm:w-md" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-xs">
                <thead class="bg-violet-50/50 dark:bg-violet-900/20 text-left text-[11px]
                              uppercase tracking-wider text-slate-600 dark:text-slate-300">
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
                        @php $meta = $report->statusMeta(); @endphp
                        <tr wire:key="rpt-{{ $report->id }}"
                            class="hover:bg-violet-50/30 dark:hover:bg-zinc-800/50 transition-colors">

                            {{-- Keyword + Proposal --}}
                            <td class="px-4 py-3 max-w-md">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.tag class="size-3 text-violet-600 dark:text-violet-400 shrink-0" />
                                        <span class="font-medium text-slate-900 dark:text-zinc-100 truncate"
                                            title="{{ $report->keyword }}">
                                            {{ $report->keyword }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-slate-500 dark:text-zinc-400 truncate"
                                        title="{{ $report->proposal?->title }}">
                                        {{ Str::limit($report->proposal?->title ?? '—', 50) }}
                                    </span>
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
                                    @if ($report->isApproved())
                                        <flux:icon.check-circle class="size-3" />
                                    @elseif ($report->isRejected())
                                        <flux:icon.arrow-path class="size-3" />
                                    @else
                                        <flux:icon.eye class="size-3" />
                                    @endif
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:button size="xs" variant="primary" icon="pencil-square"
                                    wire:click="openReview({{ $report->id }})">
                                    Review
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.document-chart-bar class="size-10 text-slate-300 dark:text-zinc-700" />
                                    <div class="flex flex-col gap-1">
                                        <span class="font-medium text-slate-700 dark:text-zinc-300">
                                            {{ $statusFilter === 'all' ? 'No progress reports assigned to you' : 'No reports match this filter' }}
                                        </span>
                                        <span class="text-xs">
                                            @if ($search)
                                                Try a different search term.
                                            @elseif ($statusFilter === 'all')
                                                You will see reports here once the admin assigns them to you.
                                            @else
                                                Try switching to a different filter tab.
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

    {{-- ══════════ Review Modal ══════════ --}}
    @if ($reviewingId && ($report = \App\Models\ProgressReport::with(['proposal.researchScheme', 'proposal.author'])->find($reviewingId)))
        <div x-data="{ show: @entangle('showReviewModal') }" x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            x-on:keydown.escape.window="$wire.closeReview()" x-cloak
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
                   p-0 sm:p-4 bg-inverse-surface/40 backdrop-blur-sm">

            <div x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
                       w-full sm:max-w-2xl
                       rounded-t-2xl sm:rounded-xl
                       max-h-[92vh] sm:max-h-[90vh] flex flex-col
                       border border-outline-variant/50 dark:border-zinc-700">

                {{-- Header --}}
                <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500
                            px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                                    <flux:icon.document-chart-bar class="size-4 text-white" />
                                </div>
                                <h3 class="font-heading text-base sm:text-lg font-semibold text-white">
                                    Review Progress Report
                                </h3>
                            </div>
                            <p class="text-[11px] text-white/75 mt-1 truncate"
                                title="{{ $report->proposal?->title }}">
                                {{ $report->proposal?->title ?? '—' }}
                            </p>
                            <p class="text-[10px] text-white/60 mt-0.5 truncate">
                                {{ $report->proposal?->researchScheme?->scheme_name ?? '—' }}
                            </p>
                        </div>

                        <button type="button" wire:click="closeReview"
                            class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                                   text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                            <flux:icon.x-mark class="size-4" />
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="submitReview"
                    class="flex-1 flex flex-col overflow-hidden">

                    <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">

                        {{-- Meta grid --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-lg bg-surface-container-low dark:bg-zinc-800/40
                                        border border-outline-variant/60 dark:border-zinc-700">
                                <div class="text-[10px] uppercase tracking-wider font-semibold
                                            text-outline dark:text-zinc-500">
                                    Author
                                </div>
                                <p class="mt-1 text-sm font-medium truncate
                                          text-on-surface dark:text-zinc-100">
                                    {{ $report->proposal?->author?->full_name ?? '—' }}
                                </p>
                            </div>

                            <div class="p-3 rounded-lg bg-surface-container-low dark:bg-zinc-800/40
                                        border border-outline-variant/60 dark:border-zinc-700">
                                <div class="text-[10px] uppercase tracking-wider font-semibold
                                            text-outline dark:text-zinc-500">
                                    Submitted
                                </div>
                                <p class="mt-1 text-sm font-medium text-on-surface dark:text-zinc-100">
                                    {{ $report->created_at?->format('d M Y, H:i') ?? '—' }}
                                </p>
                            </div>
                        </div>

                        {{-- Keyword --}}
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold
                                        text-outline dark:text-zinc-500 mb-1">
                                Keyword
                            </div>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                        bg-violet-50 dark:bg-violet-900/20
                                        border border-violet-200/80 dark:border-violet-800/60">
                                <flux:icon.tag class="size-3.5 text-violet-600 dark:text-violet-400" />
                                <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">
                                    {{ $report->keyword }}
                                </span>
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold
                                        text-outline dark:text-zinc-500 mb-1">
                                Summary
                            </div>
                            <div class="p-3 rounded-lg bg-surface-container-low dark:bg-zinc-800/40
                                        border border-outline-variant/60 dark:border-zinc-700
                                        text-[12px] leading-relaxed whitespace-pre-line
                                        text-on-surface-variant dark:text-zinc-300">
                                {{ $report->summary }}
                            </div>
                        </div>

                        {{-- Files --}}
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold
                                        text-outline dark:text-zinc-500 mb-2">
                                Attachments
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($report->report_path)
                                    <a href="{{ Storage::disk('public')->url($report->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-rose-50 hover:bg-rose-100
                                               dark:bg-rose-900/20 dark:hover:bg-rose-900/40
                                               border border-rose-200/80 dark:border-rose-800/60
                                               text-rose-700 dark:text-rose-300
                                               text-[11px] font-semibold transition-colors">
                                        <flux:icon.document-text class="size-4" />
                                        View Report PDF
                                        <flux:icon.arrow-up-right class="size-3 opacity-60" />
                                    </a>
                                @endif

                                @if ($report->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($report->ppt_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-orange-50 hover:bg-orange-100
                                               dark:bg-orange-900/20 dark:hover:bg-orange-900/40
                                               border border-orange-200/80 dark:border-orange-800/60
                                               text-orange-700 dark:text-orange-300
                                               text-[11px] font-semibold transition-colors">
                                        <flux:icon.presentation-chart-bar class="size-4" />
                                        View Presentation
                                        <flux:icon.arrow-up-right class="size-3 opacity-60" />
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="border-t border-outline-variant/40 dark:border-zinc-700"></div>

                        {{-- Decision --}}
                        <div>
                            <label class="block text-sm font-medium
                                          text-on-surface-variant dark:text-zinc-300 mb-2">
                                Decision <span class="text-error">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="decision" value="approve" class="sr-only peer" />
                                    <div class="flex items-center gap-3 p-3 rounded-lg border-2 transition-all
                                                border-outline-variant/60 dark:border-zinc-700
                                                peer-checked:border-emerald-500 peer-checked:bg-emerald-50
                                                dark:peer-checked:bg-emerald-900/20
                                                hover:border-emerald-300">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700
                                                    dark:bg-emerald-900/40 dark:text-emerald-300
                                                    flex items-center justify-center shrink-0">
                                            <flux:icon.check-circle class="size-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-on-surface dark:text-zinc-100">
                                                Approve
                                            </div>
                                            <div class="text-[10px] text-outline dark:text-zinc-500">
                                                Mark this report as accepted
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="decision" value="revise" class="sr-only peer" />
                                    <div class="flex items-center gap-3 p-3 rounded-lg border-2 transition-all
                                                border-outline-variant/60 dark:border-zinc-700
                                                peer-checked:border-amber-500 peer-checked:bg-amber-50
                                                dark:peer-checked:bg-amber-900/20
                                                hover:border-amber-300">
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700
                                                    dark:bg-amber-900/40 dark:text-amber-300
                                                    flex items-center justify-center shrink-0">
                                            <flux:icon.arrow-path class="size-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-on-surface dark:text-zinc-100">
                                                Request Revision
                                            </div>
                                            <div class="text-[10px] text-outline dark:text-zinc-500">
                                                Send back to author
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @error('decision')
                                <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Comment --}}
                        <div>
                            <label for="reviewerComment"
                                class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                                Review Comment <span class="text-error">*</span>
                            </label>
                            <textarea id="reviewerComment" wire:model="reviewerComment" rows="4"
                                placeholder="Provide feedback on the progress report — what was done well, what needs improvement..."
                                class="mt-1 block w-full rounded-md shadow-sm resize-none
                                       border-outline-variant dark:border-zinc-600
                                       bg-surface-container-lowest dark:bg-zinc-800
                                       text-on-surface dark:text-zinc-100
                                       placeholder:text-outline dark:placeholder-zinc-500
                                       focus:border-violet-500 focus:ring-violet-500
                                       sm:text-sm py-2 px-3"></textarea>
                            <div class="flex items-center justify-between mt-1">
                                @error('reviewerComment')
                                    <span class="text-error text-xs">{{ $message }}</span>
                                @else
                                    <span class="text-[11px] text-outline dark:text-zinc-500">
                                        Minimum 10 characters.
                                    </span>
                                @enderror
                                <span class="text-[11px] text-outline dark:text-zinc-500 font-mono">
                                    {{ strlen($reviewerComment) }}/2000
                                </span>
                            </div>
                        </div>

                        {{-- Recommendation --}}
                        <div>
                            <label for="reviewerRecommendation"
                                class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                                Recommendation
                                <span class="text-outline dark:text-zinc-500 text-xs font-normal">(optional)</span>
                            </label>
                            <textarea id="reviewerRecommendation" wire:model="reviewerRecommendation" rows="3"
                                placeholder="Suggestions for the next phase — references, focus areas, additional data..."
                                class="mt-1 block w-full rounded-md shadow-sm resize-none
                                       border-outline-variant dark:border-zinc-600
                                       bg-surface-container-lowest dark:bg-zinc-800
                                       text-on-surface dark:text-zinc-100
                                       placeholder:text-outline dark:placeholder-zinc-500
                                       focus:border-violet-500 focus:ring-violet-500
                                       sm:text-sm py-2 px-3"></textarea>
                            @error('reviewerRecommendation')
                                <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                                px-5 sm:px-6 py-4
                                border-t border-outline-variant/40 dark:border-zinc-700
                                bg-surface-container-lowest dark:bg-zinc-900
                                rounded-b-2xl sm:rounded-b-xl">

                        <button type="button" wire:click="closeReview"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md
                                   text-on-surface-variant dark:text-zinc-300
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   border border-outline-variant dark:border-zinc-600
                                   hover:bg-surface-container-low dark:hover:bg-zinc-700
                                   transition-colors duration-150">
                            Cancel
                        </button>

                        <button type="submit" wire:loading.attr="disabled" wire:target="submitReview"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                                   px-5 py-2 text-sm font-medium text-white rounded-md
                                   bg-gradient-to-r from-violet-600 to-violet-500
                                   hover:from-violet-700 hover:to-violet-600
                                   focus:outline-none focus:ring-2 focus:ring-violet-500
                                   transition-all duration-200
                                   disabled:opacity-60 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="submitReview"
                                class="inline-flex items-center gap-1.5">
                                <flux:icon.paper-airplane class="size-3.5" />
                                Submit Review
                            </span>
                            <span wire:loading.flex wire:target="submitReview"
                                class="items-center gap-1.5">
                                <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
