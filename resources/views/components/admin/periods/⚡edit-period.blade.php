<?php

use App\Livewire\Forms\PeriodForm;
use App\Models\Period;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

new class extends Component {
    public PeriodForm $form;

    #[On('open-edit-period')]
    public function load(int $id): void
    {
        $period = Period::find($id);

        if (! $period) {
            Flux::toast('Period not found.', variant: 'danger');
            return;
        }

        if ($this->isLocked($period)) {
            Flux::toast('This period is locked and cannot be edited.', variant: 'danger');
            return;
        }

        $this->form->setPeriod($period);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('show-edit-period');
    }

    protected function isLocked(Period $period): bool
    {
        if ($period->open_to) {
            $currentYear = now()->year;
            $openToYear  = $period->open_to->year;

            if ($openToYear > $currentYear) {
                $hasActiveCurrentYear = Period::query()
                    ->where('is_active', true)
                    ->where('id', '!=', $period->id)
                    ->whereYear('open_from', $currentYear)
                    ->exists();

                if ($hasActiveCurrentYear) {
                    return true;
                }
            }
        }

        if ($period->proposals()->exists()) {
            return true;
        }

        return false;
    }

    public function update(): void
    {
        if ($this->form->period && $this->isLocked($this->form->period)) {
            Flux::toast('This period is locked and cannot be edited.', variant: 'danger');
            return;
        }

        try {
            if ($this->form->is_active) {
                Period::where('is_active', true)
                    ->where('id', '!=', $this->form->period?->id)
                    ->update(['is_active' => false]);
            }

            $this->form->update();

            $this->dispatch('period-updated', message: 'Period updated successfully.');
            $this->dispatch('close-edit-period-modal');

        } catch (ValidationException $e) {
            $this->dispatch('period-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('period-error', message: 'Failed to update period. Please try again.');
        }
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-edit-period', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-edit-period-modal', () => {
                this.show = false;
            });
            window.addEventListener('period-error', (e) => {
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
               bg-white dark:bg-zinc-900
               rounded-t-2xl sm:rounded-xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="update" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Edit Period
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Update the period information
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

                {{-- Period Name --}}
                <div>
                    <label for="edit-periode"
                        class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Period Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="edit-periode" wire:model="form.periode"
                        placeholder="e.g. 2025/2026"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               placeholder:text-slate-400 dark:placeholder:text-zinc-500
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                               py-1.5 px-2.5 transition-colors" />
                    @error('form.periode')
                        <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                            <flux:icon.exclamation-circle class="size-3 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Open From / Open To --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label for="edit-open-from"
                            class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Open From <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="edit-open-from" wire:model="form.open_from"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors
                                   dark:[color-scheme:dark]" />
                        @error('form.open_from')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-open-to"
                            class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Open To <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="edit-open-to" wire:model="form.open_to"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors
                                   dark:[color-scheme:dark]" />
                        @error('form.open_to')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
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
                            Set as active period
                        </span>
                        <span class="block mt-0.5 text-[9.5px] leading-relaxed
                                     text-slate-500 dark:text-zinc-400">
                            The active period will automatically deactivate other active periods.
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
                    wire:target="update"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-md
                           text-white
                           bg-blue-600 hover:bg-blue-700
                           disabled:opacity-60 disabled:cursor-wait
                           transition-colors">
                    <svg wire:loading wire:target="update"
                         class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="update">Save Changes</span>
                    <span wire:loading wire:target="update">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
