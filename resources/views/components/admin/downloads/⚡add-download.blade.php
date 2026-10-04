<?php

use App\Livewire\Forms\DownloadForm;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public DownloadForm $form;
    public bool $show = false;

    #[On('open-add-download')]
    public function open(): void
    {
        $this->form->reset();
        $this->form->category = 'general';
        $this->form->is_active = true;
        $this->form->show_on_welcome = true;
        $this->form->show_on_dashboard = true;
        $this->form->sort_order = 0;

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

            Flux::toast('File uploaded successfully.', variant: 'success');
            $this->dispatch('download-added');
            $this->dispatch('close-add-download-modal');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('download-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('download-error', message: 'Failed to upload file.');
        }
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.arrow-up-tray class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Add File
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Upload a file for users to download
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close"
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

            {{-- Title --}}
            <div>
                <x-input
                    wire:model="form.title"
                    label="Title"
                    required
                    placeholder="e.g. Proposal Writing Guideline 2026" />
                @error('form.title')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <x-textarea
                    wire:model="form.description"
                    label="Description"
                    rows="3"
                    color="violet"
                    placeholder="Short description..." />
                @error('form.description')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- File --}}
            <div>
                <x-input
                    type="file"
                    wire:model="form.file"
                    label="File"
                    required
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt"
                    hint="Max 20 MB · pdf, doc, xls, ppt, zip, rar, txt" />

                <div class="mt-0.5 flex items-center justify-end">
                    <div wire:loading wire:target="form.file"
                        class="inline-flex items-center gap-1 text-[9.5px] text-violet-600 dark:text-violet-400">
                        <svg class="animate-spin size-2.5" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        Uploading...
                    </div>
                </div>
                @error('form.file')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Category + Sort Order --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                {{-- Category --}}
                <div>
                    <x-select
                        wire:model="form.category"
                        label="Category"
                        size="lg"
                        color="violet">
                        <option value="guideline">Guideline</option>
                        <option value="template">Template</option>
                        <option value="form">Form</option>
                        <option value="general">General</option>
                    </x-select>
                    @error('form.category')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sort Order --}}
                <div>
                    <x-input
                        type="number"
                        wire:model="form.sort_order"
                        label="Sort Order"
                        min="0"
                        hint="Lower = shown first" />
                    @error('form.sort_order')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Toggles --}}
            <div class="space-y-1.5">
                <label class="flex items-center gap-2 cursor-pointer select-none
                              p-2 rounded-md dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-50 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="form.is_active"
                        class="rounded border-slate-300 dark:border-zinc-600
                               text-violet-600 focus:ring-violet-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                        Active (available for download)
                    </span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none
                              p-2 rounded-md
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="form.show_on_welcome"
                        class="rounded border-slate-300 dark:border-zinc-600
                               text-violet-600 focus:ring-violet-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                        Show on Welcome Page
                    </span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none
                              p-2 rounded-md
                              bg-slate-50 dark:bg-zinc-800/40
                              border border-slate-200 dark:border-zinc-700/60
                              hover:bg-slate-100 dark:hover:bg-zinc-800/70
                              transition-colors">
                    <input type="checkbox" wire:model="form.show_on_dashboard"
                        class="rounded border-slate-300 dark:border-zinc-600
                               text-violet-600 focus:ring-violet-500 focus:ring-1
                               w-3.5 h-3.5" />
                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                        Show on User Dashboard
                    </span>
                </label>
            </div>
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                    px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">
            <flux:button type="button" wire:click="close">
                Cancel
            </flux:button>
            <flux:button type="button" wire:click="save" variant="primary"
                wire:loading.attr="disabled" wire:target="save,form.file">
                <svg wire:loading wire:target="save"
                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="save">Upload File</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
