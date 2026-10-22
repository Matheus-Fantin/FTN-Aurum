<?php

namespace Tests\Feature;

use App\Models\MarketAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BuscarTest extends TestCase
{
    use RefreshDatabase;

    private function ativo(string $ticker, array $extra = []): MarketAsset
    {
        return MarketAsset::create([
            'ticker' => $ticker, 'nome' => "Empresa $ticker", 'classe' => 'acao', 'preco' => 10, 'variacao' => 1, 'volume' => 1e8, ...$extra,
        ]);
    }

    public function test_filtros_se_combinam(): void
    {
        $this->ativo('BOAA3', ['dy' => 9, 'pl' => 5]);
        $this->ativo('SODY3', ['dy' => 9, 'pl' => 12]);
        $this->ativo('SOPL3', ['dy' => 2, 'pl' => 5]);

        $this->assertSame(['BOAA3', 'SODY3'], MarketAsset::filtros(['dividendos'])->orderBy('ticker')->pluck('ticker')->all());
        $this->assertSame(['BOAA3'], MarketAsset::filtros(['dividendos', 'barata'])->pluck('ticker')->all());

        $this->actingAs(User::factory()->create())
            ->get('/buscar?f[]=dividendos&f[]=barata')->assertOk()
            ->assertSee('BOAA3')->assertSee('1 resultado')
            ->assertViewHas('resultados', fn ($r) => $r->pluck('ticker')->all() === ['BOAA3']);
    }

    public function test_pl_negativo_nao_conta_como_barata(): void
    {
        $this->ativo('PREJ3', ['pl' => -3]);
        $this->assertSame([], MarketAsset::filtros(['barata'])->pluck('ticker')->all());
    }

    public function test_busca_por_nome_e_classe(): void
    {
        $this->ativo('PETR4', ['nome' => 'Petrobras PN', 'setor' => 'Petróleo']);
        $this->ativo('HGLG11', ['nome' => 'CSHG Logística', 'classe' => 'fii']);

        $user = User::factory()->create();
        $tickers = fn (string $url) => $this->actingAs($user)->get($url)->assertOk()->viewData('resultados')->pluck('ticker')->all();
        $this->assertSame(['PETR4'], $tickers('/buscar?q=petrobras'));
        $this->assertSame(['PETR4'], $tickers('/buscar?q=petróleo'));
        $this->assertSame(['HGLG11'], $tickers('/buscar?classe=fii'));
    }

    public function test_sugestoes_vem_do_banco_sem_chamar_api_quando_ha_bastante(): void
    {
        Http::fake();
        foreach (['ITUB3', 'ITUB4', 'ITSA3', 'ITSA4', 'ITAU34'] as $t) {
            $this->ativo($t);
        }

        $this->actingAs(User::factory()->create())->getJson('/buscar/sugestoes?q=it')
            ->assertOk()->assertJsonCount(5)->assertJsonFragment(['ticker' => 'ITUB4', 'tipo' => 'Ações']);
        Http::assertNothingSent();
    }

    public function test_sem_mercado_busca_direto_na_brapi(): void
    {
        Http::fake(['*/quote/list*' => Http::response(['stocks' => [['stock' => 'VALE3', 'name' => 'Vale', 'close' => 58.4, 'change' => -1.2]]])]);

        $this->actingAs(User::factory()->create())->get('/buscar?q=vale')->assertOk()->assertSee('VALE3')->assertSee('Encontrados direto na B3');
    }
}
