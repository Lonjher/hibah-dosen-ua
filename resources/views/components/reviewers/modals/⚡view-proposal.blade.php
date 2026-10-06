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

        $this->form->reset();
        $this->form->noteable_id   = $id;
        $this->form->noteable_type = 'proposal';
        $this->form->reviewer_id   = auth()->id();
        $this->form->is_approved   = false;

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-view-proposal');
    }

    public function addNote(): void
    {
        abort_unless($this->canReviewNotes, 403);

        $this->form->validate();
        $this->form->create();

        if ($this->proposal->status === 'accepted') {
            Proposal::where('id', $this->proposal_id)->update(['status' => 'revised']);
            Flux::toast('Note added. Proposal status reverted to Revised.', variant: 'success');
        } else {
            Flux::toast('Note added successfully.', variant: 'success');
        }

        $this->form->reset('comment', 'recommendation');
        $this->form->is_approved   = false;
        $this->form->noteable_id   = $this->proposal_id;
        $this->form->noteable_type = 'proposal';
        $this->form->reviewer_id   = auth()->id();

        $this->reload();
    }

    public function acceptNote(int $noteId): void
    {
        abort_unless($this->canReviewNotes, 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->firstOrFail();

        if ($note->is_approved) {
            Flux::toast('Note already approved.', variant: 'info');
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
            Flux::toast('All notes approved. Proposal accepted.', variant: 'success');
        } else {
            Proposal::where('id', $this->proposal_id)->update(['status' => 'revised']);
            Flux::toast('Note approved.', variant: 'success');
        }

        $this->reload();
    }

    public function reviseNote(int $noteId): void
    {
        abort_unless($this->canReviewNotes, 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->firstOrFail();

        if (! $note->is_approved) {
            Flux::toast('Note already marked for revision.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => false]);

        Proposal::where('id', $this->proposal_id)->update(['status' => 'revised']);

        Flux::toast('Note marked for revision.', variant: 'success');
        $this->reload();
    }

    public function finalize(): void
    {
        abort_unless($this->canReviewNotes, 403);

        $pendingCount = ReviewerNote::where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->where('is_approved', false)
            ->count();

        if ($pendingCount === 0 && $this->proposal->reviewerNotes()->count() === 0) {
            Flux::toast('No notes to finalize.', variant: 'danger');
            return;
        }

        ReviewerNote::where('noteable_id', $this->proposal_id)
            ->where('noteable_type', 'proposal')
            ->where('reviewer_id', auth()->id())
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        Proposal::where('id', $this->proposal_id)->update(['status' => 'accepted']);

        Flux::toast('Proposal finalized. Status: Accepted.', variant: 'success');
        $this->reload();
    }

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

    <div class="flex max-h-[92vh] w-full sm:max-w-3xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            @php $meta = $proposal->statusMeta(); @endphp

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500
                        px-4 py-3 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.clipboard-document-check class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $proposal->title }}
                            </h3>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="text-[9.5px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[9.5px] text-white/75">
                                    {{ $proposal->is_research ? 'Research' : 'Community Service' }}
                                </span>
                                @if ($pendingNotesCount > 0)
                                    <span class="text-[9.5px] font-semibold px-2 py-0.5 rounded-full
                                                 bg-amber-100 text-amber-800">
                                        {{ $pendingNotesCount }} pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95
                               transition-all duration-150">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

                {{-- ══════════ Meta Grid ══════════ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5">
                        <p class="text-[9.5px] font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                            Author
                        </p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->author?->full_name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5">
                        <p class="text-[9.5px] font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                            Reviewer
                        </p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->reviewer?->full_name ?? 'Not assigned' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5">
                        <p class="text-[9.5px] font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                            Scheme
                        </p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->researchScheme?->name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5">
                        <p class="text-[9.5px] font-semibold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                            Period
                        </p>
                        <p class="text-[11px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $proposal->period?->periode ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- ══════════ Keywords ══════════ --}}
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
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
                                <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic">No keywords.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ══════════ Summary ══════════ --}}
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showSummary = !showSummary"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.document-text class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                Summary
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

                {{-- ══════════ Budget ══════════ --}}
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showBudget = !showBudget"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.banknotes class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                Budget
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
                            Remaining: Rp {{ number_format($budgetRemaining, 0, ',', '.') }}
                        </p>

                        <div class="space-y-1.5">
                            @forelse ($proposal->budgetProposals as $item)
                                <div class="flex items-center justify-between gap-3 px-3 py-1.5 rounded-full
                                            bg-slate-50 dark:bg-zinc-800/40
                                            border border-slate-100 dark:border-zinc-800/60">
                                    <span class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate pl-0.5">
                                        {{ $item->item_name }}
                                    </span>
                                    <span class="text-[10.5px] font-semibold text-slate-900 dark:text-zinc-100 whitespace-nowrap pr-0.5">
                                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic text-center py-2">
                                    No budget items yet.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ══════════ Admin Notes ══════════ --}}
                @if ($proposal->adminNotes->isNotEmpty())
                    <div class="rounded-2xl border border-amber-200 dark:border-amber-800 overflow-hidden">
                        <button type="button" @click="showAdminNotes = !showAdminNotes"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                                   bg-amber-50 dark:bg-amber-900/20
                                   hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                            <span class="flex items-center gap-2">
                                <flux:icon.chat-bubble-left-right class="size-3.5 text-amber-600 dark:text-amber-400" />
                                <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                    Admin Notes
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
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
                                <div class="rounded-2xl bg-amber-50 dark:bg-amber-900/20
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
                                        <p class="text-[10.5px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="text-[9.5px] text-amber-700 dark:text-amber-300 mt-1">
                                            <span class="font-semibold">Recommendation:</span> {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ══════════ Reviewer Notes ══════════ --}}
                <div class="rounded-2xl border border-violet-200 dark:border-violet-800 overflow-hidden">
                    <button type="button" @click="showReviewerNotes = !showReviewerNotes"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-violet-50 dark:bg-violet-900/20
                               hover:bg-violet-100 dark:hover:bg-violet-900/30 transition-colors">
                        <span class="flex items-center gap-2 flex-wrap">
                            <flux:icon.clipboard-document-check class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-violet-800 dark:text-violet-300">
                                Reviewer Notes
                            </span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                         bg-violet-200 text-violet-800
                                         dark:bg-violet-800 dark:text-violet-200">
                                {{ $proposal->reviewerNotes->count() }}
                            </span>
                            @if ($pendingNotesCount > 0)
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
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

                        {{-- List --}}
                        <div class="px-3 py-3 space-y-2 max-h-80 overflow-y-auto">
                            @forelse ($proposal->reviewerNotes as $note)
                                <div wire:key="note-{{ $note->id }}"
                                    class="rounded-2xl border p-3
                                        {{ $note->is_approved
                                            ? 'bg-emerald-50 dark:bg-emerald-900/15 border-emerald-200 dark:border-emerald-800'
                                            : 'bg-amber-50 dark:bg-amber-900/15 border-amber-200 dark:border-amber-800' }}">

                                    <div class="flex items-center justify-between gap-2 mb-1.5 flex-wrap">
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
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase
                                                    {{ $note->is_approved
                                                        ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                        : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                            {{ $note->is_approved ? 'Approved' : 'Revision' }}
                                        </span>
                                    </div>

                                    @if ($note->comment)
                                        <p class="text-[10.5px] leading-snug whitespace-pre-wrap
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
                                            <p class="text-[9.5px]
                                                    {{ $note->is_approved
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-amber-700 dark:text-amber-300' }}">
                                                <span class="font-semibold">Recommendation:</span> {{ $note->recommendation }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($canReviewNotes)
                                        <div class="mt-2 pt-2 border-t flex justify-end gap-1.5
                                                    {{ $note->is_approved
                                                        ? 'border-emerald-200 dark:border-emerald-800'
                                                        : 'border-amber-200 dark:border-amber-800' }}">
                                            @if (! $note->is_approved)
                                                <button type="button"
                                                    wire:click="acceptNote({{ $note->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="acceptNote,reviseNote"
                                                    class="inline-flex items-center gap-1
                                                           px-3 py-1 rounded-full
                                                           text-[10px] font-semibold text-white
                                                           bg-emerald-600/90 hover:bg-emerald-600
                                                           shadow-sm shadow-emerald-500/20 hover:shadow-sm hover:shadow-emerald-500/30
                                                           hover:scale-[1.02] active:scale-[0.97]
                                                           disabled:opacity-60 disabled:hover:scale-100
                                                           transition-all duration-150">
                                                    <span wire:loading.remove wire:target="acceptNote"
                                                        class="inline-flex items-center gap-1">
                                                        <flux:icon.check-circle class="size-2.5" />
                                                        Accept
                                                    </span>
                                                    <span wire:loading.flex wire:target="acceptNote">
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
                                                           px-3 py-1 rounded-full
                                                           text-[10px] font-semibold text-white
                                                           bg-amber-500/90 hover:bg-amber-500
                                                           shadow-sm shadow-amber-500/20 hover:shadow-sm hover:shadow-amber-500/30
                                                           hover:scale-[1.02] active:scale-[0.97]
                                                           disabled:opacity-60 disabled:hover:scale-100
                                                           transition-all duration-150">
                                                    <span wire:loading.remove wire:target="reviseNote"
                                                        class="inline-flex items-center gap-1">
                                                        <flux:icon.pencil-square class="size-2.5" />
                                                        Revise
                                                    </span>
                                                    <span wire:loading.flex wire:target="reviseNote">
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
                                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-zinc-800
                                                flex items-center justify-center mx-auto mb-2">
                                        <flux:icon.clipboard-document-check class="size-5 text-slate-300 dark:text-zinc-600" />
                                    </div>
                                    <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                                        No notes yet. Add one below.
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        {{-- ADD NOTE FORM --}}
                        @if ($canReviewNotes)
                            <div class="border-t border-violet-200 dark:border-violet-800
                                        bg-violet-50/50 dark:bg-violet-900/10">

                                <button type="button" @click="showAddNote = !showAddNote"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                                           hover:bg-violet-100 dark:hover:bg-violet-900/20 transition-colors">
                                    <span class="flex items-center gap-2">
                                        <flux:icon.plus-circle class="size-3.5 text-violet-600 dark:text-violet-400" />
                                        <span class="text-[11px] font-semibold text-violet-800 dark:text-violet-300">
                                            Add Note
                                        </span>
                                    </span>
                                    <flux:icon.chevron-down
                                        class="size-3.5 text-violet-600 dark:text-violet-400 transition-transform duration-200"
                                        ::class="showAddNote && 'rotate-180'" />
                                </button>

                                <div x-show="showAddNote" x-collapse>
                                    <div class="px-3 pb-3 space-y-3">

                                        <x-textarea
                                            wire:model="form.comment"
                                            label="Note"
                                            required
                                            rows="3"
                                            rounded="full"
                                            color="violet"
                                            placeholder="Write your review or revision note..." />

                                        <x-input
                                            wire:model="form.recommendation"
                                            label="Recommendation (optional)"
                                            rounded="full"
                                            placeholder="Suggestion for the author..." />

                                        <div class="flex items-start gap-2 p-2.5 rounded-2xl
                                                    bg-violet-100/60 dark:bg-violet-900/30
                                                    border border-violet-200 dark:border-violet-800">
                                            <flux:icon.information-circle class="size-3.5 text-violet-600 dark:text-violet-400 shrink-0 mt-0.5" />
                                            <p class="text-[10px] text-violet-700 dark:text-violet-300 leading-snug">
                                                New notes start as <strong>pending</strong>.
                                                Click <strong>Accept</strong> to approve, or
                                                <strong>Revise</strong> to request changes.
                                            </p>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="button" wire:click="addNote"
                                                wire:loading.attr="disabled" wire:target="addNote"
                                                class="inline-flex items-center gap-1
                                                       px-3 py-1.5 rounded-full
                                                       text-[11px] font-semibold text-white
                                                       bg-violet-600/90 hover:bg-violet-600
                                                       shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                                                       disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                                                       hover:scale-[1.02] active:scale-[0.97]
                                                       transition-all duration-150">
                                                <span wire:loading.remove wire:target="addNote"
                                                    class="inline-flex items-center gap-1">
                                                    <flux:icon.plus class="size-3" />
                                                    Save Note
                                                </span>
                                                <span wire:loading.flex wire:target="addNote" class="items-center gap-1">
                                                    <svg class="animate-spin size-3" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4" />
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                    </svg>
                                                    Saving...
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
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-violet-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Close
                </button>

                @if ($canReviewNotes && $proposal->reviewerNotes->count() > 0)
                    <button type="button" wire:click="finalize"
                        wire:confirm="Approve all notes and finalize proposal?"
                        wire:loading.attr="disabled" wire:target="finalize"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                               px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                               bg-violet-600/90 hover:bg-violet-600
                               shadow-sm shadow-violet-500/20 hover:shadow-sm hover:shadow-violet-500/30
                               disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                               hover:scale-[1.02] active:scale-[0.97]
                               transition-all duration-150">
                        <svg wire:loading wire:target="finalize" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        <flux:icon.check-circle wire:loading.remove wire:target="finalize" class="size-3.5" />
                        <span wire:loading.remove wire:target="finalize">Finalize (Approve All)</span>
                        <span wire:loading wire:target="finalize">Processing...</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
