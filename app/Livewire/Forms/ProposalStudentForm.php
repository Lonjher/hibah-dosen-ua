<?php

namespace App\Livewire\Forms;

use App\Models\ProposalStudent;
use Livewire\Form;

class ProposalStudentForm extends Form
{
    public ?ProposalStudent $proposalStudent = null;

    public ?int   $proposal_id = null;
    public string $nim = '';
    public string $name = '';
    public string $program_study = '';
    public string $role = 'member';

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'proposal_id'   => ['required', 'exists:proposals,id'],
            'nim'           => [
                'required',
                'string',
                'max:50',
                // Unique: NIM sama tidak boleh muncul 2x di proposal yang sama
                function ($attribute, $value, $fail) {
                    $exists = ProposalStudent::query()
                        ->where('proposal_id', $this->proposal_id)
                        ->where('nim', $value)
                        ->when($this->proposalStudent, fn ($q) => $q->where('id', '!=', $this->proposalStudent->id))
                        ->exists();

                    if ($exists) {
                        $fail('NIM ini sudah terdaftar di proposal ini.');
                    }
                },
            ],
            'name'          => ['required', 'string', 'max:255'],
            'program_study' => ['required', 'string', 'max:255'],
            'role'          => ['required', 'string', 'in:leader,member'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required'   => 'Proposal is required.',
            'proposal_id.exists'     => 'Invalid proposal.',

            'nim.required'           => 'NIM wajib diisi.',
            'nim.max'                => 'NIM maksimal 50 karakter.',

            'name.required'          => 'Nama mahasiswa wajib diisi.',
            'name.max'               => 'Nama maksimal 255 karakter.',

            'program_study.required' => 'Program studi wajib diisi.',
            'program_study.max'      => 'Program studi maksimal 255 karakter.',

            'role.required'          => 'Role is required.',
            'role.in'                => 'Role harus leader atau member.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setProposalStudent(ProposalStudent $student): void
    {
        $this->proposalStudent = $student;
        $this->proposal_id     = $student->proposal_id;
        $this->nim             = $student->nim;
        $this->name            = $student->name;
        $this->program_study   = $student->program_study;
        $this->role            = $student->role;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): ProposalStudent
    {
        $this->validate();

        $student = ProposalStudent::create([
            'proposal_id'   => $this->proposal_id,
            'nim'           => $this->nim,
            'name'          => $this->name,
            'program_study' => $this->program_study,
            'role'          => $this->role,
        ]);

        $this->reset('nim', 'name', 'program_study', 'role');
        $this->role = 'member';

        return $student;
    }

    public function update(): ProposalStudent
    {
        if (! $this->proposalStudent) {
            throw new \RuntimeException(
                'No proposal student loaded. Call setProposalStudent() before update().'
            );
        }

        $this->validate();

        $this->proposalStudent->update([
            'nim'           => $this->nim,
            'name'          => $this->name,
            'program_study' => $this->program_study,
            'role'          => $this->role,
        ]);

        $updated = $this->proposalStudent;

        $this->reset('nim', 'name', 'program_study', 'role');
        $this->role = 'member';

        return $updated;
    }
}
