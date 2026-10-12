<?php

namespace App\Observers;

use App\Models\User;
use App\Concerns\NotifiesSubmission;

class OutcomeObserver
{
    use NotifiesSubmission;

    protected function notificationType(): string
    {
        return 'outcome';
    }

    protected function authorOf(object $model): ?User
    {
        return $model->proposal?->author;
    }
}
