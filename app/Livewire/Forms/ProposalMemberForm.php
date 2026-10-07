<?php

namespace App\Livewire\Forms;

use App\Models\ProposalMember;
use Livewire\Form;

class ProposalMemberForm extends Form
{
    public ?ProposalMember $proposalMember = null;

    public ?int    $proposal_id = null;
    public ?int    $user_id = null;
    public string  $role = 'member';

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id' => ['required', 'exists:proposals,id'],
            'user_id'     => [
                'required',
                'exists:users,id',
                // Unique: satu user hanya boleh muncul 1x per proposal
                function ($attribute, $value, $fail) {
                    $exists = ProposalMember::query()
                        ->where('proposal_id', $this->proposal_id)
                        ->where('user_id', $value)
                        ->when($this->proposalMember, fn ($q) => $q->where('id', '!=', $this->proposalMember->id))
                        ->exists();

                    if ($exists) {
                        $fail('User ini sudah terdaftar sebagai anggota proposal.');
                    }
                },
            ],
            'role'        => ['required', 'string', 'in:leader,member'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required' => 'Proposal is required.',
            'proposal_id.exists'   => 'Invalid proposal.',

            'user_id.required'     => 'Anggota wajib dipilih.',
            'user_id.exists'       => 'Anggota tidak valid.',

            'role.required'        => 'Role is required.',
            'role.in'              => 'Role harus leader atau member.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setProposalMember(ProposalMember $member): void
    {
        $this->proposalMember = $member;
        $this->proposal_id    = $member->proposal_id;
        $this->user_id        = $member->user_id;
        $this->role           = $member->role;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): ProposalMember
    {
        $this->validate();

        $member = ProposalMember::create([
            'proposal_id' => $this->proposal_id,
            'user_id'     => $this->user_id,
            'role'        => $this->role,
        ]);

        $this->reset('user_id', 'role');
        $this->role = 'member';

        return $member;
    }

    public function update(): ProposalMember
    {
        if (! $this->proposalMember) {
            throw new \RuntimeException(
                'No proposal member loaded. Call setProposalMember() before update().'
            );
        }

        $this->validate();

        $this->proposalMember->update([
            'user_id' => $this->user_id,
            'role'    => $this->role,
        ]);

        $updated = $this->proposalMember;

        $this->reset('user_id', 'role');
        $this->role = 'member';

        return $updated;
    }
}
