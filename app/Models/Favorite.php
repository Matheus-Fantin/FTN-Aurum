<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = ['user_id', 'ticker'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mercado(): BelongsTo
    {
        return $this->belongsTo(MarketAsset::class, 'ticker', 'ticker');
    }
}
