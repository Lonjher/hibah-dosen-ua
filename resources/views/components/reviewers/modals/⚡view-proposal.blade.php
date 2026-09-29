<?php

use App\Livewire\Forms\ReviewerNoteForm;
use App\Models\Proposal;
use App\Models\ReviewerNote;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $proposal_id = null;
    public ?Proposal $proposal = null;

    public ReviewerNoteForm $form;

    public bool $canReviewNotes = false;

    #[On('open-view-proposal')]
    public function load(int $id): void
    {
        $this->proposal_id = $id;
        $this->reload();

        // Setup form
        $this->form->reset();
        $this->form->noteable_id   = $id;
        $this->form->noteable_type = 'proposal';
        $this->form->reviewer_id   = auth()->id();
        $this->form->is_approved   = false;

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-view-proposal');
    }

    // ═══════════════ ACTION: Add Note ═══════════════
    public function addNote(): void
    {
        abort_unless($this->canReviewNotes, 403);

        $this->form->validate();
        $this->form->create();

        // Kalau proposal sudah accepted → ubah kembali ke revised
        if ($this->proposal->status === 'accepted') {
            Proposal::where('id', $this->proposal_id)->update([
                'status' => 'revised',
            ]);
            Flux::toast('Catatan ditambahkan. Status proposal kembali ke Revised.', variant: 'success');
        } else {
            Flux::toast('Catatan berhasil ditambahkan.', variant: 'success');
        }

        // Reset form (kecuali konteks)
        $this->form->reset('comment', 'recommendation');
        $this->form->is_approved   = false;
        $this->form->noteable_id   = $this->proposal_id;
        $this->form->noteable_type = 'proposal';
        $this->form->reviewer_id   = auth()->id();

        $this->reload();
    }

    // ═══════════════ ACTION: Accept Note ═══════════════
    public function acceptNote(int $noteId): void
    {
        abort_unless($this->canReviewNotes, 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->firstOrFail();

        if ($note->is_approved) {
            Flux::toast('Catatan sudah di-approve.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => true]);

        $stillPending = ReviewerNote::where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->where('is_approved', false)
            ->exists();

        if (! $stillPending) {
            Proposal::where('id', $this->proposal_id)->update(['status' => 'accepted']);
            Flux::toast('Semua catatan di-approve. Proposal accepted.', variant: 'success');
        } else {
            Proposal::where('id', $this->proposal_id)->update(['status' => 'revised']);
            Flux::toast('Catatan di-approve.', variant: 'success');
        }

        $this->reload();
    }

    // ═══════════════ ACTION: Revise Note ═══════════════
    public function reviseNote(int $noteId): void
    {
        abort_unless($this->canReviewNotes, 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->firstOrFail();

        if (! $note->is_approved) {
            Flux::toast('Catatan sudah ditandai revisi.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => false]);

        Proposal::where('id', $this->proposal_id)->update(['status' => 'revised']);

        Flux::toast('Catatan ditandai butuh revisi.', variant: 'success');
        $this->reload();
    }

    // ═══════════════ ACTION: Finalize ═══════════════
    public function finalize(): void
    {
        abort_unless($this->canReviewNotes, 403);

        $pendingCount = ReviewerNote::where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->where('is_approved', false)
            ->count();

        if ($pendingCount === 0 && $this->proposal->reviewerNotes()->count() === 0) {
            Flux::toast('Belum ada catatan untuk di-finalize.', variant: 'danger');
            return;
        }

        ReviewerNote::where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        Proposal::where('id', $this->proposal_id)->update(['status' => 'accepted']);

        Flux::toast('Proposal berhasil di-finalize. Status: Accepted.', variant: 'success');
        $this->reload();
    }

    // ═══════════════ HELPERS ═══════════════
    protected function reload(): void
    {
        $this->proposal = Proposal::with([
            'author',
            'researchScheme',
            'period',
            'reviewer',
            'budgetProposals',
            'adminNotes' => fn ($q) => $q->latest()->with('admin'),
            'reviewerNotes' => fn ($q) => $q->latest()->with('reviewer'),
        ])->findOrFail($this->proposal_id);

        // Refresh flag — boleh review walau accepted, asal bukan rejected
        $this->canReviewNotes = auth()->user()->role?->role_code === 'REVIEWER'
            && $this->proposal->reviewer_id === auth()->id()
            && $this->proposal->status !== 'rejected';
    }

    public function with(): array
    {
        $budgetTotal = 0;
        $budgetLimit = 0;

        if ($this->proposal) {
            $budgetTotal = (int) $this->proposal->budgetProposals->sum('amount');
            $budgetLimit = (int) ($this->proposal->researchScheme?->budget_limit ?? 0);
        }

        $pendingNotesCount = $this->proposal
            ? $this->proposal->reviewerNotes->where('is_approved', false)->count()
            : 0;

        return [
            'budgetTotal'       => $budgetTotal,
            'budgetLimit'       => $budgetLimit,
            'budgetRemaining'   => $budgetLimit - $budgetTotal,
            'budgetPercent'     => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
            'pendingNotesCount' => $pendingNotesCount,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        showKeywords: false,
        showSummary: false,
        showBudget: false,
        showReviewerNotes: true,
        showAdminNotes: false,
        showAddNote: false,
        init() {
            window.addEventListener('show-view-proposal', () => { this.show = true; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-3xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            @php $meta = $proposal->statusMeta(); @endphp

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.clipboard-document-check class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $proposal->title }}
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[10px] text-white/70">
                                    {{ $proposal->is_research ? 'Penelitian' : 'Pengabdian' }}
                                </span>
                                @if ($pendingNotesCount > 0)
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                                                 bg-amber-100 text-amber-800">
                                        {{ $pendingNotesCount }} pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-3">

                {{-- ══════════ Meta Grid ══════════ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Author</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->author?->full_name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Reviewer</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->reviewer?->full_name ?? 'Belum di-assign' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Skema</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->researchScheme?->name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Periode</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->period?->periode ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- ══════════ Keywords (Collapsable) ══════════ --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showKeywords = !showKeywords"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.tag class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                Keywords
                            </span>
                        </span>
                        <flux:icon.chevron-down
                            class="size-3.5 text-slate-500 dark:text-zinc-400 transition-transform duration-200"
                            ::class="showKeywords && 'rotate-180'" />
                    </button>
                    <div x-show="showKeywords" x-collapse
                        class="px-3 py-3 border-t border-slate-200 dark:border-zinc-700">
                        <div class="flex flex-wrap gap-1.5">
                            @forelse (array_filter(array_map('trim', explode(',', $proposal->keywords ?? ''))) as $kw)
                                <span class="text-[10px] px-2 py-0.5 rounded-full
                                             bg-violet-50 dark:bg-violet-900/20
                                             text-violet-700 dark:text-violet-300
                                             border border-violet-200 dark:border-violet-800">
                                    {{ $kw }}
                                </span>
                            @empty
                                <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic">Tidak ada keyword.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ══════════ Summary (Collapsable) ══════════ --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showSummary = !showSummary"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.document-text class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                Ringkasan
                            </span>
                        </span>
                        <flux:icon.chevron-down
                            class="size-3.5 text-slate-500 dark:text-zinc-400 transition-transform duration-200"
                            ::class="showSummary && 'rotate-180'" />
                    </button>
                    <div x-show="showSummary" x-collapse
                        class="px-3 py-3 border-t border-slate-200 dark:border-zinc-700">
                        <p class="text-[11px] text-slate-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap">
                            {{ $proposal->summary }}
                        </p>
                    </div>
                </div>

                {{-- ══════════ Budget (Collapsable) ══════════ --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showBudget = !showBudget"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.banknotes class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                Anggaran
                            </span>
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold text-slate-700 dark:text-zinc-300">
                                Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                                <span class="text-slate-400 font-normal">
                                    / Rp {{ number_format($budgetLimit, 0, ',', '.') }}
                                </span>
                            </span>
                            <flux:icon.chevron-down
                                class="size-3.5 text-slate-500 dark:text-zinc-400 transition-transform duration-200"
                                ::class="showBudget && 'rotate-180'" />
                        </span>
                    </button>
                    <div x-show="showBudget" x-collapse
                        class="px-3 py-3 border-t border-slate-200 dark:border-zinc-700 space-y-3">

                        <div class="h-1.5 rounded-full bg-slate-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500 transition-all"
                                style="width: {{ $budgetPercent }}%"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                            Sisa: Rp {{ number_format($budgetRemaining, 0, ',', '.') }}
                        </p>

                        <div class="space-y-1.5">
                            @forelse ($proposal->budgetProposals as $item)
                                <div class="flex items-center justify-between gap-3 p-2 rounded-md
                                            bg-slate-50 dark:bg-zinc-800/40">
                                    <span class="text-[11px] text-slate-700 dark:text-zinc-300 truncate">
                                        {{ $item->item_name }}
                                    </span>
                                    <span class="text-[11px] font-medium text-slate-900 dark:text-zinc-100 whitespace-nowrap">
                                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic text-center py-2">
                                    Belum ada item anggaran.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ══════════ Admin Notes (Collapsable) ══════════ --}}
                @if ($proposal->adminNotes->isNotEmpty())
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 overflow-hidden">
                        <button type="button" @click="showAdminNotes = !showAdminNotes"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                                   bg-amber-50 dark:bg-amber-900/20
                                   hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                            <span class="flex items-center gap-2">
                                <flux:icon.chat-bubble-left-right class="size-3.5 text-amber-600 dark:text-amber-400" />
                                <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                    Admin Notes
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                             bg-amber-200 text-amber-800
                                             dark:bg-amber-800 dark:text-amber-200">
                                    {{ $proposal->adminNotes->count() }}
                                </span>
                            </span>
                            <flux:icon.chevron-down
                                class="size-3.5 text-amber-600 dark:text-amber-400 transition-transform duration-200"
                                ::class="showAdminNotes && 'rotate-180'" />
                        </button>
                        <div x-show="showAdminNotes" x-collapse
                            class="px-3 py-3 border-t border-amber-200 dark:border-amber-800 space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($proposal->adminNotes as $note)
                                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20
                                            border border-amber-200 dark:border-amber-800 p-2.5">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] font-semibold text-amber-800 dark:text-amber-300">
                                            {{ $note->admin?->full_name ?? 'Admin' }}
                                        </span>
                                        <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                            {{ $note->created_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[11px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                                            <span class="font-semibold">Rekomendasi:</span> {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ══════════ Reviewer Notes (Collapsable) ══════════ --}}
                <div class="rounded-lg border border-violet-200 dark:border-violet-800 overflow-hidden">
                    <button type="button" @click="showReviewerNotes = !showReviewerNotes"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-violet-50 dark:bg-violet-900/20
                               hover:bg-violet-100 dark:hover:bg-violet-900/30 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.clipboard-document-check class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-violet-800 dark:text-violet-300">
                                Reviewer Notes
                            </span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                         bg-violet-200 text-violet-800
                                         dark:bg-violet-800 dark:text-violet-200">
                                {{ $proposal->reviewerNotes->count() }}
                            </span>
                            @if ($pendingNotesCount > 0)
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                             bg-amber-200 text-amber-800
                                             dark:bg-amber-800 dark:text-amber-200">
                                    {{ $pendingNotesCount }} pending
                                </span>
                            @endif
                        </span>
                        <flux:icon.chevron-down
                            class="size-3.5 text-violet-600 dark:text-violet-400 transition-transform duration-200"
                            ::class="showReviewerNotes && 'rotate-180'" />
                    </button>
                    <div x-show="showReviewerNotes" x-collapse
                        class="border-t border-violet-200 dark:border-violet-800">

                        {{-- List Notes --}}
                        <div class="px-3 py-3 space-y-2 max-h-80 overflow-y-auto">
                            @forelse ($proposal->reviewerNotes as $note)
                                <div wire:key="note-{{ $note->id }}"
                                    class="rounded-lg border p-3
                                        {{ $note->is_approved
                                            ? 'bg-emerald-50 dark:bg-emerald-900/15 border-emerald-200 dark:border-emerald-800'
                                            : 'bg-amber-50 dark:bg-amber-900/15 border-amber-200 dark:border-amber-800' }}">

                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full
                                                        flex items-center justify-center text-[9px] font-bold
                                                        {{ $note->is_approved
                                                            ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                            : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                {{ strtoupper(substr($note->reviewer?->full_name ?? 'R', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-semibold truncate
                                                        {{ $note->is_approved
                                                            ? 'text-emerald-800 dark:text-emerald-300'
                                                            : 'text-amber-800 dark:text-amber-300' }}">
                                                    {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                                </p>
                                                <p class="text-[9px] {{ $note->is_approved
                                                            ? 'text-emerald-600 dark:text-emerald-400'
                                                            : 'text-amber-600 dark:text-amber-400' }}">
                                                    {{ $note->created_at?->format('d M Y, H:i') }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase
                                                    {{ $note->is_approved
                                                        ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                        : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                            {{ $note->is_approved ? 'Approved' : 'Revision' }}
                                        </span>
                                    </div>

                                    @if ($note->comment)
                                        <p class="text-[11px] leading-snug whitespace-pre-wrap
                                                {{ $note->is_approved
                                                    ? 'text-emerald-900 dark:text-emerald-100'
                                                    : 'text-amber-900 dark:text-amber-100' }}">
                                            {{ $note->comment }}
                                        </p>
                                    @endif

                                    @if ($note->recommendation)
                                        <div class="mt-2 pt-2 border-t
                                                    {{ $note->is_approved
                                                        ? 'border-emerald-200 dark:border-emerald-800'
                                                        : 'border-amber-200 dark:border-amber-800' }}">
                                            <p class="text-[10px]
                                                    {{ $note->is_approved
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-amber-700 dark:text-amber-300' }}">
                                                <span class="font-semibold">Rekomendasi:</span> {{ $note->recommendation }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($canReviewNotes)
                                        <div class="mt-2 pt-2 border-t
                                                    {{ $note->is_approved
                                                        ? 'border-emerald-200 dark:border-emerald-800'
                                                        : 'border-amber-200 dark:border-amber-800' }}
                                                    flex justify-end gap-1.5">
                                            @if (! $note->is_approved)
                                                <button type="button"
                                                    wire:click="acceptNote({{ $note->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="acceptNote,reviseNote"
                                                    class="inline-flex items-center gap-1
                                                        px-2.5 py-1 rounded-md
                                                        text-[10px] font-semibold text-white
                                                        bg-gradient-to-r from-emerald-600 to-emerald-500
                                                        hover:from-emerald-700 hover:to-emerald-600
                                                        shadow-sm shadow-emerald-500/20
                                                        disabled:opacity-60 transition-all">
                                                    <span wire:loading.remove wire:target="acceptNote"
                                                        class="inline-flex items-center gap-1">
                                                        <flux:icon.check-circle class="size-2.5" />
                                                        Accept
                                                    </span>
                                                    <span wire:loading.flex wire:target="acceptNote"
                                                        class="items-center gap-1">
                                                        <svg class="animate-spin size-2.5" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                stroke="currentColor" stroke-width="4" />
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                        </svg>
                                                    </span>
                                                </button>
                                            @else
                                                <button type="button"
                                                    wire:click="reviseNote({{ $note->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="acceptNote,reviseNote"
                                                    class="inline-flex items-center gap-1
                                                        px-2.5 py-1 rounded-md
                                                        text-[10px] font-semibold text-white
                                                        bg-gradient-to-r from-amber-500 to-amber-400
                                                        hover:from-amber-600 hover:to-amber-500
                                                        shadow-sm shadow-amber-500/20
                                                        disabled:opacity-60 transition-all">
                                                    <span wire:loading.remove wire:target="reviseNote"
                                                        class="inline-flex items-center gap-1">
                                                        <flux:icon.pencil-square class="size-2.5" />
                                                        Revise
                                                    </span>
                                                    <span wire:loading.flex wire:target="reviseNote"
                                                        class="items-center gap-1">
                                                        <svg class="animate-spin size-2.5" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                stroke="currentColor" stroke-width="4" />
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                        </svg>
                                                    </span>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-6">
                                    <flux:icon.clipboard-document-check class="size-8 text-slate-300 dark:text-zinc-600 mx-auto mb-2" />
                                    <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                        Belum ada catatan. Tambahkan catatan di bawah.
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        {{-- ══════════ ADD NOTE FORM (Reviewer only) ══════════ --}}
                        @if ($canReviewNotes)
                            <div class="border-t border-violet-200 dark:border-violet-800
                                        bg-violet-50/50 dark:bg-violet-900/10">

                                {{-- Toggle Button --}}
                                <button type="button" @click="showAddNote = !showAddNote"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                                           hover:bg-violet-100 dark:hover:bg-violet-900/20 transition-colors">
                                    <span class="flex items-center gap-2">
                                        <flux:icon.plus-circle class="size-3.5 text-violet-600 dark:text-violet-400" />
                                        <span class="text-[11px] font-semibold text-violet-800 dark:text-violet-300">
                                            Tambah Catatan
                                        </span>
                                    </span>
                                    <flux:icon.chevron-down
                                        class="size-3.5 text-violet-600 dark:text-violet-400 transition-transform duration-200"
                                        ::class="showAddNote && 'rotate-180'" />
                                </button>

                                {{-- Form --}}
                                <div x-show="showAddNote" x-collapse>
                                    <div class="px-3 pb-3 space-y-3">

                                        {{-- Comment --}}
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                                Catatan <span class="text-rose-500">*</span>
                                            </label>
                                            <textarea wire:model="form.comment" rows="3"
                                                placeholder="Tulis catatan review atau revisi..."
                                                class="block w-full rounded-md shadow-sm text-[11px] resize-none
                                                       border-slate-300 dark:border-zinc-600
                                                       bg-white dark:bg-zinc-800
                                                       text-slate-900 dark:text-zinc-100
                                                       focus:border-violet-500 focus:ring-violet-500 py-2 px-2.5"></textarea>
                                            @error('form.comment')
                                                <p class="mt-1 text-[10px] text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Recommendation --}}
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                                Rekomendasi <span class="text-slate-400">(opsional)</span>
                                            </label>
                                            <input type="text" wire:model="form.recommendation"
                                                placeholder="Saran untuk author..."
                                                class="block w-full rounded-md shadow-sm text-[11px]
                                                       border-slate-300 dark:border-zinc-600
                                                       bg-white dark:bg-zinc-800
                                                       text-slate-900 dark:text-zinc-100
                                                       focus:border-violet-500 focus:ring-violet-500 py-2 px-2.5" />
                                        </div>

                                        {{-- Info --}}
                                        <div class="flex items-start gap-2 p-2 rounded-md
                                                    bg-violet-100/60 dark:bg-violet-900/30
                                                    border border-violet-200 dark:border-violet-800">
                                            <flux:icon.information-circle class="size-3 text-violet-600 dark:text-violet-400 shrink-0 mt-0.5" />
                                            <p class="text-[10px] text-violet-700 dark:text-violet-300 leading-snug">
                                                Catatan baru akan berstatus <strong>pending</strong>.
                                                Klik <strong>Accept</strong> pada catatan untuk menyetujui,
                                                atau <strong>Revise</strong> untuk meminta perbaikan.
                                            </p>
                                        </div>

                                        {{-- Submit --}}
                                        <div class="flex justify-end">
                                            <button type="button" wire:click="addNote"
                                                wire:loading.attr="disabled" wire:target="addNote"
                                                class="inline-flex items-center gap-1
                                                    px-3 py-1.5 rounded-md
                                                    text-[11px] font-semibold text-white
                                                    bg-gradient-to-r from-violet-600 to-violet-500
                                                    hover:from-violet-700 hover:to-violet-600
                                                    shadow-sm shadow-violet-500/20
                                                    disabled:opacity-60 transition-all">
                                                <span wire:loading.remove wire:target="addNote"
                                                    class="inline-flex items-center gap-1">
                                                    <flux:icon.plus class="size-3" />
                                                    Simpan Catatan
                                                </span>
                                                <span wire:loading.flex wire:target="addNote"
                                                    class="items-center gap-1">
                                                    <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4" />
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                    </svg>
                                                    Menyimpan...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">

                <flux:button type="button" @click="show = false" variant="ghost" size="sm">
                    Tutup
                </flux:button>

                {{-- Finalize Button (Reviewer only, kalau ada pending note) --}}
                @if ($canReviewNotes && $proposal->reviewerNotes->count() > 0)
                    <flux:button type="button" wire:click="finalize"
                        variant="primary" size="sm"
                        wire:confirm="Approve semua catatan dan finalisasi proposal?"
                        wire:loading.attr="disabled" wire:target="finalize">
                        <span wire:loading.remove wire:target="finalize"
                            class="inline-flex items-center gap-1">
                            <flux:icon.check-circle class="size-3.5" />
                            Finalize (Approve All)
                        </span>
                        <span wire:loading.flex wire:target="finalize" class="items-center gap-1">
                            Memproses...
                        </span>
                    </flux:button>
                @endif
            </div>
        @endif
    </div>
</div>
