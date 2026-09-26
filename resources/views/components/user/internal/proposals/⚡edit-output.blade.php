<?php

use App\Livewire\Forms\OutputForm;
use App\Models\Output;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public OutputForm $form;

    #[On('open-edit-output')]
    public function load(int $id): void
    {
        $output = Output::whereHas('proposal', fn ($q) => $q->where('user_id', auth()->id()))
            ->findOrFail($id);

        if (! in_array($output->status, ['pending', 'revised'])) {
            Flux::toast('Output ini tidak dapat diedit.', variant: 'danger');
            return;
        }

        $this->form->setOutput($output);
        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('show-edit-output');
    }

    public function save(): void
    {
        $this->form->validate();

        $this->form->status = 'pending';
        $this->form->update();

        Flux::toast('Output berhasil diperbarui.', variant: 'success');
        $this->dispatch('output-updated');
        $this->form->reset();
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-edit-output', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('output-error', (e) => { this.errorMessage = e.detail.message; });
            window.addEventListener('output-updated', () => { this.show = false; });
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
                                Edit Output
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Perbarui luaran penelitian.
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
                        Journal Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="form.journal_name"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    @error('form.journal_name')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Journal Link <span class="text-rose-500">*</span>
                    </label>
                    <input type="url" wire:model="form.journal_link"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    @error('form.journal_link')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Edition <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="form.edition"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                   border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Volume <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="form.volume"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                   border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Level <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model="form.level"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                   border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:border-blue-500 focus:ring-blue-500 py-2 px-3">
                            <option value="">— Pilih —</option>
                            <option value="Lokal">Lokal</option>
                            <option value="Nasional">Nasional</option>
                            <option value="Internasional">Internasional</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Update</span>
                    <span wire:loading.flex wire:target="save">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
