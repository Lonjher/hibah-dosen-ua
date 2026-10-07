<?php

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public UserForm $form;
    public ?int $editingId = null;

    #[On('open-edit-user')]
    public function load(int $id): void
    {
        $user = User::find($id);

        if (! $user) {
            Flux::toast('User not found.', variant: 'danger');
            return;
        }

        $this->editingId = $id;
        $this->form->setUser($user);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('show-edit-user');
    }

    public function save(): void
    {
        try {
            $this->form->update();

            Flux::toast('User updated successfully.', variant: 'success');
            $this->dispatch('user-updated', message: 'User updated successfully.');
            $this->dispatch('close-edit-user-modal');

            $this->editingId = null;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('user-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('user-error', message: 'Failed to update user. Please try again.');
        }
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-edit-user', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-edit-user-modal', () => {
                this.show = false;
            });
            window.addEventListener('user-error', (e) => {
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
        class="flex max-h-[92vh] w-full sm:max-w-xl flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-blue-600 to-blue-500 px-5 py-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.pencil-square class="size-4 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-white leading-tight">
                                Edit User
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-tight">
                                Update the lecturer details below
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               transition-colors">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-2.5 rounded-2xl
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] leading-relaxed
                                  text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- ─── Section: Personal Information ─── --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.user-circle class="size-3.5 text-blue-600 dark:text-blue-400" />
                        <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Personal Information
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- NIDN --}}
                        <div>
                            <x-input
                                wire:model="form.nidn"
                                label="NIDN"
                                required
                                placeholder="0000000000"
                                class="rounded-full" />
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
                                required
                                placeholder="e.g. Ahmad Fauzi"
                                class="rounded-full" />
                            @error('form.full_name')
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
                                class="rounded-full dark:[color-scheme:dark]" />
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
                                color="blue"
                                class="rounded-full">
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
                    </div>
                </section>

                {{-- ─── Section: Contact ─── --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.envelope class="size-3.5 text-blue-600 dark:text-blue-400" />
                        <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Contact
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Email --}}
                        <div>
                            <x-input
                                type="email"
                                wire:model="form.email"
                                label="Email"
                                required
                                placeholder="name@ua.ac.id"
                                class="rounded-full" />
                            @error('form.email')
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
                                label="Phone"
                                placeholder="0812..."
                                class="rounded-full" />
                            @error('form.phone_number')
                                <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                    <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Address (textarea, full width) --}}
                        <div class="sm:col-span-2">
                            <x-textarea
                                wire:model="form.address"
                                label="Address"
                                required
                                rows="3"
                                placeholder="Street, City, Province..."
                                class="rounded-2xl resize-none" />
                            @error('form.address')
                                <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                    <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- ─── Section: Security ─── --}}
                <section class="space-y-3">
                    <header class="flex items-center gap-2">
                        <flux:icon.lock-closed class="size-3.5 text-blue-600 dark:text-blue-400" />
                        <h4 class="text-[10.5px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Security
                        </h4>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-zinc-700"></div>
                    </header>

                    <div class="grid grid-cols-1 gap-3">
                        {{-- Password (optional) --}}
                        <div>
                            <x-input
                                type="password"
                                wire:model="form.password"
                                label="Password (optional)"
                                placeholder="Leave empty to keep current password"
                                class="rounded-full" />
                            <p class="mt-1 text-[10.5px] text-slate-500 dark:text-zinc-400">
                                Fill only if you want to change the password.
                            </p>
                            @error('form.password')
                                <p class="mt-1 flex items-center gap-1 text-[10.5px] text-rose-600">
                                    <flux:icon.exclamation-circle class="size-3 shrink-0" />
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            {{-- ══════════ FOOTER ══════════ --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 py-3.5
                        border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-4 py-1.5 text-[11px] font-medium
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

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-4 py-1.5 text-[11px] font-medium rounded-full
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
