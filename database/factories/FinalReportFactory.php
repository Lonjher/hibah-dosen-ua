<?php

namespace Database\Factories;

use App\Models\FinalReport;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinalReport>
 */
class FinalReportFactory extends Factory
{
    protected $model = FinalReport::class;

    public function definition(): array
    {
        return [
            'proposal_id'      => Proposal::factory()->accepted(),
            'summary'          => fake()->paragraph(5),
            'keyword'          => implode(', ', fake()->words(3)),
            'report_path'      => 'final-reports/'.fake()->uuid().'.pdf',
            'ppt_path'         => 'final-reports/'.fake()->uuid().'.pptx',
            'research_output'  => 'final-reports/'.fake()->uuid().'.docx',
            'submission_proof' => 'final-reports/'.fake()->uuid().'.jpg',
            'status'           => 'pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function revised(): static
    {
        return $this->state(fn () => ['status' => 'revised']);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'accepted']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
