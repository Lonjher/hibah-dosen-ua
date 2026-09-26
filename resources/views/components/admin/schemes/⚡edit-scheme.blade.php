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
        $scheme = ResearchScheme::findOrFail($id);
        $this->editingId = $id;
        $this->form->setResearchScheme($scheme);
        $this->dispatch('show-edit-scheme');
    }

    public function save(): void
    {
        try {
            $this->form->update();
            Flux::toast('Skema berhasil diperbarui.');
            $this->dispatch('scheme-updated', message: 'Skema berhasil diperbarui.');
            $this->editingId = null;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('scheme-error', message: $e->getMessage());
            throw $e;
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
            window.addEventListener('scheme-error', (e) => {
                this.errorMessage = e.detail.message;
            });
            window.addEventListener('scheme-updated', () => {
                this.show = false;
            });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-lg flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER (blue untuk edit) --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Edit Scheme
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-snug">
                                Perbarui informasi skema penelitian.
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

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-3 rounded-lg
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] leading-relaxed text-rose-700 dark:text-rose-300" x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Field sama seperti add, hanya warna focus biru --}}
                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Scheme Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="form.name"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    @error('form.name')
                        <p class="mt-1 flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" /> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Scheme Code <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="form.code"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    @error('form.code')
                        <p class="mt-1 flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" /> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Budget Limit <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" wire:model="form.budget_limit"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3" />
                    @error('form.budget_limit')
                        <p class="mt-1 flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" /> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Description <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="form.description" rows="3"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-blue-500 focus:ring-blue-500 py-2 px-3"></textarea>
                    @error('form.description')
                        <p class="mt-1 flex items-center gap-1 text-[11px] text-rose-600 dark:text-rose-400">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" /> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="rounded-xl border p-3.5 border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800/40">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="form.is_active"
                            class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900" />
                        <div class="flex-1">
                            <span class="block text-[12px] font-medium text-slate-900 dark:text-zinc-100">
                                Aktifkan skema ini
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">

                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Batal</flux:button>

                <flux:button type="submit" variant="primary" size="sm"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Update</span>
                    <span wire:loading.flex wire:target="save" class="items-center gap-1.5">
                        <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Updating...
                    </span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
