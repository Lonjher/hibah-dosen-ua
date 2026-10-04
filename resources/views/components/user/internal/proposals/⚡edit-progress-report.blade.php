<?php

use App\Livewire\Forms\ProgressReportForm;
use App\Models\ProgressReport;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ProgressReportForm $form;

    public $report_file = null;
    public $ppt_file = null;
    public ?string $existing_report_path = null;
    public ?string $existing_ppt_path = null;

    #[On('open-edit-progress-report')]
    public function load(int $id): void
    {
        $report = ProgressReport::whereHas('proposal', fn ($q) => $q->where('user_id', auth()->id()))
            ->findOrFail($id);

        if (! in_array($report->status, ['pending', 'revised'])) {
            Flux::toast('Progress report ini tidak dapat diedit.', variant: 'danger');
            return;
        }

        $this->form->setProgressReport($report);
        $this->existing_report_path = $report->report_path;
        $this->existing_ppt_path    = $report->ppt_path;
        $this->reset(['report_file', 'ppt_file']);
        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-edit-progress-report');
    }

    public function save(): void
    {
        $this->form->validate();

        $this->validate([
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'ppt_file'    => ['nullable', 'file', 'mimes:ppt,pptx', 'max:20480'],
        ]);

        if ($this->report_file) {
            $this->form->report_path = $this->report_file->store('progress-reports/reports', 'public');
        }
        if ($this->ppt_file) {
            $this->form->ppt_path = $this->ppt_file->store('progress-reports/ppt', 'public');
        }

        $this->form->status = 'pending';
        $this->form->update();

        Flux::toast('Progress Report berhasil diperbarui.', variant: 'success');
        $this->dispatch('progress-report-updated');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->reset(['report_file', 'ppt_file', 'existing_report_path', 'existing_ppt_path']);
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
            window.addEventListener('show-edit-progress-report', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('progress-report-error', (e) => { this.errorMessage = e.detail.message; });
            window.addEventListener('progress-report-updated', () => { this.show = false; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-full shadow-2xl
                border border-slate-200 dark:border-zinc-700
                hover:shadow-blue-500/15 transition-shadow duration-300"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER blue --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-full">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Edit Progress Report
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Perbarui laporan kemajuan.
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
                        color="blue"
                        placeholder="Ringkasan progres..." />
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

                {{-- File Laporan + File Presentasi --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    {{-- File Laporan --}}
                    <div>
                        <x-input
                            type="file"
                            wire:model="report_file"
                            label="File Laporan (PDF) — Opsional"
                            accept=".pdf"
                            rounded="full" />

                        @if ($existing_report_path)
                            <p class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                <flux:icon.document-text class="size-3 shrink-0" />
                                File saat ini: {{ basename($existing_report_path) }}
                            </p>
                        @endif
                        <div wire:loading wire:target="report_file"
                            class="mt-1 text-[10px] text-blue-600 dark:text-blue-400">
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
                            label="File Presentasi (PPT) — Opsional"
                            accept=".ppt,.pptx"
                            rounded="full" />

                        @if ($existing_ppt_path)
                            <p class="mt-1 text-[10px] text-slate-500 dark:text-zinc-500 flex items-center gap-1">
                                <flux:icon.document-text class="size-3 shrink-0" />
                                File saat ini: {{ basename($existing_ppt_path) }}
                            </p>
                        @endif
                        <div wire:loading wire:target="ppt_file"
                            class="mt-1 text-[10px] text-blue-600 dark:text-blue-400">
                            Uploading...
                        </div>
                        @error('ppt_file')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-full">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40
                           hover:shadow-sm hover:shadow-blue-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Batal
                </button>

                <button type="submit"
                    wire:loading.attr="disabled" wire:target="save,report_file,ppt_file"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                           bg-blue-600/90 hover:bg-blue-600
                           shadow-sm shadow-blue-500/20
                           hover:shadow-sm hover:shadow-blue-500/30
                           disabled:opacity-60 disabled:cursor-wait
                           hover:scale-[1.02] active:scale-[0.97]
                           disabled:hover:scale-100
                           transition-all duration-150">
                    <svg wire:loading wire:target="save" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Update</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
