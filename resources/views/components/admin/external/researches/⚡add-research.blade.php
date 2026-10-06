<?php

use App\Livewire\Forms\ExternalResearchForm;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ExternalResearchForm $form;
    public bool $show = false;

    // User picker helper state
    public string $userSearch = '';
    public bool $userHasSearched = false;

    #[On('open-add-admin-external')]
    public function open(): void
    {
        $this->form->reset();
        $this->form->role = 'leader';
        $this->form->status = 'ongoing';
        $this->form->start_date = now()->format('Y-m-d');
        $this->form->fund_amount = 0;
        $this->form->is_verified = false;

        $this->userSearch = '';
        $this->userHasSearched = false;

        $this->resetErrorBag();
        $this->resetValidation();
        $this->show = true;
    }

    public function close(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->userSearch = '';
        $this->userHasSearched = false;
        $this->show = false;
    }

    /* ============================================================
     |  USER PICKER
     ============================================================ */

    public function updatedUserSearch(): void
    {
        $this->userHasSearched = true;
    }

    public function selectUser(int $id): void
    {
        $this->form->user_id = $id;
        $this->userSearch = '';
        $this->userHasSearched = false;
        $this->resetErrorBag('form.user_id');
        $this->resetValidation('form.user_id');
    }

    public function clearUser(): void
    {
        $this->form->user_id = null;
        $this->userSearch = '';
        $this->userHasSearched = false;
    }

    /* ============================================================
     |  SAVE
     ============================================================ */

    public function save(): void
    {
        $this->form->validate();

        try {
            $this->form->create();

            Flux::toast('External research added successfully.', variant: 'success');
            $this->dispatch('external-added');
            $this->dispatch('close-add-admin-external-modal');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('external-error', message: $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('external-error', message: 'Failed to add external research.');
        }
    }

    /* ============================================================
     |  RENDER DATA
     ============================================================ */

    public function with(): array
    {
        $selectedUser = $this->form->selectedUser();

        $suggestions = collect();
        if (! $selectedUser) {
            $suggestions = User::query()
                ->when($this->userSearch, fn ($q) => $q->where(fn ($q) => $q
                    ->where('full_name', 'like', "%{$this->userSearch}%")
                    ->orWhere('nidn', 'like', "%{$this->userSearch}%")
                    ->orWhere('email', 'like', "%{$this->userSearch}%")))
                ->orderBy('full_name')
                ->limit(5)
                ->get();
        }

        return [
            'selectedUser' => $selectedUser,
            'suggestions'  => $suggestions,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('open-add-admin-external', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-add-admin-external-modal', () => {
                this.show = false;
            });
            window.addEventListener('external-error', (e) => {
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
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700
               hover:shadow-indigo-500/15 transition-shadow duration-300"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- ══════════ HEADER ══════════ --}}
            <div class="shrink-0 bg-gradient-to-r from-indigo-600 to-violet-500
                        px-4 py-3 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.globe-alt class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Add External Research
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Add a record on behalf of a user
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95
                               transition-all duration-150">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>

            {{-- ══════════ BODY ══════════ --}}
            <div class="flex-1 overflow-y-auto px-4 py-4">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="mb-3 flex items-start gap-2 p-2.5 rounded-2xl
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[10.5px] leading-relaxed text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                <div class="space-y-3">

                    {{-- ══════════ USER PICKER (custom, keep) ══════════ --}}
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                            Record Owner <span class="text-rose-500">*</span>
                        </label>

                        @if ($selectedUser)
                            {{-- Selected state --}}
                            <div class="rounded-full border border-indigo-300 dark:border-indigo-700
                                        bg-indigo-50 dark:bg-indigo-900/20 pl-1 pr-1.5 py-1">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full shrink-0
                                                bg-indigo-200 dark:bg-indigo-800/50
                                                flex items-center justify-center text-[11px] font-bold
                                                text-indigo-700 dark:text-indigo-300">
                                        {{ strtoupper(substr($selectedUser->full_name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-semibold truncate
                                                  text-indigo-900 dark:text-indigo-100">
                                            {{ $selectedUser->full_name }}
                                        </p>
                                        <p class="text-[9.5px] text-indigo-600 dark:text-indigo-400">
                                            {{ $selectedUser->nidn ?? $selectedUser->email }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="clearUser"
                                        aria-label="Change user"
                                        class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                               text-indigo-600 dark:text-indigo-400
                                               hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                        <flux:icon.x-mark class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Search state --}}
                            <div class="relative">
                                <flux:icon.magnifying-glass
                                    class="absolute left-3 top-1/2 -translate-y-1/2 size-3.5
                                           text-slate-400 pointer-events-none" />
                                <input type="text"
                                    wire:model.live.debounce.200ms="userSearch"
                                    placeholder="Search user by name, NIDN, or email..."
                                    class="block w-full rounded-full shadow-sm text-[11px] pl-9 pr-3.5
                                           border border-slate-200 dark:border-zinc-700
                                           bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           placeholder:text-slate-400 dark:placeholder:text-zinc-500
                                           py-1.5
                                           hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
                                           hover:border-slate-300 dark:hover:border-zinc-600
                                           focus:outline-none
                                           focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500
                                           focus:shadow-md focus:shadow-emerald-500/10
                                           transition-all duration-200" />
                            </div>

                            {{-- Suggestions --}}
                            <div class="mt-1.5 rounded-xl border border-slate-200 dark:border-zinc-700
                                        bg-white dark:bg-zinc-800 overflow-hidden">
                                @forelse ($suggestions as $u)
                                    <button type="button"
                                        wire:key="user-{{ $u->id }}"
                                        wire:click="selectUser({{ $u->id }})"
                                        class="w-full flex items-center gap-2.5 px-2.5 py-1.5
                                               text-left transition-colors
                                               hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                                               {{ ! $loop->last ? 'border-b border-slate-100 dark:border-zinc-700/70' : '' }}">
                                        <div class="w-7 h-7 rounded-full shrink-0
                                                    bg-indigo-100 dark:bg-indigo-900/40
                                                    flex items-center justify-center
                                                    text-[10px] font-bold
                                                    text-indigo-700 dark:text-indigo-300">
                                            {{ strtoupper(substr($u->full_name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[11px] font-medium truncate
                                                      text-slate-900 dark:text-zinc-100">
                                                {{ $u->full_name }}
                                            </p>
                                            <p class="text-[9.5px] truncate text-slate-500 dark:text-zinc-500">
                                                {{ $u->nidn ?? '—' }} · {{ $u->email }}
                                            </p>
                                        </div>
                                        <flux:icon.chevron-right class="size-3 text-slate-400 shrink-0" />
                                    </button>
                                @empty
                                    <div class="px-3 py-3 text-center">
                                        @if ($userSearch)
                                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                                                No user found
                                            </p>
                                        @else
                                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400">
                                                Type to search users
                                            </p>
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                        @endif

                        @error('form.user_id')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ TITLE ══════════ --}}
                    <div>
                        <x-input
                            wire:model="form.title"
                            label="Research Title"
                            required
                            rounded="full"
                            placeholder="e.g. Sistem Deteksi Dini Banjir Berbasis IoT" />
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
                                rounded="full"
                                placeholder="e.g. Penelitian Dasar Unggulan" />
                            @error('form.scheme')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input
                                wire:model="form.funding_source"
                                label="Funding Source"
                                required
                                rounded="full"
                                placeholder="e.g. DRTPM, BRIN, Industry" />
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
                                color="indigo">
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
                                color="indigo">
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
                                rounded="full"
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
                                rounded="full"
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
                            rounded="full"
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
                            color="indigo"
                            rounded="full"
                            placeholder="Short description..." />
                        @error('form.description')
                            <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ══════════ SUPPORTING DOCUMENTS ══════════ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">

                        {{-- Proposal Document --}}
                        <div>
                            <x-input
                                type="file"
                                wire:model="form.proposal_document"
                                label="Proposal Document (optional)"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                rounded="full"
                                hint="Max 10 MB" />
                            <div wire:loading wire:target="form.proposal_document"
                                class="mt-1 text-[10px] text-indigo-600 dark:text-indigo-400">
                                Uploading...
                            </div>
                            @error('form.proposal_document')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Report Document --}}
                        <div>
                            <x-input
                                type="file"
                                wire:model="form.report_document"
                                label="Report Document (optional)"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                rounded="full"
                                hint="Max 10 MB" />
                            <div wire:loading wire:target="form.report_document"
                                class="mt-1 text-[10px] text-indigo-600 dark:text-indigo-400">
                                Uploading...
                            </div>
                            @error('form.report_document')
                                <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
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
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-indigo-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Cancel
                </button>

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save, form.proposal_document, form.report_document"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                           bg-indigo-600/90 hover:bg-indigo-600
                           shadow-sm shadow-indigo-500/20 hover:shadow-sm hover:shadow-indigo-500/30
                           disabled:opacity-60 disabled:cursor-wait disabled:hover:scale-100
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
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
