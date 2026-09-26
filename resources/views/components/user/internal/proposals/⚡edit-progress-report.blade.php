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
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
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
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3"></textarea>
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
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    @error('form.keyword')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            File Laporan (PDF) <span class="text-slate-400">(opsional)</span>
                        </label>
                        <input type="file" wire:model="report_file" accept=".pdf"
                            class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                   file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                   file:text-[11px] file:font-medium file:bg-blue-50 file:text-blue-700" />
                        @if ($existing_report_path)
                            <p class="mt-1 text-[10px] text-slate-500">
                                File saat ini: {{ basename($existing_report_path) }}
                            </p>
                        @endif
                        @error('report_file')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            File Presentasi (PPT) <span class="text-slate-400">(opsional)</span>
                        </label>
                        <input type="file" wire:model="ppt_file" accept=".ppt,.pptx"
                            class="block w-full text-[11px] text-slate-600 dark:text-zinc-400
                                   file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                   file:text-[11px] file:font-medium file:bg-blue-50 file:text-blue-700" />
                        @if ($existing_ppt_path)
                            <p class="mt-1 text-[10px] text-slate-500">
                                File saat ini: {{ basename($existing_ppt_path) }}
                            </p>
                        @endif
                        @error('ppt_file')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm"
                    wire:loading.attr="disabled" wire:target="save,report_file,ppt_file">
                    <span wire:loading.remove wire:target="save">Update</span>
                    <span wire:loading.flex wire:target="save">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
