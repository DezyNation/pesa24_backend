<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FundRequest
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $user;
    protected $amount;

    /**
     * Create a new event instance.
     */
    public function __construct(string $user, float $amount)
    {
        $this->user = $user;
        $this->amount = $amount;
    }

    public function broadcastAs(): string
    {
        return 'new-fund-request';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('fund-request'),
        ];
    }
}
