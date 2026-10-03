<?php

use App\Livewire\Forms\AdminNoteForm;
use App\Models\AdminNote;
use App\Models\FinalReport;
use App\Models\Output;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ReviewerNote;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $proposal_id = null;
    public ?Proposal $proposal = null;
    public string $activeTab = 'progress_report';

    public ?ProgressReport $progressReport = null;
    public ?FinalReport $finalReport = null;
    public ?Output $output = null;

    public AdminNoteForm $adminNoteForm;

    /* ============================================================
     |  LOAD
     ============================================================ */

    #[On('open-view-submission')]
    public function load(int $proposalId): void
    {
        $this->proposal_id = $proposalId;
        $this->reload();

        if (! $this->proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        $this->activeTab = match (true) {
            $this->progressReport !== null => 'progress_report',
            $this->finalReport    !== null => 'final_report',
            $this->output         !== null => 'output',
            default                        => 'progress_report',
        };

        $this->setupForm();
        $this->dispatch('show-view-submission');
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->setupForm();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    /* ============================================================
     |  HELPERS
     ============================================================ */

    protected function reload(): void
    {
        $this->proposal = Proposal::with([
            'researchScheme', 'period', 'author', 'reviewer',
        ])->find($this->proposal_id);

        if (! $this->proposal) {
            return;
        }

        $this->progressReport = ProgressReport::with([
            'reviewer',
            'reviewerNotes' => fn ($q) => $q->latest()->with('reviewer'),
            'adminNotes'    => fn ($q) => $q->latest()->with('admin'),
        ])->where('proposal_id', $this->proposal_id)->first();

        $this->finalReport = FinalReport::with([
            'adminNotes' => fn ($q) => $q->latest()->with('admin'),
        ])->where('proposal_id', $this->proposal_id)->first();

        $this->output = Output::with([
            'adminNotes' => fn ($q) => $q->latest()->with('admin'),
        ])->where('proposal_id', $this->proposal_id)->first();
    }

    protected function setupForm(): void
    {
        $this->adminNoteForm->reset();
        $this->adminNoteForm->admin_id      = Auth::id();
        $this->adminNoteForm->noteable_id   = $this->currentSubmission()?->id;
        $this->adminNoteForm->noteable_type = $this->activeTab;
    }

    public function currentSubmission()
    {
        return match ($this->activeTab) {
            'progress_report' => $this->progressReport,
            'final_report'    => $this->finalReport,
            'output'          => $this->output,
            default           => null,
        };
    }

    /* ============================================================
     |  ADMIN ACTIONS
     ============================================================ */

    public function submit(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (! $sub) return;

        if (! in_array($sub->status, ['pending', 'revised'])) {
            Flux::toast('Cannot submit at this status.', variant: 'danger');
            return;
        }

        $this->updateStatus($sub, 'submitted');
        Flux::toast('Submitted successfully.', variant: 'success');
        $this->reload();
    }

    public function accept(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (! $sub) return;

        if (! in_array($sub->status, ['pending', 'revised'])) {
            Flux::toast('Cannot accept at this status.', variant: 'danger');
            return;
        }

        $this->updateStatus($sub, 'accepted');
        Flux::toast('Accepted successfully.', variant: 'success');
        $this->reload();
    }

    public function reject(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (! $sub) return;

        if ($sub->status === 'rejected') {
            Flux::toast('Already rejected.', variant: 'info');
            return;
        }

        $this->updateStatus($sub, 'rejected');
        Flux::toast('Rejected successfully.', variant: 'success');
        $this->reload();
    }

    protected function updateStatus($sub, string $status): void
    {
        $sub::where('id', $sub->id)->update(['status' => $status]);
    }

    /* ============================================================
     |  ADMIN NOTES
     ============================================================ */

    public function saveAdminNote(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (! $sub) return;

        $this->adminNoteForm->validate();
        $this->adminNoteForm->create();

        Flux::toast('Admin note saved.', variant: 'success');

        $this->adminNoteForm->reset('comment', 'recommendation');
        $this->adminNoteForm->admin_id      = Auth::id();
        $this->adminNoteForm->noteable_id   = $sub->id;
        $this->adminNoteForm->noteable_type = $this->activeTab;

        $this->reload();
    }

    public function deleteAdminNote(int $id): void
    {
        abort_unless($this->isAdmin(), 403);
        AdminNote::find($id)?->delete();
        Flux::toast('Note deleted.', variant: 'success');
        $this->reload();
    }

    /* ============================================================
     |  REVIEWER NOTE ACTIONS
     ============================================================ */

    public function acceptProgressReport(): void
    {
        abort_unless($this->isAdmin(), 403);

        $sub = $this->progressReport;
        if (! $sub) return;

        if ($sub->status !== 'under_review') {
            Flux::toast('Progress Report can only be accepted while Under Review.', variant: 'danger');
            return;
        }

        ProgressReport::where('id', $sub->id)->update([
            'status'      => 'accepted',
            'reviewed_at' => now(),
        ]);

        Flux::toast('Progress Report accepted.', variant: 'success');
        $this->reload();
    }

    public function acceptReviewerNote(int $noteId): void
    {
        abort_unless($this->isAdmin(), 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->progressReport?->id)
            ->where('noteable_type', 'progress_report')
            ->first();

        if (! $note) {
            Flux::toast('Note not found.', variant: 'danger');
            return;
        }

        if ($note->is_approved) {
            Flux::toast('Note already approved.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => true]);

        $stillPending = ReviewerNote::where('noteable_id', $this->progressReport->id)
            ->where('noteable_type', 'progress_report')
            ->where('is_approved', false)
            ->exists();

        if (! $stillPending) {
            ProgressReport::where('id', $this->progressReport->id)->update([
                'status'      => 'accepted',
                'reviewed_at' => now(),
            ]);
            Flux::toast('All notes approved. Progress Report accepted.', variant: 'success');
        } else {
            Flux::toast('Note approved.', variant: 'success');
        }

        $this->reload();
    }

    public function reviseReviewerNote(int $noteId): void
    {
        abort_unless($this->isAdmin(), 403);

        $note = ReviewerNote::where('id', $noteId)
            ->where('noteable_id', $this->progressReport?->id)
            ->where('noteable_type', 'progress_report')
            ->first();

        if (! $note) {
            Flux::toast('Note not found.', variant: 'danger');
            return;
        }

        if (! $note->is_approved) {
            Flux::toast('Note already marked for revision.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => false]);

        ProgressReport::where('id', $this->progressReport->id)->update([
            'status' => 'revised',
        ]);

        Flux::toast('Note marked for revision.', variant: 'success');
        $this->reload();
    }

    protected function isAdmin(): bool
    {
        return in_array(auth()->user()->role?->role_code, ['ADMIN', 'SUPERADMIN']);
    }

    public function levelMeta(?string $level): array
    {
        return match ($level) {
            'Scopus'  => ['class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', 'icon' => 'star'],
            'Sinta 1' => ['class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'icon' => 'star'],
            'Sinta 2' => ['class' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300', 'icon' => 'star'],
            'Sinta 3' => ['class' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300', 'icon' => 'star'],
            'Sinta 4' => ['class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'icon' => 'star'],
            'Sinta 5' => ['class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', 'icon' => 'star'],
            'Sinta 6' => ['class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'icon' => 'star'],
            default   => ['class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300', 'icon' => 'star'],
        };
    }
};
?>

<div
    x-data="{
        show: false,
        init() {
            window.addEventListener('show-view-submission', () => { this.show = true; });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="flex max-h-[92vh] w-full sm:max-w-2xl flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            @php $meta = $proposal->statusMeta(); @endphp

            {{-- ══════════ HEADER (indigo → violet) ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-indigo-600 to-violet-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.eye class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight line-clamp-2">
                                {{ $proposal->title }}
                            </h3>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="text-[9.5px] font-semibold px-1.5 py-0.5 rounded-full {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[9.5px] text-white/75">
                                    {{ $proposal->is_research ? 'Research' : 'Dedication' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ TABS ══════════ --}}
            <div class="shrink-0 bg-slate-50 dark:bg-zinc-800/40
                        border-b border-slate-200 dark:border-zinc-700">
                <div class="flex items-center gap-1 px-3 pt-2 overflow-x-auto">

                    {{-- Tab: Progress Report --}}
                    <button type="button" wire:click="switchTab('progress_report')"
                        @class([
                            'inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-t-md
                             text-[10.5px] font-semibold transition-all whitespace-nowrap',
                            'bg-white dark:bg-zinc-900 text-violet-700 dark:text-violet-300
                             border-x border-t border-slate-200 dark:border-zinc-700'
                                => $activeTab === 'progress_report',
                            'text-slate-500 dark:text-zinc-400
                             hover:text-slate-700 dark:hover:text-zinc-200'
                                => $activeTab !== 'progress_report',
                        ])>
                        <flux:icon.document-chart-bar class="size-3" />
                        <span>Progress Report</span>
                        @if ($progressReport)
                            <span class="text-[9px] font-bold px-1.5 rounded-full
                                         bg-violet-100 text-violet-700
                                         dark:bg-violet-900/40 dark:text-violet-300">
                                {{ $progressReport->reviewerNotes->count() + $progressReport->adminNotes->count() }}
                            </span>
                        @endif
                    </button>

                    {{-- Tab: Final Report --}}
                    <button type="button" wire:click="switchTab('final_report')"
                        @class([
                            'inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-t-md
                             text-[10.5px] font-semibold transition-all whitespace-nowrap',
                            'bg-white dark:bg-zinc-900 text-blue-700 dark:text-blue-300
                             border-x border-t border-slate-200 dark:border-zinc-700'
                                => $activeTab === 'final_report',
                            'text-slate-500 dark:text-zinc-400
                             hover:text-slate-700 dark:hover:text-zinc-200'
                                => $activeTab !== 'final_report',
                            'opacity-50' => ! $finalReport,
                        ])>
                        <flux:icon.document-check class="size-3" />
                        <span>Final Report</span>
                        @if ($finalReport)
                            <span class="text-[9px] font-bold px-1.5 rounded-full
                                         bg-blue-100 text-blue-700
                                         dark:bg-blue-900/40 dark:text-blue-300">
                                {{ $finalReport->adminNotes->count() }}
                            </span>
                        @endif
                    </button>

                    {{-- Tab: Output --}}
                    <button type="button" wire:click="switchTab('output')"
                        @class([
                            'inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-t-md
                             text-[10.5px] font-semibold transition-all whitespace-nowrap',
                            'bg-white dark:bg-zinc-900 text-amber-700 dark:text-amber-300
                             border-x border-t border-slate-200 dark:border-zinc-700'
                                => $activeTab === 'output',
                            'text-slate-500 dark:text-zinc-400
                             hover:text-slate-700 dark:hover:text-zinc-200'
                                => $activeTab !== 'output',
                            'opacity-50' => ! $output,
                        ])>
                        <flux:icon.trophy class="size-3" />
                        <span>Output</span>
                        @if ($output)
                            <span class="text-[9px] font-bold px-1.5 rounded-full
                                         bg-amber-100 text-amber-700
                                         dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $output->adminNotes->count() }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

                @php $submission = $this->currentSubmission(); @endphp

                @if (! $submission)
                    {{-- Empty state --}}
                    <div class="flex flex-col items-center gap-2 py-12 text-center">
                        <div class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-zinc-800
                                    flex items-center justify-center">
                            <flux:icon.document-plus class="size-7 text-slate-400 dark:text-zinc-600" />
                        </div>
                        <div>
                            <p class="text-[12px] font-semibold text-slate-700 dark:text-zinc-300">
                                {{ match ($activeTab) {
                                    'progress_report' => 'Progress Report Not Uploaded',
                                    'final_report'    => 'Final Report Not Uploaded',
                                    'output'          => 'Output Not Uploaded',
                                    default           => 'No Data',
                                } }}
                            </p>
                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-1 max-w-xs">
                                @if ($activeTab === 'progress_report')
                                    User must upload the progress report after the proposal is accepted.
                                @elseif ($activeTab === 'final_report')
                                    Final report can be uploaded after the progress report is accepted.
                                @elseif ($activeTab === 'output')
                                    Output can be uploaded after the final report is accepted.
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    {{-- ══════════ META GRID ══════════ --}}
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="rounded-md border border-indigo-100 dark:border-indigo-900/40
                                    bg-indigo-50/50 dark:bg-indigo-900/10 px-2.5 py-2">
                            <p class="text-[9px] uppercase tracking-wider font-semibold
                                      text-indigo-600 dark:text-indigo-400">
                                Author
                            </p>
                            <p class="mt-0.5 text-[10.5px] font-semibold truncate
                                      text-slate-900 dark:text-zinc-100">
                                {{ $proposal->author?->full_name ?? '—' }}
                            </p>
                        </div>
                        <div class="rounded-md border border-violet-100 dark:border-violet-900/40
                                    bg-violet-50/50 dark:bg-violet-900/10 px-2.5 py-2">
                            <p class="text-[9px] uppercase tracking-wider font-semibold
                                      text-violet-600 dark:text-violet-400">
                                {{ $activeTab === 'progress_report' ? 'Reviewer' : 'Submitted' }}
                            </p>
                            <p class="mt-0.5 text-[10.5px] font-semibold truncate
                                      text-slate-900 dark:text-zinc-100">
                                @if ($activeTab === 'progress_report')
                                    {{ $submission->reviewer?->full_name ?? 'Not assigned' }}
                                @else
                                    {{ $submission->created_at?->format('d M Y, H:i') ?? '—' }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- ══════════ KEYWORD (Progress only) ══════════ --}}
                    @if ($activeTab === 'progress_report' && $submission->keyword)
                        <div>
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <flux:icon.tag class="size-3 text-violet-500 dark:text-violet-400" />
                                <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                          text-slate-500 dark:text-zinc-400">
                                    Keyword
                                </p>
                            </div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                        bg-violet-50 dark:bg-violet-900/20
                                        border border-violet-200 dark:border-violet-800/60">
                                <flux:icon.tag class="size-3 text-violet-600 dark:text-violet-400" />
                                <span class="text-[11px] font-semibold
                                             text-violet-700 dark:text-violet-300">
                                    {{ $submission->keyword }}
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════ SUMMARY ══════════ --}}
                    <div>
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <flux:icon.document-text class="size-3 text-indigo-500 dark:text-indigo-400" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Summary
                            </p>
                        </div>
                        <div class="rounded-md border border-slate-200 dark:border-zinc-700/70
                                    bg-slate-50 dark:bg-zinc-800/40 px-2.5 py-2
                                    text-[10.5px] leading-relaxed whitespace-pre-wrap
                                    text-slate-700 dark:text-zinc-300
                                    max-h-40 overflow-y-auto">
                            {{ $submission->summary }}
                        </div>
                    </div>

                    {{-- ══════════ FILES ══════════ --}}
                    <div>
                        <div class="flex items-center gap-1.5 mb-2">
                            <flux:icon.paper-clip class="size-3 text-emerald-500 dark:text-emerald-400" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Attachments
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">

                            {{-- Progress Report Files --}}
                            @if ($activeTab === 'progress_report')
                                @if ($submission->report_path)
                                    <a href="{{ Storage::disk('public')->url($submission->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                               bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40
                                               border border-rose-200 dark:border-rose-800/60
                                               text-rose-700 dark:text-rose-300 text-[10.5px] font-semibold
                                               transition-colors">
                                        <flux:icon.document-text class="size-3.5" />
                                        Report PDF
                                    </a>
                                @endif
                                @if ($submission->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($submission->ppt_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                               bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/40
                                               border border-orange-200 dark:border-orange-800/60
                                               text-orange-700 dark:text-orange-300 text-[10.5px] font-semibold
                                               transition-colors">
                                        <flux:icon.presentation-chart-bar class="size-3.5" />
                                        Presentation
                                    </a>
                                @endif
                            @endif

                            {{-- Final Report Files --}}
                            @if ($activeTab === 'final_report')
                                @if ($submission->report_path)
                                    <a href="{{ Storage::disk('public')->url($submission->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                               bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20
                                               border border-rose-200 dark:border-rose-800/60
                                               text-rose-700 dark:text-rose-300 text-[10.5px] font-semibold
                                               transition-colors">
                                        <flux:icon.document-text class="size-3.5" />
                                        Report
                                    </a>
                                @endif
                                @if ($submission->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($submission->ppt_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                               bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20
                                               border border-orange-200 dark:border-orange-800/60
                                               text-orange-700 dark:text-orange-300 text-[10.5px] font-semibold
                                               transition-colors">
                                        <flux:icon.presentation-chart-bar class="size-3.5" />
                                        Presentation
                                    </a>
                                @endif
                                @if ($submission->research_output)
                                    <a href="{{ Storage::disk('public')->url($submission->research_output) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                               bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20
                                               border border-blue-200 dark:border-blue-800/60
                                               text-blue-700 dark:text-blue-300 text-[10.5px] font-semibold
                                               transition-colors">
                                        <flux:icon.document-arrow-down class="size-3.5" />
                                        Research Output
                                    </a>
                                @endif
                                @if ($submission->submission_proof)
                                    <a href="{{ Storage::disk('public')->url($submission->submission_proof) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                               bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20
                                               border border-emerald-200 dark:border-emerald-800/60
                                               text-emerald-700 dark:text-emerald-300 text-[10.5px] font-semibold
                                               transition-colors">
                                        <flux:icon.photo class="size-3.5" />
                                        Submission Proof
                                    </a>
                                @endif
                            @endif

                            {{-- Output Files --}}
                            @if ($activeTab === 'output')
                                <a href="{{ $submission->journal_link }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md
                                           bg-sky-50 hover:bg-sky-100 dark:bg-sky-900/20
                                           border border-sky-200 dark:border-sky-800/60
                                           text-sky-700 dark:text-sky-300 text-[10.5px] font-semibold
                                           transition-colors">
                                    <flux:icon.arrow-up-right class="size-3.5" />
                                    View Journal
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- ══════════ OUTPUT DETAILS ══════════ --}}
                    @if ($activeTab === 'output')
                        <div class="rounded-md border border-slate-200 dark:border-zinc-700/70 overflow-hidden">
                            <div class="px-2.5 py-2 bg-slate-50 dark:bg-zinc-800/40
                                        border-b border-slate-200 dark:border-zinc-700/70">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.trophy class="size-3 text-amber-500 dark:text-amber-400" />
                                    <span class="text-[10.5px] font-semibold
                                                 text-slate-700 dark:text-zinc-300">
                                        Journal Details
                                    </span>
                                </div>
                            </div>
                            <div class="px-2.5 py-3 grid grid-cols-2 gap-2.5">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wider font-semibold
                                              text-slate-500 dark:text-zinc-400">
                                        Journal
                                    </p>
                                    <p class="mt-0.5 text-[10.5px] font-medium
                                              text-slate-900 dark:text-zinc-100">
                                        {{ $submission->journal_name }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wider font-semibold
                                              text-slate-500 dark:text-zinc-400">
                                        Level
                                    </p>
                                    @php $levelMeta = $this->levelMeta($submission->level); @endphp
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                 text-[9.5px] font-semibold mt-0.5
                                                 {{ $levelMeta['class'] }}">
                                        <flux:icon.star class="size-2.5" />
                                        {{ $submission->level }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wider font-semibold
                                              text-slate-500 dark:text-zinc-400">
                                        Edition
                                    </p>
                                    <p class="mt-0.5 text-[10.5px] font-medium
                                              text-slate-900 dark:text-zinc-100">
                                        {{ $submission->edition }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wider font-semibold
                                              text-slate-500 dark:text-zinc-400">
                                        Volume
                                    </p>
                                    <p class="mt-0.5 text-[10.5px] font-medium
                                              text-slate-900 dark:text-zinc-100">
                                        {{ $submission->volume }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════ REVIEWER NOTES (Progress only) ══════════ --}}
                    @if ($activeTab === 'progress_report' && $submission->reviewerNotes->count() > 0)
                        @php $pendingNotesCount = $submission->reviewerNotes->where('is_approved', false)->count(); @endphp

                        <div class="rounded-md border border-violet-200 dark:border-violet-800/60 overflow-hidden">
                            <div class="px-2.5 py-2 bg-violet-50 dark:bg-violet-900/20
                                        border-b border-violet-200 dark:border-violet-800/60
                                        flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <flux:icon.clipboard-document-check
                                        class="size-3 text-violet-500 dark:text-violet-400" />
                                    <span class="text-[10.5px] font-semibold
                                                 text-violet-800 dark:text-violet-300">
                                        Reviewer Notes
                                    </span>
                                    <span class="text-[9px] font-bold px-1.5 rounded-full
                                                 bg-violet-200 text-violet-800
                                                 dark:bg-violet-800 dark:text-violet-200">
                                        {{ $submission->reviewerNotes->count() }}
                                    </span>
                                    @if ($pendingNotesCount > 0)
                                        <span class="text-[9px] font-bold px-1.5 rounded-full
                                                     bg-amber-200 text-amber-800
                                                     dark:bg-amber-800 dark:text-amber-200">
                                            {{ $pendingNotesCount }} pending
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-2.5 space-y-2 max-h-60 overflow-y-auto">
                                @foreach ($submission->reviewerNotes as $note)
                                    @php $approved = $note->is_approved; @endphp
                                    <div wire:key="rn-{{ $note->id }}"
                                        class="rounded-md border px-2.5 py-2
                                        {{ $approved
                                            ? 'bg-emerald-50 dark:bg-emerald-900/15 border-emerald-200 dark:border-emerald-800/60'
                                            : 'bg-amber-50 dark:bg-amber-900/15 border-amber-200 dark:border-amber-800/60' }}">

                                        {{-- Header --}}
                                        <div class="flex items-center justify-between gap-2 mb-1.5 flex-wrap">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <div class="w-5 h-5 rounded-md shrink-0
                                                            flex items-center justify-center
                                                            text-[8px] font-bold
                                                    {{ $approved
                                                        ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                        : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                    {{ strtoupper(substr($note->reviewer?->full_name ?? 'R', 0, 1)) }}
                                                </div>
                                                <p class="text-[10.5px] font-semibold truncate
                                                    {{ $approved
                                                        ? 'text-emerald-800 dark:text-emerald-300'
                                                        : 'text-amber-800 dark:text-amber-300' }}">
                                                    {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                                </p>
                                                <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                                <span class="text-[9px]
                                                    {{ $approved
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-amber-600 dark:text-amber-400' }}">
                                                    {{ $note->created_at?->diffForHumans() }}
                                                </span>
                                            </div>

                                            <span class="inline-flex items-center gap-0.5 shrink-0
                                                         text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase
                                                {{ $approved
                                                    ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                                    : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                <flux:icon :name="$approved ? 'check' : 'arrow-path'" class="size-2.5" />
                                                {{ $approved ? 'Approved' : 'Revision' }}
                                            </span>
                                        </div>

                                        {{-- Comment --}}
                                        @if ($note->comment)
                                            <p class="mt-1 text-[10.5px] leading-relaxed whitespace-pre-wrap
                                                {{ $approved
                                                    ? 'text-emerald-900 dark:text-emerald-100'
                                                    : 'text-amber-900 dark:text-amber-100' }}">
                                                {{ $note->comment }}
                                            </p>
                                        @endif

                                        {{-- Recommendation --}}
                                        @if ($note->recommendation)
                                            <div class="mt-1.5 pt-1.5 border-t
                                                {{ $approved
                                                    ? 'border-emerald-200 dark:border-emerald-800/60'
                                                    : 'border-amber-200 dark:border-amber-800/60' }}">
                                                <p class="text-[9.5px]
                                                    {{ $approved
                                                        ? 'text-emerald-700 dark:text-emerald-300'
                                                        : 'text-amber-700 dark:text-amber-300' }}">
                                                    <span class="font-semibold">Recommendation:</span>
                                                    {{ $note->recommendation }}
                                                </p>
                                            </div>
                                        @endif

                                        {{-- Admin Actions --}}
                                        @if ($this->isAdmin())
                                            <div class="mt-2 pt-2 border-t flex justify-end gap-1.5
                                                {{ $approved
                                                    ? 'border-emerald-200 dark:border-emerald-800/60'
                                                    : 'border-amber-200 dark:border-amber-800/60' }}">

                                                @if (! $approved)
                                                    <button type="button"
                                                        wire:click="acceptReviewerNote({{ $note->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="acceptReviewerNote,reviseReviewerNote"
                                                        class="inline-flex items-center gap-1
                                                               px-2 py-1 rounded-md
                                                               text-[10px] font-semibold text-white
                                                               bg-emerald-600 hover:bg-emerald-700
                                                               disabled:opacity-60 transition-colors">
                                                        <span wire:loading.remove wire:target="acceptReviewerNote"
                                                            class="inline-flex items-center gap-1">
                                                            <flux:icon.check-circle class="size-2.5" />
                                                            Accept
                                                        </span>
                                                        <span wire:loading.flex wire:target="acceptReviewerNote">
                                                            <svg class="animate-spin size-2.5" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                    stroke="currentColor" stroke-width="4" />
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        wire:click="reviseReviewerNote({{ $note->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="acceptReviewerNote,reviseReviewerNote"
                                                        class="inline-flex items-center gap-1
                                                               px-2 py-1 rounded-md
                                                               text-[10px] font-semibold text-white
                                                               bg-amber-600 hover:bg-amber-700
                                                               disabled:opacity-60 transition-colors">
                                                        <span wire:loading.remove wire:target="reviseReviewerNote"
                                                            class="inline-flex items-center gap-1">
                                                            <flux:icon.pencil-square class="size-2.5" />
                                                            Revise
                                                        </span>
                                                        <span wire:loading.flex wire:target="reviseReviewerNote">
                                                            <svg class="animate-spin size-2.5" fill="none"
                                                                viewBox="0 0 24 24">
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
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ══════════ ADMIN NOTES ══════════ --}}
                    <div class="rounded-md border border-amber-200 dark:border-amber-800/60 overflow-hidden">
                        <div class="px-2.5 py-2 bg-amber-50 dark:bg-amber-900/20
                                    border-b border-amber-200 dark:border-amber-800/60
                                    flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <flux:icon.chat-bubble-left-right
                                    class="size-3 text-amber-500 dark:text-amber-400" />
                                <span class="text-[10.5px] font-semibold
                                             text-amber-800 dark:text-amber-300">
                                    Admin Notes
                                </span>
                                <span class="text-[9px] font-bold px-1.5 rounded-full
                                             bg-amber-200 text-amber-800
                                             dark:bg-amber-800 dark:text-amber-200">
                                    {{ $submission->adminNotes->count() }}
                                </span>
                            </div>
                        </div>

                        {{-- List Notes --}}
                        <div class="p-2.5 space-y-1.5 max-h-60 overflow-y-auto">
                            @forelse ($submission->adminNotes as $note)
                                <div wire:key="an-{{ $note->id }}"
                                    class="rounded-md border border-amber-200 dark:border-amber-800/60
                                           bg-amber-50 dark:bg-amber-900/20 px-2.5 py-2">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                                            <div class="w-5 h-5 rounded-md shrink-0
                                                        bg-amber-200 dark:bg-amber-800/50
                                                        flex items-center justify-center
                                                        text-[8px] font-bold
                                                        text-amber-800 dark:text-amber-300">
                                                {{ strtoupper(substr($note->admin?->full_name ?? 'A', 0, 1)) }}
                                            </div>
                                            <p class="text-[10.5px] font-semibold truncate
                                                      text-amber-800 dark:text-amber-300">
                                                {{ $note->admin?->full_name ?? 'Admin' }}
                                            </p>
                                            <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                                            <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                                {{ $note->created_at?->diffForHumans() }}
                                            </span>
                                        </div>
                                        @if ($this->isAdmin())
                                            <button type="button"
                                                wire:click="deleteAdminNote({{ $note->id }})"
                                                wire:confirm="Delete this note?"
                                                aria-label="Delete note"
                                                class="shrink-0 p-1 rounded
                                                       text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/30
                                                       transition-colors">
                                                <flux:icon.trash class="size-3" />
                                            </button>
                                        @endif
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[10.5px] leading-relaxed whitespace-pre-wrap
                                                  text-amber-900 dark:text-amber-100">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="mt-1 text-[9.5px] text-amber-700 dark:text-amber-300">
                                            <span class="font-semibold">Recommendation:</span>
                                            {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @empty
                                <div class="py-4 text-center">
                                    <flux:icon.chat-bubble-left-right
                                        class="size-5 mx-auto mb-1 text-slate-300 dark:text-zinc-600" />
                                    <p class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">
                                        No admin notes yet.
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Add Note Form --}}
                        @if ($this->isAdmin())
                            <div class="border-t border-amber-200 dark:border-amber-800/60
                                        bg-amber-50/50 dark:bg-amber-900/10 p-2.5 space-y-2">
                                <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                          text-amber-800 dark:text-amber-300">
                                    Add Note
                                </p>

                                <textarea wire:model="adminNoteForm.comment" rows="2"
                                    placeholder="Write a note..."
                                    class="block w-full rounded-md shadow-sm text-[10.5px] resize-none
                                           border-amber-300 dark:border-amber-700
                                           bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                           focus:ring-1 focus:ring-amber-500 focus:border-amber-500
                                           py-1.5 px-2.5 transition-colors"></textarea>

                                <input type="text" wire:model="adminNoteForm.recommendation"
                                    placeholder="Recommendation (optional)"
                                    class="block w-full rounded-md shadow-sm text-[10.5px]
                                           border-amber-300 dark:border-amber-700
                                           bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                           focus:ring-1 focus:ring-amber-500 focus:border-amber-500
                                           py-1.5 px-2.5 transition-colors" />

                                <div class="flex justify-end">
                                    <button type="button" wire:click="saveAdminNote"
                                        wire:loading.attr="disabled"
                                        wire:target="saveAdminNote"
                                        class="inline-flex items-center gap-1
                                               px-2.5 py-1.5 rounded-md
                                               text-[10.5px] font-semibold text-white
                                               bg-amber-600 hover:bg-amber-700
                                               disabled:opacity-60 disabled:cursor-wait
                                               transition-colors">
                                        <span wire:loading.remove wire:target="saveAdminNote"
                                            class="inline-flex items-center gap-1">
                                            <flux:icon.plus class="size-3" />
                                            Save Note
                                        </span>
                                        <span wire:loading.flex wire:target="saveAdminNote"
                                            class="items-center gap-1">
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
                        @endif
                    </div>
                @endif
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between
                        gap-1.5 px-4 py-3
                        border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-md
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           transition-colors">
                    Close
                </button>

                @if ($submission && $this->isAdmin())
                    <div class="flex flex-col-reverse sm:flex-row gap-1.5">

                        {{-- Progress Report: Assign Reviewer --}}
                        @if ($activeTab === 'progress_report' && $submission->status === 'submitted')
                            <button type="button" x-data
                                x-on:click="$dispatch('open-assign-reviewer-progress', { id: {{ $submission->id }} })"
                                class="inline-flex items-center justify-center gap-1
                                       px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                                       bg-violet-600 hover:bg-violet-700
                                       transition-colors">
                                <flux:icon.user-plus class="size-3" />
                                Assign Reviewer
                            </button>
                        @endif

                        {{-- Progress Report: Submit --}}
                        @if ($activeTab === 'progress_report' && in_array($submission->status, ['pending', 'revised']))
                            <button type="button" wire:click="submit"
                                wire:loading.attr="disabled" wire:target="submit"
                                class="inline-flex items-center justify-center gap-1
                                       px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                                       bg-violet-600 hover:bg-violet-700
                                       disabled:opacity-60 disabled:cursor-wait
                                       transition-colors">
                                <span wire:loading.remove wire:target="submit">Submit</span>
                                <span wire:loading wire:target="submit">Processing...</span>
                            </button>
                        @endif

                        {{-- Progress Report: Accept Progress Report --}}
                        @if ($activeTab === 'progress_report' && $submission->status === 'under_review')
                            <button type="button" wire:click="acceptProgressReport"
                                wire:confirm="Accept this Progress Report? Make sure the reviewer has finished."
                                wire:loading.attr="disabled" wire:target="acceptProgressReport"
                                class="inline-flex items-center justify-center gap-1
                                       px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                                       bg-emerald-600 hover:bg-emerald-700
                                       disabled:opacity-60 disabled:cursor-wait
                                       transition-colors">
                                <span wire:loading.remove wire:target="acceptProgressReport"
                                    class="inline-flex items-center gap-1">
                                    <flux:icon.check-circle class="size-3" />
                                    Accept Progress Report
                                </span>
                                <span wire:loading wire:target="acceptProgressReport">Processing...</span>
                            </button>
                        @endif

                        {{-- Final Report & Output: Accept --}}
                        @if (in_array($activeTab, ['final_report', 'output']) && in_array($submission->status, ['pending', 'revised']))
                            <button type="button" wire:click="accept"
                                wire:loading.attr="disabled" wire:target="accept"
                                class="inline-flex items-center justify-center gap-1
                                       px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                                       bg-emerald-600 hover:bg-emerald-700
                                       disabled:opacity-60 disabled:cursor-wait
                                       transition-colors">
                                <span wire:loading.remove wire:target="accept">Accept</span>
                                <span wire:loading wire:target="accept">Processing...</span>
                            </button>
                        @endif

                        {{-- Reject --}}
                        @if ($submission->status !== 'rejected')
                            <button type="button" wire:click="reject"
                                wire:confirm="Reject this submission?"
                                wire:loading.attr="disabled" wire:target="reject"
                                class="inline-flex items-center justify-center gap-1
                                       px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                                       bg-rose-600 hover:bg-rose-700
                                       disabled:opacity-60 disabled:cursor-wait
                                       transition-colors">
                                <span wire:loading.remove wire:target="reject">Reject</span>
                                <span wire:loading wire:target="reject">Processing...</span>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
