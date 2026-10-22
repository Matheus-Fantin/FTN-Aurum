<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Todas as telas abrem com a conta de demonstração (sem chamar a API de verdade). */
class TelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['results' => [], 'currency' => []])]);
        $this->seed(DatabaseSeeder::class);
    }

    private function demo(): User
    {
        return User::where('email', 'demo@ftnaurum.test')->first();
    }

    public function test_login_tem_olho_para_ver_a_senha(): void
    {
        auth()->logout();
        $this->get('/login')->assertOk()
            ->assertSee('FTN', false)
            ->assertSee('Mostrar senha')
            ->assertSee('Caps Lock ativado');
    }

    public function test_painel_mostra_blocos_e_olho_de_privacidade(): void
    {
        $this->actingAs($this->demo())->get('/painel')->assertOk()
            ->assertSee('Patrimônio em bolsa')
            ->assertSee('data-alternar-valores', false)
            ->assertSee('R$ •••••')
            ->assertSee('Maior alta')
            ->assertSee('PRIO3')
            ->assertSee('Maior baixa')
            ->assertSee('VALE3')
            ->assertSee('Radar')
            ->assertSee('Próximo provento');
    }

    public function test_paginas_principais_abrem(): void
    {
        $user = $this->demo();
        foreach (['/buscar', '/buscar?f[]=dividendos&f[]=barata', '/buscar?classe=fii', '/buscar?ordem=radar', '/buscar?q=petro', '/carteira', '/favoritos', '/proventos', '/ativo/ITUB4', '/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_ativo_inexistente_mostra_aviso(): void
    {
        $this->actingAs($this->demo())->get('/ativo/XYZW9')->assertOk()->assertSee('Não encontramos XYZW9');
    }

    public function test_alerta_novo_some_do_sino_depois_de_ver(): void
    {
        $user = $this->demo();
        $this->assertSame(1, $user->alertasNovos());
        $this->actingAs($user)->get('/favoritos')->assertOk()->assertSee('novo');
        $this->assertSame(0, $user->alertasNovos());
    }
}
