<?php

use App\Livewire\Forms\AdminNoteForm;
use App\Models\FinalReport;
use App\Models\Output;
use App\Models\ProgressReport;
use App\Models\ReviewerNote;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $proposal_id = null;
    public ?\App\Models\Proposal $proposal = null;

    public string $activeTab = 'progress_report';

    // Submissions (bisa null)
    public ?ProgressReport $progressReport = null;
    public ?FinalReport $finalReport = null;
    public ?Output $output = null;

    public AdminNoteForm $adminNoteForm;

    #[On('open-view-submission')]
    public function load(int $proposalId): void
    {
        $this->proposal_id = $proposalId;
        $this->reload();

        // Default tab: progress report kalau ada, kalau tidak final, kalau tidak output
        $this->activeTab = match (true) {
            $this->progressReport !== null => 'progress_report',
            $this->finalReport !== null => 'final_report',
            $this->output !== null => 'output',
            default => 'progress_report',
        };

        $this->setupForm();
        $this->dispatch('show-view-submission');
    }

    // ═══════════════ TAB SWITCH ═══════════════

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->setupForm();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ═══════════════ HELPERS ═══════════════
    protected function reload(): void
    {
        $this->proposal = \App\Models\Proposal::with(['researchScheme', 'period', 'author', 'reviewer'])->findOrFail($this->proposal_id);

        $this->progressReport = ProgressReport::with(['reviewer', 'reviewerNotes' => fn($q) => $q->latest()->with('reviewer'), 'adminNotes' => fn($q) => $q->latest()->with('admin')])
            ->where('proposal_id', $this->proposal_id)
            ->first();

        $this->finalReport = FinalReport::with([
            'adminNotes' => fn($q) => $q->latest()->with('admin'),
        ])
            ->where('proposal_id', $this->proposal_id)
            ->first();

        $this->output = Output::with([
            'adminNotes' => fn($q) => $q->latest()->with('admin'),
        ])
            ->where('proposal_id', $this->proposal_id)
            ->first();
    }

    protected function setupForm(): void
    {
        $this->adminNoteForm->reset();
        $this->adminNoteForm->admin_id = Auth::id();
        $this->adminNoteForm->noteable_id = $this->currentSubmission()?->id;
        $this->adminNoteForm->noteable_type = $this->activeTab;
    }

    public function currentSubmission()
    {
        return match ($this->activeTab) {
            'progress_report' => $this->progressReport,
            'final_report' => $this->finalReport,
            'output' => $this->output,
            default => null,
        };
    }

    // ═══════════════ ADMIN ACTIONS ═══════════════
    public function submit(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (!$sub) {
            return;
        }

        if (!in_array($sub->status, ['pending', 'revised'])) {
            Flux::toast('Tidak dapat di-submit pada status ini.', variant: 'danger');
            return;
        }

        $this->updateStatus($sub, 'submitted');
        Flux::toast('Berhasil di-submit.', variant: 'success');
        $this->reload();
    }

    public function accept(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (!$sub) {
            return;
        }

        if (!in_array($sub->status, ['pending', 'revised'])) {
            Flux::toast('Tidak dapat di-accept pada status ini.', variant: 'danger');
            return;
        }

        $this->updateStatus($sub, 'accepted');
        Flux::toast('Berhasil di-accept.', variant: 'success');
        $this->reload();
    }

    public function reject(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (!$sub) {
            return;
        }

        if ($sub->status === 'rejected') {
            Flux::toast('Sudah ditolak.', variant: 'info');
            return;
        }

        $this->updateStatus($sub, 'rejected');
        Flux::toast('Berhasil ditolak.', variant: 'success');
        $this->reload();
    }

    protected function updateStatus($sub, string $status): void
    {
        $sub::where('id', $sub->id)->update(['status' => $status]);
    }

    // ═══════════════ ADMIN NOTES ═══════════════
    public function saveAdminNote(): void
    {
        abort_unless($this->isAdmin(), 403);
        $sub = $this->currentSubmission();
        if (!$sub) {
            return;
        }

        $this->adminNoteForm->validate();
        $this->adminNoteForm->create();

        Flux::toast('Catatan admin tersimpan.', variant: 'success');

        $this->adminNoteForm->reset('comment', 'recommendation');
        $this->adminNoteForm->admin_id = Auth::id();
        $this->adminNoteForm->noteable_id = $sub->id;
        $this->adminNoteForm->noteable_type = $this->activeTab;

        $this->reload();
    }

    public function deleteAdminNote(int $id): void
    {
        abort_unless($this->isAdmin(), 403);
        \App\Models\AdminNote::findOrFail($id)->delete();
        Flux::toast('Catatan dihapus.', variant: 'success');
        $this->reload();
    }

    // ═══════════════ UTILS ═══════════════
    protected function isAdmin(): bool
    {
        return in_array(auth()->user()->role?->role_code, ['ADMIN', 'SUPERADMIN']);
    }

    // ═══════════════ ADMIN: ACCEPT PROGRESS REPORT ═══════════════

    /**
     * Admin accept Progress Report (setelah reviewer selesai review).
     * Hanya bisa kalau status = under_review.
     */
    public function acceptProgressReport(): void
    {
        abort_unless($this->isAdmin(), 403);

        $sub = $this->progressReport;
        if (!$sub) {
            return;
        }

        if ($sub->status !== 'under_review') {
            Flux::toast('Progress Report hanya bisa di-accept saat status Under Review.', variant: 'danger');
            return;
        }

        ProgressReport::where('id', $sub->id)->update([
            'status' => 'accepted',
            'reviewed_at' => now(),
        ]);

        Flux::toast('Progress Report berhasil di-accept.', variant: 'success');
        $this->reload();
    }

    // ═══════════════ ADMIN: REVIEWER NOTE ACTIONS ═══════════════

    /**
     * Admin accept Reviewer Note.
     * Kalau semua note approved → Progress Report accepted.
     */
    public function acceptReviewerNote(int $noteId): void
    {
        abort_unless($this->isAdmin(), 403);

        $note = ReviewerNote::where('id', $noteId)->where('noteable_id', $this->progressReport?->id)->where('noteable_type', 'progress_report')->firstOrFail();

        if ($note->is_approved) {
            Flux::toast('Catatan sudah di-approve.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => true]);

        // Cek sisa note pending
        $stillPending = ReviewerNote::where('noteable_id', $this->progressReport->id)->where('noteable_type', 'progress_report')->where('is_approved', false)->exists();

        if (!$stillPending) {
            // Semua approved → Progress Report accepted
            ProgressReport::where('id', $this->progressReport->id)->update([
                'status' => 'accepted',
                'reviewed_at' => now(),
            ]);
            Flux::toast('Semua catatan di-approve. Progress Report accepted.', variant: 'success');
        } else {
            Flux::toast('Catatan di-approve.', variant: 'success');
        }

        $this->reload();
    }

    /**
     * Admin revise Reviewer Note (minta revisi).
     * Status Progress Report kembali ke revised.
     */
    public function reviseReviewerNote(int $noteId): void
    {
        abort_unless($this->isAdmin(), 403);

        $note = ReviewerNote::where('id', $noteId)->where('noteable_id', $this->progressReport?->id)->where('noteable_type', 'progress_report')->firstOrFail();

        if (!$note->is_approved) {
            Flux::toast('Catatan sudah ditandai revisi.', variant: 'info');
            return;
        }

        $note->update(['is_approved' => false]);

        // Status → revised
        ProgressReport::where('id', $this->progressReport->id)->update([
            'status' => 'revised',
        ]);

        Flux::toast('Catatan ditandai butuh revisi.', variant: 'success');
        $this->reload();
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

<div x-data="{
    show: false,
    init() {
        window.addEventListener('show-view-submission', () => { this.show = true; });
    }
}" x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-3xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            @php $meta = $proposal->statusMeta(); @endphp

            {{-- ══════════ HEADER ══════════ --}}
            <div
                class="shrink-0 bg-gradient-to-r from-slate-700 to-slate-600
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.eye class="size-4 text-white" />
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

            {{-- ══════════ TABS ══════════ --}}
            <div class="shrink-0 bg-slate-50 dark:bg-zinc-800/40 border-b border-slate-200 dark:border-zinc-700">
                <div class="flex items-center gap-1 px-3 sm:px-4 pt-2 overflow-x-auto">

                    {{-- Tab: Progress Report --}}
                    <button type="button" wire:click="switchTab('progress_report')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-t-lg text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-white dark:bg-zinc-900 text-violet-700 dark:text-violet-300 border-x border-t border-slate-200 dark:border-zinc-700' =>
                            $activeTab === 'progress_report',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200' =>
                            $activeTab !== 'progress_report',
                    ])>
                        <flux:icon.document-chart-bar class="size-3.5" />
                        <span>Progress Report</span>
                        @if ($progressReport)
                            <span
                                class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                         bg-violet-100 text-violet-700
                                         dark:bg-violet-900/40 dark:text-violet-300">
                                {{ $progressReport->reviewerNotes->count() + $progressReport->adminNotes->count() }}
                            </span>
                        @endif
                    </button>

                    {{-- Tab: Final Report --}}
                    <button type="button" wire:click="switchTab('final_report')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-t-lg text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-white dark:bg-zinc-900 text-blue-700 dark:text-blue-300 border-x border-t border-slate-200 dark:border-zinc-700' =>
                            $activeTab === 'final_report',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200' =>
                            $activeTab !== 'final_report',
                        'opacity-50' => !$finalReport,
                    ])>
                        <flux:icon.document-check class="size-3.5" />
                        <span>Final Report</span>
                        @if ($finalReport)
                            <span
                                class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                         bg-blue-100 text-blue-700
                                         dark:bg-blue-900/40 dark:text-blue-300">
                                {{ $finalReport->adminNotes->count() }}
                            </span>
                        @endif
                    </button>

                    {{-- Tab: Output --}}
                    <button type="button" wire:click="switchTab('output')" @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-t-lg text-[11px] font-semibold transition-all whitespace-nowrap',
                        'bg-white dark:bg-zinc-900 text-amber-700 dark:text-amber-300 border-x border-t border-slate-200 dark:border-zinc-700' =>
                            $activeTab === 'output',
                        'text-slate-500 dark:text-zinc-400 hover:text-slate-700 dark:hover:text-zinc-200' =>
                            $activeTab !== 'output',
                        'opacity-50' => !$output,
                    ])>
                        <flux:icon.trophy class="size-3.5" />
                        <span>Output</span>
                        @if ($output)
                            <span
                                class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                         bg-amber-100 text-amber-700
                                         dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $output->adminNotes->count() }}
                            </span>
                        @endif
                    </button>

                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-3">

                @php
                    $submission = $this->currentSubmission();
                @endphp

                @if (!$submission)
                    {{-- Empty state --}}
                    <div class="flex flex-col items-center justify-center py-16">
                        <div
                            class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800
                                    flex items-center justify-center mb-4">
                            <flux:icon.document-plus class="size-8 text-slate-400 dark:text-zinc-600" />
                        </div>
                        <p class="text-[13px] font-semibold text-slate-700 dark:text-zinc-300">
                            {{ match ($activeTab) {
                                'progress_report' => 'Progress Report Belum Diunggah',
                                'final_report' => 'Final Report Belum Diunggah',
                                'output' => 'Output Belum Diunggah',
                                default => 'Belum Ada Data',
                            } }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 text-center max-w-xs">
                            @if ($activeTab === 'progress_report')
                                User harus mengunggah progress report terlebih dahulu setelah proposal di-accept.
                            @elseif ($activeTab === 'final_report')
                                Final report dapat diunggah setelah progress report di-accept.
                            @elseif ($activeTab === 'output')
                                Output dapat diunggah setelah final report di-accept.
                            @endif
                        </p>
                    </div>
                @else
                    @php $subMeta = $submission->statusMeta(); @endphp

                    {{-- ══════════ STATUS & META GRID ══════════ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            class="rounded-lg border border-slate-200 dark:border-zinc-700
                                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                            <p
                                class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                                Author</p>
                            <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                                {{ $proposal->author?->full_name ?? '—' }}
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-slate-200 dark:border-zinc-700
                                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                            <p
                                class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ $activeTab === 'progress_report' ? 'Reviewer' : 'Submitted' }}
                            </p>
                            <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1">
                                @if ($activeTab === 'progress_report')
                                    {{ $submission->reviewer?->full_name ?? 'Belum di-assign' }}
                                @else
                                    {{ $submission->created_at?->format('d M Y, H:i') ?? '—' }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- ══════════ KEYWORD (Progress Only) ══════════ --}}
                    @if ($activeTab === 'progress_report' && $submission->keyword)
                        <div>
                            <p
                                class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-1.5">
                                Keyword
                            </p>
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                        bg-violet-50 dark:bg-violet-900/20
                                        border border-violet-200 dark:border-violet-800">
                                <flux:icon.tag class="size-3.5 text-violet-600 dark:text-violet-400" />
                                <span class="text-[12px] font-semibold text-violet-700 dark:text-violet-300">
                                    {{ $submission->keyword }}
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════ SUMMARY ══════════ --}}
                    <div>
                        <p
                            class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-1.5">
                            Ringkasan
                        </p>
                        <div
                            class="p-3 rounded-lg bg-slate-50 dark:bg-zinc-800/40
                                    border border-slate-200 dark:border-zinc-700
                                    text-[11px] leading-relaxed whitespace-pre-wrap
                                    text-slate-700 dark:text-zinc-300 max-h-40 overflow-y-auto">
                            {{ $submission->summary }}
                        </div>
                    </div>

                    {{-- ══════════ FILES ══════════ --}}
                    <div>
                        <p
                            class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400 mb-2">
                            Attachment
                        </p>
                        <div class="flex flex-wrap gap-2">

                            {{-- Progress Report Files --}}
                            @if ($activeTab === 'progress_report')
                                @if ($submission->report_path)
                                    <a href="{{ Storage::disk('public')->url($submission->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20
                                               border border-rose-200 dark:border-rose-800
                                               text-rose-700 dark:text-rose-300 text-[11px] font-semibold">
                                        <flux:icon.document-text class="size-4" />
                                        Report PDF
                                    </a>
                                @endif
                                @if ($submission->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($submission->ppt_path) }}" target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20
                                               border border-orange-200 dark:border-orange-800
                                               text-orange-700 dark:text-orange-300 text-[11px] font-semibold">
                                        <flux:icon.presentation-chart-bar class="size-4" />
                                        Presentation
                                    </a>
                                @endif
                            @endif

                            {{-- Final Report Files --}}
                            @if ($activeTab === 'final_report')
                                @if ($submission->report_path)
                                    <a href="{{ Storage::disk('public')->url($submission->report_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20
                                               border border-rose-200 dark:border-rose-800
                                               text-rose-700 dark:text-rose-300 text-[11px] font-semibold">
                                        <flux:icon.document-text class="size-4" />
                                        Report
                                    </a>
                                @endif
                                @if ($submission->ppt_path)
                                    <a href="{{ Storage::disk('public')->url($submission->ppt_path) }}" target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20
                                               border border-orange-200 dark:border-orange-800
                                               text-orange-700 dark:text-orange-300 text-[11px] font-semibold">
                                        <flux:icon.presentation-chart-bar class="size-4" />
                                        Presentation
                                    </a>
                                @endif
                                @if ($submission->research_output)
                                    <a href="{{ Storage::disk('public')->url($submission->research_output) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20
                                               border border-blue-200 dark:border-blue-800
                                               text-blue-700 dark:text-blue-300 text-[11px] font-semibold">
                                        <flux:icon.document-arrow-down class="size-4" />
                                        Research Output
                                    </a>
                                @endif
                                @if ($submission->submission_proof)
                                    <a href="{{ Storage::disk('public')->url($submission->submission_proof) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                               bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20
                                               border border-emerald-200 dark:border-emerald-800
                                               text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">
                                        <flux:icon.photo class="size-4" />
                                        Bukti Submit
                                    </a>
                                @endif
                            @endif

                            {{-- Output Files --}}
                            @if ($activeTab === 'output')
                                <a href="{{ $submission->journal_link }}" target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                           bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20
                                           border border-blue-200 dark:border-blue-800
                                           text-blue-700 dark:text-blue-300 text-[11px] font-semibold">
                                    <flux:icon.arrow-up-right class="size-4" />
                                    View Journal
                                </a>
                            @endif

                        </div>
                    </div>

                    {{-- ══════════ OUTPUT DETAILS ══════════ --}}
                    @if ($activeTab === 'output')
                        <div class="rounded-lg border border-slate-200 dark:border-zinc-700 overflow-hidden">
                            <div
                                class="px-3 py-2.5 bg-slate-50 dark:bg-zinc-800/40 border-b border-slate-200 dark:border-zinc-700">
                                <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                    Detail Jurnal
                                </span>
                            </div>
                            <div class="px-3 py-3 grid grid-cols-2 gap-3">
                                <div>
                                    <p
                                        class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                        Journal</p>
                                    <p class="text-[11px] font-medium text-slate-900 dark:text-zinc-100 mt-0.5">
                                        {{ $submission->journal_name }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                        Level</p>
                                    {{-- <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold mt-0.5
                                                 bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                                        {{ $submission->level }}
                                    </span> --}}
                                    @php $levelMeta = $this->levelMeta($submission->level); @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold mt-0.5
                                                {{ $levelMeta['class'] }}">
                                        <flux:icon.star class="size-2.5" />
                                        {{ $submission->level }}
                                    </span>
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                        Edition</p>
                                    <p class="text-[11px] font-medium text-slate-900 dark:text-zinc-100 mt-0.5">
                                        {{ $submission->edition }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 dark:text-zinc-400">
                                        Volume</p>
                                    <p class="text-[11px] font-medium text-slate-900 dark:text-zinc-100 mt-0.5">
                                        {{ $submission->volume }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════ REVIEWER NOTES (Progress Only) ══════════ --}}
                    {{-- ══════════ REVIEWER NOTES (Progress Only) ══════════ --}}
                    @if ($activeTab === 'progress_report' && $submission->reviewerNotes->count() > 0)
                        <div class="rounded-lg border border-violet-200 dark:border-violet-800 overflow-hidden">
                            <div
                                class="px-3 py-2.5 bg-violet-50 dark:bg-violet-900/20
                    border-b border-violet-200 dark:border-violet-800
                    flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <flux:icon.clipboard-document-check
                                        class="size-3.5 text-violet-600 dark:text-violet-400" />
                                    <span class="text-[11px] font-semibold text-violet-800 dark:text-violet-300">
                                        Reviewer Notes
                                    </span>
                                    <span
                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded
                             bg-violet-200 text-violet-800
                             dark:bg-violet-800 dark:text-violet-200">
                                        {{ $submission->reviewerNotes->count() }}
                                    </span>
                                    @php $pendingNotesCount = $submission->reviewerNotes->where('is_approved', false)->count(); @endphp
                                    @if ($pendingNotesCount > 0)
                                        <span
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                 bg-amber-200 text-amber-800
                                 dark:bg-amber-800 dark:text-amber-200">
                                            {{ $pendingNotesCount }} pending
                                        </span>
                                    @endif
                                </span>
                            </div>
                            <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                                @foreach ($submission->reviewerNotes as $note)
                                    <div wire:key="rn-{{ $note->id }}"
                                        class="rounded-lg border p-2.5
                        {{ $note->is_approved
                            ? 'bg-emerald-50 dark:bg-emerald-900/15 border-emerald-200 dark:border-emerald-800'
                            : 'bg-amber-50 dark:bg-amber-900/15 border-amber-200 dark:border-amber-800' }}">

                                        {{-- Header --}}
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold
                                        {{ $note->is_approved
                                            ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                            : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                    {{ strtoupper(substr($note->reviewer?->full_name ?? 'R', 0, 1)) }}
                                                </div>
                                                <p
                                                    class="text-[10px] font-semibold
                                    {{ $note->is_approved ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300' }}">
                                                    {{ $note->reviewer?->full_name ?? 'Reviewer' }}
                                                </p>
                                                <span
                                                    class="text-[9px] {{ $note->is_approved ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                    {{ $note->created_at?->diffForHumans() }}
                                                </span>
                                            </div>
                                            <span
                                                class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase
                                    {{ $note->is_approved
                                        ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                                        : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' }}">
                                                {{ $note->is_approved ? 'Approved' : 'Revision' }}
                                            </span>
                                        </div>

                                        {{-- Comment --}}
                                        @if ($note->comment)
                                            <p
                                                class="text-[11px] whitespace-pre-wrap mt-1
                                {{ $note->is_approved ? 'text-emerald-900 dark:text-emerald-100' : 'text-amber-900 dark:text-amber-100' }}">
                                                {{ $note->comment }}
                                            </p>
                                        @endif

                                        {{-- Recommendation --}}
                                        @if ($note->recommendation)
                                            <div
                                                class="mt-1.5 pt-1.5 border-t
                                    {{ $note->is_approved ? 'border-emerald-200 dark:border-emerald-800' : 'border-amber-200 dark:border-amber-800' }}">
                                                <p
                                                    class="text-[10px]
                                    {{ $note->is_approved ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                                                    <span class="font-semibold">Rekomendasi:</span>
                                                    {{ $note->recommendation }}
                                                </p>
                                            </div>
                                        @endif

                                        {{-- ══════════ ADMIN ACTIONS (Accept / Revise per note) ══════════ --}}
                                        @if ($this->isAdmin())
                                            <div
                                                class="mt-2 pt-2 border-t
                                    {{ $note->is_approved ? 'border-emerald-200 dark:border-emerald-800' : 'border-amber-200 dark:border-amber-800' }}
                                    flex justify-end gap-1.5">

                                                @if (!$note->is_approved)
                                                    <button type="button"
                                                        wire:click="acceptReviewerNote({{ $note->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="acceptReviewerNote,reviseReviewerNote"
                                                        class="inline-flex items-center gap-1
                                        px-2.5 py-1 rounded-md
                                        text-[10px] font-semibold text-white
                                        bg-gradient-to-r from-emerald-600 to-emerald-500
                                        hover:from-emerald-700 hover:to-emerald-600
                                        shadow-sm shadow-emerald-500/20
                                        disabled:opacity-60 transition-all">
                                                        <span wire:loading.remove wire:target="acceptReviewerNote"
                                                            class="inline-flex items-center gap-1">
                                                            <flux:icon.check-circle class="size-2.5" />
                                                            Accept
                                                        </span>
                                                        <span wire:loading.flex wire:target="acceptReviewerNote"
                                                            class="items-center gap-1">
                                                            <svg class="animate-spin size-2.5" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12"
                                                                    cy="12" r="10" stroke="currentColor"
                                                                    stroke-width="4" />
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
                                        px-2.5 py-1 rounded-md
                                        text-[10px] font-semibold text-white
                                        bg-gradient-to-r from-amber-500 to-amber-400
                                        hover:from-amber-600 hover:to-amber-500
                                        shadow-sm shadow-amber-500/20
                                        disabled:opacity-60 transition-all">
                                                        <span wire:loading.remove wire:target="reviseReviewerNote"
                                                            class="inline-flex items-center gap-1">
                                                            <flux:icon.pencil-square class="size-2.5" />
                                                            Revise
                                                        </span>
                                                        <span wire:loading.flex wire:target="reviseReviewerNote"
                                                            class="items-center gap-1">
                                                            <svg class="animate-spin size-2.5" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12"
                                                                    cy="12" r="10" stroke="currentColor"
                                                                    stroke-width="4" />
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
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 overflow-hidden">
                        <div
                            class="px-3 py-2.5 bg-amber-50 dark:bg-amber-900/20
                                    border-b border-amber-200 dark:border-amber-800
                                    flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <flux:icon.chat-bubble-left-right
                                    class="size-3.5 text-amber-600 dark:text-amber-400" />
                                <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                    Admin Notes
                                </span>
                                <span
                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded
                                             bg-amber-200 text-amber-800
                                             dark:bg-amber-800 dark:text-amber-200">
                                    {{ $submission->adminNotes->count() }}
                                </span>
                            </span>
                        </div>

                        {{-- List Notes --}}
                        <div class="p-3 space-y-2 max-h-60 overflow-y-auto">
                            @forelse ($submission->adminNotes as $note)
                                <div wire:key="an-{{ $note->id }}"
                                    class="rounded-lg border border-amber-200 dark:border-amber-800
                                           bg-amber-50 dark:bg-amber-900/20 p-2.5">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-5 h-5 rounded-full bg-amber-200 dark:bg-amber-800/50
                                                        flex items-center justify-center text-[8px] font-bold
                                                        text-amber-800 dark:text-amber-300">
                                                {{ strtoupper(substr($note->admin?->full_name ?? 'A', 0, 1)) }}
                                            </div>
                                            <p class="text-[10px] font-semibold text-amber-800 dark:text-amber-300">
                                                {{ $note->admin?->full_name ?? 'Admin' }}
                                            </p>
                                            <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                                {{ $note->created_at?->diffForHumans() }}
                                            </span>
                                        </div>
                                        @if ($this->isAdmin())
                                            <button type="button" wire:click="deleteAdminNote({{ $note->id }})"
                                                wire:confirm="Hapus catatan ini?"
                                                class="p-1 rounded-md text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/30">
                                                <flux:icon.trash class="size-3" />
                                            </button>
                                        @endif
                                    </div>
                                    @if ($note->comment)
                                        <p class="text-[11px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                            {{ $note->comment }}
                                        </p>
                                    @endif
                                    @if ($note->recommendation)
                                        <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                                            <span class="font-semibold">Rekomendasi:</span>
                                            {{ $note->recommendation }}
                                        </p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-center text-[11px] text-slate-400 dark:text-zinc-500 italic py-4">
                                    Belum ada catatan admin.
                                </p>
                            @endforelse
                        </div>

                        {{-- Add Note Form --}}
                        @if ($this->isAdmin())
                            <div
                                class="border-t border-amber-200 dark:border-amber-800
                                        bg-amber-50/50 dark:bg-amber-900/10 p-3 space-y-2">
                                <p
                                    class="text-[10px] font-semibold text-amber-800 dark:text-amber-300 uppercase tracking-wider">
                                    Tambah Catatan
                                </p>

                                <textarea wire:model="adminNoteForm.comment" rows="2" placeholder="Tulis catatan..."
                                    class="block w-full rounded-md shadow-sm text-[11px] resize-none
                                           border-amber-300 dark:border-amber-700
                                           bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-amber-500 focus:ring-amber-500 py-2 px-2.5"></textarea>

                                <input type="text" wire:model="adminNoteForm.recommendation"
                                    placeholder="Rekomendasi (opsional)"
                                    class="block w-full rounded-md shadow-sm text-[11px]
                                           border-amber-300 dark:border-amber-700
                                           bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:border-amber-500 focus:ring-amber-500 py-2 px-2.5" />

                                <div class="flex justify-end">
                                    <button type="button" wire:click="saveAdminNote" wire:loading.attr="disabled"
                                        wire:target="saveAdminNote"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md
                                               text-[11px] font-semibold text-white
                                               bg-gradient-to-r from-amber-500 to-amber-400
                                               hover:from-amber-600 hover:to-amber-500
                                               shadow-sm shadow-amber-500/20
                                               disabled:opacity-60 transition-all">
                                        <span wire:loading.remove wire:target="saveAdminNote"
                                            class="inline-flex items-center gap-1">
                                            <flux:icon.plus class="size-3" />
                                            Simpan
                                        </span>
                                        <span wire:loading.flex wire:target="saveAdminNote">Menyimpan...</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div
                class="shrink-0 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">

                <flux:button type="button" @click="show = false" variant="ghost" size="sm">
                    Tutup
                </flux:button>

                {{-- Actions (kalau ada submission & admin) --}}
                @if ($submission && $this->isAdmin())
                    @php $subMeta = $submission->statusMeta(); @endphp
                    <div class="flex flex-col-reverse sm:flex-row gap-2">

                        {{-- ══════════ PROGRESS REPORT: ASSIGN REVIEWER (status: submitted) ══════════ --}}
                        @if ($activeTab === 'progress_report' && $submission->status === 'submitted')
                            <flux:button type="button" x-data
                                x-on:click="$dispatch('open-assign-reviewer-progress', { id: {{ $submission->id }} })"
                                variant="primary" size="sm" icon="user-plus">
                                Assign Reviewer
                            </flux:button>
                        @endif

                        {{-- ══════════ PROGRESS REPORT: SUBMIT (status: pending, revised) ══════════ --}}
                        @if ($activeTab === 'progress_report' && in_array($submission->status, ['pending', 'revised']))
                            <flux:button type="button" wire:click="submit" variant="primary" size="sm"
                                wire:loading.attr="disabled" wire:target="submit">
                                <span wire:loading.remove wire:target="submit">Submit</span>
                                <span wire:loading.flex wire:target="submit">Memproses...</span>
                            </flux:button>
                        @endif

                        {{-- ══════════ PROGRESS REPORT: ACCEPT (status: under_review) ══════════ --}}
                        @if ($activeTab === 'progress_report' && $submission->status === 'under_review')
                            <flux:button type="button" wire:click="acceptProgressReport" variant="primary"
                                size="sm"
                                wire:confirm="Accept Progress Report ini? Pastikan reviewer sudah selesai mereview."
                                wire:loading.attr="disabled" wire:target="acceptProgressReport">
                                <span wire:loading.remove wire:target="acceptProgressReport"
                                    class="inline-flex items-center gap-1">
                                    <flux:icon.check-circle class="size-3.5" />
                                    Accept Progress Report
                                </span>
                                <span wire:loading.flex wire:target="acceptProgressReport">Memproses...</span>
                            </flux:button>
                        @endif

                        {{-- ══════════ FINAL REPORT & OUTPUT: ACCEPT (status: pending, revised) ══════════ --}}
                        @if (in_array($activeTab, ['final_report', 'output']) && in_array($submission->status, ['pending', 'revised']))
                            <flux:button type="button" wire:click="accept" variant="primary" size="sm"
                                wire:loading.attr="disabled" wire:target="accept">
                                <span wire:loading.remove wire:target="accept">Accept</span>
                                <span wire:loading.flex wire:target="accept">Memproses...</span>
                            </flux:button>
                        @endif

                        {{-- ══════════ REJECT (semua tab, kalau belum rejected) ══════════ --}}
                        @if ($submission->status !== 'rejected')
                            <flux:button type="button" wire:click="reject" variant="danger" size="sm"
                                wire:confirm="Yakin ingin menolak submission ini?" wire:loading.attr="disabled"
                                wire:target="reject">
                                <span wire:loading.remove wire:target="reject">Reject</span>
                                <span wire:loading.flex wire:target="reject">Memproses...</span>
                            </flux:button>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
