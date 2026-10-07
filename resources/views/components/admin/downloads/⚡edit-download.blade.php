<?php

use App\Livewire\Forms\DownloadForm;
use App\Models\Download;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public DownloadForm $form;
    public bool $show = false;

    #[On('open-edit-download')]
    public function load(int $id): void
    {
        $download = Download::find($id);

        if (! $download) {
            Flux::toast('File not found.', variant: 'danger');
            return;
        }

        $this->form->setDownload($download);
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

    public function update(): void
    {
        if (! $this->form->download) {
            return;
        }

        $this->form->validate();

        try {
            $this->form->update();

            Flux::toast('File updated successfully.', variant: 'success');
            $this->dispatch('download-updated');
            $this->dispatch('close-edit-download-modal');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('download-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('download-error', message: 'Failed to update file.');
        }
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.pencil-square class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[12.5px] font-semibold text-white leading-tight">
                            Edit File
                        </h3>
                        <p class="text-[10px] text-white/75 mt-0.5 leading-tight">
                            Update file details or replace the file
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="close"
                    aria-label="Close"
                    class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10
                           transition-colors">
                    <flux:icon.x-mark class="size-3" />
                </button>
            </div>
        </div>

        {{-- ══════════ BODY ══════════ --}}
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">

            {{-- ─── Section: File Details ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.document-arrow-up class="size-3 text-blue-600 dark:text-blue-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        File Details
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
                            placeholder="e.g. Proposal Writing Guideline 2026"
                            class="rounded-full" />
                        @error('form.title')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-textarea
                            wire:model="form.description"
                            label="Description"
                            rows="3"
                            color="blue"
                            placeholder="Short description..."
                            class="rounded-2xl resize-none" />
                        @error('form.description')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- ─── Section: File ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.document-text class="size-3 text-blue-600 dark:text-blue-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        File
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                {{-- Current File --}}
                @if ($form->download)
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700/70
                                bg-slate-50 dark:bg-zinc-800/40 px-3 py-2">
                        <div class="flex items-center gap-1.5">
                            <flux:icon.document-text class="size-3 text-slate-400 dark:text-zinc-500" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Current File
                            </p>
                        </div>
                        <p class="mt-1 text-[11px] font-medium truncate
                                  text-slate-800 dark:text-zinc-200"
                            title="{{ $form->download->file_name }}">
                            {{ $form->download->file_name }}
                        </p>
                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500">
                            {{ $form->download->file_size_human }}
                        </p>
                    </div>
                @endif

                {{-- Replace File --}}
                <div>
                    <x-input
                        type="file"
                        wire:model="form.file"
                        label="Replace File"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt"
                        hint="Leave empty to keep current file · Max 20 MB"
                        class="rounded-full" />

                    <div class="mt-0.5 flex items-center justify-end">
                        <div wire:loading wire:target="form.file"
                            class="inline-flex items-center gap-1 text-[9.5px] text-blue-600 dark:text-blue-400">
                            <svg class="animate-spin size-2.5" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                            Uploading...
                        </div>
                    </div>
                    @error('form.file')
                        <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                            <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </section>

            {{-- ─── Section: Settings ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.adjustments-horizontal class="size-3 text-blue-600 dark:text-blue-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        Settings
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    {{-- Category --}}
                    <div>
                        <x-select
                            wire:model="form.category"
                            label="Category"
                            size="lg"
                            color="blue"
                            class="rounded-full">
                            <option value="guideline">Guideline</option>
                            <option value="template">Template</option>
                            <option value="form">Form</option>
                            <option value="general">General</option>
                        </x-select>
                        @error('form.category')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <x-input
                            type="number"
                            wire:model="form.sort_order"
                            label="Sort Order"
                            min="0"
                            hint="Lower = shown first"
                            class="rounded-full" />
                        @error('form.sort_order')
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-2.5 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- ─── Section: Visibility ─── --}}
            <section class="space-y-2.5">
                <header class="flex items-center gap-1.5">
                    <flux:icon.eye class="size-3 text-blue-600 dark:text-blue-400" />
                    <h4 class="text-[10px] font-semibold uppercase tracking-wider
                               text-slate-500 dark:text-zinc-400">
                        Visibility
                    </h4>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                </header>

                <div class="space-y-1.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none
                                  p-2 rounded-full
                                  bg-slate-50 dark:bg-zinc-800/40
                                  border border-slate-200 dark:border-zinc-700/60
                                  hover:bg-slate-100 dark:hover:bg-zinc-800/70
                                  transition-colors">
                        <input type="checkbox" wire:model="form.is_active"
                            class="rounded-full border-slate-300 dark:border-zinc-600
                                   text-blue-600 focus:ring-blue-500 focus:ring-1
                                   w-3.5 h-3.5 shrink-0" />
                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                            Active (available for download)
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer select-none
                                  p-2 rounded-full
                                  bg-slate-50 dark:bg-zinc-800/40
                                  border border-slate-200 dark:border-zinc-700/60
                                  hover:bg-slate-100 dark:hover:bg-zinc-800/70
                                  transition-colors">
                        <input type="checkbox" wire:model="form.show_on_welcome"
                            class="rounded-full border-slate-300 dark:border-zinc-600
                                   text-blue-600 focus:ring-blue-500 focus:ring-1
                                   w-3.5 h-3.5 shrink-0" />
                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                            Show on Welcome Page
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer select-none
                                  p-2 rounded-full
                                  bg-slate-50 dark:bg-zinc-800/40
                                  border border-slate-200 dark:border-zinc-700/60
                                  hover:bg-slate-100 dark:hover:bg-zinc-800/70
                                  transition-colors">
                        <input type="checkbox" wire:model="form.show_on_dashboard"
                            class="rounded-full border-slate-300 dark:border-zinc-600
                                   text-blue-600 focus:ring-blue-500 focus:ring-1
                                   w-3.5 h-3.5 shrink-0" />
                        <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                            Show on User Dashboard
                        </span>
                    </label>
                </div>
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

            <button type="button" wire:click="update"
                wire:loading.attr="disabled"
                wire:target="update,form.file"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                       px-3.5 py-1.5 text-[11px] font-medium rounded-full
                       text-white
                       bg-blue-600/90 hover:bg-blue-600
                       shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                       disabled:opacity-60 disabled:cursor-wait
                       hover:scale-[1.02] active:scale-[0.97]
                       transition-all duration-150">
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
    </div>
</div>
