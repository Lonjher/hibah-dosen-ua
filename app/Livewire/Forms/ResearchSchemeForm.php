<?php

namespace App\Livewire\Forms;

use App\Models\ResearchScheme;
use Livewire\Form;

class ResearchSchemeForm extends Form
{
    public ?ResearchScheme $researchScheme = null;

    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $budget_limit = '';
    public bool   $is_active = true;

    // ═══════════════ Validation ═══════════════

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['required', 'string', 'max:255', 'unique:research_schemes,code,'.($this->researchScheme?->id ?? 'NULL')],
            'description'  => ['required', 'string', 'max:255'],
            'budget_limit' => ['required', 'string', 'max:255'],
            'is_active'    => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Nama skema wajib diisi.',
            'name.max'              => 'Nama skema maksimal 255 karakter.',

            'code.required'         => 'Kode skema wajib diisi.',
            'code.unique'           => 'Kode skema sudah digunakan.',
            'code.max'              => 'Kode skema maksimal 255 karakter.',

            'description.required'  => 'Deskripsi wajib diisi.',
            'description.max'       => 'Deskripsi maksimal 255 karakter.',

            'budget_limit.required' => 'Batas anggaran wajib diisi.',
            'budget_limit.max'      => 'Batas anggaran maksimal 255 karakter.',

            'is_active.boolean'     => 'Status aktif harus true atau false.',
        ];
    }

    // ═══════════════ Load ═══════════════

    public function setResearchScheme(ResearchScheme $researchScheme): void
    {
        $this->researchScheme = $researchScheme;
        $this->name           = $researchScheme->name;
        $this->code           = $researchScheme->code;
        $this->description    = $researchScheme->description;
        $this->budget_limit   = $researchScheme->budget_limit;
        $this->is_active      = (bool) $researchScheme->is_active;
    }

    // ═══════════════ Actions ═══════════════

    public function create(): ResearchScheme
    {
        $this->validate();

        $scheme = ResearchScheme::create([
            'name'         => $this->name,
            'code'         => $this->code,
            'description'  => $this->description,
            'budget_limit' => $this->budget_limit,
            'is_active'    => $this->is_active,
        ]);

        $this->reset();

        return $scheme;
    }

    public function update(): ResearchScheme
    {
        if (! $this->researchScheme) {
            throw new \RuntimeException(
                'No research scheme loaded. Call setResearchScheme() before update().'
            );
        }

        $this->validate();

        $this->researchScheme->update([
            'name'         => $this->name,
            'code'         => $this->code,
            'description'  => $this->description,
            'budget_limit' => $this->budget_limit,
            'is_active'    => $this->is_active,
        ]);

        $updated = $this->researchScheme;

        $this->reset();

        return $updated;
    }
}
