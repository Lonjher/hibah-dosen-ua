<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Reviewers')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /* ============================================================
     |  DELETE FLOW
     ============================================================ */

    public function confirmDelete(int $id): void
    {
        $user = User::find($id);

        if (! $user) {
            Flux::toast('Reviewer not found.', variant: 'danger');
            return;
        }

        if ($user->reviewedProposals()->exists() || $user->reviewedProgressReports()->exists()) {
            Flux::toast('Cannot delete a reviewer who already has review assignments.', variant: 'danger');
            return;
        }

        $this->dispatch(
            'confirm-delete',
            title: 'Delete Reviewer?',
            message: 'You are about to delete:',
            subject: $user->full_name,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            action: 'deleteReviewer',
            payload: ['id' => $user->id],
        );
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action === 'deleteReviewer') {
            $this->deleteReviewer($payload['id'] ?? null);
        }
    }

    public function deleteReviewer(?int $id): void
    {
        if (! $id) return;

        $user = User::find($id);
        if (! $user) return;

        if ($user->reviewedProposals()->exists() || $user->reviewedProgressReports()->exists()) {
            Flux::toast('Cannot delete a reviewer who already has review assignments.', variant: 'danger');
            return;
        }

        try {
            $name = $user->full_name;
            $user->delete();
            Flux::toast("Reviewer '{$name}' deleted.", variant: 'success');
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            Flux::toast('Failed to delete reviewer.', variant: 'danger');
        }
    }

    /* ============================================================
     |  QUERY
     ============================================================ */

    public function with(): array
    {
        $reviewers = User::query()
            ->with('role')
            ->withCount(['reviewedProposals', 'reviewedProgressReports'])
            ->whereHas('role', fn ($q) => $q->where('role_code', 'REVIEWER'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('full_name', 'like', "%{$this->search}%")
                  ->orWhere('nidn', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(10);

        return ['reviewers' => $reviewers];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50
            dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950
            p-2.5 sm:p-3 lg:p-4">
    <div class="max-w-7xl mx-auto space-y-3">

        {{-- ══════════ HEADER ══════════ --}}
        <x-dashboard-header icon="user" title="Manage Reviewers"
            leading="Manage all reviewers who will evaluate proposals." />

        {{-- ══════════ MAIN CARD ══════════ --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl
                    shadow-sm shadow-slate-200/50 dark:shadow-zinc-950/50
                    border border-slate-200 dark:border-zinc-800 overflow-hidden">

            {{-- ─────── TOOLBAR ─────── --}}
            <div class="px-2.5 sm:px-3 py-2
                        border-b border-slate-200 dark:border-zinc-800
                        bg-slate-50/50 dark:bg-zinc-900/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">

                    {{-- Left: stat pill --}}
                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md
                                bg-emerald-50 dark:bg-emerald-900/20
                                border border-emerald-100 dark:border-emerald-900/40
                                w-fit">
                        <flux:icon.user class="size-3 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[10.5px] font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ $reviewers->total() }}
                        </span>
                        <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70">
                            reviewers
                        </span>
                    </div>

                    {{-- Right: search + new --}}
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <div class="flex-1 sm:flex-none sm:w-56">
                            <x-input-search name="q" wire:model.live="search" id="search-reviewer"
                                placeholder="Search reviewers..."
                                class="w-full !text-[10.5px]" />
                        </div>
                        <flux:button icon="plus" variant="primary" size="xs"
                            x-data x-on:click="$dispatch('open-add-reviewer')"
                            class="shrink-0 !text-[10.5px]">
                            New
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- ─────── TABLE ─────── --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-zinc-900/50
                                   border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Reviewer
                            </th>
                            <th class="hidden md:table-cell px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                NIDN
                            </th>
                            <th class="px-3 py-1.5 text-left text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Assignments
                            </th>
                            <th class="px-3 py-1.5 text-right text-[9.5px] font-semibold uppercase tracking-wider
                                       text-slate-500 dark:text-zinc-400">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                        @forelse ($reviewers as $reviewer)
                            <tr wire:key="reviewer-{{ $reviewer->id }}"
                                class="group transition-colors duration-150
                                       hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">

                                {{-- REVIEWER --}}
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-md shrink-0
                                                    bg-gradient-to-br from-emerald-100 to-teal-50
                                                    dark:from-emerald-900/30 dark:to-teal-900/20
                                                    flex items-center justify-center
                                                    text-[10px] font-bold
                                                    text-emerald-700 dark:text-emerald-300
                                                    ring-1 ring-white/40 dark:ring-zinc-800/40
                                                    group-hover:scale-105 transition-transform">
                                            {{ $reviewer->initials() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[11.5px] font-semibold leading-tight truncate
                                                      text-slate-900 dark:text-white">
                                                {{ $reviewer->full_name }}
                                            </p>
                                            <p class="mt-0.5 text-[9.5px] truncate
                                                      text-slate-500 dark:text-zinc-500">
                                                {{ $reviewer->email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- NIDN --}}
                                <td class="hidden md:table-cell px-3 py-2">
                                    <span class="text-[10.5px] font-mono
                                                 text-slate-600 dark:text-zinc-400">
                                        {{ $reviewer->nidn ?: '—' }}
                                    </span>
                                </td>

                                {{-- ASSIGNMENTS --}}
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                     bg-slate-100 dark:bg-zinc-800
                                                     text-slate-600 dark:text-zinc-400
                                                     text-[9.5px] font-medium">
                                            <flux:icon.document-text class="size-2.5" />
                                            {{ $reviewer->reviewed_proposals_count }}
                                            <span class="text-slate-400 dark:text-zinc-500">proposal</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full
                                                     bg-slate-100 dark:bg-zinc-800
                                                     text-slate-600 dark:text-zinc-400
                                                     text-[9.5px] font-medium">
                                            <flux:icon.clipboard-document-check class="size-2.5" />
                                            {{ $reviewer->reviewed_progress_reports_count }}
                                            <span class="text-slate-400 dark:text-zinc-500">progress</span>
                                        </span>
                                    </div>
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
                                            <flux:menu.item icon="pencil-square" x-data
                                                x-on:click="$dispatch('open-edit-reviewer', { id: {{ $reviewer->id }} })">
                                                Edit Reviewer
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="confirmDelete({{ $reviewer->id }})">
                                                Delete Reviewer
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>

                        @empty
                            {{-- EMPTY STATE ── --}}
                            <tr>
                                <td colspan="4" class="px-4 py-10">
                                    <div class="flex flex-col items-center gap-2 text-center">
                                        <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-zinc-800
                                                    flex items-center justify-center">
                                            <flux:icon.user class="size-5 text-slate-400 dark:text-zinc-600" />
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-slate-900 dark:text-white">
                                                No reviewers yet
                                            </p>
                                            <p class="text-[10.5px] text-slate-500 dark:text-zinc-400 mt-0.5">
                                                Click "New" to create the first reviewer.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ─────── PAGINATION ─────── --}}
            @if ($reviewers->hasPages())
                <div class="px-3 py-2 border-t border-slate-200 dark:border-zinc-800
                            bg-slate-50/50 dark:bg-zinc-900/50">
                    {{ $reviewers->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ MODALS ══════════ --}}
    <x-confirm-delete />
    <livewire:admin.reviewers.add-reviewer />
    <livewire:admin.reviewers.edit-reviewer />
</div>
