<?php

namespace Tests\Feature;

use App\Models\MarketAsset;
use App\Models\User;
use App\Services\CarteiraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FavoritosAlertasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['results' => []])]);
    }

    public function test_estrela_liga_e_desliga_favorito(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/ativo/PETR4/favorito')->assertSessionHas('sucesso');
        $this->assertTrue($user->favoritou('PETR4'));
        $this->actingAs($user)->post('/ativo/PETR4/favorito');
        $this->assertFalse($user->favoritou('PETR4'));
    }

    public function test_alerta_dispara_quando_preco_chega(): void
    {
        $user = User::factory()->create();
        MarketAsset::create(['ticker' => 'PETR4', 'nome' => 'Petrobras', 'classe' => 'acao', 'preco' => 36]);

        $this->actingAs($user)->post('/ativo/PETR4/alerta', ['condicao' => 'abaixo', 'preco' => '35,00'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/ativo/PETR4/alerta', ['condicao' => 'acima', 'preco' => '40'])->assertSessionHasNoErrors();

        $servico = app(CarteiraService::class);
        $this->assertSame(0, $servico->verificarAlertas());

        MarketAsset::where('ticker', 'PETR4')->update(['preco' => 34.5]);
        $this->assertSame(1, $servico->verificarAlertas());
        $this->assertSame(1, $user->alertasNovos());
        // Um alerta disparado não dispara de novo.
        $this->assertSame(0, $servico->verificarAlertas());
    }

    public function test_alerta_invalido_usa_erros_separados(): void
    {
        $this->actingAs(User::factory()->create())->from('/ativo/PETR4')->post('/ativo/PETR4/alerta', ['condicao' => 'abaixo', 'preco' => ''])
            ->assertSessionHasErrorsIn('alerta', 'preco');
    }

    public function test_nao_exclui_alerta_de_outra_pessoa(): void
    {
        $dono = User::factory()->create();
        $alerta = $dono->priceAlerts()->create(['ticker' => 'PETR4', 'condicao' => 'acima', 'preco' => 40]);
        $this->actingAs(User::factory()->create())->delete("/alertas/{$alerta->id}")->assertNotFound();
    }

    public function test_proventos_registra_a_receber_e_recebido(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/proventos', ['ticker' => 'itub4', 'tipo' => 'jcp', 'data' => now()->addDays(5)->toDateString(), 'valor' => '42,60'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/proventos', ['ticker' => 'HGLG11', 'tipo' => 'rendimento', 'data' => now()->subDays(5)->toDateString(), 'valor' => '44'])->assertSessionHasNoErrors();

        $this->actingAs($user)->get('/proventos')->assertOk()->assertSee('A receber')->assertSee('ITUB4')->assertSee('R$ 44,00');
    }
}
