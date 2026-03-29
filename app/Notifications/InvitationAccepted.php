<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\BookInvitation;

class InvitationAccepted extends Notification
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
        return 'invitation_accepted';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'book_name' => $this->invitation->book->name,
            'user_name' => $this->invitation->user->name,
            'message' => "{$this->invitation->user->name} accepted your invitation for {$this->invitation->book->name}"
        ];
    }
}
