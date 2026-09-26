<?php

use App\Livewire\Forms\BudgetProposalForm;
use App\Livewire\Forms\ProposalForm;
use App\Models\BudgetProposal;
use App\Models\Period;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use Flux\Flux;
use Livewire\Component;

new class extends Component {
    public ProposalForm $form;
    public BudgetProposalForm $budgetForm;

    public bool $isResearch = true;
    public int $step = 1;
    public ?int $draftProposalId = null;

    public function mount(bool $isResearch = true): void
    {
        $this->isResearch = $isResearch;
        $this->form->is_research = $isResearch;
        $this->form->status = 'pending';
    }

    public function nextStep(): void
    {
        $this->form->validateStep1();

        if ($this->draftProposalId) {
            Proposal::where('user_id', auth()->id())
                ->findOrFail($this->draftProposalId)
                ->update([
                    'research_scheme_id' => $this->form->research_scheme_id,
                    'title'              => $this->form->title,
                    'summary'            => $this->form->summary,
                    'keywords'           => $this->form->keywords,
                    'period_id'          => $this->form->period_id,
                ]);
        } else {
            $proposal = Proposal::create([
                'user_id'            => auth()->id(),
                'research_scheme_id' => $this->form->research_scheme_id,
                'title'              => $this->form->title,
                'summary'            => $this->form->summary,
                'keywords'           => $this->form->keywords,
                'period_id'          => $this->form->period_id,
                'is_research'        => $this->isResearch,
                'status'             => 'pending',
                'reviewer_id'        => null,
                'file_path'          => '',
            ]);

            $this->draftProposalId = $proposal->id;
            $this->form->user_id = auth()->id();
        }

        $this->budgetForm->proposal_id = $this->draftProposalId;
        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    public function addBudgetItem(): void
    {
        if (! $this->draftProposalId) return;

        $this->budgetForm->proposal_id = $this->draftProposalId;
        $this->budgetForm->validate();

        $amount = (int) preg_replace('/\D/', '', (string) $this->budgetForm->amount);

        $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
        $limit    = (int) ($proposal?->researchScheme?->budget_limit ?? 0);
        $current  = (int) BudgetProposal::where('proposal_id', $this->draftProposalId)->sum('amount');

        if ($current + $amount > $limit) {
            $remaining = max($limit - $current, 0);
            $this->budgetForm->addError('amount', 'Melebihi batas anggaran. Maksimal: Rp '.number_format($remaining, 0, ',', '.'));
            return;
        }

        $this->budgetForm->amount = $amount;
        $this->budgetForm->create();

        $this->budgetForm->reset('item_name', 'amount');
        $this->budgetForm->proposal_id = $this->draftProposalId;

        $this->resetErrorBag();
        $this->resetValidation();

        Flux::toast('Item anggaran ditambahkan.', variant: 'success');
    }

    public function removeBudgetItem(int $id): void
    {
        if (! $this->draftProposalId) return;

        BudgetProposal::where('proposal_id', $this->draftProposalId)
            ->where('id', $id)
            ->delete();

        Flux::toast('Item anggaran dihapus.', variant: 'success');
    }

    public function finish(): void
    {
        if (! $this->draftProposalId) return;

        $total = (int) BudgetProposal::where('proposal_id', $this->draftProposalId)->sum('amount');

        if ($total <= 0) {
            Flux::toast('Tambahkan minimal 1 item anggaran.', variant: 'danger');
            return;
        }

        $proposal = Proposal::with('researchScheme')->find($this->draftProposalId);
        $limit    = (int) ($proposal?->researchScheme?->budget_limit ?? 0);

        if ($total > $limit) {
            Flux::toast('Total anggaran melebihi batas.', variant: 'danger');
            return;
        }

        Flux::toast('Proposal berhasil disimpan.', variant: 'success');
        $this->dispatch('proposal-added', message: 'Proposal berhasil ditambahkan.');
        $this->resetAll();
    }

    public function resetAll(): void
    {
        $this->form->reset();
        $this->budgetForm->reset();
        $this->reset(['step', 'draftProposalId']);
        $this->step = 1;
        $this->form->is_research = $this->isResearch;
        $this->form->status = 'pending';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function with(): array
    {
        $budgetItems = collect();
        $budgetTotal = 0;
        $budgetLimit = 0;

        if ($this->draftProposalId) {
            $proposal    = Proposal::with('researchScheme')->find($this->draftProposalId);
            $budgetItems = BudgetProposal::where('proposal_id', $this->draftProposalId)->orderBy('id')->get();
            $budgetTotal = (int) $budgetItems->sum('amount');
            $budgetLimit = (int) ($proposal?->researchScheme?->budget_limit ?? 0);
        } elseif ($this->form->research_scheme_id) {
            $budgetLimit = (int) (ResearchScheme::find($this->form->research_scheme_id)?->budget_limit ?? 0);
        }

        $theme = $this->isResearch
            ? ['icon' => 'document-plus', 'gradient' => 'from-emerald-600 to-emerald-500', 'label' => 'Penelitian', 'accent' => 'emerald']
            : ['icon' => 'heart',          'gradient' => 'from-rose-600 to-rose-500',       'label' => 'Pengabdian', 'accent' => 'rose'];

        return [
            'schemes'         => ResearchScheme::where('is_active', true)->orderBy('name')->get(),
            'periods'         => Period::orderByDesc('periode')->get(),
            'budgetItems'     => $budgetItems,
            'budgetTotal'     => $budgetTotal,
            'budgetLimit'     => $budgetLimit,
            'budgetRemaining' => $budgetLimit - $budgetTotal,
            'budgetPercent'   => $budgetLimit > 0 ? min(round(($budgetTotal / $budgetLimit) * 100, 1), 100) : 0,
            'theme'           => $theme,
        ];
    }
};
?>

<div
    x-data="{
        show: false,
        errorMessage: '',
        init() {
            window.addEventListener('open-add-proposal', () => {
                this.errorMessage = '';
                this.show = true;
            });
            window.addEventListener('proposal-error', (e) => { this.errorMessage = e.detail.message; });
            window.addEventListener('proposal-added', () => { this.show = false; });
        }
    }"
    x-show="show" x-transition.opacity x-cloak
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
    @click.self="show = false">

    <div class="flex max-h-[90vh] w-full sm:max-w-2xl flex-col overflow-hidden
                bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-xl shadow-2xl
                border border-slate-200 dark:border-zinc-700"
        @click.stop>

        <form class="flex min-h-0 flex-1 flex-col">

            {{-- HEADER --}}
            <div class="shrink-0 bg-gradient-to-r {{ $theme['gradient'] }}
                        px-5 sm:px-6 py-4 rounded-t-2xl sm:rounded-t-xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <flux:icon :name="$theme['icon']" class="size-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-[15px] font-semibold text-white leading-tight">
                                Add New {{ $theme['label'] }}
                            </h3>
                            <p class="text-[11px] text-white/75 mt-0.5">
                                Step {{ $step }} dari 2 — {{ $step === 1 ? 'Metadata' : 'Anggaran' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="show = false"
                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                               text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold
                                    {{ $step >= 1 ? 'bg-white text-' . $theme['accent'] . '-600' : 'bg-white/30 text-white/70' }}">1</div>
                        <span class="text-[10px] font-medium {{ $step >= 1 ? 'text-white' : 'text-white/60' }}">Metadata</span>
                    </div>
                    <div class="flex-1 h-px bg-white/30"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold
                                    {{ $step >= 2 ? 'bg-white text-' . $theme['accent'] . '-600' : 'bg-white/30 text-white/70' }}">2</div>
                        <span class="text-[10px] font-medium {{ $step >= 2 ? 'text-white' : 'text-white/60' }}">Anggaran</span>
                    </div>
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

                @if ($step === 1)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Judul <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="form.title" placeholder="Judul proposal"
                                class="block w-full rounded-md shadow-sm text-[12px]
                                       border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                       text-slate-900 dark:text-zinc-100
                                       focus:ring-emerald-500 py-2 px-3" />
                            @error('form.title')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Skema <span class="text-rose-500">*</span>
                                </label>
                                <select wire:model.live="form.research_scheme_id"
                                    class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:ring-emerald-500 py-2 px-3">
                                    <option value="">— Pilih Skema —</option>
                                    @foreach ($schemes as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                                    @endforeach
                                </select>
                                @error('form.research_scheme_id')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Periode <span class="text-rose-500">*</span>
                                </label>
                                <select wire:model="form.period_id"
                                    class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:ring-emerald-500 py-2 px-3">
                                    <option value="">— Pilih Periode —</option>
                                    @foreach ($periods as $p)
                                        <option value="{{ $p->id }}">{{ $p->periode }}</option>
                                    @endforeach
                                </select>
                                @error('form.period_id')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Kata Kunci <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="form.keywords" placeholder="kata kunci 1, kata kunci 2"
                                class="block w-full rounded-md shadow-sm text-[12px]
                                       border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                       text-slate-900 dark:text-zinc-100
                                       focus:ring-emerald-500 py-2 px-3" />
                            @error('form.keywords')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                Ringkasan <span class="text-rose-500">*</span>
                            </label>
                            <textarea wire:model="form.summary" rows="4" placeholder="Ringkasan proposal..."
                                class="block w-full rounded-md shadow-sm text-[12px]
                                       border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                       text-slate-900 dark:text-zinc-100
                                       focus:ring-emerald-500 py-2 px-3"></textarea>
                            @error('form.summary')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endif

                @if ($step === 2)
                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200 dark:border-zinc-700
                                    bg-slate-50 dark:bg-zinc-800/40 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-medium text-slate-700 dark:text-zinc-300">Total Anggaran</span>
                                <span class="text-[11px] font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($budgetTotal, 0, ',', '.') }}
                                    <span class="text-slate-400 font-normal">
                                        / Rp {{ number_format($budgetLimit, 0, ',', '.') }}
                                    </span>
                                </span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-200 dark:bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full bg-{{ $theme['accent'] }}-500 transition-all"
                                    style="width: {{ $budgetPercent }}%"></div>
                            </div>
                            <p class="mt-1.5 text-[10px] text-slate-500 dark:text-zinc-400">
                                Sisa: Rp {{ number_format($budgetRemaining, 0, ',', '.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_140px_auto] gap-2 items-end">
                            <div>
                                <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Nama Item
                                </label>
                                <input type="text" wire:model="budgetForm.item_name" placeholder="Contoh: Honorarium"
                                    class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:ring-emerald-500 py-2 px-3" />
                                @error('budgetForm.item_name')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-[12px] font-medium text-slate-700 dark:text-zinc-300 mb-1">
                                    Jumlah (Rp)
                                </label>
                                <input type="text" wire:model="budgetForm.amount" placeholder="1000000"
                                    class="block w-full rounded-md shadow-sm text-[12px]
                                           border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                           text-slate-900 dark:text-zinc-100
                                           focus:ring-emerald-500 py-2 px-3" />
                                @error('budgetForm.amount')<p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <flux:button type="button" wire:click="addBudgetItem"
                                icon="plus" variant="primary" size="sm">
                                Add
                            </flux:button>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @forelse ($budgetItems as $item)
                                <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg
                                            border border-slate-200 dark:border-zinc-700
                                            bg-white dark:bg-zinc-800/40">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[12px] font-medium text-slate-900 dark:text-zinc-100 truncate">
                                            {{ $item->item_name }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 dark:text-zinc-400">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeBudgetItem({{ $item->id }})"
                                        wire:confirm="Hapus item ini?"
                                        class="p-1.5 rounded-md text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            @empty
                                <p class="text-[11px] text-center text-slate-400 dark:text-zinc-500 italic py-4">
                                    Belum ada item anggaran.
                                </p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-between gap-2
                        px-5 sm:px-6 py-4 border-t border-slate-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900 rounded-b-2xl sm:rounded-b-xl">
                <flux:button type="button" @click="show = false" variant="ghost" size="sm">Batal</flux:button>
                <div class="flex flex-col-reverse sm:flex-row gap-2">
                    @if ($step === 2)
                        <flux:button type="button" wire:click="prevStep" variant="ghost" size="sm">← Kembali</flux:button>
                    @endif
                    @if ($step === 1)
                        <flux:button type="button" wire:click="nextStep" variant="primary" size="sm"
                            wire:loading.attr="disabled" wire:target="nextStep">
                            Lanjut →
                        </flux:button>
                    @else
                        <flux:button type="button" wire:click="finish" variant="primary" size="sm"
                            wire:loading.attr="disabled" wire:target="finish">
                            <span wire:loading.remove wire:target="finish">Simpan Proposal</span>
                            <span wire:loading.flex wire:target="finish">Menyimpan...</span>
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
