<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Proposal;
use App\Models\ReviewerNote;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;
use Livewire\Attributes\Title;

new #[Title('Review Proposal')] class extends Component {
    use WithPagination;

    // ─────── List ───────
    public string $search = '';
    public string $statusFilter = 'all';

    // ─────── Review modal ───────
    public bool $showReviewModal = false;
    public ?int $reviewingId = null;
    public string $reviewingTitle = '';
    public string $reviewingScheme = '';
    public string $reviewingAuthor = '';
    public string $comment = '';
    public string $recommendation = '';
    public array $previousNotes = [];

    protected function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:2000'],
            'recommendation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ═══════════════ View details ═══════════════
    public function viewDetails(int $id): void
    {
        $proposal = Proposal::query()
            ->with(['researchScheme', 'period', 'author', 'budgetProposal', 'adminNotes' => fn($q) => $q->latest(), 'reviewerNotes' => fn($q) => $q->latest()])
            ->where('reviewer_id', Auth::id())
            ->findOrFail($id);

        $meta = $this->statusMeta($proposal->status_proposal);

        $budgetItems = $proposal->budgetProposal->map(fn($i) => ['item_name' => $i->item_name, 'amount' => (int) $i->amount])->values()->all();
        $budgetTotal = (int) collect($budgetItems)->sum('amount');
        $budgetLimit = (int) ($proposal->researchScheme->budget_limit ?? 0);

        $adminNotes = $proposal->adminNotes
            ->map(
                fn($n) => [
                    'comment' => $n->comment ?? '',
                    'recommendation' => $n->recommendation ?? '',
                    'createdAt' => $n->created_at?->format('d M Y, H:i') ?? '—',
                    'relative' => $n->created_at?->diffForHumans() ?? '',
                ],
            )
            ->values()
            ->all();

        $reviewerNotes = $proposal->reviewerNotes
            ->map(
                fn($n) => [
                    'comment' => $n->comment ?? '',
                    'recommendation' => $n->recommendation ?? '',
                    'isApproved' => (bool) $n->is_approved,
                    'createdAt' => $n->created_at?->format('d M Y, H:i') ?? '—',
                    'relative' => $n->created_at?->diffForHumans() ?? '',
                ],
            )
            ->values()
            ->all();

        $this->dispatch('view-details', title: $proposal->title, scheme: $proposal->researchScheme?->scheme_name ?? '—', period: $proposal->period?->periode ?? '—', status: $meta['label'], statusClass: $meta['class'], is_research: (bool) $proposal->is_research, keywords: array_filter(array_map('trim', explode(',', $proposal->keywords ?? ''))), summary: $proposal->summary ?? '', reviewer: Auth::user()->full_name, owner: $proposal->author?->full_name ?? '—', budgetItems: $budgetItems, budgetLimit: $budgetLimit, budgetTotal: $budgetTotal, budgetRemaining: $budgetLimit - $budgetTotal, budgetPercent: $budgetLimit > 0 ? round(($budgetTotal / $budgetLimit) * 100, 1) : 0, createdAt: $proposal->created_at?->format('d M Y, H:i') ?? '—', updatedAt: $proposal->updated_at?->format('d M Y, H:i') ?? '—', adminNotes: $adminNotes, reviewerNotes: $reviewerNotes);
    }

    /**
     * Load riwayat catatan reviewer untuk proposal tertentu.
     * Dipakai saat openReview() dan setelah acceptNote().
     */
    protected function loadPreviousNotes(int $proposalId): void
    {
        $this->previousNotes = ReviewerNote::where('proposal_id', $proposalId)
            ->where('reviewer_id', Auth::id())
            ->latest()
            ->get()
            ->map(
                fn($n) => [
                    'id' => $n->id, // ← tambahan
                    'comment' => $n->comment ?? '',
                    'recommendation' => $n->recommendation ?? '',
                    'isApproved' => (bool) $n->is_approved,
                    'createdAt' => $n->created_at?->format('d M Y, H:i') ?? '—',
                    'relative' => $n->created_at?->diffForHumans() ?? '',
                ],
            )
            ->values()
            ->all();
    }

    // ═══════════════ Review modal ═══════════════
    public function openReview(int $id): void
    {
        $proposal = Proposal::query()
            ->with(['researchScheme', 'author'])
            ->where('reviewer_id', Auth::id())
            ->findOrFail($id);

        $this->loadPreviousNotes($proposal->id);

        $this->reviewingId = $proposal->id;
        $this->reviewingTitle = $proposal->title;
        $this->reviewingScheme = $proposal->researchScheme?->scheme_name ?? '—';
        $this->reviewingAuthor = $proposal->author?->full_name ?? '—';

        $this->comment = '';
        $this->recommendation = '';

        $this->resetErrorBag();
        $this->resetValidation();
        $this->showReviewModal = true;
    }

    /**
     * Tandai satu note sebagai approved.
     * Kalau SETELAH ini semua note sudah approved → proposal status jadi 'accepted'.
     * Kalau masih ada note yang belum approved → status tetap 'reviewer_revision'.
     */
    public function acceptNote(int $noteId): void
    {
        $note = ReviewerNote::where('reviewer_id', Auth::id())->findOrFail($noteId);

        // Guard: sudah approved → no-op
        if ($note->is_approved) {
            Flux::toast(text: 'This note is already approved.', variant: 'info');
            return;
        }

        $proposal = Proposal::find($note->proposal_id);

        // Guard: proposal sudah finalized
        if (! $proposal || in_array($proposal->status_proposal, ['accepted', 'rejected'])) {
            Flux::toast(text: 'This proposal has been finalized.', variant: 'danger');
            return;
        }

        // ─── 1. Tandai note ini approved ───
        $note->update(['is_approved' => true]);

        // ─── 2. Cek sisa note yang belum approved untuk proposal ini ───
        $stillPending = ReviewerNote::where('proposal_id', $proposal->id)
            ->where('reviewer_id', Auth::id())
            ->where('is_approved', false)
            ->exists();

        // ─── 3. Update status proposal HANYA kalau tidak ada yang pending ───
        if (! $stillPending) {
            $proposal->update(['status_proposal' => 'accepted']);
            Flux::toast(text: 'All notes approved. Proposal accepted.', variant: 'success');
        } else {
            // Status biarkan apa adanya (reviewer_revision)
            // Pastikan tetap reviewer_revision kalau sebelumnya masih under_review
            if ($proposal->status_proposal !== 'reviewer_revision') {
                $proposal->update(['status_proposal' => 'reviewer_revision']);
            }
            Flux::toast(
                text: 'Note approved. Other notes still pending revision.',
                variant: 'success'
            );
        }

        $this->loadPreviousNotes($proposal->id);
    }

    /**
     * Tandai satu note sebagai butuh revisi (is_approved = false).
     * Status proposal otomatis jadi 'reviewer_revision'.
     */
    public function reviseNote(int $noteId): void
    {
        $note = ReviewerNote::where('reviewer_id', Auth::id())->findOrFail($noteId);

        // Guard: sudah revision → no-op
        if (! $note->is_approved) {
            Flux::toast(text: 'This note is already marked for revision.', variant: 'info');
            return;
        }

        $proposal = Proposal::find($note->proposal_id);

        if (! $proposal || $proposal->status_proposal === 'rejected') {
            Flux::toast(text: 'This proposal cannot be revised.', variant: 'danger');
            return;
        }

        // ─── 1. Tandai note ini butuh revisi ───
        $note->update(['is_approved' => false]);

        // ─── 2. Status proposal kembali ke reviewer_revision ───
        $proposal->update(['status_proposal' => 'reviewer_revision']);

        Flux::toast(text: 'Proposal status set back to reviewer revision.', variant: 'success');

        $this->loadPreviousNotes($proposal->id);
    }

    /**
     * APPROVE — reviewer yakin proposal sudah benar.
     * Komentar & rekomendasi opsional. Status → accepted (permanent).
     */
    public function approveReview(): void
    {
        $this->validate([
            'comment'        => ['nullable', 'string', 'max:2000'],
            'recommendation' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $this->reviewingId) {
            return;
        }

        $proposal = Proposal::where('reviewer_id', Auth::id())
            ->findOrFail($this->reviewingId);

        // 🔒 Guard: proposal sudah finalized
        if (in_array($proposal->status_proposal, ['accepted', 'rejected'])) {
            Flux::toast(
                text: 'This proposal has been finalized.',
                variant: 'danger'
            );
            return;
        }

        // ─── 2. Approve SEMUA note yang masih pending (is_approved = false) ───
        ReviewerNote::where('proposal_id', $proposal->id)
            ->where('reviewer_id', Auth::id())
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        // ─── 3. Baru ubah status proposal ───
        $proposal->update(['status_proposal' => 'accepted']);

        Flux::toast(
            text: 'All notes approved. Proposal accepted.',
            variant: 'success'
        );

        $this->closeModal();
    }

    /**
     * REQUEST REVISION — reviewer minta perbaikan.
     * Komentar WAJIB. Status → reviewer_revision.
     */
    public function requestRevision(): void
    {
        $this->validate(
            [
                'comment' => ['required', 'string', 'min:10'],
                'recommendation' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'comment.required' => 'Please explain what needs to be revised.',
                'comment.min' => 'Comment must be at least 10 characters.',
            ],
        );

        if (!$this->reviewingId) {
            return;
        }

        $proposal = Proposal::where('reviewer_id', Auth::id())->findOrFail($this->reviewingId);

        ReviewerNote::create([
            'proposal_id' => $proposal->id,
            'reviewer_id' => Auth::id(),
            'comment' => $this->comment,
            'is_approved' => false,
            'recommendation' => $this->recommendation ?: null,
        ]);

        $proposal->update(['status_proposal' => 'reviewer_revision']);

        Flux::toast(text: 'Revision requested to the author.', variant: 'success');

        $this->closeModal();
    }

    public function cancelReview(): void
    {
        $this->closeModal();
    }

    protected function closeModal(): void
    {
        $this->showReviewModal = false;
        $this->reset(['reviewingId', 'reviewingTitle', 'reviewingScheme', 'reviewingAuthor', 'comment', 'recommendation', 'previousNotes']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ═══════════════ Helpers ═══════════════
    public function statusMeta(string $status): array
    {
        return match ($status) {
            'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300'],
            'admin_revision' => ['label' => 'Admin Revision', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            'submitted' => ['label' => 'Submitted', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            'under_review' => ['label' => 'Under Review', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300'],
            'reviewer_revision' => ['label' => 'Reviewer Revision', 'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'],
            'accepted' => ['label' => 'Accepted', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
            default => ['label' => ucfirst($status), 'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300'],
        };
    }

    // ═══════════════ Render ═══════════════
    public function with(): array
    {
        $query = Proposal::query()
            ->with(['researchScheme', 'period', 'author'])
            ->where('reviewer_id', Auth::id())
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('keywords', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%")
                        ->orWhereHas('author', fn($q) => $q->where('full_name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status_proposal', $this->statusFilter))
            ->latest();

        $statusCounts = Proposal::where('reviewer_id', Auth::id())->selectRaw('status_proposal, count(*) as total')->groupBy('status_proposal')->pluck('total', 'status_proposal');

        return [
            'proposals' => $query->paginate(10),
            'statusCounts' => $statusCounts,
            'totalCount' => Proposal::where('reviewer_id', Auth::id())->count(),
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    {{-- ══════════ Header ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <x-dashboard-header icon="clipboard-document-check" title="Review Proposals"
            leading="Review research proposals assigned to you." />
    </div>

    {{-- ══════════ Table Card ══════════ --}}
    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
                border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        {{-- ── Header: info + filter + search ── --}}
        <div
            class="flex flex-col lg:flex-row lg:items-center lg:justify-between
                    gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            {{-- Record info --}}
            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400 min-w-0">
                @if ($proposals->total() > 0)
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-emerald-50 text-emerald-700 font-semibold
                                 dark:bg-emerald-900/30 dark:text-emerald-300 shrink-0">
                        <flux:icon.list-bullet class="size-3" />
                        {{ $proposals->total() }} {{ Str::plural('proposal', $proposals->total()) }}
                    </span>
                    <span class="text-slate-400 dark:text-zinc-600 hidden sm:inline">·</span>
                    <span class="truncate hidden sm:inline">
                        Showing
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $proposals->firstItem() }}–{{ $proposals->lastItem() }}
                        </span>
                        of
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $proposals->total() }}
                        </span>
                    </span>
                @else
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                                 bg-slate-100 text-slate-500 font-semibold
                                 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.list-bullet class="size-3" />
                        No proposals assigned
                    </span>
                @endif
            </div>

            {{-- Filter + Search --}}
            <div
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2
                        w-full lg:w-auto">

                @php
                    $statusOptions = [
                        'all' => ['label' => 'All Status', 'dot' => 'bg-slate-400'],
                        'under_review' => ['label' => 'Under Review', 'dot' => 'bg-violet-400'],
                        'reviewer_revision' => ['label' => 'Revision Requested', 'dot' => 'bg-orange-400'],
                        'accepted' => ['label' => 'Accepted', 'dot' => 'bg-emerald-400'],
                        'rejected' => ['label' => 'Rejected', 'dot' => 'bg-rose-400'],
                    ];
                    $currentStyle = $statusOptions[$statusFilter] ?? $statusOptions['all'];
                @endphp

                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false"
                    class="relative shrink-0">

                    <button type="button" @click="open = ! open"
                        class="group w-full sm:w-auto inline-flex items-center justify-between gap-2
                               px-3 py-2 rounded-xl
                               bg-white dark:bg-zinc-800
                               border border-slate-200 dark:border-zinc-700
                               shadow-sm shadow-slate-900/5 dark:shadow-black/20
                               text-[11px] font-heading font-semibold tracking-tight
                               text-slate-700 dark:text-zinc-200
                               hover:border-slate-300 dark:hover:border-zinc-600
                               hover:bg-slate-50 dark:hover:bg-zinc-700/70
                               focus:outline-none focus-visible:ring-2
                               focus-visible:ring-emerald-500/40
                               transition-all duration-150 whitespace-nowrap">

                        <span class="inline-flex items-center gap-2 min-w-0">
                            <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $currentStyle['dot'] }}"></span>
                            <span class="truncate">{{ $currentStyle['label'] }}</span>
                        </span>

                        <span class="flex items-center gap-1.5 shrink-0">
                            <span
                                class="inline-flex items-center justify-center
                                         min-w-[20px] h-5 px-1.5 rounded-md
                                         bg-slate-100 text-slate-600
                                         dark:bg-zinc-700 dark:text-zinc-300
                                         text-[10px] font-bold tabular-nums leading-none">
                                {{ $statusFilter === 'all' ? $totalCount : $statusCounts[$statusFilter] ?? 0 }}
                            </span>
                            <flux:icon.chevron-down
                                class="size-3.5 text-slate-400 dark:text-zinc-500
                                       transition-transform duration-200"
                                ::class="open && 'rotate-180'" />
                        </span>
                    </button>

                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        x-cloak
                        class="absolute right-0 z-30 mt-1.5 w-56 origin-top-right
                               rounded-xl
                               bg-white dark:bg-zinc-900
                               border border-slate-200 dark:border-zinc-700
                               shadow-lg shadow-slate-900/10 dark:shadow-black/40
                               p-1 max-h-80 overflow-y-auto">

                        @foreach ($statusOptions as $key => $option)
                            @php
                                $isSelected = $statusFilter === $key;
                                $count = $key === 'all' ? $totalCount : $statusCounts[$key] ?? 0;
                            @endphp

                            <button type="button" wire:click="$set('statusFilter', '{{ $key }}')"
                                @click="open = false"
                                class="group w-full flex items-center gap-2
                                       px-2.5 py-2 rounded-lg
                                       text-left text-[12px] font-medium
                                       transition-colors duration-100
                                       {{ $isSelected
                                           ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                           : 'text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800' }}">
                                <span
                                    class="h-1.5 w-1.5 rounded-full shrink-0 {{ $option['dot'] }}
                                             {{ $isSelected ? '' : 'opacity-60 group-hover:opacity-100' }}
                                             transition-opacity"></span>
                                <span class="flex-1 truncate">{{ $option['label'] }}</span>
                                <span
                                    class="inline-flex items-center justify-center
                                             min-w-[20px] h-[18px] px-1.5 rounded-md
                                             text-[10px] font-bold tabular-nums leading-none
                                             {{ $isSelected
                                                 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'
                                                 : 'bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-500' }}">
                                    {{ $count }}
                                </span>
                                @if ($isSelected)
                                    <flux:icon.check class="size-3.5 shrink-0" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <x-input-search name="search" id="search-reviewer" wire:model.live.debounce.300ms="search"
                    placeholder="Search title, author..." max-width="max-w-sm" class="!flex w-full sm:w-auto flex-1" />
            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-xs">
                <thead
                    class="bg-slate-50/60 dark:bg-zinc-800/40 text-left text-[10px]
                              uppercase tracking-wider
                              text-slate-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Title & Scheme</th>
                        <th class="px-4 py-3 font-semibold">Author</th>
                        <th class="px-4 py-3 font-semibold">Period</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse ($proposals as $proposal)
                        @php $meta = $this->statusMeta($proposal->status_proposal); @endphp

                        <tr wire:key="prop-{{ $proposal->id }}"
                            class="group hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition-colors">

                            {{-- Title + Scheme --}}
                            <td class="px-4 py-3 max-w-md">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="font-medium text-slate-900 dark:text-zinc-100 truncate"
                                        title="{{ $proposal->title }}">
                                        {{ Str::limit($proposal->title, 50) }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                        {{ $proposal->researchScheme?->scheme_name ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Author --}}
                            <td class="px-4 py-3">
                                @if ($proposal->author)
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div
                                            class="w-6 h-6 rounded-full shrink-0
                                                    bg-emerald-100 text-emerald-700
                                                    flex items-center justify-center
                                                    text-[10px] font-bold
                                                    dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                        </div>
                                        <span
                                            class="truncate max-w-[140px]
                                                     text-slate-700 dark:text-zinc-300">
                                            {{ $proposal->author->full_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 italic">—</span>
                                @endif
                            </td>

                            {{-- Period --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300 whitespace-nowrap">
                                {{ $proposal->period?->periode ?? '—' }}
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                             text-[11px] font-semibold whitespace-nowrap
                                             {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    {{-- Dropdown --}}
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            title="Actions"
                                            class="rounded-lg text-slate-500 hover:bg-slate-100
                                                   dark:text-zinc-400 dark:hover:bg-zinc-700/60" />

                                        <flux:menu>
                                            <flux:menu.item icon="eye"
                                                wire:click="viewDetails({{ $proposal->id }})">
                                                View Details
                                            </flux:menu.item>

                                            <flux:menu.separator />
                                            <flux:menu.item icon="pencil-square"
                                                wire:click="openReview({{ $proposal->id }})"
                                                class="text-violet-600 dark:text-violet-400
                                                    hover:bg-violet-50! dark:hover:bg-violet-900/30!">
                                                {{ $proposal->status_proposal === 'reviewer_revision' ? 'Re-Review' : 'Submit Review' }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div
                                        class="w-12 h-12 rounded-full
                                                bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center">
                                        <flux:icon.clipboard-document-check
                                            class="size-5 text-slate-400 dark:text-zinc-500" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm font-medium
                                                     text-slate-700 dark:text-zinc-300">
                                            No proposals assigned to you
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-zinc-400">
                                            @if ($search || $statusFilter !== 'all')
                                                Try adjusting your search or filter.
                                            @else
                                                You'll see proposals here once the admin assigns them.
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

        @if ($proposals->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $proposals->links() }}
            </div>
        @endif
    </div>

    {{-- ══════════ Review Modal ══════════ --}}
    <div x-data="{ show: @entangle('showReviewModal') }" x-show="show" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" x-on:keydown.escape.window="$wire.cancelReview()" x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
            p-0 sm:p-4 bg-inverse-surface/40 backdrop-blur-sm">

        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
                w-full sm:max-w-xl
                rounded-t-2xl sm:rounded-xl
                max-h-[92vh] sm:max-h-[90vh] flex flex-col
                border border-outline-variant/50 dark:border-zinc-700">

            {{-- ── Header ── --}}
            <div
                class="shrink-0
                        bg-gradient-to-r from-violet-600 to-violet-500
                        px-4 sm:px-5 py-3
                        rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-md bg-white/20
                                        flex items-center justify-center shrink-0">
                                <flux:icon.clipboard-document-check class="size-3.5 text-white" />
                            </div>
                            <h3 class="font-heading text-[13px] sm:text-[14px] font-semibold text-white">
                                Submit Review
                            </h3>
                        </div>
                        <p class="text-[10px] text-white/75 mt-0.5 truncate" title="{{ $reviewingTitle }}">
                            {{ $reviewingTitle }}
                        </p>
                    </div>

                    <button type="button" wire:click="cancelReview"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                            text-white/80 hover:text-white hover:bg-white/10
                            transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ── Body ── --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">

                {{-- Meta info --}}
                <div class="grid grid-cols-2 gap-2">
                    <div
                        class="p-2.5 rounded-lg
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <div
                            class="text-[9px] uppercase tracking-wider font-semibold
                                    text-outline dark:text-zinc-500">
                            Author
                        </div>
                        <p
                            class="mt-0.5 text-[12px] font-medium truncate
                                text-on-surface dark:text-zinc-100">
                            {{ $reviewingAuthor }}
                        </p>
                    </div>
                    <div
                        class="p-2.5 rounded-lg
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <div
                            class="text-[9px] uppercase tracking-wider font-semibold
                                    text-outline dark:text-zinc-500">
                            Scheme
                        </div>
                        <p
                            class="mt-0.5 text-[12px] font-medium truncate
                                text-on-surface dark:text-zinc-100">
                            {{ $reviewingScheme }}
                        </p>
                    </div>
                </div>

                {{-- ═════════ Previous Reviewer Notes ═════════ --}}
                @if (!empty($previousNotes))
                    <div x-data="{ open: true }"
                        class="rounded-lg border border-violet-200/70 dark:border-violet-800/60
                            bg-violet-50/50 dark:bg-violet-900/10 overflow-hidden">

                        {{-- Toggle header --}}
                        <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2
                                px-2.5 py-2
                                hover:bg-violet-100/60 dark:hover:bg-violet-900/20
                                transition-colors">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <flux:icon.chat-bubble-left-right
                                    class="size-3.5 text-violet-600 dark:text-violet-400 shrink-0" />
                                <span
                                    class="text-[11px] font-heading font-semibold
                                            text-violet-800 dark:text-violet-200 truncate">
                                    Previous Notes
                                </span>
                                <span
                                    class="inline-flex items-center justify-center
                                            min-w-[18px] h-[16px] px-1 rounded
                                            bg-violet-200/70 text-violet-800
                                            dark:bg-violet-800/50 dark:text-violet-200
                                            text-[9px] font-bold tabular-nums leading-none">
                                    {{ count($previousNotes) }}
                                </span>
                            </div>
                            <flux:icon.chevron-down
                                class="size-3 text-violet-600 dark:text-violet-400 shrink-0
                                    transition-transform duration-200"
                                x-bind:class="open && 'rotate-180'" />
                        </button>

                        {{-- Timeline --}}
                        <div x-show="open" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0" x-collapse
                            class="border-t border-violet-200/60 dark:border-violet-800/50">

                            <div class="p-2.5 space-y-1.5 max-h-56 overflow-y-auto">
                                @foreach ($previousNotes as $i => $note)
                                    <div
                                        class="relative pl-3.5 border-l-2
                                                {{ $note['isApproved']
                                                    ? 'border-emerald-300/60 dark:border-emerald-700/60'
                                                    : 'border-amber-300/60 dark:border-amber-700/60' }}">

                                        {{-- Dot --}}
                                        <div
                                            class="absolute -left-[5px] top-2 w-2 h-2 rounded-full
                                                    ring-2 ring-violet-50/50 dark:ring-zinc-900/50
                                                    {{ $note['isApproved'] ? 'bg-emerald-500' : 'bg-amber-500' }}">
                                        </div>

                                        <div
                                            class="p-2 rounded-md border
                                                    {{ $note['isApproved']
                                                        ? 'bg-emerald-50/60 dark:bg-emerald-900/15 border-emerald-200/70 dark:border-emerald-800/50'
                                                        : 'bg-amber-50/60 dark:bg-amber-900/15 border-amber-200/70 dark:border-amber-800/50' }}">

                                            {{-- Header --}}
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span
                                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded
                                                            text-[8px] font-bold uppercase tracking-wider
                                                            {{ $note['isApproved']
                                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                                    @if ($note['isApproved'])
                                                        <flux:icon.check-circle class="size-2" />
                                                        Approved
                                                    @else
                                                        <flux:icon.pencil-square class="size-2" />
                                                        Revision
                                                    @endif
                                                </span>
                                                <span class="text-[9px] text-outline dark:text-zinc-500 font-mono">
                                                    {{ $note['createdAt'] }}
                                                </span>
                                            </div>

                                            {{-- Comment --}}
                                            @if ($note['comment'])
                                                <p
                                                    class="text-[11px] leading-snug whitespace-pre-line
                                                        text-on-surface dark:text-zinc-100">
                                                    {{ $note['comment'] }}
                                                </p>
                                            @endif

                                            {{-- Recommendation --}}
                                            @if ($note['recommendation'])
                                                <div
                                                    class="mt-1.5 pt-1.5 border-t
                                                            {{ $note['isApproved']
                                                                ? 'border-emerald-200/70 dark:border-emerald-800/50'
                                                                : 'border-amber-200/70 dark:border-amber-800/50' }}">
                                                    <div class="flex items-center gap-1 mb-0.5">
                                                        <flux:icon.light-bulb
                                                            class="size-2
                                                            {{ $note['isApproved'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}" />
                                                        <span
                                                            class="text-[8px] uppercase tracking-wider font-bold
                                                                    {{ $note['isApproved'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                                                            Recommendation
                                                        </span>
                                                    </div>
                                                    <p
                                                        class="text-[10px] leading-snug whitespace-pre-line
                                                            text-on-surface-variant dark:text-zinc-400">
                                                        {{ $note['recommendation'] }}
                                                    </p>
                                                </div>
                                            @endif

                                            {{-- ── Footer: Accept button ── --}}
                                            @if (!$note['isApproved'])
                                                {{-- ── Accept button — emerald (approve) ── --}}
                                                <div
                                                    class="mt-2 pt-2 border-t
                border-amber-200/70 dark:border-amber-800/50
                flex justify-end">
                                                    <button type="button"
                                                        wire:click="acceptNote({{ $note['id'] }})"
                                                        wire:confirm="Approve this proposal? This will finalize it and cannot be undone."
                                                        wire:loading.attr="disabled"
                                                        wire:target="acceptNote,reviseNote"
                                                        class="inline-flex items-center gap-1
                   px-2 py-1 rounded-md
                   text-[10px] font-heading font-semibold
                   text-white
                   bg-gradient-to-r from-emerald-600 to-emerald-500
                   hover:from-emerald-700 hover:to-emerald-600
                   focus:outline-none focus:ring-2 focus:ring-emerald-500
                   shadow-sm shadow-emerald-600/20
                   transition-all duration-150
                   disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <span wire:loading.remove wire:target="acceptNote"
                                                            class="inline-flex items-center gap-1">
                                                            <flux:icon.check-circle class="size-2.5" />
                                                            Accept
                                                        </span>
                                                        <span wire:loading.flex wire:target="acceptNote"
                                                            class="items-center gap-1">
                                                            <svg class="animate-spin size-2.5" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12"
                                                                    cy="12" r="10" stroke="currentColor"
                                                                    stroke-width="4" />
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                            </svg>
                                                            Accepting...
                                                        </span>
                                                    </button>
                                                </div>
                                            @else
                                                {{-- ── Revise button — amber (revision) ── --}}
                                                <div
                                                    class="mt-2 pt-2 border-t
                                                    border-emerald-200/70 dark:border-emerald-800/50
                                                    flex justify-end">
                                                    <button type="button"
                                                        wire:click="reviseNote({{ $note['id'] }})"
                                                        wire:confirm="Revise this proposal? This will change the status back to reviewer revision."
                                                        wire:loading.attr="disabled"
                                                        wire:target="acceptNote,reviseNote"
                                                        class="inline-flex items-center gap-1
                                                            px-2 py-1 rounded-md
                                                            text-[10px] font-heading font-semibold
                                                            text-white
                                                            bg-gradient-to-r from-amber-500 to-amber-400
                                                            hover:from-amber-600 hover:to-amber-500
                                                            focus:outline-none focus:ring-2 focus:ring-amber-500
                                                            shadow-sm shadow-amber-500/20
                                                            transition-all duration-150
                                                            disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <span wire:loading.remove wire:target="reviseNote"
                                                            class="inline-flex items-center gap-1">
                                                            <flux:icon.pencil-square class="size-2.5" />
                                                            Revise
                                                        </span>
                                                        <span wire:loading.flex wire:target="reviseNote"
                                                            class="items-center gap-1">
                                                            <svg class="animate-spin size-2.5" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12"
                                                                    cy="12" r="10" stroke="currentColor"
                                                                    stroke-width="4" />
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                            </svg>
                                                            Revising...
                                                        </span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Comment --}}
                <div>
                    <label for="comment"
                        class="block text-[12px] font-medium text-on-surface-variant dark:text-zinc-300">
                        Review Comment
                        <span class="text-outline dark:text-zinc-500 text-[10px] font-normal">
                            (required for revision)
                        </span>
                    </label>

                    <textarea id="comment" wire:model="comment" rows="4"
                        placeholder="Explain your assessment, or specify what needs to be revised..."
                        class="mt-1 block w-full rounded-md shadow-sm resize-none
                            border-outline-variant dark:border-zinc-600
                            bg-surface-container-lowest dark:bg-zinc-800
                            text-on-surface dark:text-zinc-100
                            placeholder:text-outline dark:placeholder-zinc-500
                            focus:border-violet-500 focus:ring-violet-500
                            text-[12px] py-2 px-3"></textarea>

                    <div class="flex items-center justify-between mt-1 gap-2">
                        @error('comment')
                            <span class="text-error text-[11px]">{{ $message }}</span>
                        @else
                            <span class="text-[10px] text-outline dark:text-zinc-500">
                                Required if you request a revision.
                            </span>
                        @enderror
                        <span class="text-[10px] text-outline dark:text-zinc-500 font-mono shrink-0">
                            {{ strlen($comment) }}/2000
                        </span>
                    </div>
                </div>

                {{-- Recommendation --}}
                <div>
                    <label for="recommendation"
                        class="block text-[12px] font-medium text-on-surface-variant dark:text-zinc-300">
                        Recommendations
                        <span class="text-outline dark:text-zinc-500 text-[10px] font-normal">(optional)</span>
                    </label>

                    <textarea id="recommendation" wire:model="recommendation" rows="3"
                        placeholder="Suggestions for the author (references, methodology, sources, etc.)..."
                        class="mt-1 block w-full rounded-md shadow-sm resize-none
                            border-outline-variant dark:border-zinc-600
                            bg-surface-container-lowest dark:bg-zinc-800
                            text-on-surface dark:text-zinc-100
                            placeholder:text-outline dark:placeholder-zinc-500
                            focus:border-violet-500 focus:ring-violet-500
                            text-[12px] py-2 px-3"></textarea>

                    <div class="flex items-center justify-between mt-1">
                        @error('recommendation')
                            <span class="text-error text-[11px]">{{ $message }}</span>
                        @else
                            <span class="text-[10px] text-outline dark:text-zinc-500">
                                Optional. Helpful hints for the author.
                            </span>
                        @enderror
                        <span class="text-[10px] text-outline dark:text-zinc-500 font-mono shrink-0">
                            {{ strlen($recommendation) }}/2000
                        </span>
                    </div>
                </div>

                {{-- Info about actions --}}
                <div
                    class="flex items-start gap-2 p-2.5 rounded-lg
                            bg-surface-container-low dark:bg-zinc-800/40
                            border border-outline-variant/60 dark:border-zinc-700">
                    <flux:icon.information-circle class="size-3.5 text-outline dark:text-zinc-500 shrink-0 mt-0.5" />
                    <div class="text-[10px] text-on-surface-variant dark:text-zinc-400 leading-snug space-y-1">
                        <p>
                            <strong class="text-amber-600 dark:text-amber-400">Request Revision</strong>
                            — sends the proposal back to the author with your comments.
                            Status becomes <strong>Reviewer Revision</strong>.
                        </p>
                        <p>
                            <strong class="text-emerald-600 dark:text-emerald-400">Approve</strong>
                            — finalizes the proposal. Status becomes <strong>Accepted</strong>
                            and <em>cannot be changed anymore</em>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Footer ── --}}
            <div
                class="shrink-0 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between
                        gap-2 px-4 sm:px-5 py-3
                        border-t border-outline-variant/40 dark:border-zinc-700
                        bg-surface-container-lowest dark:bg-zinc-900
                        rounded-b-2xl sm:rounded-b-xl">

                {{-- Left: Cancel --}}
                <button type="button" wire:click="cancelReview"
                    class="w-full sm:w-auto px-3.5 py-1.5 text-[12px] font-medium rounded-md
                        text-on-surface-variant dark:text-zinc-300
                        bg-surface-container-lowest dark:bg-zinc-800
                        border border-outline-variant dark:border-zinc-600
                        hover:bg-surface-container-low dark:hover:bg-zinc-700
                        focus:outline-none focus:ring-2 focus:ring-primary
                        transition-colors duration-150">
                    Cancel
                </button>

                <div class="flex flex-col-reverse sm:flex-row gap-2 w-full sm:w-auto">
                    {{-- Request Revision --}}
                    <button type="button" wire:click="requestRevision" wire:loading.attr="disabled"
                        wire:target="requestRevision,approveReview"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                            px-3.5 py-1.5 text-[12px] font-medium text-white rounded-md
                            bg-gradient-to-r from-amber-500 to-amber-400
                            hover:from-amber-600 hover:to-amber-500
                            focus:outline-none focus:ring-2 focus:ring-amber-500
                            transition-all duration-200
                            disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="requestRevision"
                            class="inline-flex items-center gap-1.5">
                            <flux:icon.pencil-square class="size-3" />
                            Request Revision
                        </span>
                        <span wire:loading.flex wire:target="requestRevision" class="items-center gap-1.5">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Sending...
                        </span>
                    </button>

                    {{-- Approve --}}
                    <button type="button" wire:click="approveReview"
                        wire:confirm="Approve this proposal? This action is permanent and cannot be undone."
                        wire:loading.attr="disabled" wire:target="requestRevision,approveReview"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                            px-3.5 py-1.5 text-[12px] font-medium text-white rounded-md
                            bg-gradient-to-r from-emerald-600 to-emerald-500
                            hover:from-emerald-700 hover:to-emerald-600
                            focus:outline-none focus:ring-2 focus:ring-emerald-500
                            transition-all duration-200
                            disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="approveReview"
                            class="inline-flex items-center gap-1.5">
                            <flux:icon.check-circle class="size-3" />
                            Approve
                        </span>
                        <span wire:loading.flex wire:target="approveReview" class="items-center gap-1.5">
                            <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Approving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ Shared Modals ══════════ --}}
    <x-view-details />
</div>
