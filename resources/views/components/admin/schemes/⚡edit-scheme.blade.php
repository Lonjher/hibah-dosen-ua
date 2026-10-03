<?php

use App\Livewire\Forms\ResearchSchemeForm;
use App\Models\ResearchScheme;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ResearchSchemeForm $form;
    public ?int $editingId = null;

    #[On('open-edit-scheme')]
    public function load(int $id): void
    {
        $scheme = ResearchScheme::find($id);

        if (! $scheme) {
            Flux::toast('Scheme not found.', variant: 'danger');
            return;
        }

        $this->editingId = $id;
        $this->form->setResearchScheme($scheme);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('show-edit-scheme');
    }

    public function save(): void
    {
        try {
            $this->form->update();

            Flux::toast('Scheme updated successfully.', variant: 'success');
            $this->dispatch('scheme-updated', message: 'Scheme updated successfully.');
            $this->dispatch('close-edit-scheme-modal');

            $this->editingId = null;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('scheme-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('scheme-error', message: 'Failed to update scheme. Please try again.');
        }
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-edit-scheme', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-edit-scheme-modal', () => {
                this.show = false;
            });
            window.addEventListener('scheme-error', (e) => {
                this.errorMessage = e.detail.message;
            });
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
        class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Edit Scheme
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Update the scheme information
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-2.5 rounded-md
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[10.5px] leading-relaxed
                                  text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Scheme Name --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Scheme Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="form.name"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                               py-1.5 px-2.5 transition-colors" />
                    @error('form.name')
                        <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Scheme Code + Budget --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Scheme Code <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="form.code"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors" />
                        @error('form.code')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Budget Limit <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" wire:model="form.budget_limit"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors" />
                        @error('form.budget_limit')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Description <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="form.description" rows="3"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                               py-1.5 px-2.5 resize-none transition-colors"></textarea>
                    @error('form.description')
                        <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Is Active --}}
                <label class="flex items-start gap-2 cursor-pointer select-none
                              p-2 rounded-md
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="form.is_active"
                        class="mt-0.5 rounded border-slate-300 dark:border-zinc-600
                               text-blue-600 focus:ring-blue-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <div class="flex-1 min-w-0">
                        <span class="block text-[11px] font-medium
                                     text-slate-700 dark:text-zinc-300">
                            Activate this scheme
                        </span>
                        <span class="block mt-0.5 text-[9.5px] leading-relaxed
                                     text-slate-500 dark:text-zinc-400">
                            Active schemes will appear in proposal submission forms.
                        </span>
                    </div>
                </label>
                @error('form.is_active')
                    <p class="-mt-2 flex items-center gap-1 text-[10.5px] text-rose-600">
                        <flux:icon.exclamation-circle class="size-3 shrink-0" />
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3
                        border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium
                           rounded-md
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           transition-colors">
                    Cancel
                </button>

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-md
                           text-white
                           bg-blue-600 hover:bg-blue-700
                           disabled:opacity-60 disabled:cursor-wait
                           transition-colors">
                    <svg wire:loading wire:target="save"
                         class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Save Changes</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
