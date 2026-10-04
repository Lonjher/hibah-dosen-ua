<?php

use App\Livewire\Forms\InformationForm;
use App\Models\Informations;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public InformationForm $form;
    public bool $show = false;        // ← TAMBAHKAN INI

    #[On('open-edit-information')]
    public function load(int $id): void
    {
        $information = Informations::find($id);

        if (! $information) {
            Flux::toast('Information not found.', variant: 'danger');
            return;
        }

        $this->form->setInformation($information);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->show = true;            // ← set show
    }

    public function close(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->show = false;           // ← set show
    }

    public function update(): void
    {
        if (! $this->form->information) {
            return;
        }

        $this->form->validate();

        try {
            $this->form->update();

            Flux::toast('Information updated successfully.', variant: 'success');
            $this->dispatch('information-updated');
            $this->dispatch('close-edit-information-modal');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('information-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('information-error', message: 'Failed to update information.');
        }
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="$wire.close()">

    <div class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-full shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        {{-- ══════════ HEADER ══════════ --}}
        <div class="shrink-0 bg-gradient-to-r from-violet-600 to-violet-500 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                        <flux:icon.pencil-square class="size-3.5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold text-white leading-tight">
                            Edit Information
                        </h3>
                        <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                            Update the existing information
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
                    size="md" />
                @error('form.title')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content --}}
            <div>
                <x-textarea
                    wire:model="form.content"
                    label="Content"
                    required
                    rows="4"
                    color="violet" />
                @error('form.content')
                    <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type + Priority --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <x-select
                        wire:model="form.type"
                        label="Type"
                        size="lg"
                        color="violet">
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Important</option>
                    </x-select>
                    @error('form.type')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input
                        type="number"
                        wire:model="form.priority"
                        label="Priority"
                        min="0"
                        max="99"
                        hint="Higher = displayed first" />
                    @error('form.priority')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Publish At + Expires At --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <x-input
                        type="datetime-local"
                        wire:model="form.published_at"
                        label="Publish At" />
                    @error('form.published_at')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input
                        type="datetime-local"
                        wire:model="form.expires_at"
                        label="Expires At"
                        hint="Leave empty for no expiry" />
                    @error('form.expires_at')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Publish toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none
                          p-2 rounded-md
                          bg-slate-50 dark:bg-zinc-800/40
                          border border-slate-200 dark:border-zinc-700/60
                          hover:bg-slate-100 dark:hover:bg-zinc-800/70
                          transition-colors">
                <input type="checkbox" wire:model="form.is_published"
                    class="rounded border-slate-300 dark:border-zinc-600
                           text-violet-600 focus:ring-violet-500 focus:ring-1
                           w-3.5 h-3.5" />
                <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                    Published
                </span>
            </label>
        </div>

        {{-- ══════════ FOOTER ══════════ --}}
        <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                    px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                    bg-slate-50/50 dark:bg-zinc-900/50">
            <flux:button type="button" wire:click="close">
                Cancel
            </flux:button>
            <flux:button type="button" wire:click="update" variant="primary"
                wire:loading.attr="disabled" wire:target="update">
                <svg wire:loading wire:target="update"
                     class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="update">Save Changes</span>
                <span wire:loading wire:target="update">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
