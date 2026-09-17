<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Proposal;
use App\Models\AdminNotes;
use App\Models\User;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    // ─────── List ───────
    public string $search = '';
    public string $statusFilter = 'all';

    // ─────── Manage modal ───────
    public bool $showManageModal = false;
    public ?int $managingId = null;
    public string $managingTitle = '';
    public string $managingOwner = '';
    public string $managingScheme = '';

    // ─────── Manage form ───────
    public string $status_proposal = 'draft';
    public ?int $reviewer_id = null;

    // Revisi
    public bool $showReviseModal = false;
    public ?int $revisingId = null;
    public string $revisingTitle = '';
    public string $comment = '';
    public string $recommendation = '';

    // ═══════════════ Validation ═══════════════
    protected function rules(): array
    {
        return [
            'status_proposal' => ['required', 'in:draft,admin_revision,submitted,under_review,reviewer_revision,accepted,rejected'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ═══════════════ Manage modal ═══════════════]
    public function manage(int $id): void
    {
        $proposal = Proposal::query()
            ->with(['researchScheme', 'author', 'reviewer'])
            ->where('is_research', true)
            ->findOrFail($id);

        $this->managingId = $proposal->id;
        $this->managingTitle = $proposal->title;
        $this->managingOwner = $proposal->author?->full_name ?? '—';
        $this->managingScheme = $proposal->researchScheme?->scheme_name ?? '—';
        $this->status_proposal = $proposal->status_proposal;
        $this->reviewer_id = $proposal->reviewer_id;

        $this->resetErrorBag();
        $this->resetValidation();
        $this->showManageModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if (!$this->managingId) {
            return;
        }

        $proposal = Proposal::where('is_research', true)->findOrFail($this->managingId);

        $update = [
            'status_proposal' => $this->status_proposal,
            'reviewer_id' => $this->reviewer_id,
        ];

        // Business rule: reviewer hanya bermakna saat under_review atau setelahnya.
        // Kalau status draft / submitted / admin_revision, bersihkan reviewer.
        if (in_array($this->status_proposal, ['draft', 'admin_revision', 'submitted'])) {
            $update['reviewer_id'] = null;
        }

        $proposal->update($update);

        Flux::toast(text: 'Proposal updated successfully.', variant: 'success');

        $this->showManageModal = false;
        $this->reset(['managingId', 'managingTitle', 'managingOwner', 'managingScheme', 'status_proposal', 'reviewer_id']);
    }

    // ═══════════════ Quick actions ═══════════════
    public function quickSetStatus(int $id, string $status): void
    {
        if (!in_array($status, ['admin_revision', 'submitted', 'under_review', 'accepted', 'rejected'])) {
            return;
        }

        $proposal = Proposal::where('is_research', true)->findOrFail($id);

        // Kalau status di-set ke accepted / rejected / submitted, hapus reviewer
        // Kecuali under_review, biarkan reviewer tetap
        $reviewer = $proposal->reviewer_id;
        if (in_array($status, ['submitted', 'admin_revision'])) {
            $reviewer = null;
        }

        $proposal->update([
            'status_proposal' => $status,
            'reviewer_id' => $reviewer,
        ]);

        $meta = $this->statusMeta($status);
        Flux::toast(text: "Status changed to {$meta['label']}.", variant: 'success');
    }

    // ═══════════════ View details ═══════════════
    public function viewDetails(int $id): void
    {
        $proposal = Proposal::query()
            ->with([
                'researchScheme',
                'period',
                'reviewer',
                'author',
                'budgetProposal',
                'adminNotes' => fn($q) => $q->latest(),
            ])
            ->where('is_research', true)
            ->findOrFail($id);

        $meta = $this->statusMeta($proposal->status_proposal);

        $budgetItems = $proposal->budgetProposal
            ->map(
                fn($i) => [
                    'item_name' => $i->item_name,
                    'amount' => (int) $i->amount,
                ],
            )
            ->values()
            ->all();

        $budgetTotal = (int) collect($budgetItems)->sum('amount');
        $budgetLimit = (int) ($proposal->scheme->budget_limit ?? 0);

        // ⬇ Tambahan: map semua notes
        $notes = $proposal->adminNotes
            ->map(
                fn($note) => [
                    'comment' => $note->comment ?? '',
                    'recommendation' => $note->recommendation ?? '',
                    'createdAt' => $note->created_at?->format('d M Y, H:i') ?? '—',
                    'relative' => $note->created_at?->diffForHumans() ?? '',
                ],
            )
            ->values()
            ->all();

        $this->dispatch('view-details', title: $proposal->title, scheme: $proposal->researchScheme?->scheme_name ?? '—', period: $proposal->period?->periode ?? '—', status: $meta['label'], statusClass: $meta['class'], is_research: (bool) $proposal->is_research, keywords: array_filter(array_map('trim', explode(',', $proposal->keywords ?? ''))), summary: $proposal->summary ?? '', reviewer: $proposal->reviewer?->full_name, owner: $proposal->author?->full_name ?? '—', budgetItems: $budgetItems, budgetLimit: $budgetLimit, budgetTotal: $budgetTotal, budgetRemaining: $budgetLimit - $budgetTotal, budgetPercent: $budgetLimit > 0 ? round(($budgetTotal / $budgetLimit) * 100, 1) : 0, createdAt: $proposal->created_at?->format('d M Y, H:i') ?? '—', updatedAt: $proposal->updated_at?->format('d M Y, H:i') ?? '—', adminNotes: $notes);
    }

    // ═══════════════ Delete ═══════════════
    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::where('is_research', true)->findOrFail($id);

        $this->dispatch('confirm-delete', subject: $proposal->title, action: 'deleteProposal', payload: ['id' => $proposal->id], title: 'Delete Proposal?', note: 'This action cannot be undone. All associated budget items will also be deleted.');
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        if ($action !== 'deleteProposal') {
            return;
        }
        if (empty($payload['id'])) {
            return;
        }

        Proposal::where('is_research', true)->findOrFail($payload['id'])->delete();

        Flux::toast(text: 'Proposal deleted successfully.', variant: 'success');
    }

    // ═══════════════ Helpers ═══════════════
    public function statusMeta(string $status): array
    {
        return match ($status) {
            'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300'],
            'admin_revision' => ['label' => 'Admin Revision', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            'submitted' => ['label' => 'Submitted', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
            'under_review' => ['label' => 'Under Review', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300'],
            'reviewer_revision' => ['label' => 'Reviewer Revision', 'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'],
            'accepted' => ['label' => 'Accepted', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
            default => ['label' => ucfirst($status), 'class' => 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300'],
        };
    }

    public function with(): array
    {
        $query = Proposal::query()
            ->with(['researchScheme', 'period', 'reviewer', 'author'])
            ->where('is_research', true) // ← Research only
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('keywords', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%")
                        ->orWhereHas('author', function ($q) {
                            $q->where('full_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status_proposal', $this->statusFilter))
            ->latest();

        // Reviewer list — adjust relation name if needed
        $reviewers = User::query()->whereHas('role', fn($q) => $q->where('role_code', 'REVIEWER'))->orderBy('full_name')->get();

        return [
            'proposals' => $query->paginate(10),
            'reviewers' => $reviewers,

            'statusCounts' => Proposal::where('is_research', true)->selectRaw('status_proposal, count(*) as total')->groupBy('status_proposal')->pluck('total', 'status_proposal'),

            'totalCount' => Proposal::where('is_research', true)->count(),
        ];
    }

    // ═══════════════ Quick actions ═══════════════
    public function accept(int $id): void
    {
        $proposal = Proposal::where('is_research', true)->findOrFail($id);

        $proposal->update([
            'status_proposal' => 'accepted',
        ]);

        Flux::toast(text: 'Proposal accepted.', variant: 'success');
    }

    public function reject(int $id): void
    {
        $proposal = Proposal::where('is_research', true)->findOrFail($id);

        $proposal->update([
            'status_proposal' => 'rejected',
            'reviewer_id' => null,
        ]);

        Flux::toast(text: 'Proposal rejected.', variant: 'success');
    }

    // ═══════════════ Revise modal ═══════════════
    public function revise(int $id): void
    {
        $proposal = Proposal::where('is_research', true)->findOrFail($id);

        $this->revisingId = $proposal->id;
        $this->revisingTitle = $proposal->title;
        $this->comment = '';
        $this->recommendation = '';

        $this->resetErrorBag();
        $this->resetValidation();
        $this->showReviseModal = true;
    }

    public function saveRevision(): void
    {
        $this->validate(
            [
                'comment' => ['required', 'string', 'min:5'],
                'recommendation' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'comment.required' => 'Please explain what needs to be revised.',
                'comment.min' => 'Comment must be at least 5 characters.',
            ],
        );

        if (!$this->revisingId) {
            return;
        }

        $proposal = Proposal::where('is_research', true)->findOrFail($this->revisingId);

        AdminNotes::create([
            'proposal_id' => $proposal->id,
            'comment' => $this->comment,
            'recommendation' => $this->recommendation ?: null,
        ]);

        $proposal->update([
            'status_proposal' => 'admin_revision',
            'reviewer_id' => null,
        ]);

        Flux::toast(text: 'Revision request sent to the author.', variant: 'success');

        $this->showReviseModal = false;
        $this->reset(['revisingId', 'revisingTitle', 'comment', 'recommendation']);
    }

    public function cancelRevision(): void
    {
        $this->showReviseModal = false;
        $this->reset(['revisingId', 'revisingTitle', 'comment', 'recommendation']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    {{-- ══════════ Header ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <x-dashboard-header icon="beaker" title="Manage Research" leading="Review and manage all research proposals." />
    </div>

    {{-- ══════════ Status Filter Tabs ══════════ --}}
    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
               border border-white/80 dark:border-zinc-800 shadow-sm p-3">
        <div
            class="flex items-center gap-1 overflow-x-auto
                    [-ms-overflow-style:none] [scrollbar-width:none]
                    [&::-webkit-scrollbar]:hidden">

            @php
                $tabs = [
                    'all' => ['label' => 'All', 'count' => $totalCount],
                    'draft' => ['label' => 'Draft', 'count' => $statusCounts['draft'] ?? 0],
                    'admin_revision' => ['label' => 'Admin Revision', 'count' => $statusCounts['admin_revision'] ?? 0],
                    'submitted' => ['label' => 'Submitted', 'count' => $statusCounts['submitted'] ?? 0],
                    'under_review' => ['label' => 'Under Review', 'count' => $statusCounts['under_review'] ?? 0],
                    'reviewer_revision' => [
                        'label' => 'Reviewer Revision',
                        'count' => $statusCounts['reviewer_revision'] ?? 0,
                    ],
                    'accepted' => ['label' => 'Accepted', 'count' => $statusCounts['accepted'] ?? 0],
                    'rejected' => ['label' => 'Rejected', 'count' => $statusCounts['rejected'] ?? 0],
                ];
            @endphp

            @foreach ($tabs as $key => $tab)
                <button type="button" wire:click="$set('statusFilter', '{{ $key }}')"
                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                           text-[11px] font-medium whitespace-nowrap
                           transition-colors duration-150
                           {{ $statusFilter === $key
                               ? 'bg-emerald-700 text-white shadow-sm dark:bg-emerald-600'
                               : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' }}">
                    {{ $tab['label'] }}
                    <span
                        class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold
                               {{ $statusFilter === $key
                                   ? 'bg-white/20 text-white'
                                   : 'bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                        {{ $tab['count'] }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══════════ Table Card ══════════ --}}
    <div
        class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-2xl
               border border-white/80 dark:border-zinc-800 shadow-sm overflow-hidden">

        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                   gap-3 p-4 border-b border-slate-100 dark:border-zinc-800">

            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400 min-w-0">
                @if ($proposals->total() > 0)
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                               bg-emerald-50 text-emerald-700 font-semibold
                               dark:bg-emerald-900/30 dark:text-emerald-300">
                        <flux:icon.list-bullet class="size-3" />
                        {{ $proposals->total() }} {{ Str::plural('proposal', $proposals->total()) }}
                    </span>
                    <span class="text-slate-400 dark:text-zinc-600 hidden sm:inline">·</span>
                    <span class="truncate hidden sm:inline">
                        Showing
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $proposals->firstItem() }}–{{ $proposals->lastItem() }}
                        </span>
                        of
                        <span class="font-semibold text-slate-700 dark:text-zinc-200">
                            {{ $proposals->total() }}
                        </span>
                    </span>
                @else
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md
                               bg-slate-100 text-slate-500 font-semibold
                               dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon.list-bullet class="size-3" />
                        No proposals
                    </span>
                @endif
            </div>

            <x-input-search name="search" id="search-admin-research" wire:model.live.debounce.300ms="search"
                placeholder="Search title, owner, keywords..." max-width="max-w-sm" class="!flex w-full sm:w-auto" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-xs">
                <thead
                    class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px]
                           uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Title & Scheme</th>
                        <th class="px-4 py-3 font-semibold">Author</th>
                        <th class="px-4 py-3 font-semibold">Period</th>
                        <th class="px-4 py-3 font-semibold">Reviewer</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse ($proposals as $proposal)
                        <tr wire:key="prop-{{ $proposal->id }}"
                            class="hover:bg-emerald-50/30 dark:hover:bg-zinc-800/50 transition-colors">

                            {{-- Title + Scheme --}}
                            <td class="px-4 py-3 max-w-md">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="font-medium text-slate-900 dark:text-zinc-100 truncate"
                                        title="{{ $proposal->title }}">
                                        {{ $proposal->title }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                        {{ $proposal->researchScheme?->scheme_name ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Owner --}}
                            <td class="px-4 py-3">
                                @if ($proposal->author)
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full
                                                   bg-emerald-100 text-emerald-700
                                                   flex items-center justify-center
                                                   text-[10px] font-bold shrink-0
                                                   dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ strtoupper(substr($proposal->author->full_name, 0, 1)) }}
                                        </div>
                                        <span
                                            class="truncate max-w-[140px]
                                                     text-slate-700 dark:text-zinc-300">
                                            {{ $proposal->author->full_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 italic">—</span>
                                @endif
                            </td>

                            {{-- Period --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300 whitespace-nowrap">
                                {{ $proposal->period?->periode ?? '—' }}
                            </td>

                            {{-- Reviewer --}}
                            <td class="px-4 py-3">
                                @if ($proposal->reviewer)
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full
                                                   bg-violet-100 text-violet-700
                                                   flex items-center justify-center
                                                   text-[10px] font-bold shrink-0
                                                   dark:bg-violet-900/40 dark:text-violet-300">
                                            {{ strtoupper(substr($proposal->reviewer->full_name, 0, 1)) }}
                                        </div>
                                        <span
                                            class="truncate max-w-[120px]
                                                     text-slate-700 dark:text-zinc-300">
                                            {{ $proposal->reviewer->full_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 italic">
                                        Not assigned
                                    </span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                @php $meta = $this->statusMeta($proposal->status_proposal); @endphp
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                           text-[11px] font-semibold whitespace-nowrap {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        title="Actions"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-700/60" />
                                    <flux:menu>
                                        {{-- View & Manage --}}
                                        <flux:menu.item icon="eye" wire:click="viewDetails({{ $proposal->id }})">
                                            View Details
                                        </flux:menu.item>

                                        <flux:menu.item icon="adjustments-horizontal"
                                            wire:click="manage({{ $proposal->id }})">
                                            Manage Status
                                        </flux:menu.item>

                                            <flux:menu.separator />

                                            {{-- Accept --}}
                                            <flux:menu.item icon="check-circle"
                                                wire:click="accept({{ $proposal->id }})"
                                                wire:confirm="Accept this proposal? This will notify the author."
                                                class="text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50! dark:hover:bg-emerald-900/30!">
                                                Accept
                                            </flux:menu.item>

                                            {{-- Revise --}}
                                            <flux:menu.item icon="pencil-square"
                                                wire:click="revise({{ $proposal->id }})"
                                                class="text-amber-600 dark:text-amber-400 hover:bg-amber-50! dark:hover:bg-amber-900/30!">
                                                Request Revision
                                            </flux:menu.item>

                                            {{-- Reject --}}
                                            <flux:menu.item icon="x-circle" wire:click="reject({{ $proposal->id }})"
                                                wire:confirm="Reject this proposal? This action can be changed later."
                                                class="text-rose-600 dark:text-rose-400 hover:bg-rose-50! dark:hover:bg-rose-900/30!">
                                                Reject
                                            </flux:menu.item>

                                        {{-- Delete --}}
                                        <flux:menu.separator />

                                        <flux:menu.item variant="danger" icon="trash"
                                            wire:click="confirmDelete({{ $proposal->id }})">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.beaker class="size-10 text-slate-300 dark:text-zinc-700" />
                                    <div class="flex flex-col gap-1">
                                        <span class="font-medium text-slate-700 dark:text-zinc-300">
                                            No proposals yet
                                        </span>
                                        <span class="text-xs">
                                            @if ($search || $statusFilter !== 'all')
                                                No results match your current filters.
                                            @else
                                                There are no research proposals in the system.
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($proposals->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-zinc-800">
                {{ $proposals->links() }}
            </div>
        @endif
    </div>

    {{-- ══════════ Manage Modal ══════════ --}}
    <div x-data="{ show: @entangle('showManageModal') }" x-show="show" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
               p-0 sm:p-4 bg-inverse-surface/40 backdrop-blur-sm">

        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.away="show = false"
            class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
                   w-full sm:max-w-lg
                   rounded-t-2xl sm:rounded-xl
                   max-h-[92vh] sm:max-h-[90vh] flex flex-col
                   border border-outline-variant/50 dark:border-zinc-700">

            {{-- Header --}}
            <div
                class="shrink-0
                        bg-gradient-to-r from-primary to-primary-container
                        px-5 sm:px-6 py-4
                        rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="font-heading text-base sm:text-lg font-semibold text-on-primary truncate">
                            Manage Proposal
                        </h3>
                        <p class="text-[11px] text-on-primary/80 mt-0.5 truncate">
                            {{ $managingTitle }}
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showManageModal', false)"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-on-primary/80 hover:text-on-primary hover:bg-on-primary/10
                               transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">

                {{-- Proposal summary --}}
                <div class="grid grid-cols-2 gap-3">
                    <div
                        class="p-3 rounded-lg
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <div
                            class="text-[10px] uppercase tracking-wider font-semibold
                                    text-outline dark:text-zinc-500">
                            Owner
                        </div>
                        <p class="mt-1 text-sm font-medium truncate
                                  text-on-surface dark:text-zinc-100"
                            title="{{ $managingOwner }}">
                            {{ $managingOwner }}
                        </p>
                    </div>

                    <div
                        class="p-3 rounded-lg
                                bg-surface-container-low dark:bg-zinc-800/40
                                border border-outline-variant/60 dark:border-zinc-700">
                        <div
                            class="text-[10px] uppercase tracking-wider font-semibold
                                    text-outline dark:text-zinc-500">
                            Scheme
                        </div>
                        <p class="mt-1 text-sm font-medium truncate
                                  text-on-surface dark:text-zinc-100"
                            title="{{ $managingScheme }}">
                            {{ $managingScheme }}
                        </p>
                    </div>
                </div>

                {{-- Status select --}}
                <div>
                    <label for="status_proposal"
                        class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                        Status <span class="text-error">*</span>
                    </label>
                    <select id="status_proposal" wire:model.live="status_proposal"
                        class="mt-1 block w-full rounded-md shadow-sm
                               border-outline-variant dark:border-zinc-600
                               bg-surface-container-lowest dark:bg-zinc-800
                               text-on-surface dark:text-zinc-100
                               focus:border-primary focus:ring-primary
                               sm:text-sm py-2 px-3">
                        <option value="draft">Draft</option>
                        <option value="admin_revision">Admin Revision</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="reviewer_revision">Reviewer Revision</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    @error('status_proposal')
                        <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Reviewer select — hanya relevan saat under_review atau setelahnya --}}
                <div x-data="{ relevant: @entangle('status_proposal').live }">
                    <div x-show="['under_review', 'reviewer_revision', 'accepted', 'rejected'].includes(relevant)"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <label for="reviewer_id"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Reviewer
                        </label>
                        <select id="reviewer_id" wire:model="reviewer_id"
                            class="mt-1 block w-full rounded-md shadow-sm
                                   border-outline-variant dark:border-zinc-600
                                   bg-surface-container-lowest dark:bg-zinc-800
                                   text-on-surface dark:text-zinc-100
                                   focus:border-primary focus:ring-primary
                                   sm:text-sm py-2 px-3">
                            <option value="">— Not assigned —</option>
                            @foreach ($reviewers as $reviewer)
                                <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
                            @endforeach
                        </select>
                        @error('reviewer_id')
                            <span class="text-error text-xs mt-1 block">{{ $message }}</span>
                        @enderror

                        @if ($reviewers->isEmpty())
                            <p class="text-[11px] text-outline dark:text-zinc-500 mt-1">
                                No reviewers available. Please add users with the Reviewer role first.
                            </p>
                        @endif
                    </div>

                    <div x-show="!['under_review', 'reviewer_revision', 'accepted', 'rejected'].includes(relevant)"
                        x-cloak
                        class="flex items-start gap-2 p-3 rounded-lg
                               bg-surface-container-low dark:bg-zinc-800/40
                               border border-outline-variant/60 dark:border-zinc-700">
                        <flux:icon.information-circle
                            class="size-3.5 text-outline dark:text-zinc-500 shrink-0 mt-0.5" />
                        <p class="text-[11px] text-outline dark:text-zinc-500 leading-relaxed">
                            Reviewer assignment is only relevant when status is
                            <strong class="text-on-surface-variant dark:text-zinc-300">Under Review</strong>
                            or later. It will be cleared otherwise.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div
                class="shrink-0 flex justify-end gap-2
                        px-5 sm:px-6 py-4
                        border-t border-outline-variant/40 dark:border-zinc-700
                        bg-surface-container-lowest dark:bg-zinc-900
                        rounded-b-2xl sm:rounded-b-xl">
                <button type="button" wire:click="$set('showManageModal', false)"
                    class="px-4 py-2 text-sm font-medium rounded-md
                           text-on-surface-variant dark:text-zinc-300
                           bg-surface-container-lowest dark:bg-zinc-800
                           border border-outline-variant dark:border-zinc-600
                           hover:bg-surface-container-low dark:hover:bg-zinc-700
                           focus:outline-none focus:ring-2 focus:ring-primary
                           transition-colors duration-150">
                    Cancel
                </button>

                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium
                           text-on-primary rounded-md
                           bg-gradient-to-r from-primary to-primary-container
                           hover:from-primary-container hover:to-primary
                           focus:outline-none focus:ring-2 focus:ring-primary
                           transition-all duration-200
                           disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">Save Changes</span>
                    <span wire:loading.flex wire:target="save" class="items-center gap-1.5">
                        <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════ Request Revision Modal ══════════ --}}
    <div x-data="{ show: @entangle('showReviseModal') }" x-show="show" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" x-on:keydown.escape.window="$wire.cancelRevision()" x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
           p-0 sm:p-4 bg-inverse-surface/40 backdrop-blur-sm">

        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="bg-surface-container-lowest dark:bg-zinc-900 shadow-lg
               w-full sm:max-w-lg
               rounded-t-2xl sm:rounded-xl
               max-h-[92vh] sm:max-h-[90vh] flex flex-col
               border border-outline-variant/50 dark:border-zinc-700">

            {{-- Header --}}
            <div
                class="shrink-0
                    bg-gradient-to-r from-amber-500 to-amber-400
                    px-5 sm:px-6 py-4
                    rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-7 h-7 rounded-lg
                                    bg-white/20 flex items-center justify-center">
                                <flux:icon.pencil-square class="size-4 text-white" />
                            </div>
                            <h3 class="font-heading text-base sm:text-lg font-semibold text-white">
                                Request Revision
                            </h3>
                        </div>
                        <p class="text-[11px] text-white/80 mt-1 truncate" title="{{ $revisingTitle }}">
                            {{ $revisingTitle }}
                        </p>
                    </div>

                    <button type="button" wire:click="cancelRevision"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                           text-white/80 hover:text-white hover:bg-white/10
                           transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <form wire:submit.prevent="saveRevision" class="flex-1 flex flex-col overflow-hidden">

                <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">

                    <div
                        class="flex items-start gap-2 p-3 rounded-lg
                           bg-amber-50 dark:bg-amber-900/20
                           border border-amber-200/80 dark:border-amber-800/80">
                        <flux:icon.information-circle
                            class="size-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                            This note will be sent to the author. The proposal status will change to
                            <strong>Admin Revision</strong> and the reviewer assignment will be cleared.
                        </p>
                    </div>

                    {{-- Comment --}}
                    <div>
                        <label for="comment"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Comment <span class="text-error">*</span>
                        </label>
                        <textarea id="comment" wire:model="comment" rows="4"
                            placeholder="Explain what needs to be revised (e.g. incomplete methodology, unclear objectives, insufficient budget breakdown)..."
                            class="mt-1 block w-full rounded-md shadow-sm resize-none
                               border-outline-variant dark:border-zinc-600
                               bg-surface-container-lowest dark:bg-zinc-800
                               text-on-surface dark:text-zinc-100
                               placeholder:text-outline dark:placeholder-zinc-500
                               focus:border-amber-500 focus:ring-amber-500
                               sm:text-sm py-2 px-3"></textarea>
                        <div class="flex items-center justify-between mt-1">
                            @error('comment')
                                <span class="text-error text-xs">{{ $message }}</span>
                            @else
                                <span class="text-[11px] text-outline dark:text-zinc-500">
                                    Required. Minimum 5 characters.
                                </span>
                            @enderror
                            <span class="text-[11px] text-outline dark:text-zinc-500 font-mono">
                                {{ strlen($comment) }}/1000
                            </span>
                        </div>
                    </div>

                    {{-- Recommendation --}}
                    <div>
                        <label for="recommendation"
                            class="block text-sm font-medium text-on-surface-variant dark:text-zinc-300">
                            Recommendation <span
                                class="text-outline dark:text-zinc-500 text-xs font-normal">(optional)</span>
                        </label>
                        <textarea id="recommendation" wire:model="recommendation" rows="3"
                            placeholder="Suggestions for improvement (references, sources, methodology, etc.)..."
                            class="mt-1 block w-full rounded-md shadow-sm resize-none
                               border-outline-variant dark:border-zinc-600
                               bg-surface-container-lowest dark:bg-zinc-800
                               text-on-surface dark:text-zinc-100
                               placeholder:text-outline dark:placeholder-zinc-500
                               focus:border-amber-500 focus:ring-amber-500
                               sm:text-sm py-2 px-3"></textarea>
                        <div class="flex items-center justify-between mt-1">
                            @error('recommendation')
                                <span class="text-error text-xs">{{ $message }}</span>
                            @else
                                <span class="text-[11px] text-outline dark:text-zinc-500">
                                    Helpful hints for the author.
                                </span>
                            @enderror
                            <span class="text-[11px] text-outline dark:text-zinc-500 font-mono">
                                {{ strlen($recommendation) }}/1000
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div
                    class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2
                        px-5 sm:px-6 py-4
                        border-t border-outline-variant/40 dark:border-zinc-700
                        bg-surface-container-lowest dark:bg-zinc-900
                        rounded-b-2xl sm:rounded-b-xl">

                    <button type="button" wire:click="cancelRevision"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md
                           text-on-surface-variant dark:text-zinc-300
                           bg-surface-container-lowest dark:bg-zinc-800
                           border border-outline-variant dark:border-zinc-600
                           hover:bg-surface-container-low dark:hover:bg-zinc-700
                           focus:outline-none focus:ring-2 focus:ring-primary
                           transition-colors duration-150">
                        Cancel
                    </button>

                    <button type="submit" wire:loading.attr="disabled" wire:target="saveRevision"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5
                           px-5 py-2 text-sm font-medium
                           text-white rounded-md
                           bg-gradient-to-r from-amber-500 to-amber-400
                           hover:from-amber-600 hover:to-amber-500
                           focus:outline-none focus:ring-2 focus:ring-amber-500
                           transition-all duration-200
                           disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="saveRevision" class="inline-flex items-center gap-1.5">
                            <flux:icon.paper-airplane class="size-3.5" />
                            Send Revision Request
                        </span>
                        <span wire:loading.flex wire:target="saveRevision" class="items-center gap-1.5">
                            <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Sending...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- ══════════ Shared Modals ══════════ --}}
    <x-view-details />
    <x-confirm-delete />
</div>
