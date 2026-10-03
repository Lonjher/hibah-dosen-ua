<?php

namespace App\Observers;

use App\Models\User;
use App\Concerns\NotifiesSubmission;

class FinalReportObserver
{
    use NotifiesSubmission;

    protected function notificationType(): string
    {
        return 'final_report';
    }

    protected function authorOf(object $model): ?User
    {
        return $model->proposal?->author;
    }
}
