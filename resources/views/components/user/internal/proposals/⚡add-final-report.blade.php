<?php

use App\Livewire\Forms\FinalReportForm;
use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public FinalReportForm $form;

    public $report_file = null;
    public $ppt_file = null;
    public $research_output_file = null;
    public $submission_proof_file = null;

    #[On('open-add-final-report')]
    public function load(int $proposalId): void
    {
        $this->form->reset();
        $this->reset(['report_file', 'ppt_file', 'research_output_file', 'submission_proof_file']);
        $this->resetErrorBag();
        $this->resetValidation();

        $this->form->proposal_id = $proposalId;
        $this->form->status = 'pending';

        $this->dispatch('show-add-final-report');
    }

    public function save(): void
    {
        $this->form->validate();

        $this->validate([
            'report_file'           => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'ppt_file'              => ['required', 'file', 'mimes:ppt,pptx', 'max:20480'],
            'research_output_file'  => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'submission_proof_file' => ['required', 'image', 'max:5120'],
        ], [
            'report_file.required'           => 'File laporan wajib diunggah.',
            'ppt_file.required'              => 'File presentasi wajib diunggah.',
            'research_output_file.required'  => 'File output penelitian wajib diunggah.',
            'submission_proof_file.required' => 'Bukti submit wajib diunggah.',
        ]);

        $proposal = Proposal::where('user_id', auth()->id())->findOrFail($this->form->proposal_id);

        if ($proposal->progressReport?->status !== 'accepted' || $proposal->finalReport) {
            Flux::toast('Final report tidak dapat diunggah.', variant: 'danger');
            return;
        }

        $this->form->report_path      = $this->report_file->store('final-reports/reports', 'public');
        $this->form->ppt_path         = $this->ppt_file->store('final-reports/ppt', 'public');
        $this->form->research_output  = $this->research_output_file->store('final-reports/output', 'public');
        $this->form->submission_proof = $this->submission_proof_file->store('final-reports/proofs', 'public');

        $this->form->create();

        Flux::toast('Final Report berhasil diunggah.', variant: 'success');
        $this->dispatch('final-report-added');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->reset(['report_file', 'ppt_file', 'research_output_file', 'submission_proof_file']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-add-final-report', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('final-report-error', (e) => { this.errorMessage = e.detail.message; });
            window.addEventListener('final-report-added', () => { this.show = false; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-full shadow-2xl
                border border-slate-200 dark:border-zinc-700
                hover:shadow-emerald-500/15 transition-shadow duration-300"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER emerald --}}
            <div class="shrink-0 bg-gradient-to-r from-emerald-600 to-emerald-500
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-full">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.document-check class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Upload Final Report
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Unggah laporan akhir penelitian.
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95
                               transition-all duration-150">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-3 rounded-2xl
                                bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] text-rose-700 dark:text-rose-300" x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Ringkasan --}}
                <div>
                    <x-textarea
                        wire:model="form.summary"
                        label="Ringkasan"
                        required
                        rows="3"
                        rounded="full"
                        color="emerald"
                        placeholder="Ringkasan laporan akhir..." />
                    @error('form.summary')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kata Kunci --}}
                <div>
                    <x-input
                        wire:model="form.keyword"
                        label="Kata Kunci"
                        type="text"
                        rounded="full"
                        required />
                    @error('form.keyword')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File uploads grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    {{-- File Laporan --}}
                    <div>
                        <x-input
                            type="file"
                            wire:model="report_file"
                            label="File Laporan (PDF)"
                            accept=".pdf"
                            rounded="full"
                            required
                            hint="Max 10 MB · format PDF" />
                        <div wire:loading wire:target="report_file"
                            class="mt-1 text-[10px] text-emerald-600 dark:text-emerald-400">
                            Uploading...
                        </div>
                        @error('report_file')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- File Presentasi --}}
                    <div>
                        <x-input
                            type="file"
                            wire:model="ppt_file"
                            label="File Presentasi (PPT)"
                            accept=".ppt,.pptx"
                            rounded="full"
                            required
                            hint="Max 10 MB · format PPT/PPTX" />
                        <div wire:loading wire:target="ppt_file"
                            class="mt-1 text-[10px] text-emerald-600 dark:text-emerald-400">
                            Uploading...
                        </div>
                        @error('ppt_file')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Research Output --}}
                    <div>
                        <x-input
                            type="file"
                            wire:model="research_output_file"
                            label="Research Output (PDF/DOC)"
                            accept=".pdf,.doc,.docx"
                            rounded="full"
                            required
                            hint="Max 10 MB · PDF, DOC, DOCX" />
                        <div wire:loading wire:target="research_output_file"
                            class="mt-1 text-[10px] text-emerald-600 dark:text-emerald-400">
                            Uploading...
                        </div>
                        @error('research_output_file')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Bukti Submit --}}
                    <div>
                        <x-input
                            type="file"
                            wire:model="submission_proof_file"
                            label="Bukti Submit (Image)"
                            accept="image/*"
                            rounded="full"
                            required
                            hint="Max 10 MB · JPG, PNG" />
                        <div wire:loading wire:target="submission_proof_file"
                            class="mt-1 text-[10px] text-emerald-600 dark:text-emerald-400">
                            Uploading...
                        </div>
                        @error('submission_proof_file')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-full">

                <flux:button type="button" @click="show = false" variant="danger">
                    Cancel
                </flux:button>

                <flux:button type="submit"
                    wire:loading.attr="disabled" wire:target="save">
                    <svg wire:loading wire:target="save" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Upload</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
