<?php

use App\Models\AdminNote;
use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Researches')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public function with()
    {
        $proposals = Proposal::query()
            ->with(['author', 'researchScheme', 'period', 'reviewer'])
            ->where('is_research', true) // 🔑 HANYA PENELITIAN
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('keywords', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return ['proposals' => $proposals];
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    // ═══════════════ AKSI ADMIN ═══════════════

    /**
     * Submit: pending/revised → submitted
     */
    public function submit(Proposal $proposal): void
    {
        if (! in_array($proposal->status, ['pending', 'revised'])) {
            Flux::toast('Proposal tidak dapat di-submit pada status ini.', variant: 'danger');
            return;
        }

        $proposal->update(['status' => 'submitted']);
        Flux::toast('Proposal berhasil di-submit.');
    }

    /**
     * Revise: minta revisi ke user + auto-create AdminNote.
     */
    public function revise(Proposal $proposal): void
    {
        if (! in_array($proposal->status, ['pending', 'submitted'])) {
            Flux::toast('Proposal tidak dapat direvisi pada status ini.', variant: 'danger');
            return;
        }

        AdminNote::create([
            'noteable_id'    => $proposal->id,
            'noteable_type'  => 'proposal',
            'admin_id'       => auth()->id(),
            'comment'        => 'Proposal penelitian perlu direvisi. Silakan periksa kembali kelengkapan dan isi proposal sesuai ketentuan.',
            'recommendation' => 'Revisi proposal',
        ]);

        $proposal->update(['status' => 'revised']);
        Flux::toast('Proposal dikembalikan untuk revisi.');
    }

    /**
     * Reject: tolak permanen.
     */
    public function reject(Proposal $proposal): void
    {
        if ($proposal->status === 'rejected') {
            Flux::toast('Proposal sudah ditolak.', variant: 'danger');
            return;
        }

        $proposal->update(['status' => 'rejected']);
        Flux::toast('Proposal ditolak.');
    }

    /**
     * Hapus permanen
     */
    public function delete(Proposal $proposal): void
    {
        $proposal->delete();
        Flux::toast('Proposal dihapus.');
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 p-3 sm:p-4 lg:p-6">
    <div class="max-w-7xl mx-auto space-y-4">

        <x-dashboard-header icon="document-text" title="Manage Researches"
            leading="Kelola proposal penelitian. Verifikasi, revisi, atau tolak proposal." />

        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-zinc-950/50 border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ══════════ TOOLBAR ══════════ --}}
            <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-zinc-800 bg-gradient-to-r from-slate-50 to-white dark:from-zinc-900 dark:to-zinc-900/50">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center w-5 h-5 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                                <flux:icon.list-bullet class="size-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <p class="text-[10px] font-medium text-slate-900 dark:text-zinc-400">
                                {{ $proposals->total() }} found
                            </p>
                        </div>

                        {{-- Filter Status --}}
                        <select wire:model.live="statusFilter"
                            class="text-[11px] rounded-md border-slate-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800 text-slate-900 dark:text-zinc-100
                                   focus:border-emerald-500 focus:ring-emerald-500 py-1 px-2">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="revised">Revised</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1 lg:flex-none lg:w-64">
                            <x-input-search name="q" wire:model.live="search" id="search-research"
                                placeholder="Cari proposal..." class="w-full text-xs" />
                        </div>
                        {{-- TIDAK ADA tombol Tambah --}}
                    </div>
                </div>
            </div>

            {{-- ══════════ TABLE ══════════ --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-900/50 border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Research</span>
                            </th>
                            <th class="hidden md:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Author</span>
                            </th>
                            <th class="hidden lg:table-cell px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Scheme</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-left">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Status</span>
                            </th>
                            <th class="px-3 sm:px-4 py-2.5 text-right">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-700 dark:text-zinc-300">Action</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                        @forelse ($proposals as $proposal)
                            @php $meta = $proposal->statusMeta(); @endphp
                            <tr class="group hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">

                                {{-- Research --}}
                                <td class="px-3 sm:px-4 py-2.5">
                                    <div class="flex items-start gap-2">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-lg
                                                    bg-gradient-to-br from-emerald-100 to-teal-50
                                                    dark:from-emerald-900/30 dark:to-teal-900/20
                                                    shrink-0 group-hover:scale-105 transition-transform">
                                            <flux:icon.document-text class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-slate-900 dark:text-white line-clamp-1">
                                                {{ $proposal->title }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[9px] px-1.5 py-0.5 rounded
                                                            bg-emerald-100 text-emerald-700
                                                            dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    Penelitian
                                                </span>
                                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 line-clamp-1">
                                                    {{ $proposal->keywords }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Author --}}
                                <td class="hidden md:table-cell px-3 sm:px-4 py-2.5">
                                    <span class="text-[11px] text-slate-700 dark:text-zinc-300">
                                        {{ $proposal->author?->full_name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Scheme --}}
                                <td class="hidden lg:table-cell px-3 sm:px-4 py-2.5">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full
                                                 bg-slate-100 dark:bg-zinc-800
                                                 text-slate-700 dark:text-zinc-300">
                                        {{ $proposal->researchScheme?->code ?? '—' }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-3 sm:px-4 py-2.5">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-semibold {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-3 sm:px-4 py-2.5 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800" />

                                        <flux:menu class="min-w-[200px] text-xs">

                                            {{-- Submit --}}
                                            @if (in_array($proposal->status, ['pending', 'revised']))
                                                <flux:menu.item icon="check-circle"
                                                    wire:click="submit({{ $proposal->id }})">
                                                    Submit (Lolos Verifikasi)
                                                </flux:menu.item>
                                            @endif

                                            {{-- Revise --}}
                                            @if (in_array($proposal->status, ['pending', 'submitted']))
                                                <flux:menu.item icon="arrow-path"
                                                    wire:click="revise({{ $proposal->id }})">
                                                    Minta Revisi
                                                </flux:menu.item>
                                            @endif

                                            {{-- Reject --}}
                                            @if ($proposal->status !== 'rejected')
                                                <flux:menu.item variant="danger" icon="x-circle"
                                                    wire:click="reject({{ $proposal->id }})"
                                                    wire:confirm="Yakin ingin menolak proposal ini?">
                                                    Tolak Proposal
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.separator />

                                            {{-- Hapus --}}
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="delete({{ $proposal->id }})"
                                                wire:confirm="Yakin ingin menghapus proposal ini? Data akan hilang permanen.">
                                                Hapus Proposal
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 sm:px-4 py-12">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-slate-100 dark:bg-zinc-800">
                                            <flux:icon.document-text class="size-7 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                Belum ada proposal penelitian
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                                Proposal penelitian akan muncul setelah user mengajukan.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($proposals->hasPages())
                <div class="px-3 sm:px-4 py-3 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900/50">
                    {{ $proposals->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TIDAK ADA MODAL --}}
</div>
