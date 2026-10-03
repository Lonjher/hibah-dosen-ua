<?php

use App\Models\Proposal;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Dedications')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    /* ============================================================
     |  ACTIONS
     ============================================================ */

    public function submit(int $id): void
    {
        $proposal = Proposal::find($id);
        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if (! in_array($proposal->status, ['pending', 'revised', 'rejected'], true)) {
            Flux::toast('Proposal cannot be submitted at this status.', variant: 'danger');
            return;
        }

        $proposal->update(['status' => 'submitted']);
        Flux::toast('Proposal submitted successfully.', variant: 'success');
    }

    public function reject(int $id): void
    {
        $proposal = Proposal::find($id);
        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        if ($proposal->status === 'rejected') {
            Flux::toast('Proposal already rejected.', variant: 'danger');
            return;
        }

        $proposal->update(['status' => 'rejected']);
        Flux::toast('Proposal rejected.', variant: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::find($id);
        if (! $proposal) {
            Flux::toast('Proposal not found.', variant: 'danger');
            return;
        }

        $this->dispatch(
            'confirm-delete',
            title: 'Delete Proposal?',
            message: 'You are about to delete:',
            subject: $proposal->title,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            action: 'deleteProposal',
            payload: ['id' => $proposal->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteProposal') {
            $this->deleteProposal($payload['id'] ?? null);
        }
    }

    public function deleteProposal(?int $id): void
    {
        if (! $id) return;

        $proposal = Proposal::find($id);
        if (! $proposal) return;

        try {
            $title = $proposal->title;
            $proposal->delete();
            Flux::toast("Proposal \"{$title}\" deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete proposal.', variant: 'danger');
        }
    }

    /* ============================================================
     |  QUERY
     ============================================================ */

    public function with(): array
    {
        $dedications = Proposal::query()
            ->with(['author', 'researchScheme', 'period', 'reviewer'])
            ->where('is_research', false)
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('keywords', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return ['dedications' => $dedications];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="heart" title="Manage Dedications"
            leading="Manage community service proposals. Verify, revise, or reject." />

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
                    border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR ─────── --}}
            <div class="px-2.5 sm:px-3 py-2
                        border-b border-slate-200 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">

                    <div class="flex flex-wrap items-center gap-1.5">
                        <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md
                                    bg-rose-50 dark:bg-rose-900/20
                                    border border-rose-100 dark:border-rose-900/40
                                    w-fit">
                            <flux:icon.heart class="size-3 text-rose-600 dark:text-rose-400" />
                            <span class="text-[10.5px] font-semibold text-rose-700 dark:text-rose-300">
                                {{ $dedications->total() }}
                            </span>
                            <span class="text-[10px] text-rose-600/70 dark:text-rose-400/70">
                                dedications
                            </span>
                        </div>

                        <select wire:model.live="statusFilter"
                            class="text-[10.5px] rounded-md border-slate-200 dark:border-zinc-700
                                   bg-white dark:bg-zinc-800
                                   text-slate-700 dark:text-zinc-200
                                   py-1 pl-2 pr-6
                                   focus:ring-1 focus:ring-rose-500 focus:border-rose-500
                                   transition-colors">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="revised">Revised</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="flex-1 lg:flex-none lg:w-56">
                        <x-input-search name="q" wire:model.live="search" id="search-dedication"
                            placeholder="Search dedications..."
                            class="w-full !text-[10.5px]" />
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE ─────── --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-zinc-900/50
                                   border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">Dedication</th>
                            <th class="hidden md:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">Author</th>
                            <th class="hidden md:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">Reviewer</th>
                            <th class="hidden lg:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">Scheme</th>
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">Status</th>
                            <th class="px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                        @forelse ($dedications as $proposal)
                            @php $meta = $proposal->statusMeta(); @endphp
                            <tr wire:key="ded-{{ $proposal->id }}"
                                class="group transition-colors duration-150
                                       hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                {{-- DEDICATION --}}
                                <td class="px-3 py-2">
                                    <div class="flex items-start gap-2">
                                        <div class="w-7 h-7 rounded-md shrink-0
                                                    bg-gradient-to-br from-rose-100 to-pink-50
                                                    dark:from-rose-900/30 dark:to-pink-900/20
                                                    flex items-center justify-center
                                                    ring-1 ring-white/40 dark:ring-zinc-800/40
                                                    group-hover:scale-105 transition-transform">
                                            <flux:icon.heart class="size-3.5 text-rose-600 dark:text-rose-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[11.5px] font-semibold leading-tight truncate
                                                      text-slate-900 dark:text-white">
                                                {{ Str::limit($proposal->title, 40) }}
                                            </p>
                                            <div class="mt-0.5 flex items-center gap-1.5 flex-wrap
                                                        text-[9.5px]">
                                                <span class="px-1 rounded font-semibold
                                                             bg-rose-100 text-rose-700
                                                             dark:bg-rose-900/30 dark:text-rose-300">
                                                    Dedication
                                                </span>
                                                <span class="text-slate-500 dark:text-zinc-500 truncate max-w-[160px]">
                                                    {{ $proposal->keywords }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- AUTHOR --}}
                                <td class="hidden md:table-cell px-3 py-2">
                                    @if ($proposal->author)
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-6 h-6 rounded-full shrink-0
                                                        bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300
                                                        flex items-center justify-center text-[9px] font-bold">
                                                {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                            </div>
                                            <span class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate">
                                                {{ $proposal->author->full_name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">—</span>
                                    @endif
                                </td>

                                {{-- REVIEWER --}}
                                <td class="hidden md:table-cell px-3 py-2">
                                    @if ($proposal->reviewer)
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-6 h-6 rounded-full shrink-0
                                                        bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300
                                                        flex items-center justify-center text-[9px] font-bold">
                                                {{ strtoupper(substr($proposal->reviewer->full_name, 0, 1)) }}
                                            </div>
                                            <span class="text-[10.5px] text-slate-700 dark:text-zinc-300 truncate">
                                                {{ $proposal->reviewer->full_name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-[10.5px] text-slate-400 dark:text-zinc-500 italic">Not assigned</span>
                                    @endif
                                </td>

                                {{-- SCHEME --}}
                                <td class="hidden lg:table-cell px-3 py-2">
                                    <span class="text-[9.5px] px-1.5 py-0.5 rounded-full
                                                 bg-slate-100 dark:bg-zinc-800
                                                 text-slate-600 dark:text-zinc-400">
                                        {{ $proposal->researchScheme?->code ?? '—' }}
                                    </span>
                                </td>

                                {{-- STATUS --}}
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full
                                                 text-[9.5px] font-semibold
                                                 {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>

                                {{-- ACTION --}}
                                <td class="px-3 py-2 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                            class="!p-1 rounded-md text-slate-400
                                                   hover:bg-slate-100 hover:text-slate-600
                                                   dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300
                                                   opacity-60 group-hover:opacity-100
                                                   transition-opacity" />

                                        <flux:menu class="!text-[11px]">

                                            @if ($proposal->status === 'pending')
                                                <flux:menu.item icon="check-circle"
                                                    wire:click="submit({{ $proposal->id }})">
                                                    Submit Proposal
                                                </flux:menu.item>
                                                <flux:menu.item icon="arrow-path" x-data
                                                    x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                    Request Revision
                                                </flux:menu.item>
                                                <flux:menu.item variant="danger" icon="x-circle"
                                                    wire:click="reject({{ $proposal->id }})">
                                                    Reject Proposal
                                                </flux:menu.item>
                                            @endif

                                            @if ($proposal->status === 'revised')
                                                <flux:menu.item icon="check-circle"
                                                    wire:click="submit({{ $proposal->id }})">
                                                    Submit Proposal
                                                </flux:menu.item>
                                                <flux:menu.item icon="arrow-path" x-data
                                                    x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                    Request Revision
                                                </flux:menu.item>
                                                <flux:menu.item variant="danger" icon="x-circle"
                                                    wire:click="reject({{ $proposal->id }})">
                                                    Reject Proposal
                                                </flux:menu.item>
                                            @endif

                                            @if ($proposal->status === 'submitted')
                                                <flux:menu.item icon="arrow-path" x-data
                                                    x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                    Request Revision
                                                </flux:menu.item>
                                                <flux:menu.item variant="danger" icon="x-circle"
                                                    wire:click="reject({{ $proposal->id }})">
                                                    Reject Proposal
                                                </flux:menu.item>
                                                <flux:menu.item icon="user-plus" x-data
                                                    x-on:click="$dispatch('open-assign-reviewer', { id: {{ $proposal->id }} })"
                                                    class="text-violet-600 dark:text-violet-400">
                                                    Assign Reviewer
                                                </flux:menu.item>
                                            @endif

                                            @if ($proposal->status === 'rejected')
                                                <flux:menu.item icon="arrow-path" x-data
                                                    x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                    Request Revision
                                                </flux:menu.item>
                                                <flux:menu.item icon="check-circle"
                                                    wire:click="submit({{ $proposal->id }})">
                                                    Submit Proposal
                                                </flux:menu.item>
                                            @endif

                                            @if ($proposal->progressReport || $proposal->finalReport || $proposal->output)
                                                <flux:menu.separator />
                                                <flux:menu.item icon="eye" x-data
                                                    x-on:click="$dispatch('open-view-submission', { proposalId: {{ $proposal->id }} })">
                                                    View Submissions
                                                </flux:menu.item>
                                            @endif

                                            @if ($proposal->status === 'under_review')
                                                <flux:menu.item icon="arrow-path" x-data
                                                    x-on:click="$dispatch('open-admin-note-revision', { id: {{ $proposal->id }} })">
                                                    Request Revision
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                            @endif

                                            @if ($proposal->status === 'accepted')
                                                <flux:menu.item variant="danger" icon="x-circle"
                                                    wire:click="reject({{ $proposal->id }})">
                                                    Reject Proposal
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.separator />

                                            @if (! in_array($proposal->status, ['pending']))
                                                <flux:menu.item icon="chat-bubble-left-right" x-data
                                                    x-on:click="$dispatch('open-admin-notes', { id: {{ $proposal->id }}, type: 'proposal' })">
                                                    Admin Notes
                                                </flux:menu.item>
                                            @endif

                                            @if (in_array($proposal->status, ['under_review', 'accepted', 'rejected']))
                                                <flux:menu.item icon="clipboard-document-check" x-data
                                                    x-on:click="$dispatch('open-reviewer-notes', { id: {{ $proposal->id }}, type: 'proposal' })"
                                                    class="text-violet-600 dark:text-violet-400">
                                                    Reviewer Notes
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.item icon="eye" x-data
                                                x-on:click="$dispatch('open-view-proposal', { id: {{ $proposal->id }} })">
                                                View Proposal
                                            </flux:menu.item>

                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="confirmDelete({{ $proposal->id }})">
                                                Delete Proposal
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10">
                                    <div class="flex flex-col items-center gap-2 text-center">
                                        <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-zinc-800
                                                    flex items-center justify-center">
                                            <flux:icon.heart class="size-5 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                No dedications yet
                                            </p>
                                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                Dedications will appear after users submit them.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($dedications->hasPages())
                <div class="px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50">
                    {{ $dedications->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.internal.modals.assign-reviewer />
    <livewire:admin.internal.modals.admin-note-revision />
    <livewire:admin.internal.modals.reviewer-notes />
    <livewire:admin.internal.modals.admin-notes />
    <livewire:admin.internal.modals.view-proposal />
    <livewire:admin.internal.modals.view-submission />
    <livewire:admin.internal.modals.assign-reviewer-progress />
</div>
