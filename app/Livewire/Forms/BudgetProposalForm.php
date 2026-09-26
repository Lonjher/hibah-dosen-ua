<?php

namespace App\Livewire\Forms;

use App\Models\BudgetProposal;
use Livewire\Form;

class BudgetProposalForm extends Form
{
    public ?BudgetProposal $budgetProposal = null;

    public ?int   $proposal_id = null;
    public string $item_name = '';
    public ?int   $amount = null;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id' => ['required', 'exists:proposals,id'],
            'item_name'   => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required' => 'Proposal is required.',
            'proposal_id.exists'   => 'Selected proposal is invalid.',

            'item_name.required'   => 'Item name is required.',
            'item_name.max'        => 'Item name must not exceed 255 characters.',

            'amount.required'      => 'Amount is required.',
            'amount.integer'       => 'Amount must be an integer.',
            'amount.min'           => 'Amount must be at least 1.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setBudgetProposal(BudgetProposal $budgetProposal): void
    {
        $this->budgetProposal = $budgetProposal;
        $this->proposal_id    = $budgetProposal->proposal_id;
        $this->item_name      = $budgetProposal->item_name;
        $this->amount         = $budgetProposal->amount;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): BudgetProposal
    {
        $this->validate();

        $budget = BudgetProposal::create([
            'proposal_id' => $this->proposal_id,
            'item_name'   => $this->item_name,
            'amount'      => $this->amount,
        ]);

        $this->reset();

        return $budget;
    }

    public function update(): BudgetProposal
    {
        if (! $this->budgetProposal) {
            throw new \RuntimeException(
                'No budget proposal loaded. Call setBudgetProposal() before update().'
            );
        }

        $this->validate();

        $this->budgetProposal->update([
            'proposal_id' => $this->proposal_id,
            'item_name'   => $this->item_name,
            'amount'      => $this->amount,
        ]);

        $updated = $this->budgetProposal;

        $this->reset();

        return $updated;
    }
}
