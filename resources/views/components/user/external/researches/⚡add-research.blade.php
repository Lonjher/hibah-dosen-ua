<?php

use App\Livewire\Forms\ExternalResearchForm;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ExternalResearchForm $form;
    public bool $show = false;

    #[On('open-add-external-research')]
    public function open(): void
    {
        $this->form->reset();
        $this->form->role = 'leader';
        $this->form->status = 'ongoing';
        $this->form->start_date = now()->format('Y-m-d');
        $this->form->fund_amount = 0;
        $this->form->is_verified = false;
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
        $this->form->user_id = auth()->id();
        $this->form->validate();

        try {
            $this->form->create();
            Flux::toast('External research added successfully.', variant: 'success');
            $this->dispatch('external-research-added');
            $this->close();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to add external research.', variant: 'danger');
        }
    }
};
?>

<div x-data x-show="$wire.show" x-transition.opacity x-cloak
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

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-indigo-600 to-violet-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.globe-alt class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Add External Research
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Record your externally funded research
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="close" aria-label="Close"
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
                    <flux:icon.information-circle class="size-3.5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-amber-800 dark:text-amber-300">
                            Pending Verification
                        </p>
                        <p class="text-[10px] leading-relaxed text-amber-700 dark:text-amber-400 mt-0.5">
                            Your record will be reviewed by an admin before appearing as verified.
                        </p>
                    </div>
                </div>

                {{-- ══════════ FORM FIELDS ══════════ --}}

                <x-input wire:model="form.title" label="Research Title" color="indigo"
                    :required="true" placeholder="e.g. Sistem Deteksi Dini Banjir Berbasis IoT" />
                @error('form.title')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <x-input wire:model="form.scheme" label="Scheme" color="indigo"
                        placeholder="e.g. Penelitian Dasar Unggulan" />
                    @error('form.scheme')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror

                    <x-input wire:model="form.funding_source" label="Funding Source" color="indigo"
                        :required="true" placeholder="e.g. DRTPM, BRIN, Industry" />
                    @error('form.funding_source')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <x-select wire:model="form.role" color="indigo">
                        <option value="leader">Leader</option>
                        <option value="member">Member</option>
                    </x-select>
                    @error('form.role')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror

                    <x-select wire:model="form.status" color="indigo">
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </x-select>
                    @error('form.status')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <x-input type="date" wire:model="form.start_date" label="Start Date"
                        color="indigo" :required="true" class="[color-scheme:dark]" />
                    @error('form.start_date')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror

                    <x-input type="date" wire:model="form.end_date" label="End Date (Optional)"
                        color="indigo" class="[color-scheme:dark]" />
                    @error('form.end_date')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                <x-input type="number" wire:model="form.fund_amount" label="Fund Amount (IDR)"
                    color="indigo" :required="true" min="0" step="100000" placeholder="0" />
                @error('form.fund_amount')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror

                <x-textarea wire:model="form.description" label="Description" color="indigo"
                    rows="3" placeholder="Short description..." />
                @error('form.description')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror

                <x-input type="file" wire:model="form.document" label="Supporting Document"
                    color="indigo" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                    hint="Max 10 MB · pdf, doc, docx, jpg, jpeg, png" />
                <div wire:loading wire:target="form.document"
                    class="text-[10px] text-indigo-600 dark:text-indigo-400 mt-1">
                    Uploading...
                </div>
                @error('form.document')<p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>@enderror
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <button type="button" wire:click="close"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-md
                           text-slate-700 dark:text-zinc-300 bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700 transition-colors">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save, form.document"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-md text-white
                           bg-indigo-600 hover:bg-indigo-700
                           disabled:opacity-60 disabled:cursor-wait transition-colors">
                    <svg wire:loading wire:target="save" class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Save Record</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
