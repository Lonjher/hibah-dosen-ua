<?php

use App\Livewire\Forms\ExternalResearchForm;
use App\Models\ExternalProposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ExternalResearchForm $form;
    public bool $show = false;

    #[On('open-edit-external-research')]
    public function load(int $id): void
    {
        $item = ExternalProposal::where('user_id', auth()->id())
            ->where('is_research', true)
            ->find($id);

        if (! $item) {
            Flux::toast('Record not found.', variant: 'danger');
            return;
        }

        // Guard: verified locked
        if ($item->is_verified) {
            Flux::toast('Verified records cannot be edited. Contact admin.', variant: 'danger');
            return;
        }

        $this->form->setExternalResearch($item);
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
        if (! $this->form->externalResearch) {
            return;
        }

        // Guard
        $item = ExternalProposal::where('user_id', auth()->id())
            ->where('is_research', true)
            ->find($this->form->externalResearch->id);

        if (! $item || $item->is_verified) {
            Flux::toast('Verified records cannot be edited.', variant: 'danger');
            $this->close();
            return;
        }

        $this->form->validate();

        try {
            $this->form->update();

            Flux::toast('External research updated successfully.', variant: 'success');
            $this->dispatch('external-research-updated');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to update external research.', variant: 'danger');
        }
    }
};
?>

<div
    x-data
    x-show="$wire.show"
    x-transition.opacity
    x-cloak
    x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    x-on:click.self="$wire.close()">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="flex max-h-[92vh] w-full sm:max-w-lg flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        x-on:click.stop>

        <form wire:submit.prevent="update" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Edit External Research
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Update your external research details
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="close"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

                {{-- Info banner --}}
                <div class="flex items-start gap-2 p-2.5 rounded-md
                            bg-amber-50 dark:bg-amber-900/20
                            border border-amber-200 dark:border-amber-800">
                    <flux:icon.information-circle
                        class="size-3.5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-amber-800 dark:text-amber-300">
                            Pending Verification
                        </p>
                        <p class="text-[10px] leading-relaxed text-amber-700 dark:text-amber-400 mt-0.5">
                            You can edit this record until an admin verifies it.
                        </p>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Research Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="form.title"
                        placeholder="e.g. Sistem Deteksi Dini Banjir Berbasis IoT"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               placeholder:text-slate-400 dark:placeholder:text-zinc-500
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                               py-1.5 px-2.5 transition-colors" />
                    @error('form.title')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Scheme + Funding Source --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Scheme
                        </label>
                        <input type="text" wire:model="form.scheme"
                            placeholder="e.g. Penelitian Dasar Unggulan"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors" />
                        @error('form.scheme')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Funding Source <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="form.funding_source"
                            placeholder="e.g. DRTPM, BRIN, Industry"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors" />
                        @error('form.funding_source')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Role + Status --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Your Role <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model="form.role"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 pl-2.5 pr-6 transition-colors">
                            <option value="leader">Leader</option>
                            <option value="member">Member</option>
                        </select>
                        @error('form.role')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model="form.status"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 pl-2.5 pr-6 transition-colors">
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @error('form.status')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Start + End Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Start Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" wire:model="form.start_date"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors dark:[color-scheme:dark]" />
                        @error('form.start_date')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            End Date
                            <span class="text-slate-400 dark:text-zinc-500 font-normal">(optional)</span>
                        </label>
                        <input type="date" wire:model="form.end_date"
                            class="block w-full rounded-md shadow-sm text-[11.5px]
                                   border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800
                                   text-slate-900 dark:text-zinc-100
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   py-1.5 px-2.5 transition-colors dark:[color-scheme:dark]" />
                        @error('form.end_date')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Fund Amount --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Fund Amount (IDR) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" wire:model="form.fund_amount" min="0" step="100000"
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                               py-1.5 px-2.5 transition-colors" />
                    @error('form.fund_amount')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Description
                    </label>
                    <textarea wire:model="form.description" rows="3"
                        placeholder="Short description..."
                        class="block w-full rounded-md shadow-sm text-[11.5px]
                               border-slate-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               placeholder:text-slate-400 dark:placeholder:text-zinc-500
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                               py-1.5 px-2.5 resize-none transition-colors"></textarea>
                    @error('form.description')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Current Document --}}
                @if ($form->externalResearch?->document_path)
                    <div class="rounded-md border border-slate-200 dark:border-zinc-700/70
                                bg-slate-50 dark:bg-zinc-800/40 px-2.5 py-2">
                        <div class="flex items-center gap-1.5">
                            <flux:icon.document-text class="size-3 text-slate-400 dark:text-zinc-500" />
                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400">
                                Current Document
                            </p>
                        </div>
                        <p class="mt-1 text-[11px] font-medium truncate
                                  text-slate-800 dark:text-zinc-200">
                            {{ basename($form->externalResearch->document_path) }}
                        </p>
                    </div>
                @endif

                {{-- Replace Document --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Replace Document
                        <span class="text-slate-400 dark:text-zinc-500 font-normal">(optional)</span>
                    </label>
                    <input type="file" wire:model="form.document"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        class="block w-full text-[11px]
                               file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md
                               file:border-0 file:text-[10.5px] file:font-medium
                               file:bg-blue-100 file:text-blue-700
                               dark:file:bg-blue-900/40 dark:file:text-blue-300
                               hover:file:bg-blue-200 dark:hover:file:bg-blue-900/60
                               border border-slate-300 dark:border-zinc-600 rounded-md
                               bg-white dark:bg-zinc-800 transition-colors" />
                    <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                        Leave empty to keep current document · Max 10 MB
                    </p>
                    <div wire:loading wire:target="form.document"
                        class="mt-1 text-[10px] text-blue-600 dark:text-blue-400">
                        Uploading...
                    </div>
                    @error('form.document')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" wire:click="close"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-md
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700 transition-colors">
                    Cancel
                </button>

                <button type="submit"
                    wire:loading.attr="disabled" wire:target="update, form.document"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                           bg-blue-600 hover:bg-blue-700
                           disabled:opacity-60 disabled:cursor-wait transition-colors">
                    <svg wire:loading wire:target="update" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
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
