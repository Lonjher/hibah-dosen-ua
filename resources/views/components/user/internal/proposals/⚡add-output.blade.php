<?php

use App\Livewire\Forms\OutputForm;
use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public OutputForm $form;

    #[On('open-add-output')]
    public function load(int $proposalId): void
    {
        $this->form->reset();
        $this->resetErrorBag();
        $this->resetValidation();

        $this->form->proposal_id = $proposalId;
        $this->form->status = 'pending';

        $this->dispatch('show-add-output');
    }

    public function save(): void
    {
        $this->form->validate();

        $proposal = Proposal::where('user_id', auth()->id())->findOrFail($this->form->proposal_id);

        if ($proposal->finalReport?->status !== 'accepted' || $proposal->output) {
            Flux::toast('Output tidak dapat diunggah.', variant: 'danger');
            return;
        }

        $this->form->create();

        Flux::toast('Output berhasil diunggah.', variant: 'success');
        $this->dispatch('output-added');
        $this->form->reset();
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-add-output', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('output-error', (e) => { this.errorMessage = e.detail.message; });
            window.addEventListener('output-added', () => { this.show = false; });
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
                            <flux:icon.trophy class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Upload Output
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Unggah luaran penelitian.
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

                {{-- Journal Name --}}
                <div>
                    <x-input
                        wire:model="form.journal_name"
                        label="Journal Name"
                        required
                        rounded="full"
                        placeholder="Nama jurnal" />
                    @error('form.journal_name')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Journal Link --}}
                <div>
                    <x-input
                        type="url"
                        wire:model="form.journal_link"
                        label="Journal Link"
                        required
                        rounded="full"
                        placeholder="https://..." />
                    @error('form.journal_link')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Edition + Volume + Level --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                    {{-- Edition --}}
                    <div>
                        <x-input
                            wire:model="form.edition"
                            label="Edition"
                            required
                            rounded="full" />
                        @error('form.edition')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Volume --}}
                    <div>
                        <x-input
                            wire:model="form.volume"
                            label="Volume"
                            required
                            rounded="full" />
                        @error('form.volume')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Level --}}
                    <div>
                        <x-select
                            wire:model="form.level"
                            label="Level"
                            required
                            size="lg"
                            color="emerald">
                            <option value="">— Pilih Level —</option>
                            <option value="Scopus">Scopus</option>
                            <option value="Sinta 1">Sinta 1</option>
                            <option value="Sinta 2">Sinta 2</option>
                            <option value="Sinta 3">Sinta 3</option>
                            <option value="Sinta 4">Sinta 4</option>
                            <option value="Sinta 5">Sinta 5</option>
                            <option value="Sinta 6">Sinta 6</option>
                        </x-select>
                        @error('form.level')
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
                           hover:shadow-sm hover:shadow-emerald-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Batal
                </button>

                <button type="submit"
                    wire:loading.attr="disabled" wire:target="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                           bg-emerald-600/90 hover:bg-emerald-600
                           shadow-sm shadow-emerald-500/20
                           hover:shadow-sm hover:shadow-emerald-500/30
                           disabled:opacity-60 disabled:cursor-wait
                           hover:scale-[1.02] active:scale-[0.97]
                           disabled:hover:scale-100
                           transition-all duration-150">
                    <svg wire:loading wire:target="save" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Upload</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
