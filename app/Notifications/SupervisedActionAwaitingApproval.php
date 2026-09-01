<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupervisedAction;

/**
 * Avvisa DM e admin che c'è un'azione sotto vigilanza da approvare.
 */
final class SupervisedActionAwaitingApproval extends InAppNotification
{
    public function __construct(private readonly SupervisedAction $action) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Un'azione da approvare",
            'body' => "{$this->action->type->label()}: {$this->action->summary}. Aprila nel pannello per decidere.",
            'url' => '/admin/supervised-actions',
        ];
    }
}
