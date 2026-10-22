<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dividend extends Model
{
    public const TIPOS = ['dividendo' => 'Dividendo', 'jcp' => 'JCP', 'rendimento' => 'Rendimento (FII)'];

    protected $fillable = ['user_id', 'asset_id', 'ticker', 'tipo', 'data', 'valor'];

    protected function casts(): array
    {
        return ['data' => 'date', 'valor' => 'float'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function nomeTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
