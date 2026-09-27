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
    public ?int $reviewer_id = null;

    #[On('open-admin-note-revision')]
    public function load(int $id): void
    {
        $proposal = Proposal::with('reviewer')->findOrFail($id);

        $this->proposal_id    = $proposal->id;
        $this->proposal_title = $proposal->title;
        $this->reviewer_id    = $proposal->reviewer_id;

        // Ambil admin note terakhir
        $this->lastNote = AdminNote::query()
            ->where('noteable_id', $proposal->id)
            ->where('noteable_type', 'proposal')
            ->with('admin')
            ->latest()
            ->first();

        // Setup form
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
        // Validasi: comment wajib diisi untuk revisi
        $this->validate([
            'form.comment' => ['required', 'string', 'min:5'],
        ], [
            'form.comment.required' => 'Catatan revisi wajib diisi.',
            'form.comment.min'      => 'Catatan revisi minimal 5 karakter.',
        ]);

        $proposal = Proposal::findOrFail($this->proposal_id);

        // 1. Buat AdminNote
        $this->form->create();

        // 2. Update status ke revised
        $proposal->update(['status' => 'revised']);

        Flux::toast('Proposal dikembalikan untuk revisi.', variant: 'success');
        $this->dispatch('proposal-revised');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->reset(['proposal_id', 'proposal_title', 'lastNote', 'reviewer_id']);
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
            window.addEventListener('admin-note-revision-error', (e) => { this.errorMessage = e.detail.message; });
            window.addEventListener('proposal-revised', () => { this.show = false; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-lg flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-amber-600 to-amber-500
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.arrow-path class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Request Revision
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-snug">
                                Berikan catatan revisi untuk pengusul.
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                <template x-if="errorMessage">
                    <div class="flex items-start gap-2 p-3 rounded-lg
                                bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                        <flux:icon.exclamation-triangle class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] text-rose-700 dark:text-rose-300" x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Proposal Info --}}
                <div class="rounded-lg border border-slate-200 dark:border-zinc-700
                            bg-slate-50 dark:bg-zinc-800/40 p-3">
                    <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                        Proposal
                    </p>
                    <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1 line-clamp-2">
                        {{ $proposal_title }}
                    </p>
                </div>

                {{-- Last Admin Note --}}
                @if ($lastNote)
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800
                                bg-amber-50 dark:bg-amber-900/20 p-3">
                        <div class="flex items-center gap-2 mb-1.5">
                            <flux:icon.clock class="size-3 text-amber-600 dark:text-amber-400" />
                            <p class="text-[10px] font-semibold text-amber-800 dark:text-amber-300 uppercase tracking-wider">
                                Catatan Sebelumnya
                            </p>
                        </div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-medium text-amber-800 dark:text-amber-300">
                                {{ $lastNote->admin?->full_name ?? 'Admin' }}
                            </span>
                            <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                {{ $lastNote->created_at?->diffForHumans() }}
                            </span>
                        </div>
                        @if ($lastNote->comment)
                            <p class="text-[11px] text-amber-900 dark:text-amber-100 whitespace-pre-wrap">
                                {{ $lastNote->comment }}
                            </p>
                        @endif
                        @if ($lastNote->recommendation)
                            <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1">
                                <span class="font-semibold">Rekomendasi:</span> {{ $lastNote->recommendation }}
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Comment --}}
                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Catatan Revisi <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="form.comment" rows="4"
                        placeholder="Contoh: Metodologi perlu diperjelas, tambahkan referensi terbaru..."
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-amber-500 focus:ring-amber-500 py-2 px-3"></textarea>
                    @error('form.comment')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- Recommendation --}}
                <div>
                    <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                        Rekomendasi <span class="text-slate-400">(opsional)</span>
                    </label>
                    <input type="text" wire:model="form.recommendation"
                        placeholder="Contoh: Fokus pada perbaikan Bab 3"
                        class="block w-full rounded-md shadow-sm text-[12px]
                               border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                               text-slate-900 dark:text-zinc-100
                               focus:border-amber-500 focus:ring-amber-500 py-2 px-3" />
                    @error('form.recommendation')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Kirim Revisi</span>
                    <span wire:loading.flex wire:target="save">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
