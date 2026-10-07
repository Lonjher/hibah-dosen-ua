<?php

use App\Livewire\Forms\InformationForm;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public InformationForm $form;
    public bool $show = false;

    #[On('open-add-information')]
    public function open(): void
    {
        $this->form->reset();
        $this->form->type = 'info';
        $this->form->is_published = true;
        $this->form->published_at = now()->format('Y-m-d\TH:i');
        $this->form->priority = 0;

        $this->resetErrorBag();
        $this->resetValidation();
        $this->show = true;
    }

    public function close(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->show = false;
    }

    public function save(): void
    {
        $this->form->validate();

        try {
            $this->form->create();

            Flux::toast('Information added successfully.', variant: 'success');
            $this->dispatch('information-added');
            $this->dispatch('close-add-information-modal');
            $this->close();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('information-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('information-error', message: 'Failed to add information.');
        }
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak
    x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-sky-600 to-sky-500 px-4 py-2.5">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.megaphone class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[12.5px] font-semibold text-white leading-tight">
                            Add Information
                        </h3>
                        <p class="text-[10px] text-white/75 mt-0.5 leading-tight">
                            Will appear on user dashboard
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close" aria-label="Close"
                    class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10
                           transition-colors">
                    <flux:icon.x-mark class="size-3" />
                </button>
            </div>
        </div>

        {{-- ══════════ BODY ══════════ --}}
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">

            {{-- ─── Section: Content ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.document-text class="size-3 text-sky-600 dark:text-sky-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        Content
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                <div class="grid grid-cols-1 gap-2.5">
                    {{-- Title --}}
                    <div>
                        <x-input
                            wire:model="form.title"
                            label="Title"
                            required
                            placeholder="e.g. Proposal Submission Deadline"
                            class="rounded-full" />
                        @error('form.title')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Content --}}
                    <div>
                        <x-textarea
                            wire:model="form.content"
                            label="Content"
                            required
                            rows="4"
                            color="sky"
                            placeholder="Write the information..."
                            class="rounded-2xl resize-none" />
                        @error('form.content')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- ─── Section: Display ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.adjustments-horizontal class="size-3 text-sky-600 dark:text-sky-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        Display
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    {{-- Type --}}
                    <div>
                        <x-select
                            wire:model="form.type"
                            label="Type"
                            size="lg"
                            color="sky"
                            class="rounded-full">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Important</option>
                        </x-select>
                        @error('form.type')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <x-input
                            type="number"
                            wire:model="form.priority"
                            label="Priority"
                            min="0"
                            max="99"
                            placeholder="0"
                            hint="Higher = shown first"
                            class="rounded-full" />
                        @error('form.priority')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- ─── Section: Schedule ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.clock class="size-3 text-sky-600 dark:text-sky-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        Schedule
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    {{-- Publish At --}}
                    <div>
                        <x-input
                            type="datetime-local"
                            wire:model="form.published_at"
                            label="Publish At"
                            class="rounded-full dark:[color-scheme:dark]" />
                        @error('form.published_at')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Expires At --}}
                    <div>
                        <x-input
                            type="datetime-local"
                            wire:model="form.expires_at"
                            label="Expires At"
                            hint="Leave empty for no expiry"
                            class="rounded-full dark:[color-scheme:dark]" />
                        @error('form.expires_at')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- ─── Section: Status ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.bolt class="size-3 text-sky-600 dark:text-sky-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        Status
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                <label class="flex items-start gap-2 cursor-pointer select-none
                              p-2.5 rounded-full
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="form.is_published"
                        class="mt-0.5 rounded-full border-slate-300 dark:border-zinc-600
                               text-sky-600 focus:ring-sky-500 focus:ring-1
                               w-3.5 h-3.5 shrink-0" />
                    <div class="flex-1 min-w-0">
                        <span class="block text-[11px] font-medium
                                     text-slate-700 dark:text-zinc-300">
                            Publish immediately
                        </span>
                        <span class="block mt-0.5 text-[9.5px] leading-relaxed
                                     text-slate-500 dark:text-zinc-400">
                            If unchecked, saved as draft and won't be visible to users.
                        </span>
                    </div>
                </label>
            </section>
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                    px-4 py-2.5
                    border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">

            <button type="button" wire:click="close"
                class="w-full sm:w-auto px-3.5 py-1.5 text-[11px] font-medium
                       rounded-full
                       text-slate-700 dark:text-zinc-300
                       bg-white dark:bg-zinc-800
                       border border-slate-300 dark:border-zinc-600
                       hover:bg-slate-50 dark:hover:bg-zinc-700
                       shadow-sm shadow-zinc-200/40
                       hover:scale-[1.02] active:scale-[0.97]
                       transition-all duration-150">
                Cancel
            </button>

            <button type="button" wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                       px-3.5 py-1.5 text-[11px] font-medium rounded-full
                       text-white
                       bg-sky-600/90 hover:bg-sky-600
                       shadow-sm shadow-sky-500/20 hover:shadow-sm hover:shadow-sky-500/30
                       disabled:opacity-60 disabled:cursor-wait
                       hover:scale-[1.02] active:scale-[0.97]
                       transition-all duration-150">
                <svg wire:loading wire:target="save"
                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="save">Save Information</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </div>
</div>
