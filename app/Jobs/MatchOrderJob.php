<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OrderMatchingService $matchingService): void
    {
        // Reload order to get fresh state
        $order = Order::find($this->order->id);

        // Skip if order is no longer open
        if (!$order || !$order->isOpen()) {
            return;
        }

        try {
            $trade = $matchingService->matchOrder($order);

            if ($trade) {
                \Log::info('Order matched successfully', [
                    'order_id' => $order->id,
                    'trade_id' => $trade->id,
                ]);
            } else {
                \Log::info('No matching order found', [
                    'order_id' => $order->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Order matching failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}