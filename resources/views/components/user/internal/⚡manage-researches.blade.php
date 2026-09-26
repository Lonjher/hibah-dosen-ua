<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\Proposal;
use App\Models\BudgetProposal;
use App\Models\ResearchScheme;
use App\Models\Period;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    // ─────── List ───────
    public string $search = '';
    public string $statusFilter = 'all';

    // ─────── Modal ───────
    public bool $showModal = false;
    public int $step = 1;
    public bool $editMode = false;
    public ?int $editingId = null;
    public ?int $draftProposalId = null;

    // ─────── Step 1: Metadata ───────
    public ?int $research_scheme_id = null;
    public string $title = '';
    public string $summary = '';
    public string $keywords = '';
    public ?int $period_id = null;

    // ─────── Step 2: Budget ───────
    public string $item_name = '';
    public string $amount = '';

    // ═══════════════ Validation ═══════════════
    protected function rulesStep1(): array
    {
        return [
            'research_scheme_id' => ['required', 'exists:research_schemes,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'min:20'],
            'keywords' => ['required', 'string', 'max:255'],
            'period_id' => ['required', 'exists:periods,id'],
        ];
    }

    protected function rulesBudgetItem(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'research_scheme_id.required' => 'Please select a scheme.',
            'period_id.required' => 'Please select a period.',
            'title.required' => 'The title is required.',
            'summary.min' => 'Summary must be at least 20 characters.',
            'keywords.required' => 'Keywords are required.',
            'item_name.required' => 'Item name is required.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than zero.',
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

    // ═══════════════ Modal — open / close ═══════════════
    public function create(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->step = 1;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $proposal = Proposal::where('user_id', Auth::id())->findOrFail($id);

        if (!$this->canEdit($proposal->status_proposal)) {
            Flux::toast(text: 'This proposal can no longer be edited.', variant: 'danger');
            return;
        }

        $this->resetForm();
        $this->editMode = true;
        $this->editingId = $proposal->id;
        $this->draftProposalId = $proposal->id;
        $this->step = 1;

        $this->research_scheme_id = $proposal->research_scheme_id;
        $this->title = $proposal->title;
        $this->summary = $proposal->summary;
        $this->keywords = $proposal->keywords;
        $this->period_id = $proposal->period_id;

        $this->showModal = true;
    }

    public function viewDetails(int $id): void
    {
        $proposal = Proposal::query()
            ->with(['researchScheme', 'period', 'reviewer', 'author', 'budgetProposal', 'adminNotes' => fn($q) => $q->latest(), 'reviewerNotes' => fn($q) => $q->latest()])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $meta = $this->statusMeta($proposal->status_proposal);

        // Budget items
        $budgetItems = $proposal->budgetProposal
            ->map(
                fn($i) => [
                    'item_name' => $i->item_name,
                    'amount' => (int) $i->amount,
                ],
            )
            ->values()
            ->all();

        // Admin notes
        $adminNotes = $proposal->adminNotes
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

        // Reviewer notes
        $reviewerNotes = $proposal->reviewerNotes
            ->map(
                fn($note) => [
                    'comment' => $note->comment ?? '',
                    'recommendation' => $note->recommendation ?? '',
                    'isApproved' => (bool) $note->is_approved,
                    'createdAt' => $note->created_at?->format('d M Y, H:i') ?? '—',
                    'relative' => $note->created_at?->diffForHumans() ?? '',
                ],
            )
            ->values()
            ->all();

        $budgetTotal = (int) collect($budgetItems)->sum('amount');
        $budgetLimit = (int) ($proposal->researchScheme->budget_limit ?? 0);

        $this->dispatch('view-details', title: $proposal->title, scheme: $proposal->researchScheme?->scheme_name ?? '—', period: $proposal->period?->periode ?? '—', status: $meta['label'], statusClass: $meta['class'], is_research: (bool) $proposal->is_research, keywords: array_filter(array_map('trim', explode(',', $proposal->keywords ?? ''))), summary: $proposal->summary ?? '', reviewer: $proposal->reviewer?->full_name, owner: $proposal->author?->full_name ?? Auth::user()->full_name, budgetItems: $budgetItems, budgetLimit: $budgetLimit, budgetTotal: $budgetTotal, budgetRemaining: $budgetLimit - $budgetTotal, budgetPercent: $budgetLimit > 0 ? round(($budgetTotal / $budgetLimit) * 100, 1) : 0, createdAt: $proposal->created_at?->format('d M Y, H:i') ?? '—', updatedAt: $proposal->updated_at?->format('d M Y, H:i') ?? '—', adminNotes: $adminNotes, reviewerNotes: $reviewerNotes);
    }

    // ═══════════════ Step navigation ═══════════════
    public function nextStep(): void
    {
        $this->validate($this->rulesStep1());

        if ($this->draftProposalId) {
            Proposal::where('user_id', Auth::id())
                ->findOrFail($this->draftProposalId)
                ->update([
                    'research_scheme_id' => $this->research_scheme_id,
                    'title' => $this->title,
                    'summary' => $this->summary,
                    'keywords' => $this->keywords,
                    'period_id' => $this->period_id,
                ]);
        } else {
            $proposal = Proposal::create([
                'user_id' => Auth::id(),
                'research_scheme_id' => $this->research_scheme_id,
                'title' => $this->title,
                'summary' => $this->summary,
                'keywords' => $this->keywords,
                'period_id' => $this->period_id,
                'is_research' => true,
                'status_proposal' => 'draft',
                'reviewer_id' => null,
            ]);

            $this->draftProposalId = $proposal->id;
        }

        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    // ═══════════════ Budget Items ═══════════════
    public function addBudgetItem(): void
    {
        if (!$this->draftProposalId) {
            return;
        }

        $this->validate($this->rulesBudgetItem());

        $amount = (int) preg_replace('/\D/', '', $this->amount);

        $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
        $limit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);

        $current = (int) BudgetProposal::where('proposal_id', $this->draftProposalId)->sum('amount');

        if ($current + $amount > $limit) {
            $remaining = max($limit - $current, 0);
            $this->addError('amount', 'Exceeds budget limit. Maximum you can add: Rp ' . number_format($remaining, 0, ',', '.'));
            return;
        }

        BudgetProposal::create([
            'proposal_id' => $this->draftProposalId,
            'item_name' => $this->item_name,
            'amount' => $amount,
        ]);

        $this->reset(['item_name', 'amount']);
        $this->resetErrorBag();
        $this->resetValidation();

        Flux::toast(text: 'Budget item added.', variant: 'success');
    }

    public function removeBudgetItem(int $id): void
    {
        if (!$this->draftProposalId) {
            return;
        }

        BudgetProposal::where('proposal_id', $this->draftProposalId)->where('id', $id)->delete();

        Flux::toast(text: 'Budget item removed.', variant: 'success');
    }

    public function finish(): void
    {
        if (!$this->draftProposalId) {
            return;
        }

        $total = (int) BudgetProposal::where('proposal_id', $this->draftProposalId)->sum('amount');

        if ($total <= 0) {
            Flux::toast(text: 'Please add at least one budget item before finishing.', variant: 'danger');
            return;
        }

        $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
        $limit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);

        if ($total > $limit) {
            Flux::toast(text: 'Total budget exceeds the limit. Please review your items.', variant: 'danger');
            return;
        }

        Flux::toast(text: 'Proposal saved successfully.', variant: 'success');

        $this->showModal = false;
        $this->resetForm();
    }

    // ═══════════════ Delete proposal ═══════════════
    public function confirmDelete(int $id): void
    {
        $proposal = Proposal::where('user_id', Auth::id())->findOrFail($id);

        if (!$this->canDelete($proposal->status_proposal)) {
            Flux::toast(text: 'This proposal can no longer be deleted.', variant: 'danger');
            return;
        }

        $this->dispatch('confirm-delete', subject: $proposal->title, action: 'deleteProposal', payload: ['id' => $proposal->id], title: 'Delete Proposal?', note: 'This action cannot be undone. All associated budget items will also be deleted.');
    }

    #[On('delete-confirmed')]
    public function handleDeleteConfirmed(string $action, array $payload = []): void
    {
        match ($action) {
            'deleteProposal' => $this->deleteProposal($payload['id'] ?? null),
            default => null,
        };
    }

    protected function deleteProposal(?int $id): void
    {
        if (!$id) {
            return;
        }

        Proposal::where('user_id', Auth::id())->findOrFail($id)->delete();

        Flux::toast(text: 'Proposal deleted successfully.', variant: 'success');
    }

    // ═══════════════ Helpers ═══════════════
    public function resetForm(): void
    {
        $this->reset(['step', 'editingId', 'draftProposalId', 'research_scheme_id', 'title', 'summary', 'keywords', 'period_id', 'item_name', 'amount']);

        $this->step = 1;
        $this->editMode = false;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function canEdit(string $status): bool
    {
        return in_array($status, ['draft', 'admin_revision', 'reviewer_revision']);
    }

    public function canDelete(string $status): bool
    {
        return in_array($status, ['draft', 'admin_revision']);
    }

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

    // ═══════════════ Render ═══════════════
    public function with(): array
    {
        // ── List query ──
        $query = Proposal::query()
            ->with(['researchScheme', 'period', 'reviewer', 'progressReport', 'finalReport'])
            ->where('user_id', Auth::id())
            ->where('is_research', true)
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('keywords', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status_proposal', $this->statusFilter))
            ->latest();

        // ── Budget context for modal ──
        $budgetItems = collect();
        $budgetTotal = 0;
        $budgetLimit = 0;

        if ($this->draftProposalId) {
            $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
            $budgetItems = BudgetProposal::where('proposal_id', $this->draftProposalId)->orderBy('id')->get();
            $budgetTotal = (int) $budgetItems->sum('amount');
            $budgetLimit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);
        } elseif ($this->research_scheme_id) {
            $budgetLimit = (int) (ResearchScheme::find($this->research_scheme_id)?->budget_limit ?? 0);
        }

        $budgetRemaining = $budgetLimit - $budgetTotal;
        $budgetPercent = $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0;

        return [
            'proposals' => $query->paginate(10),
            'schemes' => ResearchScheme::orderBy('scheme_name')->get(),
            'periods' => Period::orderByDesc('periode')->get(),

            'statusCounts' => Proposal::where('user_id', Auth::id())->where('is_research', true)->selectRaw('status_proposal, count(*) as total')->groupBy('status_proposal')->pluck('total', 'status_proposal'),

            'totalCount' => Proposal::where('user_id', Auth::id())->where('is_research', true)->count(),

            // Modal budget data
            'budgetItems' => $budgetItems,
            'budgetTotal' => $budgetTotal,
            'budgetLimit' => $budgetLimit,
            'budgetRemaining' => $budgetRemaining,
            'budgetPercent' => $budgetPercent,
        ];
    }
};
?>

<div class="p-4 sm:p-6 space-y-6">

    {{-- ══════════ Header ══════════ --}}
    <x-dashboard-header icon="beaker" title="Research Proposals" leading="Manage your research proposals." />

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

            <div class="flex gap-3">
                <x-input-search name="search" id="search-proposal" wire:model.live.debounce.300ms="search"
                    placeholder="Search title, keywords..." max-width="max-w-sm" class="w-full sm:w-md" />

                <flux:button icon="plus" wire:click="create" variant="primary" size="sm"
                    class="shrink-0 w-full sm:w-auto justify-center">
                    New Proposal
                </flux:button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-xs">
                <thead
                    class="bg-emerald-50/50 dark:bg-emerald-900/20 text-left text-[11px]
                              uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Title & Scheme</th>
                        <th class="px-4 py-3 font-semibold">Period</th>
                        <th class="px-4 py-3 font-semibold">Reviewer</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse ($proposals as $proposal)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-zinc-800/50 transition-colors">

                            <td class="px-4 py-3 max-w-md">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="font-medium text-slate-900 dark:text-zinc-100 truncate"
                                        title="{{ $proposal->title }}">
                                        {{ Str::limit($proposal->title, 40) }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                        {{ $proposal->researchScheme?->scheme_name ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300 whitespace-nowrap">
                                {{ $proposal->period?->periode ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-slate-600 dark:text-zinc-300">
                                @if ($proposal->reviewer)
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full bg-violet-100 text-violet-700
                                                    flex items-center justify-center text-[10px] font-bold
                                                    dark:bg-violet-900/40 dark:text-violet-300">
                                            {{ strtoupper(substr($proposal->reviewer->full_name, 0, 1)) }}
                                        </div>
                                        <span class="truncate max-w-[140px]">
                                            {{ $proposal->reviewer->full_name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-zinc-500 italic">
                                        Not assigned
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @php $meta = $this->statusMeta($proposal->status_proposal); @endphp
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                             text-[11px] font-semibold whitespace-nowrap {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal"
                                        title="Actions"
                                        class="rounded-lg text-slate-500 hover:bg-slate-100
                                               dark:text-zinc-400 dark:hover:bg-zinc-700/60" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="viewDetails({{ $proposal->id }})">
                                            View Details
                                        </flux:menu.item>

                                        @if ($proposal->status_proposal === 'accepted')
                                            <flux:menu.item icon="document-chart-bar"
                                                x-on:click="$dispatch('add-progress-report', { proposalId: {{ $proposal->id }} })"
                                                class="text-emerald-600 dark:text-emerald-400
                                                       hover:bg-emerald-50! dark:hover:bg-emerald-900/30!">
                                                Progress Reports
                                            </flux:menu.item>
                                        @endif

                                        @if ($proposal->status_proposal === 'accepted' && $proposal->progressReport?->isApproved())
                                            <flux:menu.item icon="document-check"
                                                x-on:click="$dispatch('add-final-report', { proposalId: {{ $proposal->id }} })"
                                                class="text-blue-600 dark:text-blue-400
                                                    hover:bg-blue-50! dark:hover:bg-blue-900/30!">
                                                Final Report
                                            </flux:menu.item>
                                        @endif

                                        @if ($proposal->canDownloadLoA())
                                            <flux:menu.item icon="printer"
                                                wire:click="viewDetails({{ $proposal->id }})">
                                                Get LoA
                                            </flux:menu.item>
                                        @endif

                                        @if ($this->canEdit($proposal->status_proposal))
                                            <flux:menu.item icon="pencil-square"
                                                wire:click="edit({{ $proposal->id }})">
                                                Edit
                                            </flux:menu.item>
                                        @endif

                                        @if ($this->canDelete($proposal->status_proposal))
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash"
                                                wire:click="confirmDelete({{ $proposal->id }})">
                                                Delete
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-slate-500 dark:text-slate-400">
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
                                                Click "New Proposal" to get started.
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

    {{-- ══════════ Modals ══════════ --}}
    <x-proposal-modal :show-modal="$showModal" :step="$step" :edit-mode="$editMode" :schemes="$schemes" :periods="$periods"
        :budget-items="$budgetItems" :budget-limit="$budgetLimit" :budget-total="$budgetTotal" :budget-remaining="$budgetRemaining" :budget-percent="$budgetPercent" />

    <x-confirm-delete />
    <x-view-details />

    {{-- Progress Report Modal — event-driven dari menu di atas --}}
    <livewire:user.internal.researches.add-progress-report wire:key="progress-report-modal" />
    <livewire:user.internal.researches.add-final-report wire:key="final-report-modal" />
</div>
