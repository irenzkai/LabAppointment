<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; 
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\Channel;

class AppointmentNotification extends Notification implements ShouldBroadcastNow 
{
    use Queueable;

    private $details;

    /**
     * Create a new notification instance.
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // Deliver to both database and real-time broadcast channels
        return ['database', 'broadcast'];
    }

    /**
     * Define the data structure for the database storage.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->details['title'],
            'message' => $this->details['message'],
            'url' => route('appointments.index'),
            'type' => $this->details['type'], // 'info', 'success', 'danger'
        ];
    }

    /**
     * Define the data structure for the Pusher real-time broadcast.
     * We pass the target user_id so the front-end can filter incoming broadcasts safely.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'user_id' => $notifiable->id, // Target recipient ID
            'title' => $this->details['title'],
            'message' => $this->details['message'],
            'url' => route('appointments.index'),
            'type' => $this->details['type'],
        ]);
    }

    /**
     * Broadcast on a public channel to avoid authentication locks on Render free tier.
     */
    public function broadcastOn()
    {
        return new Channel('user-notifications');
    }

    /**
     * Define the broadcast type/event name cleanly.
     */
    public function broadcastType()
    {
        return 'notification.created';
    }
}