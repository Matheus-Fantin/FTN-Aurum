<?php

namespace App\Services\Mercado;

/**
 * Radar de possíveis altas: soma os pesos dos sinais presentes (config/aurum.php)
 * e devolve uma nota de 0 a 10 com a lista de motivos. Não é recomendação.
 */
class Radar
{
    /**
     * @param  array<string, mixed>  $a  preco, volume_dia, media_20, media_50, maxima_periodo, volume_medio, variacao_mes, pl
     * @return array{nota: float, sinais: array<int, string>}
     */
    public static function pontuar(array $a): array
    {
        $preco = (float) ($a['preco'] ?? 0);
        $presentes = [
            'acima_media_curta' => ($a['media_20'] ?? null) !== null && $preco > $a['media_20'],
            'medias_cruzadas' => ($a['media_20'] ?? null) !== null && ($a['media_50'] ?? null) !== null && $a['media_20'] > $a['media_50'],
            'volume_forte' => ($a['volume_dia'] ?? null) !== null && ($a['volume_medio'] ?? 0) > 0 && $a['volume_dia'] > $a['volume_medio'] * 1.2,
            'perto_da_maxima' => ($a['maxima_periodo'] ?? 0) > 0 && $preco >= $a['maxima_periodo'] * 0.95,
            'mes_positivo' => ($a['variacao_mes'] ?? null) !== null && $a['variacao_mes'] > 0,
            'pl_razoavel' => ($a['pl'] ?? null) !== null && $a['pl'] > 0 && $a['pl'] <= 15,
        ];

        $nota = 0.0;
        $sinais = [];
        foreach (config('aurum.radar') as $chave => $sinal) {
            if ($presentes[$chave] ?? false) {
                $nota += $sinal['peso'];
                $sinais[] = $sinal['texto'];
            }
        }

        return ['nota' => round(min(10, $nota), 1), 'sinais' => $sinais];
    }
}
