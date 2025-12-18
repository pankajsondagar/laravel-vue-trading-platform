<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new buy order with USD locking
     */
    public function createBuyOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock user row for update to prevent race conditions
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            $totalCost = bcmul($data['price'], $data['amount'], 8);

            // Validate sufficient balance
            if (!$user->hasEnoughBalance($totalCost)) {
                throw new \Exception('Insufficient USD balance');
            }

            // Deduct USD from balance
            $user->balance = bcsub($user->balance, $totalCost, 8);
            $user->save();

            // Create order
            return Order::create([
                'user_id' => $user->id,
                'symbol' => $data['symbol'],
                'side' => 'buy',
                'price' => $data['price'],
                'amount' => $data['amount'],
                'status' => Order::STATUS_OPEN,
                'locked_usd' => $totalCost,
            ]);
        });
    }

    /**
     * Create a new sell order with asset locking
     */
    public function createSellOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock asset row for update
            $asset = Asset::where('user_id', $user->id)
                ->where('symbol', $data['symbol'])
                ->lockForUpdate()
                ->first();

            if (!$asset || !$asset->hasEnoughAvailable($data['amount'])) {
                throw new \Exception('Insufficient asset balance');
            }

            // Lock asset amount
            $asset->lockAmount($data['amount']);

            // Create order
            return Order::create([
                'user_id' => $user->id,
                'symbol' => $data['symbol'],
                'side' => 'sell',
                'price' => $data['price'],
                'amount' => $data['amount'],
                'status' => Order::STATUS_OPEN,
                'locked_asset' => $data['amount'],
            ]);
        });
    }

    /**
     * Cancel an open order and release locked funds/assets
     */
    public function cancelOrder(Order $order): bool
    {
        if (!$order->isOpen()) {
            throw new \Exception('Only open orders can be cancelled');
        }

        return DB::transaction(function () use ($order) {
            // Lock order for update
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($order->isBuy()) {
                // Release locked USD
                $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                $user->balance = bcadd($user->balance, $order->locked_usd, 8);
                $user->save();
            } else {
                // Release locked asset
                $asset = Asset::where('user_id', $order->user_id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();
                
                if ($asset) {
                    $asset->unlockAmount($order->locked_asset);
                }
            }

            // Mark order as cancelled
            $order->markCancelled();

            return true;
        });
    }

    /**
     * Get orderbook for a symbol
     */
    public function getOrderbook(string $symbol): array
    {
        $buyOrders = Order::open()
            ->buy()
            ->symbol($symbol)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $sellOrders = Order::open()
            ->sell()
            ->symbol($symbol)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'buy_orders' => $buyOrders,
            'sell_orders' => $sellOrders,
        ];
    }
}