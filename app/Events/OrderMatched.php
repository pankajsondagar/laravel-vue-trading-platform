<?php

namespace App\Events;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trade $trade;
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Trade $trade, User $user)
    {
        $this->trade = $trade;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->user->id);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $isBuyer = $this->trade->buyer_id === $this->user->id;
        
        // Reload user to get fresh balance
        $user = User::with('assets')->find($this->user->id);

        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'side' => $isBuyer ? 'buy' : 'sell',
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'total_value' => $this->trade->total_value,
                'commission' => $this->trade->commission,
                'created_at' => $this->trade->created_at->toISOString(),
            ],
            'user' => [
                'balance' => $user->balance,
                'assets' => $user->assets->map(fn($asset) => [
                    'symbol' => $asset->symbol,
                    'amount' => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                ]),
            ],
            'message' => $isBuyer 
                ? "Your buy order for {$this->trade->amount} {$this->trade->symbol} was filled at {$this->trade->price} USD"
                : "Your sell order for {$this->trade->amount} {$this->trade->symbol} was filled at {$this->trade->price} USD",
        ];
    }
}