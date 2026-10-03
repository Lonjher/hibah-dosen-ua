<?php

namespace App\Observers;

use App\Models\Proposal;
use App\Models\User;
use App\Concerns\NotifiesSubmission;

class ProposalObserver
{
    use NotifiesSubmission;

    protected function notificationType(): string
    {
        return 'proposal';
    }

    protected function authorOf(object $model): ?User
    {
        return $model->author;
    }
}
