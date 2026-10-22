<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class SessaoExpiradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_expirado_volta_com_aviso_e_dados(): void
    {
        $user = User::factory()->create();
        $this->withMiddleware()->withExceptionHandling();
        // Simula o token CSRF inválido de uma página antiga.
        $this->app->instance(ValidateCsrfToken::class, new class
        {
            public function handle($request, $next)
            {
                throw new TokenMismatchException('CSRF token mismatch.');
            }
        });

        $this->actingAs($user)->from('/proventos')
            ->post('/proventos', ['ticker' => 'ITUB4', 'tipo' => 'jcp', 'data' => '2026-10-01', 'valor' => '100', 'descricao' => 'Mercado do mês'])
            ->assertRedirect('/proventos')
            ->assertSessionHas('aviso')
            ->assertSessionHasInput('descricao', 'Mercado do mês');
    }
}
