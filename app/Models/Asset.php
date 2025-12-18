<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'locked_amount' => 'decimal:8',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getTotalAmount(): string
    {
        return bcadd($this->amount, $this->locked_amount, 8);
    }

    public function hasEnoughAvailable(string $amount): bool
    {
        return bccomp($this->amount, $amount, 8) >= 0;
    }

    public function lockAmount(string $amount): void
    {
        $this->amount = bcsub($this->amount, $amount, 8);
        $this->locked_amount = bcadd($this->locked_amount, $amount, 8);
        $this->save();
    }

    public function unlockAmount(string $amount): void
    {
        $this->locked_amount = bcsub($this->locked_amount, $amount, 8);
        $this->amount = bcadd($this->amount, $amount, 8);
        $this->save();
    }

    public function addAmount(string $amount): void
    {
        $this->amount = bcadd($this->amount, $amount, 8);
        $this->save();
    }

    public function subtractLockedAmount(string $amount): void
    {
        $this->locked_amount = bcsub($this->locked_amount, $amount, 8);
        $this->save();
    }
}