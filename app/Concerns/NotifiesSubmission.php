<?php

namespace App\Concerns;

use App\Models\User;
use App\Notifications\NewSubmissionNotification;
use App\Notifications\StatusChangedNotification;

trait NotifiesSubmission
{
    abstract protected function notificationType(): string;

    abstract protected function authorOf(object $model): ?User;

    public function created(object $model): void
    {
        // Lewati saat seeding / migrasi
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        foreach (User::admins() as $admin) {
            $admin->notify(new NewSubmissionNotification($model, $this->notificationType()));
        }
    }

    public function updated(object $model): void
    {
        if (! $model->wasChanged('status')) {
            return;
        }

        $old = (string) ($model->getOriginal('status') ?? '');
        $new = (string) $model->status;

        // Notify author (user pengusul)
        $author = $this->authorOf($model);
        $author?->notify(
            new StatusChangedNotification($model, $this->notificationType(), $old, $new)
        );

        // Notify semua admin
        foreach (User::admins() as $admin) {
            $admin->notify(
                new StatusChangedNotification($model, $this->notificationType(), $old, $new)
            );
        }
    }
}
