<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:8',
    ];

    // Relationships
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buyer_id');
    }

    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'seller_id');
    }

    // Helper methods
    public function getAsset(string $symbol)
    {
        return $this->assets()->where('symbol', $symbol)->first();
    }

    public function hasEnoughBalance(string $amount): bool
    {
        return bccomp($this->balance, $amount, 8) >= 0;
    }

    public function hasEnoughAsset(string $symbol, string $amount): bool
    {
        $asset = $this->getAsset($symbol);
        return $asset && bccomp($asset->amount, $amount, 8) >= 0;
    }
}