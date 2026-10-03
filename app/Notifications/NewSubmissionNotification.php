<?php

namespace App\Notifications;

use App\Models\FinalReport;
use App\Models\Output;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal|ProgressReport|FinalReport|Output $model,
        public string $type,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$title, $url] = $this->meta($notifiable);

        return (new MailMessage)
            ->subject('[SIM-LITABMAS] ' . $title)
            ->markdown('emails.notifications.new-submission', [
                'title'         => $title,
                'url'           => $url,
                'recipientName' => $notifiable->full_name,
                'actorName'     => $this->getActor()?->full_name ?? 'User',
                'proposalTitle' => $this->getProposalTitle(),
                'typeLabel'     => $this->typeLabel(),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        [$title, $url] = $this->meta($notifiable);

        return [
            'type'            => 'new_submission',
            'submission_type' => $this->type,
            'title'           => $title,
            'url'             => $url,
            'author_name'     => $this->getActor()?->full_name,
            'actor_name'      => $this->getActor()?->full_name,
            'proposal_title'  => $this->getProposalTitle(),
            'model_id'        => $this->model->id,
        ];
    }

    /**
     * Tentukan URL berdasarkan ROLE penerima + jenis proposal.
     */
    protected function meta(object $notifiable): array
    {
        $title = match ($this->type) {
            'proposal'        => 'New Proposal Submitted',
            'progress_report' => 'New Progress Report Submitted',
            'final_report'    => 'New Final Report Submitted',
            'output'          => 'New Output Submitted',
        };

        // Reviewer punya halaman khusus
        if ($notifiable->hasAnyRole(['REVIEWER'])) {
            $routeName = match ($this->type) {
                'progress_report' => 'reviewer.review-progress-report',
                default           => 'reviewer.review-proposal',
            };

            return [$title, route($routeName, ['highlight' => $this->model->id])];
        }

        // Admin / Superadmin
        if ($notifiable->hasAnyRole(['ADMIN', 'SUPERADMIN'])) {
            $routeName = $this->isResearch()
                ? 'admin.internal.manage-researches'
                : 'admin.internal.manage-dedications';

            return [$title, route($routeName, ['highlight' => $this->model->id])];
        }

        // User (dosen pengusul)
        $routeName = $this->isResearch()
            ? 'user.internal.manage-researches'
            : 'user.internal.manage-dedications';

        return [$title, route($routeName, ['highlight' => $this->model->id])];
    }

    /**
     * Cek apakah proposal terkait ini adalah penelitian (bukan pengabdian).
     */
    protected function isResearch(): bool
    {
        return match ($this->type) {
            'proposal' => (bool) ($this->model->is_research ?? true),
            default    => (bool) ($this->model->proposal?->is_research ?? true),
        };
    }

    protected function typeLabel(): string
    {
        return match ($this->type) {
            'proposal'        => 'Proposal',
            'progress_report' => 'Progress Report',
            'final_report'    => 'Final Report',
            'output'          => 'Output',
        };
    }

    protected function getActor(): ?User
    {
        return match ($this->type) {
            'proposal' => $this->model->author,
            default    => $this->model->proposal?->author,
        };
    }

    protected function getProposalTitle(): string
    {
        return match ($this->type) {
            'proposal' => $this->model->title ?? '—',
            default    => $this->model->proposal?->title ?? '—',
        };
    }
}
