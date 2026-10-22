<?php

namespace Tests\Feature;

use App\Models\MarketAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Conta nova, sem ativos, num sistema em que o mercado já foi sincronizado (situação de produção). */
class PainelNovoUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_painel_abre_para_conta_sem_ativos_com_mercado_carregado(): void
    {
        Http::fake(['*' => Http::response([])]);
        MarketAsset::create(['ticker' => 'PETR4', 'nome' => 'Petrobras', 'classe' => 'acao', 'preco' => 37, 'variacao' => 1.2, 'volume' => 5e8, 'atualizado_em' => now()]);

        $this->actingAs(User::factory()->create())->get('/painel')->assertOk()->assertSee('Atualizado');
    }

    public function test_painel_abre_para_conta_sem_ativos_e_sem_mercado(): void
    {
        Http::fake(['*' => Http::response([])]);
        $this->actingAs(User::factory()->create())->get('/painel')->assertOk();
    }
}
