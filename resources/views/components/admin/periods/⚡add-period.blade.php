<?php

use Livewire\Component;
use App\Livewire\Forms\PeriodForm;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public PeriodForm $form;

    public function create()
    {
        try {
            $this->form->create();
            session()->flash('success', 'Store berhasil ditambahkan.');
            $this->dispatch('period-added', message: 'Store berhasil ditambahkan.');
        } catch (ValidationException $e) {
            session()->flash('error', 'Terjadi kesalahan saat membuat store: ' . $e->getMessage());
            $this->dispatch('period-error', message: 'Terjadi kesalahan saat membuat store: ' . $e->getMessage());
        }
    }
};
?>
<div x-data="{
        show: false,
        errorMessage: '',
        successMessage: '',
        init() {
            window.addEventListener('add-period-modal', () => {
                this.errorMessage = '';
                this.successMessage = '';
                this.show = true;
            });
            window.addEventListener('period-error', (e) => {
                this.errorMessage = e.detail.message;
            });
            window.addEventListener('period-added', (e) => {
                this.show = false;
                this.successMessage = e.detail.message;
            });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-lg flex-col overflow-hidden
                bg-surface-container-lowest dark:bg-zinc-900
                rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-outline-variant/50 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="create" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-emerald-600 to-emerald-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20
                                    flex items-center justify-center shrink-0">
                            <flux:icon.calendar-days class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                {{ 'Add New Period' }}
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-snug">
                                {{ __('Lengkapi informasi periode hibah penelitian dan pengabdian.') }}
                            </p>
                        </div>
                    </div>

                    <button type="button" @click="show = false"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-3 rounded-lg
                                bg-error-container/50 dark:bg-rose-900/20
                                border border-error/30 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-4 text-error dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] leading-relaxed
                                  text-on-error-container dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Period Name --}}
                <div>
                    <label for="periode"
                        class="block text-[12px] font-medium
                               text-on-surface-variant dark:text-zinc-300 mb-1">
                        Period Name <span class="text-error">*</span>
                    </label>
                    <input type="text" id="periode" wire:model="form.periode"
                        placeholder="Contoh: 2025/2026"
                        required
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-outline-variant dark:border-zinc-600
                               bg-surface-container-lowest dark:bg-zinc-800
                               text-on-surface dark:text-zinc-100
                               placeholder:text-outline dark:placeholder-zinc-500
                               focus:border-emerald-500 focus:ring-emerald-500
                               py-2 px-3" />
                    @error('form.periode')
                        <p class="mt-1 flex items-center gap-1 text-[11px]
                                  text-error dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Open From / Open To --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="open_from"
                            class="block text-[12px] font-medium
                                   text-on-surface-variant dark:text-zinc-300 mb-1">
                            Open From <span class="text-error">*</span>
                        </label>
                        <input type="datetime-local" id="open_from"
                            wire:model="form.open_from"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   focus:border-emerald-500 focus:ring-emerald-500
                                   py-2 px-3
                                   dark:[color-scheme:dark]" />
                        @error('form.open_from')
                            <p class="mt-1 flex items-center gap-1 text-[11px]
                                      text-error dark:text-rose-400">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="open_to"
                            class="block text-[12px] font-medium
                                   text-on-surface-variant dark:text-zinc-300 mb-1">
                            Open To <span class="text-error">*</span>
                        </label>
                        <input type="datetime-local" id="open_to"
                            wire:model="form.open_to"
                            class="block w-full rounded-md shadow-sm text-[12px]
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   focus:border-emerald-500 focus:ring-emerald-500
                                   py-2 px-3
                                   dark:[color-scheme:dark]" />
                        @error('form.open_to')
                            <p class="mt-1 flex items-center gap-1 text-[11px]
                                      text-error dark:text-rose-400">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Is Active --}}
                <div class="rounded-xl border p-3.5
                            border-outline-variant/60 dark:border-zinc-700
                            bg-surface-container-low dark:bg-zinc-800/40">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="form.is_active"
                            class="mt-0.5 rounded border-outline-variant
                                   text-emerald-600 focus:ring-emerald-500
                                   dark:border-zinc-600 dark:bg-zinc-900" />
                        <div class="flex-1 min-w-0">
                            <span class="block text-[12px] font-medium
                                         text-on-surface dark:text-zinc-100">
                                Jadikan periode ini sebagai periode aktif
                            </span>
                            <span class="block mt-0.5 text-[11px] leading-relaxed
                                         text-on-surface-variant dark:text-zinc-400">
                                Periode aktif akan otomatis menonaktifkan periode lain
                                yang sedang aktif.
                            </span>
                        </div>
                    </label>
                    @error('form.is_active')
                        <p class="mt-2 flex items-center gap-1 text-[11px]
                                  text-error dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4
                        border-t border-outline-variant/40 dark:border-zinc-700
                        bg-surface-container-lowest dark:bg-zinc-900
                        rounded-b-2xl sm:rounded-b-xl">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-4 py-2 text-[12px] font-medium rounded-md
                           text-on-surface-variant dark:text-zinc-300
                           bg-surface-container-lowest dark:bg-zinc-800
                           border border-outline-variant dark:border-zinc-600
                           hover:bg-surface-container-low dark:hover:bg-zinc-700
                           focus:outline-none focus:ring-2 focus:ring-primary
                           transition-colors duration-150">
                    Batal
                </button>

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="create"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-5 py-2 text-[12px] font-medium text-white rounded-md
                           bg-gradient-to-r from-emerald-600 to-emerald-500
                           hover:from-emerald-700 hover:to-emerald-600
                           focus:outline-none focus:ring-2 focus:ring-emerald-500
                           transition-all duration-200
                           disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="create">Submit</span>
                    <span wire:loading.flex wire:target="create"
                        class="items-center gap-1.5">
                        <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
