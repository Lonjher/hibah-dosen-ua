<?php

use App\Models\Proposal;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $proposal_id = null;
    public ?Proposal $proposal = null;

    #[On('open-view-members')]
    public function load(int $id): void
    {
        $this->proposal_id = $id;
        $this->proposal = Proposal::with([
            'author',
            'proposalMembers.user',
            'proposalStudents',
        ])->find($id);

        if (! $this->proposal) {
            return;
        }

        $this->dispatch('show-view-members');
    }
};
?>

<div
    x-data="{
        show: false,
        init() {
            window.addEventListener('show-view-members', () => { this.show = true; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-lg flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        @if ($proposal)
            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r from-emerald-600 to-emerald-500
                        px-5 sm:px-6 py-4 rounded-t-3xl sm:rounded-t-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon.user-group class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Proposal Members
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5 line-clamp-1">
                                {{ $proposal->title }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        aria-label="Close"
                        class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10
                               hover:scale-110 active:scale-95 transition-all duration-150">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- BODY --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-5">

                {{-- LEADER (Ketua) --}}
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <flux:icon.star class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <h4 class="text-[10px] font-semibold uppercase tracking-wider
                                   text-slate-500 dark:text-zinc-400">
                            Ketua
                        </h4>
                    </div>

                    @if ($proposal->author)
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-full
                                    bg-emerald-50 dark:bg-emerald-900/20
                                    border border-emerald-200 dark:border-emerald-800/60">
                            <div class="w-7 h-7 rounded-full shrink-0
                                        bg-emerald-200 dark:bg-emerald-800/50
                                        flex items-center justify-center
                                        text-[10px] font-bold
                                        text-emerald-700 dark:text-emerald-300">
                                {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11.5px] font-semibold truncate
                                          text-slate-900 dark:text-zinc-100">
                                    {{ $proposal->author->full_name }}
                                </p>
                                <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                    NIDN: {{ $proposal->author->nidn ?? '—' }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ANGGOTA DOSEN --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <flux:icon.user-group class="size-3.5 text-blue-600 dark:text-blue-400" />
                            <h4 class="text-[10px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Anggota Dosen
                            </h4>
                        </div>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                     bg-blue-100 text-blue-700
                                     dark:bg-blue-900/40 dark:text-blue-300">
                            {{ $proposal->proposalMembers->count() }}
                        </span>
                    </div>

                    @if ($proposal->proposalMembers->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($proposal->proposalMembers as $m)
                                <div wire:key="pm-{{ $m->id }}"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-full
                                           bg-white dark:bg-zinc-800
                                           border border-slate-200 dark:border-zinc-700">
                                    <div class="w-7 h-7 rounded-full shrink-0
                                                bg-blue-100 dark:bg-blue-900/40
                                                flex items-center justify-center
                                                text-[10px] font-bold
                                                text-blue-700 dark:text-blue-300">
                                        {{ strtoupper(substr($m->user?->full_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11.5px] font-medium truncate
                                                  text-slate-900 dark:text-zinc-100">
                                            {{ $m->user?->full_name ?? '—' }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                            NIDN: {{ $m->user?->nidn ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic px-2">
                            Tidak ada anggota dosen.
                        </p>
                    @endif
                </div>

                {{-- ANGGOTA MAHASISWA --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <flux:icon.academic-cap class="size-3.5 text-violet-600 dark:text-violet-400" />
                            <h4 class="text-[10px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Anggota Mahasiswa
                            </h4>
                        </div>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full
                                     bg-violet-100 text-violet-700
                                     dark:bg-violet-900/40 dark:text-violet-300">
                            {{ $proposal->proposalStudents->count() }}
                        </span>
                    </div>

                    @if ($proposal->proposalStudents->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($proposal->proposalStudents as $s)
                                <div wire:key="ps-{{ $s->id }}"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-full
                                           bg-white dark:bg-zinc-800
                                           border border-slate-200 dark:border-zinc-700">
                                    <div class="w-7 h-7 rounded-full shrink-0
                                                bg-violet-100 dark:bg-violet-900/40
                                                flex items-center justify-center
                                                text-[10px] font-bold
                                                text-violet-700 dark:text-violet-300">
                                        {{ strtoupper(substr($s->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11.5px] font-medium truncate
                                                  text-slate-900 dark:text-zinc-100">
                                            {{ $s->name }}
                                        </p>
                                        <p class="text-[9.5px] text-slate-500 dark:text-zinc-500 truncate">
                                            NIM: {{ $s->nim }} · {{ $s->program_study }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic px-2">
                            Tidak ada anggota mahasiswa.
                        </p>
                    @endif
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
                           shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-emerald-500/15
                           hover:scale-[1.02] active:scale-[0.97]
                           transition-all duration-150">
                    Tutup
                </button>
            </div>
        @endif
    </div>
</div>
