<?php

namespace App\Services\Mercado;

use App\Models\MarketAsset;
use App\Services\Cotacoes\BrapiCliente;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Lista do mercado guardada no banco: preços do dia, indicadores e nota do radar.
 * Sincronizar a lista gasta poucas requisições (uma por página); os indicadores
 * são refeitos aos poucos, começando pelos ativos mais negociados.
 */
class MercadoService
{
    // Ranking do dia ignora ativos com pouco volume (evita "altas" de papéis quase sem negócio).
    private const VOLUME_MINIMO_RANKING = 1_000_000;

    public function __construct(private BrapiCliente $brapi) {}

    /** Lê as primeiras páginas de cada tipo e grava preço, variação e volume. Retorna quantos ativos gravou. */
    public function sincronizarLista(): int
    {
        $cfg = config('cotacoes.mercado');
        $gravados = 0;

        foreach ($cfg['tipos'] as $tipo) {
            for ($pagina = 1; $pagina <= $cfg['paginas']; $pagina++) {
                $resultado = $this->brapi->pagina($tipo, $pagina, $cfg['por_pagina']);
                foreach ($resultado['ativos'] as $a) {
                    $this->gravar($a);
                    $gravados++;
                }
                if (! $resultado['tem_mais']) {
                    break;
                }
            }
        }

        return $gravados;
    }

    /** Grava (ou atualiza) um ativo vindo da lista da brapi. */
    public function gravar(array $a): MarketAsset
    {
        $volumeFinanceiro = $a['volume'] !== null && $a['preco'] !== null ? $a['volume'] * $a['preco'] : null;

        return MarketAsset::updateOrCreate(['ticker' => $a['ticker']], array_filter([
            'nome' => $a['nome'],
            'classe' => self::classe($a['ticker'], $a['tipo'] ?? null, $a['nome']),
            'setor' => $a['setor'] ?? null,
            'logo' => $a['logo'] ?? null,
            'preco' => $a['preco'],
            'variacao' => $a['variacao'],
            'volume' => $volumeFinanceiro,
            'valor_mercado' => $a['valor_mercado'] ?? null,
            'atualizado_em' => now(),
        ], fn ($v) => $v !== null));
    }

    /**
     * Refaz indicadores e nota do radar dos ativos mais negociados que estão desatualizados.
     * Cada ativo custa uma requisição à brapi.
     */
    public function atualizarFundamentos(?int $limite = null): int
    {
        $cfg = config('cotacoes.mercado');
        $validade = now()->subHours($cfg['fundamentos_validade_horas']);

        $ativos = MarketAsset::query()
            ->where(fn ($q) => $q->whereNull('fundamentos_em')->orWhere('fundamentos_em', '<', $validade))
            ->orderByDesc('volume')
            ->limit($limite ?? $cfg['fundamentos_por_rodada'])
            ->get();

        $feitos = 0;
        foreach ($ativos as $ativo) {
            $detalhe = $this->brapi->detalhe($ativo->ticker);
            if ($detalhe) {
                $this->aplicarDetalhe($ativo, $detalhe);
                $feitos++;
            }
        }

        return $feitos;
    }

    /** Calcula indicadores e radar a partir do detalhe da brapi e grava no ativo. */
    public function aplicarDetalhe(MarketAsset $ativo, array $detalhe): MarketAsset
    {
        $preco = $detalhe['preco'];
        $ind = Indicadores::calcular($detalhe['historico'], $preco, $detalhe['dividendos'] ?? []);
        $radar = Radar::pontuar([
            ...$ind,
            'preco' => $preco,
            'volume_dia' => $detalhe['volume'],
            'pl' => $detalhe['pl'],
        ]);

        $ativo->fill([
            ...$ind,
            'preco' => $preco,
            'variacao' => $detalhe['variacao'] ?? $ativo->variacao,
            'pl' => $detalhe['pl'],
            'valor_mercado' => $detalhe['valor_mercado'] ?? $ativo->valor_mercado,
            'logo' => $ativo->logo ?? $detalhe['logo'],
            'radar_nota' => $radar['nota'],
            'radar_sinais' => $radar['sinais'],
            'historico' => array_map(fn ($h) => [$h['data'], round($h['fechamento'], 4)], $detalhe['historico']),
            'fundamentos_em' => now(),
            'atualizado_em' => now(),
        ])->save();

        return $ativo;
    }

