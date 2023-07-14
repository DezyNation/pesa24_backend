<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayoutStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $content;
    public $user_id;
    /**
     * Create a new event instance.
     */
    public function __construct($title, $content, $user_id)
    {
        $this->title = $title;
        $this->content = $content;
        $this->user_id = $user_id;
    }

    public function broadcastAs(): string
    {
        return 'payout-updated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('janpay-01-development'),
        ];
    }
}
