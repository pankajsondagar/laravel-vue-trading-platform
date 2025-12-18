<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Order statuses
    const STATUS_OPEN = 1;
    const STATUS_FILLED = 2;
    const STATUS_CANCELLED = 3;

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
        'locked_usd',
        'locked_asset',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'locked_usd' => 'decimal:8',
        'locked_asset' => 'decimal:8',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeBuy($query)
    {
        return $query->where('side', 'buy');
    }

    public function scopeSell($query)
    {
        return $query->where('side', 'sell');
    }

    public function scopeSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    // Helper methods
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isFilled(): bool
    {
        return $this->status === self::STATUS_FILLED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isBuy(): bool
    {
        return $this->side === 'buy';
    }

    public function isSell(): bool
    {
        return $this->side === 'sell';
    }

    public function getTotalValue(): string
    {
        return bcmul($this->price, $this->amount, 8);
    }

    public function markFilled(): void
    {
        $this->status = self::STATUS_FILLED;
        $this->save();
    }

    public function markCancelled(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }
}