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
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            <div class="shrink-0 bg-gradient-to-r from-emerald-600 to-emerald-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
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
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-3 rounded-lg
                                bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] text-rose-700 dark:text-rose-300" x-text="errorMessage"></p>
                    </div>
                </template>

                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Ringkasan <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="form.summary" rows="3"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3"></textarea>
                    @error('form.summary')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Kata Kunci <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="form.keyword"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-emerald-500 focus:ring-emerald-500 py-2 px-3" />
                    @error('form.keyword')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            File Laporan (PDF) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" wire:model="report_file" accept=".pdf"
                            class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                   file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                   file:text-[11px] file:font-medium file:bg-emerald-50 file:text-emerald-700" />
                        @error('report_file')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            File Presentasi (PPT) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" wire:model="ppt_file" accept=".ppt,.pptx"
                            class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                   file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                   file:text-[11px] file:font-medium file:bg-emerald-50 file:text-emerald-700" />
                        @error('ppt_file')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Research Output (PDF/DOC) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" wire:model="research_output_file" accept=".pdf,.doc,.docx"
                            class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                   file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                   file:text-[11px] file:font-medium file:bg-emerald-50 file:text-emerald-700" />
                        @error('research_output_file')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Bukti Submit (Image) <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" wire:model="submission_proof_file" accept="image/*"
                            class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                   file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                   file:text-[11px] file:font-medium file:bg-emerald-50 file:text-emerald-700" />
                        @error('submission_proof_file')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Upload</span>
                    <span wire:loading.flex wire:target="save">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
