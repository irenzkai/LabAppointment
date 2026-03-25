<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentNotification extends Notification
{
    use Queueable;

    private $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via($notifiable): array
    {
        // For now we use database, we can add 'mail' here later
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->details['title'],
            'message' => $this->details['message'],
            'url' => route('appointments.index'), // Clicking will take them to history
            'type' => $this->details['type'], // 'info', 'success', 'danger'
        ];
    }
}