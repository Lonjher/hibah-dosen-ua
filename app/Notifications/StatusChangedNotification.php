<?php

namespace App\Notifications;

use App\Models\FinalReport;
use App\Models\Output;
use App\Models\ProgressReport;
use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Proposal|ProgressReport|FinalReport|Output $model,
        public string $type,
        public string $oldStatus,
        public string $newStatus,
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
            ->markdown('emails.notifications.status-changed', [
                'title'         => $title,
                'url'           => $url,
                'recipientName' => $notifiable->full_name,
                'proposalTitle' => $this->getProposalTitle(),
                'oldStatus'     => $this->oldStatus,
                'newStatus'     => $this->newStatus,
                'typeLabel'     => $this->typeLabel(),
                'isAdmin'       => $notifiable->hasAnyRole(['ADMIN', 'SUPERADMIN']),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        [$title, $url] = $this->meta($notifiable);

        // Author hanya dikirim ke admin/superadmin
        $isAdmin = method_exists($notifiable, 'hasAnyRole')
            && $notifiable->hasAnyRole(['ADMIN', 'SUPERADMIN']);

        return [
            'type'            => 'status_changed',
            'submission_type' => $this->type,
            'title'           => $title,
            'url'             => $url,
            'author_name'     => $isAdmin ? $this->getAuthorName() : null,
            'proposal_title'  => $this->getProposalTitle(),
            'old_status'      => $this->oldStatus,
            'new_status'      => $this->newStatus,
            'model_id'        => $this->model->id,
        ];
    }

    /**
     * Ambil nama author.
     */
    protected function getAuthorName(): ?string
    {
        return match ($this->type) {
            'proposal' => $this->model->author?->full_name,
            default    => $this->model->proposal?->author?->full_name,
        };
    }

    protected function meta(object $notifiable): array
    {
        $title = match ($this->type) {
            'proposal'        => 'Proposal Status Updated',
            'progress_report' => 'Progress Report Status Updated',
            'final_report'    => 'Final Report Status Updated',
            'output'          => 'Output Status Updated',
        };

        if ($notifiable->hasAnyRole(['REVIEWER'])) {
            $routeName = match ($this->type) {
                'progress_report' => 'reviewer.review-progress-report',
                default           => 'reviewer.review-proposal',
            };

            return [$title, route($routeName, ['highlight' => $this->model->id])];
        }

        if ($notifiable->hasAnyRole(['ADMIN', 'SUPERADMIN'])) {
            $routeName = $this->isResearch()
                ? 'admin.internal.manage-researches'
                : 'admin.internal.manage-dedications';

            return [$title, route($routeName, ['highlight' => $this->model->id])];
        }

        $routeName = $this->isResearch()
            ? 'user.internal.manage-researches'
            : 'user.internal.manage-dedications';

        return [$title, route($routeName, ['highlight' => $this->model->id])];
    }

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

    protected function getProposalTitle(): string
    {
        return match ($this->type) {
            'proposal' => $this->model->title ?? '—',
            default    => $this->model->proposal?->title ?? '—',
        };
    }
}
