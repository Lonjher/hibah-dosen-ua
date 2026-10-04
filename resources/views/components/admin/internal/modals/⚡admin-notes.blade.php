<?php

use App\Models\AdminNote;
use App\Models\Proposal;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $noteable_id = null;
    public string $noteable_type = 'proposal';
    public string $subject = '';
    public Collection $notes;

    public function mount(): void
    {
        $this->notes = collect();
    }

    #[On('open-admin-notes')]
    public function load(int $id, string $type = 'proposal'): void
    {
        $this->noteable_id   = $id;
        $this->noteable_type = $type;

        $this->subject = match ($type) {
            'proposal' => Proposal::find($id)?->title ?? '',
            default    => '',
        };

        $this->loadNotes();
        $this->dispatch('show-admin-notes');
    }

    public function loadNotes(): void
    {
        $this->notes = AdminNote::query()
            ->where('noteable_id', $this->noteable_id)
            ->where('noteable_type', $this->noteable_type)
            ->with('admin')
            ->latest()
            ->get();
    }
};
?>

<div
    x-data="{
        show: false,
        init() {
            window.addEventListener('show-admin-notes', () => { this.show = true; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-lg flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700
                hover:shadow-slate-500/15 transition-shadow duration-300"
        @click.stop>

        <div class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-slate-700 to-slate-600
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.chat-bubble-left-right class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Admin Notes
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 leading-snug">
                                Riwayat catatan admin.
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95
                               transition-all duration-150">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-4">

                {{-- Subject --}}
                @if ($subject)
                    <div class="rounded-2xl border border-slate-200 dark:border-zinc-700
                                bg-slate-50 dark:bg-zinc-800/40 p-3">
                        <p class="text-[10px] font-medium text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                            Subjek
                        </p>
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-zinc-100 mt-1 line-clamp-2">
                            {{ $subject }}
                        </p>
                    </div>
                @endif

                {{-- Notes List --}}
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                        Total Catatan ({{ $notes->count() }})
                    </p>

                    @forelse ($notes as $note)
                        <div wire:key="note-{{ $note->id }}"
                            class="rounded-2xl border border-amber-200 dark:border-amber-800
                                   bg-amber-50 dark:bg-amber-900/20 p-3">
                            <div class="flex items-center gap-2 mb-1.5">
                                <div class="w-6 h-6 rounded-full bg-amber-200 dark:bg-amber-800/50
                                            flex items-center justify-center text-[9px] font-bold
                                            text-amber-800 dark:text-amber-300">
                                    {{ strtoupper(substr($note->admin?->full_name ?? 'A', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-semibold text-amber-800 dark:text-amber-300 truncate">
                                        {{ $note->admin?->full_name ?? 'Admin' }}
                                    </p>
                                    <p class="text-[9px] text-amber-600 dark:text-amber-400">
                                        {{ $note->created_at?->format('d M Y, H:i') }}
                                        · {{ $note->created_at?->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase
                                            bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                    Admin
                                </span>
                            </div>

                            @if ($note->comment)
                                <p class="text-[11px] text-amber-900 dark:text-amber-100 mt-2 leading-snug">
                                    {{ $note->comment }}
                                </p>
                            @endif

                            @if ($note->recommendation)
                                <div class="mt-2 pt-2 border-t border-amber-200 dark:border-amber-800">
                                    <p class="text-[10px] text-amber-700 dark:text-amber-300">
                                        <span class="font-semibold">Rekomendasi:</span> {{ $note->recommendation }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 py-8">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-zinc-800
                                        flex items-center justify-center">
                                <flux:icon.chat-bubble-left-right class="size-6 text-slate-400 dark:text-zinc-600" />
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                Belum ada catatan admin.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex justify-end
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-slate-50/50 dark:bg-zinc-900/50 rounded-b-3xl sm:rounded-b-3xl">
                <button type="button" @click="show = false"
                    class="px-3 py-1.5 text-[11px] font-medium rounded-full
                           text-slate-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800
                           border border-slate-300 dark:border-zinc-600
                           hover:bg-slate-50 dark:hover:bg-zinc-700
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-slate-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
