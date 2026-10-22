<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alerta de preço: avisa quando o ativo passa acima ou abaixo de um valor. */
class PriceAlert extends Model
{
    protected $fillable = ['user_id', 'ticker', 'condicao', 'preco', 'disparado_em', 'preco_disparo', 'visto'];

    protected function casts(): array
    {
        return ['preco' => 'float', 'preco_disparo' => 'float', 'disparado_em' => 'datetime', 'visto' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** O preço informado atinge a condição deste alerta? */
    public function atingidoPor(float $preco): bool
    {
        return $this->condicao === 'acima' ? $preco >= $this->preco : $preco <= $this->preco;
    }

    public function descricao(): string
    {
        return ($this->condicao === 'acima' ? 'subir até ' : 'cair até ').brl($this->preco);
    }
}
