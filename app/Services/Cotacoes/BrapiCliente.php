<?php

namespace App\Services\Cotacoes;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Cotações da B3 pela brapi.dev (https://brapi.dev/docs). */
class BrapiCliente
{
    /**
     * @param  array<int, string>  $tickers
     * @return array<string, Cotacao> indexado pelo ticker
     */
    public function buscar(array $tickers): array
    {
        $config = config('cotacoes.brapi');
        $lotes = array_chunk($tickers, max(1, $config['tickers_por_requisicao']));

        $respostas = Http::pool(fn (Pool $pool) => array_map(
            function (array $lote) use ($pool, $config) {
                $requisicao = $pool->acceptJson()->timeout(config('cotacoes.timeout_segundos'));
                if ($config['token']) {
                    $requisicao = $requisicao->withToken($config['token']);
                }

                return $requisicao->get(rtrim($config['url'], '/').'/quote/'.implode(',', array_map('rawurlencode', $lote)));
            },
            $lotes,
        ));

        $cotacoes = [];
        foreach ($respostas as $resposta) {
            if (! $resposta instanceof Response || $resposta->failed()) {
                Log::warning('brapi: falha ao buscar cotação', [
                    'status' => $resposta instanceof Response ? $resposta->status() : null,
                    'mensagem' => $resposta instanceof Response ? $resposta->json('message') : (string) $resposta,
                ]);

                continue;
            }

            foreach ((array) $resposta->json('results', []) as $item) {
                $ticker = strtoupper((string) ($item['symbol'] ?? ''));
                $preco = $item['regularMarketPrice'] ?? null;
                if ($ticker === '' || ! is_numeric($preco) || $preco <= 0) {
                    continue;
                }
                $variacao = $item['regularMarketChangePercent'] ?? null;
                $cotacoes[$ticker] = new Cotacao($ticker, (float) $preco, is_numeric($variacao) ? (float) $variacao : null);
            }
        }

        return $cotacoes;
    }

    /**
     * Pesquisa ativos da B3 pelo código ou nome (endpoint /quote/list).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pesquisar(string $termo, int $limite = 20): array
    {
        return $this->listar(['search' => $termo, 'limit' => $limite, 'sortBy' => 'volume', 'sortOrder' => 'desc'])['ativos'];
    }

    /**
     * Uma página da lista do mercado, em ordem de volume.
     *
     * @param  string  $tipo  stock (ações), fund (FIIs e ETFs) ou bdr
     * @return array{ativos: array<int, array<string, mixed>>, tem_mais: bool}
     */
    public function pagina(string $tipo, int $pagina, int $porPagina = 100): array
    {
        return $this->listar(['type' => $tipo, 'page' => $pagina, 'limit' => $porPagina, 'sortBy' => 'volume', 'sortOrder' => 'desc']);
    }

    /** @return array{ativos: array<int, array<string, mixed>>, tem_mais: bool} */
    private function listar(array $query): array
    {
        $resposta = $this->get('/quote/list', $query);
        if (! $resposta) {
            return ['ativos' => [], 'tem_mais' => false];
        }

        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $ativos = collect((array) $resposta->json('stocks', []))
            ->map(fn ($a) => [
                'ticker' => strtoupper((string) ($a['stock'] ?? '')),
                'nome' => (string) ($a['name'] ?? ''),
                'preco' => $num($a['close'] ?? null),
                'variacao' => $num($a['change'] ?? null),
                'volume' => $num($a['volume'] ?? null),
                'valor_mercado' => $num($a['market_cap'] ?? null),
                'tipo' => $a['type'] ?? null,
                'setor' => $a['sector'] ?? null,
                'logo' => $a['logo'] ?? null,
            ])
            ->filter(fn ($a) => $a['ticker'] !== '')
            ->values()
            ->all();

        return ['ativos' => $ativos, 'tem_mais' => (bool) $resposta->json('hasNextPage', false)];
    }

    /**
     * Cotação detalhada com histórico diário, P/L e dividendos pagos.
     *
     * @return array<string, mixed>|null
     */
    public function detalhe(string $ticker, ?string $periodo = null): ?array
    {
        $resposta = $this->get('/quote/'.rawurlencode($ticker), [
            'range' => $periodo ?? config('cotacoes.brapi.periodo_historico'),
            'interval' => '1d',
            'fundamental' => 'true',
            'dividends' => 'true',
        ]);
        $item = $resposta?->json('results.0');
        if (! is_array($item) || ! is_numeric($item['regularMarketPrice'] ?? null)) {
            return null;
        }

        $historico = collect((array) ($item['historicalDataPrice'] ?? []))
            ->filter(fn ($h) => is_numeric($h['close'] ?? null) && isset($h['date']))
            ->map(fn ($h) => [
                'data' => date('Y-m-d', (int) $h['date']),
                'fechamento' => (float) $h['close'],
                'volume' => is_numeric($h['volume'] ?? null) ? (float) $h['volume'] : null,
            ])
            ->sortBy('data')->values()->all();

        $dividendos = collect((array) ($item['dividendsData']['cashDividends'] ?? []))
            ->filter(fn ($d) => is_numeric($d['rate'] ?? null) && ! empty($d['paymentDate'] ?? $d['lastDatePrior'] ?? null))
            ->map(fn ($d) => ['data' => substr((string) ($d['paymentDate'] ?? $d['lastDatePrior']), 0, 10), 'valor' => (float) $d['rate'], 'tipo' => $d['label'] ?? null])
            ->values()->all();

        $num = fn ($k) => is_numeric($item[$k] ?? null) ? (float) $item[$k] : null;

        return [
            'ticker' => strtoupper((string) ($item['symbol'] ?? $ticker)),
            'nome' => (string) ($item['longName'] ?? $item['shortName'] ?? $ticker),
            'preco' => (float) $item['regularMarketPrice'],
            'variacao' => $num('regularMarketChangePercent'),
            'abertura' => $num('regularMarketOpen'),
            'maxima' => $num('regularMarketDayHigh'),
            'minima' => $num('regularMarketDayLow'),
            'volume' => $num('regularMarketVolume'),
            'maxima_52' => $num('fiftyTwoWeekHigh'),
            'minima_52' => $num('fiftyTwoWeekLow'),
            'valor_mercado' => $num('marketCap'),
            'pl' => $num('priceEarnings'),
            'logo' => $item['logourl'] ?? null,
            'historico' => $historico,
            'dividendos' => $dividendos,
        ];
    }

    /** Ibovespa e dólar comercial para os blocos do painel. */
    public function indices(): array
    {
        $indices = [];

        $ibov = $this->get('/quote/%5EBVSP')?->json('results.0');
        if (is_array($ibov) && is_numeric($ibov['regularMarketPrice'] ?? null)) {
            $indices['ibovespa'] = [
                'valor' => (float) $ibov['regularMarketPrice'],
                'variacao' => is_numeric($ibov['regularMarketChangePercent'] ?? null) ? (float) $ibov['regularMarketChangePercent'] : null,
            ];
        }

        $dolar = $this->get('/v2/currency', ['currency' => 'USD-BRL'])?->json('currency.0');
        if (is_array($dolar) && is_numeric($dolar['bidPrice'] ?? null)) {
            $indices['dolar'] = [
                'valor' => (float) $dolar['bidPrice'],
                'variacao' => is_numeric($dolar['percentageChange'] ?? null) ? (float) $dolar['percentageChange'] : null,
            ];
        }

        return $indices;
    }

    private function get(string $caminho, array $query = []): ?Response
    {
        $config = config('cotacoes.brapi');
        try {
            $requisicao = Http::acceptJson()->timeout(config('cotacoes.timeout_segundos'));
            if ($config['token']) {
                $requisicao = $requisicao->withToken($config['token']);
            }
            $resposta = $requisicao->get(rtrim($config['url'], '/').$caminho, $query);
        } catch (\Throwable $e) {
            Log::warning('brapi: falha de conexão', ['erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed()) {
            Log::warning('brapi: resposta com erro', ['caminho' => $caminho, 'status' => $resposta->status()]);

            return null;
        }

        return $resposta;
    }
}
