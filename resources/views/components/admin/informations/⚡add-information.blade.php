<?php

use App\Livewire\Forms\InformationForm;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public InformationForm $form;
    public bool $show = false; // ← TAMBAHKAN INI

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
        $this->show = true; // ← set show
    }

    public function close(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->show = false; // ← set show
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

<div x-data x-show="$wire.show" x-transition.opacity x-cloak x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl rounded-full shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-sky-600 to-sky-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.megaphone class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Add Information
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Will appear on user dashboard
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close" aria-label="Close"
                    class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10
                           transition-colors">
                    <flux:icon.x-mark class="size-3.5" />
                </button>
            </div>
        </div>

        {{-- ══════════ BODY ══════════ --}}
        <div class="flex-1 overflow-y-auto px-4 py-4">

            <div class="grid grid-cols-1 gap-3.5">

                {{-- ─────── TITLE ─────── --}}
                <div>
                    <x-input wire:model="form.title" label="Title" :required="true"
                        placeholder="e.g. Proposal Submission Deadline" />
                    @error('form.title')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ─────── CONTENT ─────── --}}
                <div>
                    <x-textarea wire:model="form.content" label="Content" :required="true" rows="4"
                        placeholder="Write the information..." />
                    @error('form.content')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ─────── TYPE + PRIORITY ─────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-start">

                    {{-- Type --}}
                    <div>
                        <x-select wire:model="form.type" label="Type" size="lg" maxWidth="w-full">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Important</option>
                        </x-select>
                        @error('form.type')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <x-input type="number" wire:model="form.priority" label="Priority" min="0"
                            max="99" placeholder="0" />
                        @error('form.priority')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ─────── PUBLISH AT + EXPIRES AT ─────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-start">

                    {{-- Publish At --}}
                    <div>
                        <x-input type="datetime-local" wire:model="form.published_at" label="Publish At"
                            class="[color-scheme:dark]" />
                        @error('form.published_at')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Expires At --}}
                    <div>
                        <x-input type="datetime-local" wire:model="form.expires_at" label="Expires At"
                            hint="Leave empty for no expiry" class="[color-scheme:dark]" />
                        @error('form.expires_at')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ─────── PUBLISH TOGGLE ─────── --}}
                <label
                    class="rounded-full flex items-start gap-2.5 cursor-pointer select-none
                              p-3
                            dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-emerald-50 dark:hover:bg-zinc-800/70
                              hover:border-slate-300 dark:hover:border-zinc-600/60
                              transition-colors">

                    <input type="checkbox" wire:model="form.is_published"
                        class="mt-0.5 rounded border-slate-300 dark:border-zinc-600
                               text-sky-600 focus:ring-sky-500 focus:ring-1
                               w-3.5 h-3.5 shrink-0" />

                    <div class="flex-1 min-w-0">
                        <span
                            class="block text-[11.5px] font-medium
                                     text-slate-800 dark:text-zinc-200">
                            Publish immediately
                        </span>
                        <span
                            class="block mt-0.5 text-[10px] leading-relaxed
                                     text-slate-500 dark:text-zinc-400">
                            If unchecked, saved as draft and won't be visible to users.
                        </span>
                    </div>
                </label>
            </div>
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        <div
            class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                    px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">
            <flux:button type="button" wire:click="close">
                Cancel
            </flux:button>
            <flux:button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" variant="primary">
                <svg wire:loading wire:target="save" class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                        opacity=".25" />
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                </svg>
                <span wire:loading.remove wire:target="save">Save Information</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
