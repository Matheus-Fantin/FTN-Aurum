<?php

namespace App\Services\Mercado;

/**
 * Indicadores calculados a partir do histórico diário de um ativo:
 * médias móveis, máxima e mínima do período, volume médio, variação no mês
 * e dividend yield dos últimos 12 meses.
 */
class Indicadores
{
    /**
     * @param  array<int, array{data: string, fechamento: float, volume?: float|null}>  $historico  em ordem de data
     * @param  array<int, array{data: string, valor: float}>  $dividendos  por ação
     * @return array<string, float|bool|null>
     */
    public static function calcular(array $historico, float $preco, array $dividendos = [], ?string $hoje = null): array
    {
        $fechamentos = array_map(fn ($h) => (float) $h['fechamento'], $historico);
        $volumes = array_values(array_filter(array_map(fn ($h) => $h['volume'] ?? null, $historico), fn ($v) => $v !== null));

        $media20 = self::media(array_slice($fechamentos, -20), 20);
        $media50 = self::media(array_slice($fechamentos, -50), 50);

        // Variação no mês: fechamento de ~21 pregões atrás até o preço de agora.
        $base = count($fechamentos) > 21 ? $fechamentos[count($fechamentos) - 22] : null;
        $variacaoMes = $base && $base > 0 ? ($preco / $base - 1) * 100 : null;

        $hoje = $hoje ? strtotime($hoje) : time();
        $limite = date('Y-m-d', strtotime('-12 months', $hoje));
        $pagos = array_sum(array_map(fn ($d) => $d['valor'], array_filter($dividendos, fn ($d) => $d['data'] >= $limite && $d['data'] <= date('Y-m-d', $hoje))));

        return [
            'media_20' => $media20,
            'media_50' => $media50,
            'maxima_periodo' => $fechamentos ? max([...$fechamentos, $preco]) : null,
            'minima_periodo' => $fechamentos ? min([...$fechamentos, $preco]) : null,
            'volume_medio' => $volumes ? array_sum($volumes) / count($volumes) : null,
            'variacao_mes' => $variacaoMes,
            'dy' => $preco > 0 && $dividendos ? $pagos / $preco * 100 : null,
            // Tendência de alta: preço acima da média curta e média curta acima da longa.
            'tendencia_alta' => $media20 !== null && $media50 !== null && $preco > $media20 && $media20 > $media50,
        ];
    }

    /** Média simples, só quando há pregões suficientes. */
    private static function media(array $valores, int $minimo): ?float
    {
        return count($valores) >= $minimo ? array_sum($valores) / count($valores) : null;
    }
}
