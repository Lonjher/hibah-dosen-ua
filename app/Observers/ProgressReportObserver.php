<?php

namespace App\Observers;

use App\Models\User;
use App\Concerns\NotifiesSubmission;

class ProgressReportObserver
{
    use NotifiesSubmission;

    protected function notificationType(): string
    {
        return 'progress_report';
    }

    protected function authorOf(object $model): ?User
    {
        return $model->proposal?->author;
    }
}
