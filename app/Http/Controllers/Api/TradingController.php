<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Jobs\MatchOrderJob;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class TradingController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Get user profile with balance and assets
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('assets');

        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets->map(fn($asset) => [
                'symbol' => $asset->symbol,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount,
                'total' => $asset->getTotalAmount(),
            ]),
        ]);
    }

    /**
     * Get orders with optional filters
     */
    public function orders(Request $request)
    {
        $query = $request->user()->orders()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by symbol
        if ($request->has('symbol')) {
            $query->where('symbol', $request->symbol);
        }

        // Filter by side
        if ($request->has('side')) {
            $query->where('side', $request->side);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        return response()->json([
            'orders' => $orders->map(fn($order) => [
                'id' => $order->id,
                'symbol' => $order->symbol,
                'side' => $order->side,
                'price' => $order->price,
                'amount' => $order->amount,
                'total' => $order->getTotalValue(),
                'status' => $order->status,
                'status_label' => match($order->status) {
                    Order::STATUS_OPEN => 'Open',
                    Order::STATUS_FILLED => 'Filled',
                    Order::STATUS_CANCELLED => 'Cancelled',
                },
                'created_at' => $order->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * Get orderbook for a symbol
     */
    public function orderbook(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string|in:BTC,ETH,SOL,XRP',
        ]);

        $orderbook = $this->orderService->getOrderbook($validated['symbol']);

        return response()->json([
            'symbol' => $validated['symbol'],
            'buy_orders' => $orderbook['buy_orders']->map(fn($order) => [
                'id' => $order->id,
                'price' => $order->price,
                'amount' => $order->amount,
                'total' => $order->getTotalValue(),
                'created_at' => $order->created_at->toISOString(),
            ]),
            'sell_orders' => $orderbook['sell_orders']->map(fn($order) => [
                'id' => $order->id,
                'price' => $order->price,
                'amount' => $order->amount,
                'total' => $order->getTotalValue(),
                'created_at' => $order->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * Create a new order
     */
    public function createOrder(CreateOrderRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = $request->user();

            // Create order based on side
            if ($validated['side'] === 'buy') {
                $order = $this->orderService->createBuyOrder($user, $validated);
            } else {
                $order = $this->orderService->createSellOrder($user, $validated);
            }

            // Dispatch matching job
            MatchOrderJob::dispatch($order);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side,
                    'price' => $order->price,
                    'amount' => $order->amount,
                    'total' => $order->getTotalValue(),
                    'status' => 'open',
                    'created_at' => $order->created_at->toISOString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request, int $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $this->orderService->cancelOrder($order);

            return response()->json([
                'message' => 'Order cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}