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

    #[On('open-edit-admin-external-dedication')]
    public function load(int $id): void
    {
        $item = ExternalProposal::where('is_research', false)
            ->with('user')
            ->find($id);

        if (! $item) {
            Flux::toast('Record not found.', variant: 'danger');
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

        $this->form->validate();

        try {
            $this->form->update();

            Flux::toast('Community service updated successfully.', variant: 'success');
            $this->dispatch('external-dedication-updated');
            $this->dispatch('close-edit-admin-external-dedication-modal');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('external-dedication-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('external-dedication-error', message: 'Failed to update community service.');
        }
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('open-edit-admin-external-dedication', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-edit-admin-external-dedication-modal', () => {
                this.show = false;
            });
            window.addEventListener('external-dedication-error', (e) => {
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
        class="flex max-h-[92vh] w-full sm:max-w-lg flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
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
                                Edit External Community Service
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Update community service details
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-4 py-4">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="mb-3 flex items-start gap-2 p-2.5 rounded-md
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[10.5px] leading-relaxed text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                <div class="space-y-3">

                    {{-- ══════════ RECORD OWNER (readonly, custom) ══════════ --}}
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Record Owner
                        </label>
                        <div class="rounded-full border border-slate-200 dark:border-zinc-700/70
                                    bg-slate-50 dark:bg-zinc-800/40 pl-1 pr-2.5 py-1">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full shrink-0
                                            bg-rose-100 dark:bg-rose-900/40
                                            flex items-center justify-center text-[11px] font-bold
                                            text-rose-700 dark:text-rose-300">
                                    {{ strtoupper(substr($form->externalDedication?->user?->full_name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-semibold truncate
                                              text-slate-800 dark:text-zinc-200">
                                        {{ $form->externalDedication?->user?->full_name ?? '—' }}
                                    </p>
                                    <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                        {{ $form->externalDedication?->user?->nidn
                                           ?? $form->externalDedication?->user?->email ?? '' }}
                                    </p>
                                </div>
                                <flux:icon.lock-closed class="size-3 text-slate-400 dark:text-zinc-500 shrink-0" />
                            </div>
                        </div>
                        <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">
                            Record owner cannot be changed
                        </p>
                    </div>

                    {{-- ══════════ TITLE ══════════ --}}
                    <div>
                        <x-input
                            wire:model="form.title"
                            label="Activity Title"
                            required
                            placeholder="e.g. Pelatihan Digital Marketing untuk UMKM" />
                        @error('form.title')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ SCHEME + FUNDING SOURCE ══════════ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <x-input
                                wire:model="form.scheme"
                                label="Scheme"
                                placeholder="e.g. Pemberdayaan Masyarakat" />
                            @error('form.scheme')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input
                                wire:model="form.funding_source"
                                label="Funding Source"
                                required
                                placeholder="e.g. DRTPM, Pemda, CSR" />
                            @error('form.funding_source')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- ══════════ ROLE + STATUS ══════════ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <x-select
                                wire:model="form.role"
                                label="Role"
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
                            <x-select
                                wire:model="form.status"
                                label="Status"
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

                    {{-- ══════════ START + END DATE ══════════ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <x-input
                                type="date"
                                wire:model="form.start_date"
                                label="Start Date"
                                required
                                class="dark:[color-scheme:dark]" />
                            @error('form.start_date')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input
                                type="date"
                                wire:model="form.end_date"
                                label="End Date (optional)"
                                class="dark:[color-scheme:dark]" />
                            @error('form.end_date')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- ══════════ FUND AMOUNT ══════════ --}}
                    <div>
                        <x-input
                            type="number"
                            wire:model="form.fund_amount"
                            label="Fund Amount (IDR)"
                            required
                            min="0"
                            step="100000" />
                        @error('form.fund_amount')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ DESCRIPTION ══════════ --}}
                    <div>
                        <x-textarea
                            wire:model="form.description"
                            label="Description"
                            rows="3"
                            color="blue"
                            placeholder="Short description..." />
                        @error('form.description')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ CURRENT DOCUMENT (custom) ══════════ --}}
                    @if ($form->externalDedication?->document_path)
                        <div class="rounded-full border border-slate-200 dark:border-zinc-700/70
                                    bg-slate-50 dark:bg-zinc-800/40 pl-2.5 pr-3 py-1.5">
                            <div class="flex items-center gap-2">
                                <flux:icon.document-text class="size-3.5 text-slate-400 dark:text-zinc-500 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-[9.5px] uppercase tracking-wider font-semibold
                                              text-slate-500 dark:text-zinc-400 leading-none">
                                        Current Document
                                    </p>
                                    <p class="mt-0.5 text-[11px] font-medium truncate
                                              text-slate-800 dark:text-zinc-200"
                                        title="{{ basename($form->externalDedication->document_path) }}">
                                        {{ basename($form->externalDedication->document_path) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════ REPLACE DOCUMENT ══════════ --}}
                    <div>
                        <x-input
                            type="file"
                            wire:model="form.document"
                            label="Replace Document (optional)"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                            hint="Leave empty to keep current file · Max 10 MB" />

                        <div wire:loading wire:target="form.document"
                            class="mt-1 text-[10px] text-blue-600 dark:text-blue-400">
                            Uploading...
                        </div>
                        @error('form.document')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ VERIFY TOGGLE (custom) ══════════ --}}
                    <label class="flex items-center gap-2 cursor-pointer select-none
                                  p-2 rounded-full
                                  bg-emerald-50 dark:bg-emerald-900/20
                                  border border-emerald-200 dark:border-emerald-800/60
                                  hover:bg-emerald-100 dark:hover:bg-emerald-900/30
                                  transition-colors">
                        <input type="checkbox" wire:model="form.is_verified"
                            class="rounded border-slate-300 dark:border-zinc-600
                                   text-emerald-600 focus:ring-emerald-500 focus:ring-1
                                   w-3.5 h-3.5" />
                        <div class="flex-1 min-w-0">
                            <span class="block text-[11px] font-medium
                                         text-emerald-800 dark:text-emerald-300">
                                Mark as verified
                            </span>
                            <span class="block mt-0.5 text-[9.5px] text-emerald-600 dark:text-emerald-400">
                                Verified records are locked from user editing.
                            </span>
                        </div>
                    </label>
                    @error('form.is_verified')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" @click="show = false"
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
                    wire:loading.attr="disabled" wire:target="update, form.document"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                           bg-blue-600/90 hover:bg-blue-600
                           shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                           disabled:opacity-60 disabled:cursor-wait
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
