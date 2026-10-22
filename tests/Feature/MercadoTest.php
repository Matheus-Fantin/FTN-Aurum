<?php

namespace Tests\Feature;

use App\Models\MarketAsset;
use App\Services\Mercado\Indicadores;
use App\Services\Mercado\MercadoService;
use App\Services\Mercado\Radar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_classe_pelo_codigo_tipo_e_nome(): void
    {
        $this->assertSame('acao', MercadoService::classe('PETR4', 'stock'));
        $this->assertSame('acao', MercadoService::classe('TAEE11', 'stock', 'Taesa Unit'));
        $this->assertSame('fii', MercadoService::classe('HGLG11', 'fund', 'CSHG Logística'));
        $this->assertSame('etf', MercadoService::classe('BOVA11', 'fund', 'iShares Ibovespa'));
        $this->assertSame('etf', MercadoService::classe('XPTO11', 'fund', 'XP ETF Qualquer'));
        $this->assertSame('bdr', MercadoService::classe('AAPL34', 'bdr'));
        $this->assertSame('bdr', MercadoService::classe('MSFT34', null));
    }

    public function test_sincroniza_lista_com_volume_financeiro_e_paginas(): void
    {
        config(['cotacoes.brapi.token' => 'x', 'cotacoes.mercado.paginas' => 2, 'cotacoes.mercado.tipos' => ['stock']]);
        Http::fake([
            '*/quote/list*page=1*' => Http::response(['stocks' => [
                ['stock' => 'PETR4', 'name' => 'Petrobras', 'close' => 40, 'change' => 1.5, 'volume' => 1000, 'market_cap' => 5e11, 'type' => 'stock', 'sector' => 'Energia'],
            ], 'hasNextPage' => true]),
            '*/quote/list*page=2*' => Http::response(['stocks' => [
                ['stock' => 'VALE3', 'name' => 'Vale', 'close' => 60, 'change' => -2, 'volume' => 10, 'type' => 'stock'],
            ], 'hasNextPage' => false]),
        ]);

        $this->assertSame(2, app(MercadoService::class)->sincronizarLista());
        $petr = MarketAsset::firstWhere('ticker', 'PETR4');
        $this->assertEqualsWithDelta(40000, $petr->volume, 0.01);
        $this->assertSame('acao', $petr->classe);
        $this->assertSame('Energia', $petr->setor);
        Http::assertSentCount(2);
    }

    public function test_fundamentos_calculam_indicadores_e_radar(): void
    {
        MarketAsset::create(['ticker' => 'WEGE3', 'nome' => 'WEG', 'classe' => 'acao', 'preco' => 50, 'volume' => 1e9]);
        $historico = [];
        for ($i = 0; $i < 60; $i++) {
            $historico[] = ['date' => strtotime("2026-07-01 +$i weekdays"), 'close' => 40 + $i * 0.2, 'volume' => 1000];
        }
        Http::fake(['*/quote/WEGE3*' => Http::response(['results' => [[
            'symbol' => 'WEGE3', 'longName' => 'WEG S.A.', 'regularMarketPrice' => 52.5, 'regularMarketChangePercent' => 1.2,
            'regularMarketVolume' => 2000, 'priceEarnings' => 12.5, 'historicalDataPrice' => $historico,
            'dividendsData' => ['cashDividends' => [['rate' => 0.5, 'paymentDate' => now()->subMonths(2)->toIso8601String()], ['rate' => 9, 'paymentDate' => now()->subYears(2)->toIso8601String()]]],
        ]]])]);

        $this->assertSame(1, app(MercadoService::class)->atualizarFundamentos(5));
        $a = MarketAsset::firstWhere('ticker', 'WEGE3');
        $this->assertTrue($a->tendencia_alta);
        $this->assertEqualsWithDelta(0.5 / 52.5 * 100, $a->dy, 0.001);
        $this->assertEqualsWithDelta(12.5, $a->pl, 0.001);
        $this->assertSame(10.0, $a->radar_nota); // todos os sinais presentes
        $this->assertCount(60, $a->historico);
        $this->assertNotNull($a->fundamentos_em);
    }

    public function test_radar_soma_so_os_sinais_presentes(): void
    {
        $r = Radar::pontuar(['preco' => 10, 'media_20' => 9, 'media_50' => 9.5, 'pl' => 30, 'variacao_mes' => -2, 'maxima_periodo' => 15]);
        $this->assertSame(2.0, $r['nota']);
        $this->assertSame(['Preço acima da média de 20 dias'], $r['sinais']);
        $this->assertSame(0.0, Radar::pontuar([])['nota']);
    }

    public function test_indicadores_sem_historico_suficiente_nao_inventam_medias(): void
    {
        $ind = Indicadores::calcular([['data' => '2026-10-01', 'fechamento' => 10]], 11);
        $this->assertNull($ind['media_20']);
        $this->assertNull($ind['media_50']);
        $this->assertFalse($ind['tendencia_alta']);
        $this->assertNull($ind['dy']);
    }

    public function test_rankings_ignoram_papeis_sem_volume(): void
    {
        MarketAsset::create(['ticker' => 'GRAN3', 'nome' => 'Grande', 'classe' => 'acao', 'preco' => 10, 'variacao' => 5, 'volume' => 5e7]);
        MarketAsset::create(['ticker' => 'MICR3', 'nome' => 'Micro', 'classe' => 'acao', 'preco' => 1, 'variacao' => 40, 'volume' => 1000]);

        $altas = app(MercadoService::class)->rankings()['altas'];
        $this->assertSame(['GRAN3'], $altas->pluck('ticker')->all());
    }

    public function test_comando_sincroniza_e_confere_alertas(): void
    {
        Http::fake(['*' => Http::response(['stocks' => [], 'hasNextPage' => false])]);
        $this->artisan('mercado:sincronizar')->expectsOutputToContain('Lista do mercado')->assertSuccessful();
    }
}
