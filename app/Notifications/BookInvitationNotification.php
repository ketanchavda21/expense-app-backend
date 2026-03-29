<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\BookInvitation;

class BookInvitationNotification extends Notification
{
    use Queueable;

    public $invitation;

    public function __construct(BookInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'book_name' => $this->invitation->book->name,
            'invited_by_name' => $this->invitation->inviter->name,
            'invitation_id' => $this->invitation->id,
            'message' => "{$this->invitation->inviter->name} invited you to join {$this->invitation->book->name}",
            'type' => 'invitation'
        ];
    }
}