    /**
     * Ativo do mercado com dados completos (busca na brapi se ainda não tiver indicadores recentes).
     * Usado pela página do ativo.
     */
    public function completo(string $ticker): ?MarketAsset
    {
        $ativo = MarketAsset::firstWhere('ticker', $ticker);
        $recente = $ativo?->fundamentos_em && $ativo->fundamentos_em->gt(now()->subMinutes(config('cotacoes.cache_minutos')));
        if ($recente) {
            return $ativo;
        }

        $detalhe = Cache::remember("mercado:detalhe:$ticker", now()->addMinutes(config('cotacoes.cache_minutos')), fn () => $this->brapi->detalhe($ticker));
        if (! $detalhe) {
            return $ativo;
        }

        $ativo ??= new MarketAsset([
            'ticker' => $ticker,
            'nome' => $detalhe['nome'],
            'classe' => self::classe($ticker, null, $detalhe['nome']),
            'logo' => $detalhe['logo'],
        ]);

        return $this->aplicarDetalhe($ativo, $detalhe);
    }

    /**
     * Maiores altas, baixas e mais negociadas do dia (ações).
     *
     * @return array{altas: Collection, baixas: Collection, negociadas: Collection}
     */
    public function rankings(int $limite = 5): array
    {
        $base = fn () => MarketAsset::query()->where('classe', 'acao')->whereNotNull('variacao')->where('volume', '>=', self::VOLUME_MINIMO_RANKING);

        return [
            'altas' => $base()->where('variacao', '>', 0)->orderByDesc('variacao')->limit($limite)->get(),
            'baixas' => $base()->where('variacao', '<', 0)->orderBy('variacao')->limit($limite)->get(),
            'negociadas' => $base()->orderByDesc('volume')->limit($limite)->get(),
        ];
    }

    /** Ativos com melhor nota no radar. */
    public function radar(int $limite = 5): Collection
    {
        return MarketAsset::query()->where('radar_nota', '>', 0)->orderByDesc('radar_nota')->orderByDesc('volume')->limit($limite)->get();
    }

    /** Ibovespa e dólar, guardados por alguns minutos. */
    public function indices(): array
    {
        $chave = 'mercado:indices';
        $salvos = Cache::get($chave);
        if (is_array($salvos)) {
            return $salvos;
        }

        $indices = $this->brapi->indices();
        if ($indices !== []) {
            Cache::put($chave, $indices, now()->addMinutes(config('cotacoes.cache_minutos')));
        }

        return $indices;
    }

    /**
     * Classe do ativo a partir do código, do tipo da brapi e do nome.
     * BDRs terminam em 32–35 (ou vêm como "bdr"); fundos terminados em 11 são FII ou ETF.
     */
    public static function classe(string $ticker, ?string $tipo, ?string $nome = null): string
    {
        $ticker = strtoupper($ticker);
        $nome = mb_strtoupper((string) $nome);

        if ($tipo === 'bdr' || preg_match('/3[2-9]$/', $ticker)) {
            return 'bdr';
        }

        $pareceEtf = str_contains($nome, 'ETF') || str_contains($nome, 'ISHARES') || str_contains($nome, 'INDICE')
            || str_contains($nome, 'ÍNDICE') || in_array($ticker, ['BOVA11', 'IVVB11', 'SMAL11', 'HASH11', 'BOVV11', 'DIVO11', 'NASD11', 'GOLD11', 'XINA11', 'SPXI11', 'FIND11', 'MATB11', 'ECOO11', 'BBSD11', 'PIBB11'], true);

        if ($tipo === 'fund' || str_ends_with($ticker, '11')) {
            if ($pareceEtf) {
                return 'etf';
            }

            // Units de empresas (SANB11, TAEE11, KLBN11…) vêm como "stock".
            return $tipo === 'stock' ? 'acao' : 'fii';
        }

        return 'acao';
    }
}
