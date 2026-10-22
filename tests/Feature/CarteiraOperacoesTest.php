<?php

namespace Tests\Feature;

use App\Models\MarketAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteiraOperacoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_compra_cria_posicao_com_preco_medio_e_classe_do_mercado(): void
    {
        MarketAsset::create(['ticker' => 'HGLG11', 'nome' => 'CSHG Logística', 'classe' => 'fii', 'preco' => 160, 'variacao' => 0.5]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/carteira/operacao', ['ticker' => 'hglg11', 'tipo' => 'compra', 'data' => now()->toDateString(), 'quantidade' => '10', 'preco' => '150,00'])
            ->assertRedirect('/carteira');
        $this->actingAs($user)->post('/ativo/HGLG11/operacao', ['tipo' => 'compra', 'data' => now()->toDateString(), 'quantidade' => '10', 'preco' => '170,00', 'taxas' => '0'])
            ->assertRedirect('/ativo/HGLG11');

        $ativo = $user->assets()->first();
        $this->assertSame('fii', $ativo->classe);
        $this->assertEqualsWithDelta(20, $ativo->quantidade, 1e-9);
        $this->assertEqualsWithDelta(160, $ativo->preco_medio, 1e-9);
        $this->assertEqualsWithDelta(160, $ativo->cotacao, 1e-9);
    }

    public function test_nao_vende_mais_do_que_tem_e_venda_guarda_lucro(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/ativo/ITUB4/operacao', ['tipo' => 'compra', 'data' => now()->toDateString(), 'quantidade' => '100', 'preco' => '30']);

        $this->actingAs($user)->from('/ativo/ITUB4')->post('/ativo/ITUB4/operacao', ['tipo' => 'venda', 'data' => now()->toDateString(), 'quantidade' => '150', 'preco' => '35'])
            ->assertSessionHasErrors('quantidade');

        $this->actingAs($user)->post('/ativo/ITUB4/operacao', ['tipo' => 'venda', 'data' => now()->toDateString(), 'quantidade' => '40', 'preco' => '35']);
        $venda = $user->operations()->where('tipo', 'venda')->first();
        $this->assertEqualsWithDelta(200, $venda->resultado, 0.001);
        $this->assertEqualsWithDelta(60, $user->assets()->first()->quantidade, 1e-9);
    }

    public function test_venda_sem_posicao_e_recusada(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/carteira')->post('/carteira/operacao', ['ticker' => 'PETR4', 'tipo' => 'venda', 'data' => now()->toDateString(), 'quantidade' => '1', 'preco' => '30'])
            ->assertSessionHasErrors('quantidade');
        $this->assertSame(0, $user->assets()->count());
    }

    public function test_desfazer_recalcula_preco_medio(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/ativo/WEGE3/operacao', ['tipo' => 'compra', 'data' => now()->subDay()->toDateString(), 'quantidade' => '10', 'preco' => '40']);
        $this->actingAs($user)->post('/ativo/WEGE3/operacao', ['tipo' => 'compra', 'data' => now()->toDateString(), 'quantidade' => '10', 'preco' => '60']);
        $this->assertEqualsWithDelta(50, $user->assets()->first()->preco_medio, 1e-9);

        $ultima = $user->operations()->latest('id')->first();
        $this->actingAs($user)->delete("/carteira/operacoes/{$ultima->id}")->assertRedirect();

        $ativo = $user->assets()->first();
        $this->assertEqualsWithDelta(10, $ativo->quantidade, 1e-9);
        $this->assertEqualsWithDelta(40, $ativo->preco_medio, 1e-9);
    }

    public function test_nao_desfaz_operacao_de_outra_pessoa(): void
    {
        $dono = User::factory()->create();
        $this->actingAs($dono)->post('/ativo/WEGE3/operacao', ['tipo' => 'compra', 'data' => now()->toDateString(), 'quantidade' => '10', 'preco' => '40']);
        $op = $dono->operations()->first();

        $this->actingAs(User::factory()->create())->delete("/carteira/operacoes/{$op->id}")->assertNotFound();
        $this->assertNotNull($op->fresh());
    }

    public function test_metas_precisam_somar_100_e_salvam_aporte_mensal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/carteira/metas', ['metas' => ['acao' => '50', 'fii' => '30', 'etf' => '10', 'bdr' => '5'], 'meta_aporte_mensal' => '1.000,00'])
            ->assertSessionHasErrors('metas');

        $this->actingAs($user)->put('/carteira/metas', ['metas' => ['acao' => '50', 'fii' => '30', 'etf' => '15', 'bdr' => '5'], 'meta_aporte_mensal' => '1.000,00'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['acao' => 50.0, 'fii' => 30.0, 'etf' => 15.0, 'bdr' => 5.0], $user->fresh()->metas());
        $this->assertEqualsWithDelta(1000, $user->fresh()->meta_aporte_mensal, 0.001);
    }

    public function test_aporte_do_mes_soma_compras_do_mes(): void
    {
        $user = User::factory()->create(['meta_aporte_mensal' => 1000]);
        $this->actingAs($user)->post('/ativo/ITUB4/operacao', ['tipo' => 'compra', 'data' => now()->startOfMonth()->toDateString(), 'quantidade' => '10', 'preco' => '35']);
        $this->actingAs($user)->post('/ativo/ITUB4/operacao', ['tipo' => 'compra', 'data' => now()->subMonths(2)->toDateString(), 'quantidade' => '10', 'preco' => '30']);

        $this->actingAs($user)->get('/carteira')->assertOk()
            ->assertViewHas('aporteMes', 350.0)
            ->assertSee('35% da meta');
    }
}
