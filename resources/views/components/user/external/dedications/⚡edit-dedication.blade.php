<?php

use App\Livewire\Forms\ExternalDedicationForm;
use App\Models\ExternalProposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ExternalDedicationForm $form;
    public bool $show = false;

    #[On('open-edit-external-dedication')]
    public function load(int $id): void
    {
        $item = ExternalProposal::where('user_id', auth()->id())
            ->where('is_research', false)
            ->find($id);

        if (! $item) {
            Flux::toast('Record not found.', variant: 'danger');
            return;
        }

        if ($item->is_verified) {
            Flux::toast('Verified records cannot be edited. Contact admin.', variant: 'danger');
            return;
        }

        $this->form->setExternalDedication($item);
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
        if (! $this->form->externalDedication) {
            return;
        }

        $item = ExternalProposal::where('user_id', auth()->id())
            ->where('is_research', false)
            ->find($this->form->externalDedication->id);

        if (! $item || $item->is_verified) {
            Flux::toast('Verified records cannot be edited.', variant: 'danger');
            $this->close();
            return;
        }

        $this->form->validate();

        try {
            $this->form->update();

            Flux::toast('Community service updated successfully.', variant: 'success');
            $this->dispatch('external-dedication-updated');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to update community service.', variant: 'danger');
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
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700
               hover:shadow-blue-500/15 transition-shadow duration-300"
        x-on:click.stop>

        <form wire:submit.prevent="update" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500
                        px-4 py-3 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Edit External Dedication
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Update external dedication details
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="close"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95
                               transition-all duration-150">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-4 py-4">

                {{-- Info banner --}}
                <div class="mb-3 flex items-start gap-2 p-2.5 rounded-2xl
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

                <div class="space-y-3">

                    {{-- Title --}}
                    <div>
                        <x-input wire:model="form.title" label="Activity Title"
                            rounded="full"
                            required />
                        @error('form.title')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Scheme + Funding Source --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <x-input wire:model="form.scheme" label="Scheme"
                                rounded="full" />
                            @error('form.scheme')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input wire:model="form.funding_source" label="Funding Source"
                                rounded="full"
                                required />
                            @error('form.funding_source')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Role + Status --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <x-select wire:model="form.role" label="Your Role"
                                required
                                size="lg"
                                color="blue">
                                <option value="leader">Leader</option>
                                <option value="member">Member</option>
                            </x-select>
                            @error('form.role')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-select wire:model="form.status" label="Status"
                                required
                                size="lg"
                                color="blue">
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </x-select>
                            @error('form.status')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Start + End Date --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <x-input type="date" wire:model="form.start_date" label="Start Date"
                                rounded="full"
                                required
                                class="dark:[color-scheme:dark]" />
                            @error('form.start_date')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input type="date" wire:model="form.end_date" label="End Date (Optional)"
                                rounded="full"
                                class="dark:[color-scheme:dark]" />
                            @error('form.end_date')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Fund Amount --}}
                    <div>
                        <x-input type="number" wire:model="form.fund_amount" label="Fund Amount (IDR)"
                            rounded="full"
                            required
                            min="0" step="100000" />
                        @error('form.fund_amount')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-textarea wire:model="form.description" label="Description" color="blue"
                            rounded="full"
                            rows="3" />
                        @error('form.description')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ CURRENT DOCUMENTS ══════════ --}}
                    @if ($form->externalDedication?->proposal_document_path || $form->externalDedication?->report_document_path)
                        <div class="rounded-2xl border border-slate-200 dark:border-zinc-700/70
                                    bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5 space-y-2">

                            <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                      text-slate-500 dark:text-zinc-400 leading-none">
                                Current Documents
                            </p>

                            {{-- Proposal Document --}}
                            @if ($form->externalDedication?->proposal_document_path)
                                <div class="flex items-center gap-2 pl-1 pr-0.5 py-1
                                            rounded-full bg-white dark:bg-zinc-900/60
                                            border border-slate-200 dark:border-zinc-700/70">
                                    <div class="w-6 h-6 rounded-full shrink-0
                                                bg-indigo-100 dark:bg-indigo-900/40
                                                flex items-center justify-center">
                                        <flux:icon.document-text class="size-3 text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] uppercase tracking-wider font-semibold
                                                  text-indigo-600 dark:text-indigo-400 leading-none">
                                            Proposal
                                        </p>
                                        <p class="mt-0.5 text-[10.5px] font-medium truncate
                                                  text-slate-800 dark:text-zinc-200"
                                            title="{{ basename($form->externalDedication->proposal_document_path) }}">
                                            {{ basename($form->externalDedication->proposal_document_path) }}
                                        </p>
                                    </div>
                                    <a href="{{ Storage::disk('public')->url($form->externalDedication->proposal_document_path) }}"
                                        target="_blank" aria-label="View proposal document"
                                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                               text-indigo-600 dark:text-indigo-400
                                               hover:bg-indigo-50 dark:hover:bg-indigo-900/40
                                               hover:scale-110 active:scale-95
                                               transition-all duration-150">
                                        <flux:icon.arrow-up-right class="size-3" />
                                    </a>
                                </div>
                            @endif

                            {{-- Report Document --}}
                            @if ($form->externalDedication?->report_document_path)
                                <div class="flex items-center gap-2 pl-1 pr-0.5 py-1
                                            rounded-full bg-white dark:bg-zinc-900/60
                                            border border-slate-200 dark:border-zinc-700/70">
                                    <div class="w-6 h-6 rounded-full shrink-0
                                                bg-emerald-100 dark:bg-emerald-900/40
                                                flex items-center justify-center">
                                        <flux:icon.document-check class="size-3 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] uppercase tracking-wider font-semibold
                                                  text-emerald-600 dark:text-emerald-400 leading-none">
                                            Report
                                        </p>
                                        <p class="mt-0.5 text-[10.5px] font-medium truncate
                                                  text-slate-800 dark:text-zinc-200"
                                            title="{{ basename($form->externalDedication->report_document_path) }}">
                                            {{ basename($form->externalDedication->report_document_path) }}
                                        </p>
                                    </div>
                                    <a href="{{ Storage::disk('public')->url($form->externalDedication->report_document_path) }}"
                                        target="_blank" aria-label="View report document"
                                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                               text-emerald-600 dark:text-emerald-400
                                               hover:bg-emerald-50 dark:hover:bg-emerald-900/40
                                               hover:scale-110 active:scale-95
                                               transition-all duration-150">
                                        <flux:icon.arrow-up-right class="size-3" />
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- ══════════ REPLACE DOCUMENTS ══════════ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                        {{-- Proposal Document --}}
                        <div>
                            <x-input type="file" wire:model="form.proposal_document"
                                label="Replace Proposal (optional)"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                rounded="full"
                                hint="Leave empty to keep · Max 10 MB" />
                            <div wire:loading wire:target="form.proposal_document"
                                class="mt-1 text-[10px] text-blue-600 dark:text-blue-400">
                                Uploading...
                            </div>
                            @error('form.proposal_document')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Report Document --}}
                        <div>
                            <x-input type="file" wire:model="form.report_document"
                                label="Replace Report (optional)"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                rounded="full"
                                hint="Leave empty to keep · Max 10 MB" />
                            <div wire:loading wire:target="form.report_document"
                                class="mt-1 text-[10px] text-blue-600 dark:text-blue-400">
                                Uploading...
                            </div>
                            @error('form.report_document')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">

                <button type="button" wire:click="close"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-blue-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Cancel
                </button>

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="update, form.proposal_document, form.report_document"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                           bg-blue-600/90 hover:bg-blue-600
                           shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                           disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
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
