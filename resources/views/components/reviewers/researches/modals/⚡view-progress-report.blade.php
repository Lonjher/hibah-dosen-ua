<?php

use App\Livewire\Forms\ReviewerNoteForm;
use App\Models\ProgressReport;
use App\Models\ReviewerNote;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $progress_report_id = null;
    public ?ProgressReport $progressReport = null;

    public ReviewerNoteForm $form;

    public bool $canReviewNotes = false;

    #[On('open-view-progress-report')]
    public function load(int $id): void
    {
        // Guard: hanya reviewer yang di-assign ke progress report ini
        $report = ProgressReport::where('reviewer_id', Auth::id())->findOrFail($id);

        $this->progress_report_id = $report->id;
        $this->reload();

        // Setup form
        $this->form->reset();
        $this->form->noteable_id   = $id;
        $this->form->noteable_type = 'progress_report';
        $this->form->reviewer_id   = Auth::id();
        $this->form->is_approved   = false;

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-view-progress-report');
    }

    // ═══════════════ ACTION: Add Note ═══════════════

    public function addNote(): void
    {
        abort_unless($this->canReviewNotes, 403);

        $this->form->validate();
        $this->form->create();

        if ($this->progressReport->status === 'accepted') {
            ProgressReport::where('id', $this->progress_report_id)->update([
                'status' => 'revised',
            ]);
            Flux::toast('Catatan ditambahkan. Status progress report kembali ke Revised.', variant: 'success');
        } else {
            Flux::toast('Catatan berhasil ditambahkan.', variant: 'success');
        }

        $this->form->reset('comment', 'recommendation');
        $this->form->is_approved   = false;
        $this->form->noteable_id   = $this->progress_report_id;
        $this->form->noteable_type = 'progress_report';
        $this->form->reviewer_id   = Auth::id();

        $this->reload();
    }

    // ═══════════════ ACTION: Accept Note ═══════════════

    public function acceptNote(int $noteId): void
    {
        abort_unless($this->canReviewNotes, 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->progress_report_id)
            ->where('noteable_type', 'progress_report')
            ->where('reviewer_id', Auth::id())
            ->firstOrFail();

        if ($note->is_approved) {
            Flux::toast('Catatan sudah di-approve.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => true]);

        $stillPending = ReviewerNote::where('noteable_id', $this->progress_report_id)
            ->where('noteable_type', 'progress_report')
            ->where('reviewer_id', Auth::id())
            ->where('is_approved', false)
            ->exists();

        if (! $stillPending) {
            ProgressReport::where('id', $this->progress_report_id)->update([
                'status'      => 'accepted',
                'reviewed_at' => now(),
            ]);
            Flux::toast('Semua catatan di-approve. Progress report accepted.', variant: 'success');
        } else {
            ProgressReport::where('id', $this->progress_report_id)->update([
                'status' => 'revised',
            ]);
            Flux::toast('Catatan di-approve.', variant: 'success');
        }

        $this->reload();
    }

    // ═══════════════ ACTION: Revise Note ═══════════════

    public function reviseNote(int $noteId): void
    {
        abort_unless($this->canReviewNotes, 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->progress_report_id)
            ->where('noteable_type', 'progress_report')
            ->where('reviewer_id', Auth::id())
            ->firstOrFail();

        if (! $note->is_approved) {
            Flux::toast('Catatan sudah ditandai revisi.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => false]);

        ProgressReport::where('id', $this->progress_report_id)->update([
            'status' => 'revised',
        ]);

        Flux::toast('Catatan ditandai butuh revisi.', variant: 'success');
        $this->reload();
    }

    // ═══════════════ ACTION: Finalize ═══════════════

    public function finalize(): void
    {
        abort_unless($this->canReviewNotes, 403);

        $pendingCount = ReviewerNote::where('noteable_id', $this->progress_report_id)
            ->where('noteable_type', 'progress_report')
            ->where('reviewer_id', Auth::id())
            ->where('is_approved', false)
            ->count();

        if ($pendingCount === 0 && $this->progressReport->reviewerNotes()->count() === 0) {
            Flux::toast('Belum ada catatan untuk di-finalize.', variant: 'danger');
            return;
        }

        ReviewerNote::where('noteable_id', $this->progress_report_id)
            ->where('noteable_type', 'progress_report')
            ->where('reviewer_id', Auth::id())
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        ProgressReport::where('id', $this->progress_report_id)->update([
            'status'      => 'accepted',
            'reviewed_at' => now(),
        ]);

        Flux::toast('Progress report berhasil di-finalize. Status: Accepted.', variant: 'success');
        $this->reload();
    }

    // ═══════════════ HELPERS ═══════════════

    protected function reload(): void
    {
        $this->progressReport = ProgressReport::with([
            'proposal.researchScheme',
            'proposal.author',
            'reviewer',
            'reviewerNotes' => fn ($q) => $q->latest()->with('reviewer'),
        ])->findOrFail($this->progress_report_id);

        $this->canReviewNotes = auth()->user()->role?->role_code === 'REVIEWER'
            && $this->progressReport->reviewer_id === Auth::id()
            && $this->progressReport->status !== 'rejected';
    }

    public function with(): array
    {
        $pendingNotesCount = $this->progressReport
            ? $this->progressReport->reviewerNotes->where('is_approved', false)->count()
            : 0;

        return [
            'pendingNotesCount' => $pendingNotesCount,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        showKeyword: true,
        showSummary: true,
        showFiles: true,
        showReviewerNotes: true,
        showAddNote: false,
        init() {
            window.addEventListener('show-view-progress-report', () => { this.show = true; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-3xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($progressReport)
            @php $meta = $progressReport->statusMeta(); @endphp

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.document-chart-bar class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $progressReport->proposal?->title ?? '—' }}
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[10px] text-white/70">Progress Report</span>
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

                {{-- Meta Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Author</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $progressReport->proposal?->author?->full_name ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Submitted</p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                            {{ $progressReport->created_at?->format('d M Y, H:i') ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Keyword --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showKeyword = !showKeyword"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.tag class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">Keyword</span>
                        </span>
                        <flux:icon.chevron-down
                            class="size-3.5 text-slate-500 dark:text-zinc-400 transition-transform duration-200"
                            ::class="showKeyword && 'rotate-180'" />
                    </button>
                    <div x-show="showKeyword" x-collapse
                        class="px-3 py-3 border-t border-slate-200 dark:border-zinc-700">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                    bg-violet-50 dark:bg-violet-900/20
                                    border border-violet-200 dark:border-violet-800">
                            <flux:icon.tag class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[12px] font-semibold text-violet-700 dark:text-violet-300">
                                {{ $progressReport->keyword }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showSummary = !showSummary"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.document-text class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">Ringkasan</span>
                        </span>
                        <flux:icon.chevron-down
                            class="size-3.5 text-slate-500 dark:text-zinc-400 transition-transform duration-200"
                            ::class="showSummary && 'rotate-180'" />
                    </button>
                    <div x-show="showSummary" x-collapse
                        class="px-3 py-3 border-t border-slate-200 dark:border-zinc-700">
                        <p class="text-[11px] text-slate-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap">
                            {{ $progressReport->summary }}
                        </p>
                    </div>
                </div>

                {{-- Files --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                    <button type="button" @click="showFiles = !showFiles"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5
                               bg-slate-50 dark:bg-zinc-800/40
                               hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <flux:icon.paper-clip class="size-3.5 text-blue-600 dark:text-blue-400" />
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">Attachment</span>
                        </span>
                        <flux:icon.chevron-down
                            class="size-3.5 text-slate-500 dark:text-zinc-400 transition-transform duration-200"
                            ::class="showFiles && 'rotate-180'" />
                    </button>
                    <div x-show="showFiles" x-collapse
                        class="px-3 py-3 border-t border-slate-200 dark:border-zinc-700">
                        <div class="flex flex-wrap gap-2">
                            @if ($progressReport->report_path)
                                <a href="{{ Storage::disk('public')->url($progressReport->report_path) }}" target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                           bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20
                                           border border-rose-200 dark:border-rose-800
                                           text-rose-700 dark:text-rose-300 text-[11px] font-semibold">
                                    <flux:icon.document-text class="size-4" />
                                    View Report PDF
                                </a>
                            @endif

                            @if ($progressReport->ppt_path)
                                <a href="{{ Storage::disk('public')->url($progressReport->ppt_path) }}" target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                           bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20
                                           border border-orange-200 dark:border-orange-800
                                           text-orange-700 dark:text-orange-300 text-[11px] font-semibold">
                                    <flux:icon.presentation-chart-bar class="size-4" />
                                    View Presentation
                                </a>
                            @endif

                            @if (! $progressReport->report_path && ! $progressReport->ppt_path)
                                <p class="text-[11px] text-slate-400 dark:text-zinc-500 italic">Tidak ada attachment.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Reviewer Notes --}}
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
                                {{ $progressReport->reviewerNotes->count() }}
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
                            @forelse ($progressReport->reviewerNotes as $note)
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
                                            Tambah Catatan
                                        </span>
                                    </span>
                                    <flux:icon.chevron-down
                                        class="size-3.5 text-violet-600 dark:text-violet-400 transition-transform duration-200"
                                        ::class="showAddNote && 'rotate-180'" />
                                </button>

                                <div x-show="showAddNote" x-collapse>
                                    <div class="px-3 pb-3 space-y-3">

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

                                        <div class="flex items-start gap-2 p-2 rounded-md
                                                    bg-violet-100/60 dark:bg-violet-900/30
                                                    border border-violet-200 dark:border-violet-800">
                                            <flux:icon.information-circle class="size-3 text-violet-600 dark:text-violet-400 shrink-0 mt-0.5" />
                                            <p class="text-[10px] text-violet-700 dark:text-violet-300 leading-snug">
                                                Catatan baru akan berstatus <strong>pending</strong>.
                                                Klik <strong>Accept</strong> untuk menyetujui,
                                                atau <strong>Revise</strong> untuk minta perbaikan.
                                            </p>
                                        </div>

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

                @if ($canReviewNotes && $progressReport->reviewerNotes->count() > 0)
                    <flux:button type="button" wire:click="finalize"
                        variant="primary" size="sm"
                        wire:confirm="Approve semua catatan dan finalisasi progress report?"
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
