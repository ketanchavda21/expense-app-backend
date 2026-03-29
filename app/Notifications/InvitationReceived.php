<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\BookInvitation;

class InvitationReceived extends Notification
{
    use Queueable;

    public $invitation;

    public function __construct(BookInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'invitation_received';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'book_name' => $this->invitation->book->name,
            'invited_by_name' => $this->invitation->inviter->name,
            'message' => "{$this->invitation->inviter->name} invited you to join {$this->invitation->book->name}"
        ];
    }
}
