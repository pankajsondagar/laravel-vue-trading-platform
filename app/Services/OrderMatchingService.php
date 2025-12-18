<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    /**
     * Attempt to match a newly created order
     */
    public function matchOrder(Order $newOrder): ?Trade
    {
        if (!$newOrder->isOpen()) {
            return null;
        }

        if ($newOrder->isBuy()) {
            return $this->matchBuyOrder($newOrder);
        } else {
            return $this->matchSellOrder($newOrder);
        }
    }

    /**
     * Match a buy order with the first valid sell order
     */
    private function matchBuyOrder(Order $buyOrder): ?Trade
    {
        return DB::transaction(function () use ($buyOrder) {
            // Find first matching sell order where sell.price <= buy.price
            $sellOrder = Order::open()
                ->sell()
                ->symbol($buyOrder->symbol)
                ->where('price', '<=', $buyOrder->price)
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$sellOrder) {
                return null; // No matching order
            }

            // Full match only - amounts must be equal
            if (bccomp($buyOrder->amount, $sellOrder->amount, 8) !== 0) {
                return null;
            }

            return $this->executeTrade($buyOrder, $sellOrder);
        });
    }

    /**
     * Match a sell order with the first valid buy order
     */
    private function matchSellOrder(Order $sellOrder): ?Trade
    {
        return DB::transaction(function () use ($sellOrder) {
            // Find first matching buy order where buy.price >= sell.price
            $buyOrder = Order::open()
                ->buy()
                ->symbol($sellOrder->symbol)
                ->where('price', '>=', $sellOrder->price)
                ->orderBy('price', 'desc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$buyOrder) {
                return null; // No matching order
            }

            // Full match only - amounts must be equal
            if (bccomp($sellOrder->amount, $buyOrder->amount, 8) !== 0) {
                return null;
            }

            return $this->executeTrade($buyOrder, $sellOrder);
        });
    }

    /**
     * Execute the trade between two matched orders
     */
    private function executeTrade(Order $buyOrder, Order $sellOrder): Trade
    {
        // Use sell order price as execution price
        $executionPrice = $sellOrder->price;
        $amount = $sellOrder->amount;
        
        // Calculate values
        $totalValue = bcmul($executionPrice, $amount, 8);
        $commission = Trade::calculateCommission($totalValue);
        $buyerTotal = bcadd($totalValue, $commission, 8); // Buyer pays commission
        
        // Lock all entities
        $buyer = User::where('id', $buyOrder->user_id)->lockForUpdate()->first();
        $seller = User::where('id', $sellOrder->user_id)->lockForUpdate()->first();
        
        // Get or create buyer's asset
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->id, 'symbol' => $buyOrder->symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );
        $buyerAsset = Asset::where('id', $buyerAsset->id)->lockForUpdate()->first();
        
        // Get seller's asset
        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $sellOrder->symbol)
            ->lockForUpdate()
            ->first();

        // Verify buyer has locked funds (should always be true)
        if (bccomp($buyOrder->locked_usd, $buyerTotal, 8) < 0) {
            throw new \Exception('Buyer has insufficient locked funds');
        }

        // Execute transfer
        // 1. Deduct commission from buyer's locked USD
        $buyerRefund = bcsub($buyOrder->locked_usd, $buyerTotal, 8);
        if (bccomp($buyerRefund, '0', 8) > 0) {
            $buyer->balance = bcadd($buyer->balance, $buyerRefund, 8);
        }
        
        // 2. Transfer crypto from seller to buyer
        $sellerAsset->subtractLockedAmount($amount);
        $buyerAsset->addAmount($amount);
        
        // 3. Transfer USD to seller
        $seller->balance = bcadd($seller->balance, $totalValue, 8);
        
        // Save all changes
        $buyer->save();
        $seller->save();
        $buyerAsset->save();
        $sellerAsset->save();

        // Mark orders as filled
        $buyOrder->markFilled();
        $sellOrder->markFilled();

        // Create trade record
        $trade = Trade::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'symbol' => $buyOrder->symbol,
            'price' => $executionPrice,
            'amount' => $amount,
            'total_value' => $totalValue,
            'commission' => $commission,
        ]);

        // Broadcast event to both parties
        broadcast(new OrderMatched($trade, $buyer))->toOthers();
        broadcast(new OrderMatched($trade, $seller))->toOthers();

        return $trade;
    }
}