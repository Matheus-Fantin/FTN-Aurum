<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Foto diária do valor da carteira, para o gráfico de evolução. */
class PortfolioSnapshot extends Model
{
    protected $fillable = ['user_id', 'data', 'valor', 'custo'];

    protected function casts(): array
    {
        return ['data' => 'date', 'valor' => 'float', 'custo' => 'float'];
    }
}
