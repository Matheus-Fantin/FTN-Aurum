<?php

namespace App\Http\Controllers;

use App\Services\CarteiraService;
use App\Services\Cotacoes\CotacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CotacaoController extends Controller
{
    public function atualizar(Request $request, CotacaoService $cotacoes, CarteiraService $carteira): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $resultado = $cotacoes->atualizar($user->assets()->get(), $request->boolean('forcar'));
        if ($resultado['atualizados'] > 0) {
            $carteira->fotografar($user);
        }

        if ($request->expectsJson()) {
            return response()->json($resultado);
        }

        $mensagem = $resultado['atualizados'] > 0
            ? "Cotações atualizadas: {$resultado['atualizados']} ".($resultado['atualizados'] === 1 ? 'ativo' : 'ativos').'.'
            : 'Nenhuma cotação foi atualizada.';
        if ($resultado['falhas']) {
            $mensagem .= ' Sem cotação para: '.implode(', ', $resultado['falhas']).'.';
        }

        return back()->with('sucesso', $mensagem);
    }
}
