<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Posição do usuário em um ativo (quantidade e preço médio). */
class Asset extends Model
{
    protected $fillable = [
        'user_id', 'ticker', 'classe', 'quantidade', 'preco_medio', 'cotacao',
        'variacao_dia', 'atualizacao_automatica', 'cotacao_atualizada_em',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'float',
            'preco_medio' => 'float',
            'cotacao' => 'float',
            'variacao_dia' => 'float',
            'atualizacao_automatica' => 'boolean',
            'cotacao_atualizada_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    /** Valor de mercado: quantidade × cotação. */
    public function valor(): float
    {
        return $this->quantidade * $this->cotacao;
    }

    /** Quanto foi pago: quantidade × preço médio. */
    public function custo(): float
    {
        return $this->quantidade * $this->preco_medio;
    }

    public function resultado(): float
    {
        return $this->valor() - $this->custo();
    }

    public function resultadoPercentual(): float
    {
        return $this->custo() > 0 ? ($this->valor() / $this->custo() - 1) * 100 : 0;
    }

    /** Quanto a posição ganhou ou perdeu hoje, pela variação do dia. */
    public function variacaoDiaValor(): float
    {
        if ($this->variacao_dia === null || $this->variacao_dia <= -100) {
            return 0;
        }

        return $this->valor() - $this->valor() / (1 + $this->variacao_dia / 100);
    }

    public function nomeClasse(): string
    {
        return config("aurum.classes.{$this->classe}.nome", $this->classe);
    }

    public function cor(): string
    {
        return config("aurum.classes.{$this->classe}.cor", 's1');
    }
}
