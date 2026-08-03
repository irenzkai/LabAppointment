<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; 
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $title;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $title, $message)
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Broadcast on the public channel.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('user-notifications'),
        ];
    }

    /**
     * Define the exact event name expected by your navbar JavaScript [424].
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Structure the payload so it matches the expected 'data.data' format [424].
     */
    public function broadcastWith(): array
    {
        return [
            'data' => [
                'user_id' => $this->userId,
                'title' => $this->title,
                'message' => $this->message,
            ]
        ];
    }
}