<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Ativo da B3 na lista do mercado (compartilhada por todos os usuários). */
class MarketAsset extends Model
{
    protected $fillable = [
        'ticker', 'nome', 'classe', 'setor', 'logo', 'preco', 'variacao', 'volume', 'valor_mercado', 'atualizado_em',
        'pl', 'dy', 'media_20', 'media_50', 'maxima_periodo', 'minima_periodo', 'volume_medio', 'variacao_mes',
        'tendencia_alta', 'radar_nota', 'radar_sinais', 'historico', 'fundamentos_em',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'float',
            'variacao' => 'float',
            'volume' => 'float',
            'valor_mercado' => 'float',
            'atualizado_em' => 'datetime',
            'pl' => 'float',
            'dy' => 'float',
            'media_20' => 'float',
            'media_50' => 'float',
            'maxima_periodo' => 'float',
            'minima_periodo' => 'float',
            'volume_medio' => 'float',
            'variacao_mes' => 'float',
            'tendencia_alta' => 'boolean',
            'radar_nota' => 'float',
            'radar_sinais' => 'array',
            'historico' => 'array',
            'fundamentos_em' => 'datetime',
        ];
    }

    public function nomeClasse(): string
    {
        return config("aurum.classes.{$this->classe}.nome", $this->classe);
    }

    /**
     * Aplica os filtros prontos da tela Buscar (todos precisam ser atendidos).
     *
     * @param  array<int, string>  $filtros
     */
    public function scopeFiltros(Builder $query, array $filtros): Builder
    {
        $l = config('aurum.limites');

        foreach ($filtros as $filtro) {
            match ($filtro) {
                'dividendos' => $query->where('dy', '>=', $l['dividendos']),
                'barata' => $query->where('pl', '>', 0)->where('pl', '<=', $l['pl_maximo']),
                'alta' => $query->where('tendencia_alta', true),
                'radar' => $query->where('radar_nota', '>=', $l['radar_minimo']),
                'grande' => $query->where('valor_mercado', '>=', $l['valor_mercado']),
                'liquida' => $query->where('volume', '>=', $l['volume']),
                default => null,
            };
        }

        return $query;
    }

    /** Busca pelo código ou pelo nome. */
    public function scopeBusca(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);
        if ($termo === '') {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('ticker', 'like', mb_strtoupper($termo).'%')
            ->orWhere('nome', 'like', '%'.$termo.'%')
            ->orWhere('setor', 'like', '%'.$termo.'%'));
    }

    /** Fechamentos do histórico para gráficos: lista de números. */
    public function fechamentos(): array
    {
        return array_map(fn ($p) => (float) $p[1], $this->historico ?? []);
    }
}
