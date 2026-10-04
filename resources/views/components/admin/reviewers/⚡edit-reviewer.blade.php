<?php

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public UserForm $form;
    public ?int $editingId = null;

    #[On('open-edit-reviewer')]
    public function load(int $id): void
    {
        $user = User::find($id);

        if (! $user) {
            Flux::toast('Reviewer not found.', variant: 'danger');
            return;
        }

        $this->editingId = $id;
        $this->form->setUser($user);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('show-edit-reviewer');
    }

    public function save(): void
    {
        try {
            $this->form->update();

            Flux::toast('Reviewer updated successfully.', variant: 'success');
            $this->dispatch('reviewer-updated', message: 'Reviewer updated successfully.');
            $this->dispatch('close-edit-reviewer-modal');

            $this->editingId = null;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('reviewer-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('reviewer-error', message: 'Failed to update reviewer. Please try again.');
        }
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-edit-reviewer', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-edit-reviewer-modal', () => {
                this.show = false;
            });
            window.addEventListener('reviewer-error', (e) => {
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

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Edit Reviewer
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Update the reviewer details
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
            <div class="flex-1 overflow-y-auto px-4 py-4">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="mb-3 flex items-start gap-2 p-2.5 rounded-md
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[10.5px] leading-relaxed
                                  text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- ══════════ FORM GRID ══════════ --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                    {{-- NIDN --}}
                    <div>
                        <x-input
                            wire:model="form.nidn"
                            label="NIDN"
                            required />
                        @error('form.nidn')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Full Name --}}
                    <div>
                        <x-input
                            wire:model="form.full_name"
                            label="Full Name"
                            required />
                        @error('form.full_name')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <x-input
                            type="email"
                            wire:model="form.email"
                            label="Email"
                            required />
                        @error('form.email')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Password (optional) --}}
                    <div>
                        <x-input
                            type="password"
                            wire:model="form.password"
                            label="Password (optional)"
                            placeholder="Leave empty to keep current" />
                        @error('form.password')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Birthday --}}
                    <div>
                        <x-input
                            type="date"
                            wire:model="form.birthday"
                            label="Birthday"
                            required
                            class="dark:[color-scheme:dark]" />
                        @error('form.birthday')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Gender --}}
                    <div>
                        <x-select
                            wire:model="form.gender"
                            label="Gender"
                            required
                            size="lg"
                            color="blue">
                            <option value="">— Select —</option>
                            <option value="laki-laki">Male</option>
                            <option value="perempuan">Female</option>
                        </x-select>
                        @error('form.gender')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <x-input
                            wire:model="form.phone_number"
                            label="Phone" />
                        @error('form.phone_number')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div>
                        <x-input
                            wire:model="form.address"
                            label="Address"
                            required />
                        @error('form.address')
                            <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3
                        border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium
                           rounded-full
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
                    wire:target="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-white
                           bg-blue-600/90 hover:bg-blue-600
                           shadow-sm shadow-blue-500/20 hover:shadow-sm hover:shadow-blue-500/30
                           disabled:opacity-60 disabled:cursor-wait
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    <svg wire:loading wire:target="save"
                         class="animate-spin size-3" viewBox="0 0 24 24" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Save Changes</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
