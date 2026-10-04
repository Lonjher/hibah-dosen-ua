<?php

use App\Livewire\Forms\AdminNoteForm;
use App\Models\AdminNote;
use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public AdminNoteForm $form;
    public ?int $proposal_id = null;
    public string $proposal_title = '';
    public ?AdminNote $lastNote = null;

    #[On('open-admin-note-revision')]
    public function load(int $id): void
    {
        $proposal = Proposal::find($id);

        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        $this->proposal_id    = $proposal->id;
        $this->proposal_title = $proposal->title;

        $this->lastNote = AdminNote::query()
            ->where('noteable_id', $proposal->id)
            ->where('noteable_type', 'proposal')
            ->with('admin')
            ->latest()
            ->first();

        $this->form->reset();
        $this->form->admin_id      = auth()->id();
        $this->form->noteable_id   = $proposal->id;
        $this->form->noteable_type = 'proposal';

        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('show-admin-note-revision');
    }

    public function save(): void
    {
        $this->validate([
            'form.comment' => ['required', 'string', 'min:5'],
        ], [
            'form.comment.required' => 'Revision note is required.',
            'form.comment.min'      => 'Revision note must be at least 5 characters.',
        ]);

        $proposal = Proposal::find($this->proposal_id);
        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        $this->form->create();
        $proposal->update(['status' => 'revised']);

        Flux::toast('Proposal sent back for revision.', variant: 'success');
        $this->dispatch('proposal-revised');
        $this->dispatch('close-admin-note-revision-modal');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->reset(['proposal_id', 'proposal_title', 'lastNote']);
        $this->form->admin_id      = auth()->id();
        $this->form->noteable_type = 'proposal';
        $this->resetErrorBag();
        $this->resetValidation();
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('show-admin-note-revision', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('close-admin-note-revision-modal', () => {
                this.show = false;
            });
            window.addEventListener('admin-note-revision-error', (e) => {
                this.errorMessage = e.detail.message;
            });
        }
    }"
    x-show="show"
    x-transition.opacity
    x-cloak
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="flex max-h-[92vh] w-full sm:max-w-md flex-col overflow-hidden
               bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
               border border-slate-200 dark:border-zinc-700
               hover:shadow-amber-500/15 transition-shadow duration-300"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-amber-600 to-amber-500
                        px-4 py-3 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.arrow-path class="size-3.5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[13px] font-semibold text-white leading-tight">
                                Request Revision
                            </h3>
                            <p class="text-[10.5px] text-white/75 mt-0.5 leading-tight">
                                Provide revision notes for the author
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

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

                {{-- Global Error --}}
                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-2.5 rounded-2xl
                                bg-rose-50 dark:bg-rose-900/20
                                border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle
                            class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[10.5px] leading-relaxed
                                  text-rose-700 dark:text-rose-300"
                            x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Proposal Info --}}
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-700/70
                            bg-slate-50 dark:bg-zinc-800/40 px-3 py-2.5">
                    <p class="text-[9.5px] uppercase tracking-wider font-semibold
                              text-slate-500 dark:text-zinc-400">
                        Proposal
                    </p>
                    <p class="mt-1 text-[11px] font-semibold leading-tight line-clamp-2
                              text-slate-900 dark:text-zinc-100">
                        {{ $proposal_title }}
                    </p>
                </div>

                {{-- Last note --}}
                @if ($lastNote)
                    <div class="rounded-2xl border border-amber-200 dark:border-amber-800
                                bg-amber-50 dark:bg-amber-900/20 px-3 py-2.5">
                        <div class="flex items-center gap-1.5 mb-1">
                            <flux:icon.clock class="size-2.5 text-amber-600 dark:text-amber-400" />
                            <p class="text-[9.5px] font-semibold uppercase tracking-wider
                                      text-amber-800 dark:text-amber-300">
                                Previous Note
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 mb-1 text-[9.5px]">
                            <span class="font-medium text-amber-800 dark:text-amber-300">
                                {{ $lastNote->admin?->full_name ?? 'Admin' }}
                            </span>
                            <span class="w-0.5 h-0.5 rounded-full bg-current opacity-60"></span>
                            <span class="text-amber-600 dark:text-amber-400">
                                {{ $lastNote->created_at?->diffForHumans() }}
                            </span>
                        </div>
                        @if ($lastNote->comment)
                            <p class="text-[10.5px] leading-relaxed
                                      text-amber-900 dark:text-amber-100">
                                {{ $lastNote->comment }}
                            </p>
                        @endif
                        @if ($lastNote->recommendation)
                            <p class="mt-1 text-[9.5px] text-amber-700 dark:text-amber-300">
                                <span class="font-semibold">Recommendation:</span>
                                {{ $lastNote->recommendation }}
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Comment --}}
                <div>
                    <x-textarea
                        wire:model="form.comment"
                        label="Revision Note"
                        required
                        rows="4"
                        rounded="full"
                        color="amber"
                        placeholder="e.g. Methodology needs clarification, add recent references..." />
                    @error('form.comment')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Recommendation --}}
                <div>
                    <x-input
                        wire:model="form.recommendation"
                        label="Recommendation (optional)"
                        rounded="full"
                        placeholder="e.g. Focus on fixing Chapter 3" />
                    @error('form.recommendation')
                        <p class="mt-1 text-[10.5px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-1.5
                        px-4 py-3 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">

                <button type="button" @click="show = false"
                    class="w-full sm:w-auto px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40
                           hover:shadow-sm hover:shadow-amber-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Cancel
                </button>

                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-3 py-1.5 text-[11px] font-medium rounded-full text-white
                           bg-amber-600/90 hover:bg-amber-600
                           shadow-sm shadow-amber-500/20
                           hover:shadow-sm hover:shadow-amber-500/30
                           disabled:opacity-60 disabled:cursor-wait
                           hover:scale-[1.02] active:scale-[0.97]
                           disabled:hover:scale-100
                           transition-all duration-150">
                    <svg wire:loading wire:target="save"
                         class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">Send Revision</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
